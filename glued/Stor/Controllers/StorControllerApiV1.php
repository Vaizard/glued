<?php

declare(strict_types=1);

namespace Glued\Stor\Controllers;

use Carbon\Carbon;
use Glued\Core\Classes\Json\JsonResponseBuilder;
use Glued\Core\Controllers\AbstractTwigController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as v;
use Sabre\VObject;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpInternalServerErrorException;
use Spatie\Browsershot\Browsershot;

class StorControllerApiV1 extends AbstractTwigController
{

    
    // funkce co zpracuje poslany nahravany soubor jako api post request, vraci json
    // Post request expects to have:
    // - file[]
    // - actual_dir
    // - ??
    public function uploaderApiSave($request, $response)
    {
        // Initialize helper variables
        $errors = [];
        $files_uploaded = 0;
        $files_stored = 0;
        $files_skipped = 0;
        $return_code = 500;
        $return_data = array();
        $return_message = 'Error: some or all files could not be stored.';
        $who = $GLOBALS['_GLUED']['authn']['user_id'];

        try {
            $raw_path = $request->getParam('actual_dir');
            if (is_null($raw_path)) throw new \Exception('Expected a target, got none.');
            // vyjimka na my_files
            if ($raw_path == 'my_files') {
                $actual_dir = 'users';
                $actual_object = $who;
                // TODO my_files path still valid in stor?
                // I think that if the upload button returns with #95,
                // this code branch will just break.
            } else {
                $parts = explode('/', $raw_path);
                if (count($parts) > 1) {
                    $actual_dir = $parts[0];
                    if (!empty($parts[1])) $actual_object = $parts[1];  // case "work_dir": "app/123"
                    else $actual_object = null;                         // case "work_dir": "app/"
                } else {
                    $actual_dir = $parts[0];                            // case "work_dir": "app"
                    $actual_object = null;
                }
            }
            // Get data
            $files = $request->getUploadedFiles();

            // Make sure we have work to do
            if (!empty($files['file'])) $files_uploaded = count($files['file']);
            if ($files_uploaded == 0) throw new \Exception('Expected file(s), got none.');
            // pokud dir existuje v seznamu povolenych diru, uploadujem (ovsem je zadany timpadem i objekt)
            if (!(isset($this->stor->app_dirs[$actual_dir]))) throw new \Exception('Your cannot upload your files here.');
            
            foreach ($files['file'] as $file_index => $newfile) {

                $filename = $newfile->getClientFilename();
                if ($newfile->getError() === UPLOAD_ERR_OK) {
                    // ziskame tmp path ktere je privatni vlastnost $newfile, jeste zanorene v Stream, takze nejde normalne precist
                    // vypichneme si stream a pouzijeme na to reflection
                    $stream = $newfile->getStream();
                    $reflectionProperty = new \ReflectionProperty(\Nyholm\Psr7\Stream::class, 'uri');
                    $reflectionProperty->setAccessible(true);
                    $tmp_path = $reflectionProperty->getValue($stream);
                    
                    // zavolame funkci, ktera to vlozi. vysledek je pole dulezitych dat. nove id v tabulce links je $file_object_data['new_id']
                    $file_object_data = $this->stor->internal_create($tmp_path, $newfile, $who, $this->stor->app_tables[$actual_dir], $actual_object);
                    
                    // priprava navratovych dat
                    $return_data[$file_index]['link-id'] = $file_object_data['new_id'];
                    $return_data[$file_index]['name'] = $filename;
                    $return_data[$file_index]['module-name'] = $actual_dir;
                    $return_data[$file_index]['object-id'] = $file_object_data['sha512'];
                    $return_data[$file_index]['link'] = $this->routerParser->urlFor('stor.serve.file', ['id' => $file_object_data['new_id'], 'filename' => $filename]);
                    $return_data[$file_index]['size'] = $file_object_data['size'];
                    $return_data[$file_index]['mime-type'] = $file_object_data['mime'];
                    
                    if ($file_object_data['insert'] == 0 and $file_object_data['linked'] == 0) {
                        $return_data[$file_index]['result'] = 'Skipped.';
                        $files_skipped++;
                    }
                    else {
                        if ($file_object_data['insert'] == 1) $return_data[$file_index]['result'] = 'Stored.';
                        else $return_data[$file_index]['result'] = 'Linked.';
                        $files_stored++;
                    }
                } else {
                    $return_data[$file_index]['name'] = $filename;
                    $return_data[$file_index]['result'] = 'Failed.';
                    $errors[$newfile->getError()] = 1;
                }
            }   // konec cyklu pres nahrane soubory
            
            $successful_files_count = $files_stored + $files_skipped;
            
            if (($successful_files_count == $files_uploaded) and ($successful_files_count > 0)) {
                $return_message = 'Success: '.$files_stored.' files stored.';
                if ($files_skipped > 0) { $return_message .= ' '.$files_skipped.' files skipped.'; }
                $return_code = 200;
            } else {
                $phpFileUploadErrors = [
                    1 => 'Max supported filesize exceeded (' . ini_get('upload_max_filesize') . 'B). ',
                    2 => 'Max filesize required by client exceeded. ',
                    3 => 'Incomplete transfer. ',
                    4 => 'No file(s) transfered. ',
                    6 => 'Missing temp folder. ',
                    7 => 'Failed to write file(s). ',
                    8 => 'Extension stopped file(s) from transferring.',
                ];
                $return_message = 'Stored '.$files_stored.' out of '.$files_uploaded.'. '.$files_skipped.' skipped. Problems encountered: ';
                foreach ($errors as $key => $item) {
                    if ($item === 1) $return_message .= $phpFileUploadErrors[$key];
                }
            }
        } catch (\Exception $e) {
            $return_message = $e->getMessage();
        }
       
        // vybuildime json response
        $builder = new JsonResponseBuilder('stor/upload', 1);
        $payload = $builder->withData($return_data)->withMessage($return_message)->withCode($return_code)->build();
        return $response->withJson($payload, $return_code);
    }
    
    // funkce, ktera vraci prvni radek s dvojteckou, patri do browseru
    // davam to do samostatne funkce, protoze to bude pouzite 4x v showFilteredFiles a bude to tak prehlednejsi
    private function firstRowUplinkBrowser($dataID, $dataText) {
        
        $row_data = array(
            'type' => 'folder',
            'shortcut_id' => $dataID,
            'shortcut_text' => $dataText,
            'shortcut_title' => ' .. '
        );
        
        return $row_data;
    }
    
    // prehled odpovidajicich objektu do modal popupu pro copy/move
    public function showModalObjects($request, $response) {
        $vystup = '';
        
        $dirname = $request->getParam('dirname');
        
        // nacteme si to z containeru ktery to ma ze tridy
        $app_dirs = $this->stor->app_dirs;
        $app_tables = $this->stor->app_tables;
        
        if (isset($app_dirs[$dirname])) {
            if (isset($app_tables[$dirname])) {
                // nacteme idecka
                $cols = Array("c_uid", "c_stor_name");
                $this->db->orderBy("c_uid","asc");
                $idecka = $this->db->get($app_tables[$dirname], null, $cols);
                if ($this->db->count > 0) {
                    foreach ($idecka as $idecko) {
                        $vystup .= '<option value="'.$idecko['c_uid'].'">'.$idecko['c_uid'].' - '.$idecko['c_stor_name'].'</option>';
                    }
                }
            }
        }
        
        // protoze je to ajax, tak vystup nebudeme strkat do view ale rovnou ho vytiskneme
        
        $response->getBody()->write($vystup);
        return $response;
    }
    
    // vraci moznosti do select2 podle toho co je zatim napsane. vystup je v jsonu, pole objektu
    public function showFilterOptions($request, $response) {
        $vystup = '';
        
        // nacteme si to z containeru ktery to ma ze tridy
        $app_dirs = $this->stor->app_dirs;
        $app_tables = $this->stor->app_tables;
        
        $term = $request->getParam('term');
        
        // podle toho cim to zacne
        // lomitko = adresar, @ = uzivatel, # = tag
        $prvni_znak = mb_substr($term, 0, 1, 'utf-8');
        
        if ($term == '//') {
            $vystup .= '
    {
      "results": [
        {
          "id": "//",
          "text": "//apps"
        }
      ]
    }
            ';
        }
        else if ($prvni_znak == '/') {   // adresare
            $casti = explode('/', $term);
            $app_cast = $casti[1];
            // zjistime kolika odpovida apps
            $objekty_adresaru = array();
            $posledni_dir = '';
            foreach ($app_dirs as $dir => $description) {
                if (!isset($app_tables[$dir])) { continue; }
                if (empty($app_cast) or substr_count($dir, $app_cast) > 0) {
                    $objekty_adresaru[] = '
        {
          "id": "/'.$dir.'",
          "text": "/'.$dir.'"
        }
                    ';
                    $posledni_dir = $dir;
                }
            }
            
            $vystup .= '
{
  "results": [
            ';
            if (isset($casti[2]) and count($objekty_adresaru) == 1) { // je tam druhe lomitko, nabidneme teda objekty
                $objekt_cast = $casti[2];
                $cols = Array("c_uid", "c_stor_name");
                $this->db->orderBy("c_uid","asc");
                $idecka = $this->db->get($app_tables[$posledni_dir], null, $cols);
                if ($this->db->count > 0) {
                    $objekty_modulu = array();
                    foreach ($idecka as $idecko) {
                        $nazev = $idecko['c_uid'].' - '.$idecko['c_stor_name'];
                        if (empty($objekt_cast) or substr_count(mb_strtolower($nazev, 'utf-8'), mb_strtolower($objekt_cast, 'utf-8')) > 0) {
                            $objekty_modulu[] = '
            {
              "id": "/'.$posledni_dir.'/'.$idecko['c_uid'].'",
              "text": "/'.$posledni_dir.'/'.$nazev.'"
            }
                            ';
                        }
                    }
                    $vystup .= implode(',', $objekty_modulu);
                }
            }
            else {  // je tam jen jedno lomitko, nabidneme appy
                $vystup .= implode(',', $objekty_adresaru);
            }
            $vystup .= '
  ]
}
            ';
        }
        else if ($prvni_znak == '@') {  // uzivatele
            // nacteme idecka
            $cols = Array("c_uid", "c_name");
            $this->db->orderBy("c_uid","asc");
            $idecka = $this->db->get('t_core_users', null, $cols);
            $objekty_useru = array();
            if ($this->db->count > 0) {
                foreach ($idecka as $idecko) {
                    $objekty_useru[] = '
    {
      "id": "@'.$idecko['c_uid'].'",
      "text": "@'.$idecko['c_uid'].' - '.$idecko['c_name'].'"
    }
                    ';
                }
            }
            $vystup .= '
    {
      "results": [';
        $vystup .= implode(',', $objekty_useru);
        $vystup .= '
      ]
    }
            ';
        }
        else if ($prvni_znak == '#') {  // tagy, zatim nemame tabulku
            $vystup .= '
    {
      "results": [
        {
          "id": "#tag1",
          "text": "#tag1"
        },
        {
          "id": "#tag1",
          "text": "#tag2"
        }
      ]
    }
            ';
        }
        else {  // musime vratit prazdne pole, aby to bylo validni
            $vystup .= '
    {
      "results": [ ]
    }
            ';
        }
        
        // protoze je to ajax, tak vystup nebudeme strkat do view ale rovnou ho vytiskneme
        
        $response->getBody()->write($vystup);
        return $response;
    }
    
    
    // fukce co vypise prehled filtrovanych souboru v adresari
    public function showFilteredFiles($request, $response)
    {
        $vystup = '';
        $stor_rows = array();
        $uploader = '';
        $bude_uploader = false;
        $upload_module = '';
        $upload_id = '';
        
        // nacteme si to z containeru ktery to ma ze tridy
        $app_dirs = $this->stor->app_dirs;
        $app_tables = $this->stor->app_tables;
        
        $raw_filters = $request->getParam('filters');
        $orderby = $request->getParam('orderby');
        $direction = $request->getParam('direction');
        $page = $request->getParam('page');
        
        // dekodujeme na pole
        $filters = json_decode($raw_filters, true);
        
        // vrsek vzdy
        // kvuli tomu, ze tu mame dropdown, ktery muze sahat mimo tabulku, dame tam defaultni overflow-x: visible; ktere prebije csskove auto
        // a min width ma responsive table 800px ale to je tady zbytecne moc. prebijeme to 400px
        
        // drive byly nadpisy vyuzite i na trideni, je mozne doplnit
        // onclick="filter_stor_files(\'name\', \''.(($orderby == 'name' and $direction == 'asc')?'desc':'asc').'\', 1);"
        // onclick="filter_stor_files(\'size\', \''.(($orderby == 'size' and $direction == 'asc')?'desc':'asc').'\', 1);"
        // onclick="filter_stor_files(\'uploaded\', \''.(($orderby == 'uploaded' and $direction == 'asc')?'desc':'asc').'\', 1);"
        
        
        // zanalyzujeme si co mame zadane ($filters)
        if (count($filters) > 0) {
            $je_tam_apps = false;
            $jsou_tam_objekty = false;
            $objektovy_dir = '';
            $pole_adresaru = array();
            $pole_useru = array();
            $pole_tagu = array();
            $pole_nazvu = array();
            
            
            $uploader_path = '';
            
            foreach ($filters as $filter) {
                
                // podle toho cim to zacne
                // lomitko = adresar, @ = uzivatel, # = tag
                $prvni_znak = mb_substr($filter, 0, 1, 'utf-8');
                
                // nejdriv detekujeme pritomnost apps //
                if ($filter == '//') {
                    $je_tam_apps = true;
                }
                // lomitkovy filtr by mel byt jen jeden, pokud bude vic, vezmeme jen prvni (a nebo to muzeme vzit jako or?)
                else if ($prvni_znak == '/') {
                    $pole_adresaru[] = $filter; // dame to tam cele, protoze pak to teprve budeme delit na lomitka a muze tam byt i objekt
                    // zjistime, jestli to nahodou neni pozadavek na vypis objektu z app
                    foreach ($app_dirs as $dir => $description) {
                        if (!isset($app_tables[$dir])) { continue; }
                        if ($filter == '/'.$dir.'/') {
                            $jsou_tam_objekty = true;
                            $objektovy_dir = $dir;
                        }
                    }
                }
                // ownerovy filtr muze byt taky jen jeden, taky vemem jen prvni (a nebo to muzeme vzit jako or?)
                else if ($prvni_znak == '@') {
                    $pole_useru[] = mb_substr($filter, 1, null, 'utf-8');
                }
                // tagovych filtru muze byt vic
                else if ($prvni_znak == '#') {
                    $pole_tagu[] = mb_substr($filter, 1, null, 'utf-8');
                }
                // nazvovych filtru muze byt vic
                else {
                    $pole_nazvu[] = $filter;
                }
            }
            
            // pokud je tam apps, vypiseme jen apps
            if ($je_tam_apps) {
                // zpet do rootu
                $stor_rows[] = $this->firstRowUplinkBrowser('', '');
                foreach ($app_dirs as $dir => $description) {
                    if (!isset($app_tables[$dir])) { continue; }
                    
                    $stor_rows[] = array(
                        'type' => 'folder',
                        'shortcut_id' => '/'.$dir.'/',
                        'shortcut_text' => '/'.$dir.'/',
                        'shortcut_title' => '/'.$dir.'/'
                    );
                }
            }
            else if ($jsou_tam_objekty) {   // to znamena ze jsme v jedne app a vypisujeme jeji objekty. tady nakonec bude taky aktivni upload souboru do null objektu
                // nejdriv zpet do app
                $stor_rows[] = $this->firstRowUplinkBrowser('//', '//apps');
                // nacteme idecka
                $cols = Array("c_uid", "c_stor_name");
                $this->db->orderBy("c_uid","asc");
                $idecka = $this->db->get($app_tables[$objektovy_dir], null, $cols);
                if ($this->db->count > 0) {
                    foreach ($idecka as $idecko) {
                        
                        $stor_rows[] = array(
                            'type' => 'folder',
                            'shortcut_id' => '/'.$objektovy_dir.'/'.$idecko['c_uid'],
                            'shortcut_text' => '/'.$objektovy_dir.'/'.$idecko['c_uid'].' - '.$idecko['c_stor_name'],
                            'shortcut_title' => $idecko['c_uid'].' - '.$idecko['c_stor_name']
                        );
                        
                    }
                }
                // muzou tam byt taky soubory nahrane v NULL objektu
                $sloupce = array("lin.c_uid", "lin.c_user_id", "lin.c_filename", "lin.c_inherit_table", "lin.c_inherit_object", "lin.c_ts_created", "obj.c_sha512", "obj.c_json ->>'$.data.size' as size", "obj.c_json ->>'$.data.mime' as mime");
                $this->db->join("t_stor_objects obj", "obj.c_sha512=lin.c_sha512", "LEFT");
                $this->db->where("c_inherit_table", $app_tables[$objektovy_dir]);
                $this->db->where("c_inherit_object", NULL, 'IS');
                $files = $this->db->get('t_stor_links lin', null, $sloupce);
                if (count($files) > 0) {
                    foreach ($files as $data) {
                        $dir_path = array_search($data['c_inherit_table'], $app_tables);
                        $full_path = $dir_path.'/';
                        
                        // urcime jake mame globalni prava na inherit objekt (ne na soubor ale na objekt ve kterem soubor je)
                        // TODO, pole s nactenymi pravy by melo byt globalni, aby se necetly stejna prava u kazdeho dalsiho souboru se stejnou dvojici table objekt
                        $allowed_global_actions = array();
                        // zatim nastavujeme, ze ma vsechny prava. pozdeji toto nebude vzdy vyplneno. az budou fungovat nove prava
                        $allowed_global_actions[] = 'list';
                        $allowed_global_actions[] = 'read';
                        $allowed_global_actions[] = 'write';
                        
                        // jestli soubor vubec vylistovat, TODO tohle je ale spis read pravo na objekt. ne list.
                        if (in_array('list', $allowed_global_actions)) {
                            
                            $action_dropdown = '';
                            // jestli bude ozubene kolo
                            if (in_array('write', $allowed_global_actions)) {
                                $action_dropdown = '
                                    <div class="dropdown">
                                        <div class="btn-group dropleft">
                                          <button type="button" class="btn btn-sm btn-secondary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            Actions
                                          </button>
                                          <div class="dropdown-menu dropleft" x-placement="left-start" style="background-color: #cdd3d8; font-size: 12px;">
                                                <button class="dropdown-item" type="button" data-toggle="modal" data-target="#modal-delete-stor" data-uid="'.$data['c_uid'].'"><i class="fa fa-trash-o "></i> Delete</button>
                                                <button class="dropdown-item" type="button" data-toggle="modal" data-target="#modal-edit-stor" data-uid="'.$data['c_uid'].'" data-filename="'.htmlspecialchars($data['c_filename']).'"><i class="fa fa-pencil"></i> Edit</button>
                                                <button class="dropdown-item" type="button" data-toggle="modal" data-target="#modal-copy-move-stor" data-uid="'.$data['c_uid'].'"><i class="fa fa-files-o"></i> Copy/Move</button>
                                          </div>
                                        </div>
                                    </div>
                                ';
                            }
                            
                            // ulozene do budoucna, jak bylo drive mime souboru a creator name
                            // <i class="fa '.$this->container->stor->get_mime_icon($data['mime']).' fa-2x"></i>
                            // '.$this->container->auth->user_screenname($data['c_owner']).'
                            
                            if (in_array('read', $allowed_global_actions)) {
                                $shortcut = '
                                        <a href="'.$this->routerParser->urlFor('stor.serve.file', ['id' => $data['c_uid'], 'filename' => $data['c_filename']]).'" class="">
                                            <b id="fname_'.$data['c_uid'].'" class="item-title">'.$data['c_filename'].'</b>
                                        </a>
                                ';
                            }
                            else {
                                $shortcut = '
                                <b id="fname_'.$data['c_uid'].'" class="item-title">'.$data['c_filename'].'</b>
                                ';
                            }
                            
                            $path = '
                                        <a href="" class="stor-shortcuts" data-id="/'.$full_path.'" data-text="/'.$full_path.'">
                                            /'.$full_path.'
                                        </a>
                            ';
                            
                            $stor_rows[] = array(
                                'type' => 'file',
                                'uid' => $data['c_uid'],
                                'sha512' => $data['c_sha512'],
                                'filename' => $data['c_filename'],
                                'inherit_object' => NULL,
                                'inherit_table' => $data['c_inherit_table'],
                                'shortcut' => $shortcut,
                                'size' => $this->stor->human_readable_size($data['size']),
                                'path' => $path,
                                'created' => $data['c_ts_created'],
                                'action_buttons' => $action_dropdown
                            );
                            
                        }
                    }
                }
                
                
                // timto urcime, ze bude uploadovaci tlacitko ve formu
                $upload_module = $objektovy_dir;
            }
            else {  // vypis souboru v objektu nebo obecny vypis souboru. jen tady by mel byt aktivni upload button
                
                // krome tagovych muzeme udelat vyber jednim sql dotazem nad links tabulkou
                $sloupce = array("lin.c_uid", "lin.c_user_id", "lin.c_filename", "lin.c_inherit_table", "lin.c_inherit_object", "lin.c_ts_created", "obj.c_sha512", "obj.c_json ->>'$.data.size' as size", "obj.c_json ->>'$.data.mime' as mime");
                $this->db->join("t_stor_objects obj", "obj.c_sha512=lin.c_sha512", "LEFT");
                if (count($pole_adresaru) > 0) {
                    $casti = explode('/', $pole_adresaru[0]);   // bereme do uvahy jen prvni adresar zatim
                    $inherit_table = $app_tables[$casti[1]];
                    $this->db->where("c_inherit_table", $inherit_table);
                    if (!empty($casti[2])) {
                        $this->db->where("c_inherit_object", $casti[2]);
                        // je tam adresar i objekt, muzeme ukazat uploadovaci form
                        $bude_uploader = true;
                        $uploader_path = $casti[1].'/'.$casti[2];
                        
                        // pokud je tam jen jeden adresar a jeden objekt, muzeme uploadovat a nastavime si potrebne promenne
                        if (count($pole_adresaru) == 1) {
                            $upload_module = $casti[1];
                            $upload_id = $casti[2];
                        }
                    }
                    // jsme v lomitkovem filtru, takze mame nejakou app, pridame prvni radek ktery do ni povede zpet
                    $stor_rows[] = $this->firstRowUplinkBrowser('/'.$casti[1].'/', '/'.$casti[1].'/');
                }
                if (count($pole_useru) > 0) {
                    $this->db->where("c_user_id", $pole_useru[0]);
                }
                if (count($pole_tagu) > 0) {
                    // tagy zatim nemame
                }
                if (count($pole_nazvu) > 0) {
                    $this->db->where("c_filename", '%'.$pole_nazvu[0].'%', 'like');
                }
                
                // oderby
                if ($orderby == 'name') {
                    $this->db->orderBy("c_filename", $direction);
                }
                else if ($orderby == 'size') {
                    $this->db->orderBy("size", $direction);
                }
                else if ($orderby == 'uploaded') {
                    $this->db->orderBy("c_ts_created", $direction);
                }
                
                $files = $this->db->get('t_stor_links lin', null, $sloupce);
                if (count($files) > 0) {
                    foreach ($files as $data) {
                        $dir_path = array_search($data['c_inherit_table'], $app_tables);
                        $full_path = $dir_path.'/'.$data['c_inherit_object'];
                        
                        // urcime jake mame globalni prava na inherit objekt (ne na soubor ale na objekt ve kterem soubor je)
                        // TODO, pole s nactenymi pravy by melo byt globalni, aby se necetly stejna prava u kazdeho dalsiho souboru se stejnou dvojici table objekt
                        $allowed_global_actions = array();
                        //if ($this->container->permissions->have_action_on_object($data['c_inherit_table'], $data['c_inherit_object'], 'list')) { $allowed_global_actions[] = 'list'; }
                        //if ($this->container->permissions->have_action_on_object($data['c_inherit_table'], $data['c_inherit_object'], 'read')) { $allowed_global_actions[] = 'read'; }
                        //if ($this->container->permissions->have_action_on_object($data['c_inherit_table'], $data['c_inherit_object'], 'write')) { $allowed_global_actions[] = 'write'; }
                        
                        // zatim nastavujeme, ze ma vsechny prava. pozdeji toto nebude vzdy vyplneno. az budou fungovat nove prava
                        $allowed_global_actions[] = 'list';
                        $allowed_global_actions[] = 'read';
                        $allowed_global_actions[] = 'write';
                        
                        // jestli soubor vubec vylistovat, TODO tohle je ale spis read pravo na objekt. ne list.
                        if (in_array('list', $allowed_global_actions)) {
                            
                            $action_dropdown = '';
                            // jestli bude ozubene kolo
                            if (in_array('write', $allowed_global_actions)) {
                                $action_dropdown = '
                                    <div class="dropdown">
                                        <div class="btn-group dropleft">
                                          <button type="button" class="btn btn-sm btn-secondary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            Actions
                                          </button>
                                          <div class="dropdown-menu dropleft" x-placement="left-start" style="background-color: #cdd3d8; font-size: 12px;">
                                                <button class="dropdown-item" type="button" data-toggle="modal" data-target="#modal-delete-stor" data-uid="'.$data['c_uid'].'"><i class="fa fa-trash-o "></i> Delete</button>
                                                <button class="dropdown-item" type="button" data-toggle="modal" data-target="#modal-edit-stor" data-uid="'.$data['c_uid'].'" data-filename="'.htmlspecialchars($data['c_filename']).'"><i class="fa fa-pencil"></i> Edit</button>
                                                <button class="dropdown-item" type="button" data-toggle="modal" data-target="#modal-copy-move-stor" data-uid="'.$data['c_uid'].'"><i class="fa fa-files-o"></i> Copy/Move</button>
                                          </div>
                                        </div>
                                    </div>
                                ';
                            }
                            
                            // ulozene do budoucna, jak bylo drive mime souboru a creator name
                            // <i class="fa '.$this->container->stor->get_mime_icon($data['mime']).' fa-2x"></i>
                            // '.$this->container->auth->user_screenname($data['c_owner']).'
                            
                            if (in_array('read', $allowed_global_actions)) {
                                $shortcut = '
                                        <a href="'.$this->routerParser->urlFor('stor.serve.file', ['id' => $data['c_uid'], 'filename' => $data['c_filename']]).'" class="">
                                            <b id="fname_'.$data['c_uid'].'" class="item-title">'.$data['c_filename'].'</b>
                                        </a>
                                ';
                            }
                            else {
                                $shortcut = '
                                <b id="fname_'.$data['c_uid'].'" class="item-title">'.$data['c_filename'].'</b>
                                ';
                            }
                            
                            $path = '
                                        <a href="" class="stor-shortcuts" data-id="/'.$full_path.'" data-text="/'.$full_path.'">
                                            /'.$full_path.'
                                        </a>
                            ';
                            
                            $stor_rows[] = array(
                                'type' => 'file',
                                'uid' => $data['c_uid'],
                                'sha512' => $data['c_sha512'],
                                'filename' => $data['c_filename'],
                                'inherit_object' => $data['c_inherit_object'],
                                'inherit_table' => $data['c_inherit_table'],
                                'shortcut' => $shortcut,
                                'size' => $this->stor->human_readable_size($data['size']),
                                'path' => $path,
                                'created' => $data['c_ts_created'],
                                'action_buttons' => $action_dropdown
                            );
                            
                        }
                    }
                }
            }
        }
        else {  // jsme v zakladnim vyberu my files a app
            $your_user_id = $GLOBALS['_GLUED']['authn']['user_id'];
            //$your_screenname = $this->container->auth->user_screenname($your_user_id);
            $your_screenname = 'noob';
            
            $stor_rows[] = array(
                'type' => 'folder',
                'shortcut_id' => '@'.$your_user_id,
                'shortcut_text' => '@'.$your_user_id.' - '.$your_screenname,
                'shortcut_title' => 'Files I created'
            );
            
            $stor_rows[] = array(
                'type' => 'folder',
                'shortcut_id' => '//',
                'shortcut_text' => '//apps',
                'shortcut_title' => 'Apps'
            );
            
        }
        
        // debug
        //$vystup .= __('Filtrer json').': '.$raw_filters.', orderby: '.$orderby.', direction: '.$direction.', page: '.$page;
        
        $meta_data = array();
        $meta_data['module'] = $upload_module;
        $meta_data['id'] = $upload_id;
        
        $builder = new JsonResponseBuilder('stor.browser', 1);
        $payload = $builder->withData($stor_rows)->withMeta($meta_data)->withCode(200)->build();
        return $response->withJson($payload);
        
    }
    
    // mazani ajaxem
    public function ajaxDelete($request, $response) {
        $vystup = '';
        
        $link_id = $request->getParam('link_id');
        
        // TODO zjistit jestli mame write prava na inherit table a objekt
        
        
        $returned_data = $this->stor->delete_stor_file($link_id);
        
        // protoze je to ajax, tak vystup nebudeme strkat do view ale rovnou ho vytiskneme
        
        $response->getBody()->write($returned_data['message']);
        return $response;
    }
    
    // prejmenovani ajaxem. nebudeme na to delat zvlastni funkci ve tride, je to jednoduche
    public function ajaxUpdate($request, $response) {
        $vystup = '';
        
        $link_id = $request->getParam('link_id');
        $new_fname = $request->getParam('new_fname');
        
        // nacteme si link
        $this->db->where("c_uid", $link_id);
        $link_data = $this->db->getOne('t_stor_links');
        if ($this->db->count == 0) { // TODO, asi misto countu pouzit nejaky test $link_data
            $vystup = 'pruser, soubor neexistuje, nevim na co jste klikli, ale jste tu spatne';
        }
        else {
            // pokud mame prava na tento objekt, TODO
            
            // zmenime nazev na novy
            $data = Array (
                'c_filename' => $new_fname
            );
            $this->db->where("c_uid", $link_id);
            if ($this->db->update('t_stor_links', $data)) {
                $vystup = 'soubor byl prejmenovan';
            }
            else {
                $vystup = 'prejmenovani se nepovedlo';
            }
            
            // byvale prava
            /*
            if ($this->container->permissions->have_action_on_object($link_data['c_inherit_table'], $link_data['c_inherit_object'], 'write')) {
                
            }
            else {
                $vystup = 'k prejmenovani nemate prava';
            }
            */
        }
        
        // protoze je to ajax, tak vystup nebudeme strkat do view ale rovnou ho vytiskneme
        
        $response->getBody()->write($vystup);
        return $response;
    }
    
    // jednoduchy vypis souboru ve storu pro dany objekt (tabulka, id radku), pouzite ve funkci, ktera vypisuje soubory v ruznych jinych modulech
    // TODO ted neni vyuzite, ale budeme to zrejme potrebovat a tak si to tu zatim necham. pokud bychom to udelali jinak, tak smazat
    public function ajaxListFilesBasic($request, $response) {
        $vystup_souboru = '';
        $mame_id = false;
        
        $raw_dirname = $request->getParam('dirname');
        
        // musi tam byt id
        $dily = explode('/', $raw_dirname);
        $dirname = $dily[0];
        if (count($dily) > 1) {
            $object_id = $dily[1];
            $mame_id = true;
        }
        
        if ($mame_id and isset($this->container->stor->app_dirs[$dirname])) {
            // prava, TODO, jako v showFiles
            
            $object_tabulka = $this->container->stor->app_tables[$dirname];
            
            $sloupce = array("lin.c_uid", "lin.c_owner", "lin.c_filename", "obj.sha512", "obj.doc->>'$.data.size' as size", "obj.doc->>'$.data.mime' as mime", "obj.doc->>'$.data.ts_created' as ts_created");
            $this->container->db->join("t_stor_objects obj", "obj.sha512=lin.c_sha512", "LEFT");
            $this->container->db->where("c_inherit_table", $object_tabulka);
            $this->container->db->where("c_inherit_object", $object_id);
            $this->container->db->orderBy("lin.c_filename","asc");
            $files = $this->container->db->get('t_stor_links lin', null, $sloupce);
            if (count($files) > 0) {
                foreach ($files as $filedata) {
                    $adresa = $this->container->router->urlFor('stor.serve.file', ['id' => $filedata['c_uid'], 'filename' => $filedata['c_filename']]);
                    $vystup_souboru .= '
                    <div>
                        <a href="'.$adresa.'" class="">
                            <br />
                            '.$filedata['c_filename'].'
                            <a class="remove" href="#" data-toggle="modal" data-target="#confirm-modal" onclick="$(\'#delete_file_uid\').val('.$filedata['c_uid'].');">
                                <i class="fa fa-trash-o "></i>
                            </a>
                        </a>
                    </div>
                    ';
                }
            }
            else {
                $vystup_souboru .= '<div>no files uploaded</div>';
            }
        }
        else {
            $vystup_souboru .= '<div>bad syntax</div>';
        }
        
        // protoze je to ajax, tak vystup nebudeme strkat do view ale rovnou ho vytiskneme
        
        $response->getBody()->write($vystup_souboru);
        return $response;
    }
    
    
}
