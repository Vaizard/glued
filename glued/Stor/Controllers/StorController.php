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

class StorController extends AbstractTwigController
{
    /**
     * @param Request  $request
     * @param Response $response
     * @param array    $args
     *
     * @return Response
     */
    
    
    // zobrazovac nebo vynucovac stazeni
    public function serveFile($request, $response, $args)
    {
        // parametr id identifikuje link
        $link_id = $args['id'];
        
        // nacteme sha512
        $this->db->where ("c_uid", $link_id);
        $file_link = $this->db->getOne("t_stor_links");
        
        // nacteme mime
        $sloupce = array("c_json->>'$.data.mime' as mime", "c_json->>'$.data.storage[0].path' as path");
        $this->db->where("c_sha512", $file_link['c_sha512']);
        $file_data = $this->db->getOne("t_stor_objects", $sloupce);
        // select `c_json`->>'$.data.mime' as mime, `c_json`->>'$.data.storage[0].path' as path from t_stor_objects
        // 
        // path mame v takovem nejakem tvaru
        // ../private/stor/0/2/8/0
        $fullpath = $file_data['path'].'/'.$file_link['c_sha512'];
        
        header('Content-Type: '.$file_data['mime']);
        readfile($fullpath);    // taky vlastne nevim jestli to takto vypsat
        exit(); // ? nevim nevim
        
    }
    
    // update nazvu z popupoveho formu
    public function uploaderUpdate($request, $response)
    {
        $link_id = (int) $request->getParam('file_id');
        $actual_dir = $request->getParam('actual_dir');
        
        // nacteme si link
        $this->container->db->where("c_uid", $link_id);
        $link_data = $this->container->db->getOne('t_stor_links');
        if ($this->container->db->count == 0) { // TODO, asi misto countu pouzit nejaky test $link_data
            $this->container->flash->addMessage('error', 'pruser, soubor neexistuje, nevim na co jste klikli, ale jste tu spatne');
        }
        else {
            // pokud mame prava na tento objekt
            if ($this->container->permissions->have_action_on_object($link_data['c_inherit_table'], $link_data['c_inherit_object'], 'write')) {
                // zmenime nazev na novy
                $data = Array (
                    'c_filename' => $request->getParam('new_filename')
                );
                $this->container->db->where("c_uid", $link_id);
                if ($this->container->db->update('t_stor_links', $data)) {
                    $this->container->flash->addMessage('info', 'soubor byl prejmenovan');
                }
                else {
                    $this->container->flash->addMessage('error', 'prejmenovani se nepovedlo');
                }
            }
            else {
                $this->container->flash->addMessage('error', 'k prejmenovani nemate prava');
            }
        }
        
        // toto by melo byt vzdy nastaveno pri editaci, abychom mohli tu adresu zase vykreslit s uz zmenenym nazvem
        if (!empty($actual_dir)) {
            $redirect_url = $this->container->router->urlFor('stor.uploader').'/~/'.$actual_dir;
        }
        else {  // pro jistotu, kdyz to nebude nastaveno, jdeme na root
            $redirect_url = $this->container->router->urlFor('stor.uploader');
        }
        
        return $response->withRedirect($redirect_url);
    }
    
    // copy nebo move z modalu
    public function itemCopyMove($request, $response)
    {
        $link_id = (int) $request->getParam('file_id');
        $actual_dir = $request->getParam('actual_dir'); // jen v uploaderu (ten jeste existuje?)
        $action_type = $request->getParam('action_type');
        $target_dir = $request->getParam('target_dir');
        $target_object_id = $request->getParam('target_object_id');
        $set_new_owner = (int) $request->getParam('set_new_owner'); // 1 - system select, 2 - prihlaseny, 3 - nemenit
        $action_source = $request->getParam('action_source');   // jen v browseru (je to volane i odjinud?)
        
        // nacteme si to z containeru ktery to ma ze tridy
        $app_dirs = $this->stor->app_dirs;
        $app_tables = $this->stor->app_tables;
        
        // nacteme si link
        $this->db->where("c_uid", $link_id);
        $link_data = $this->db->getOne('t_stor_links');
        if ($this->db->count == 0) { // TODO, asi misto countu pouzit nejaky test $link_data
            $this->flash->addMessage('error', 'pruser, soubor neexistuje, nevim na co jste klikli, ale jste tu spatne');
        }
        else {
            // nacteme prava na tabulku, TODO, meli bychom ale nacist prava na ten konkretni objekt, coz neni vyladene zatim
            //$allowed_global_actions = $this->container->permissions->read_global_privileges($link_data['c_inherit_table']);
            //$allowed_global_target_actions = $this->container->permissions->read_global_privileges($this->container->stor->app_tables[$target_dir]);
            
            // urceni ownera
            if ($set_new_owner == 1) {  // system select
                // pokud presunuju nebo kopiruju do private users, mel by byt owner vzdy ten user
                if ($target_dir == 'users') {
                    $new_owner = $target_object_id;
                }
                else {  // pokud je cil nejaky modul, tak u copy bych mel byt owner ja, a u move bud nemenit nebo ja
                    $new_owner = $GLOBALS['_GLUED']['authn']['user_id'];
                }
            }
            else if ($set_new_owner == 2) { $new_owner = $GLOBALS['_GLUED']['authn']['user_id']; }    // vzdy ja
            else if ($set_new_owner == 3) { $new_owner = $link_data['c_user_id']; }   // nemenit
            
            if ($action_type == 'copy') {
                //if (in_array('read', $allowed_global_actions) and in_array('write', $allowed_global_target_actions)) {
                    $data = Array (
                    "c_sha512" => $link_data['c_sha512'],
                    "c_user_id" => $new_owner,
                    "c_filename" => $link_data['c_filename'],
                    "c_inherit_table" => $app_tables[$target_dir],
                    "c_inherit_object" => $target_object_id
                    );
                    if ($this->db->insert ('t_stor_links', $data)) {
                        $this->flash->addMessage('info', 'soubor byl zkopirovan');
                    }
                    else {
                        $this->flash->addMessage('error', 'kopirovani se nepovedlo');
                    }
                /*
                }
                else {
                    $this->flash->addMessage('error', 'ke kopirovani nemate prava');
                }
                */
            }
            else if ($action_type == 'move') {
                //if (in_array('write', $allowed_global_actions) and in_array('write', $allowed_global_target_actions)) {
                    $data = Array (
                        'c_user_id' => $new_owner,
                        'c_inherit_table' => $app_tables[$target_dir],
                        'c_inherit_object' => $target_object_id
                    );
                    $this->db->where("c_uid", $link_id);
                    if ($this->db->update('t_stor_links', $data)) {
                        $this->flash->addMessage('info', 'soubor byl presunut');
                    }
                    else {
                        $this->flash->addMessage('error', 'presunuti se nepovedlo');
                    }
                /*
                }
                else {
                    $this->flash->addMessage('error', 'k presunu nemate prava');
                }
                */
            }
        }
        
        if ($action_source == 'browser') {
            $redirect_url = $this->routerParser->urlFor('stor.browser').'?filter=/'.$target_dir.'/'.$target_object_id;
        }
        else {
            // toto by melo byt vzdy nastaveno pri editaci, abychom mohli tu adresu zase vykreslit s uz zmenenym nazvem
            if (!empty($actual_dir)) {
                $redirect_url = $this->routerParser->urlFor('stor.uploader').'/~/'.$actual_dir;
            }
            else {  // pro jistotu, kdyz to nebude nastaveno, jdeme na root
                $redirect_url = $this->routerParser->urlFor('stor.uploader');
            }
        }
        
        return $response->withRedirect($redirect_url);
    }
    
    
    /* browser with a filter */
    
    // vypis stranky s filtracnim browserem souboru, select2
    public function storBrowserGui($request, $response, $args)
    {
        $vystup = '';
        $preset_options = '';
        
        $preset_filter = $request->getParam('filter');
        
        // nacteme si to z containeru ktery to ma ze tridy
        $app_dirs = $this->stor->app_dirs;
        $app_tables = $this->stor->app_tables;
        
        // pokud je v getu nejaky filter
        if (!empty($preset_filter)) {
            $casti_filtru = explode(' ', $preset_filter);
            foreach ($casti_filtru as $filter) {
                $safe_filter = trim($filter);
                if (!empty($filter)) {
                    $preset_options .= '<option value="'.$safe_filter.'" selected>'.$safe_filter.'</option>';
                }
            }
        }
        
        
        // priprava vyberu diru do copy move popupu
        $stor_dirs_options = '';
        foreach ($app_dirs as $dir => $description) {
            if ($dir == 'my_owned' or $dir == 'my_files') { continue; }
            $stor_dirs_options .= '<option value="'.$dir.'">'.$description.'</option>';
        }
        
        return $this->render($response, 'Stor/Views/stor-browser-gui.twig',
        array(
            'vystup' => $vystup,
            'preset_options' => $preset_options,
            'article_class' => 'items-list-page',
            'stor_dirs_options' => $stor_dirs_options,
            'ui_menu_active' => 'stor.browser'
        ));
    }
    
    
}

