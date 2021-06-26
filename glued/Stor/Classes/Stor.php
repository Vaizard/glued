<?php

declare(strict_types=1);
namespace Glued\Stor\Classes;

class Stor {

    protected $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public $app_dirs = [
       "my_files"             => 'My private files',
       "my_owned"             => 'My owned files',
       "core_profiles"        => 'Profiles',
       "core_domains"         => 'Domains',
       "store_items"          => 'Store Items',
       "store_subscriptions"  => 'Store Subscriptions',
       "store_tickets"        => 'Store Tickets',
       "worklog"              => 'Worklog',
       "fin_trx"              => 'Transactions',
       "fin_costs"              => 'Fin Costs'
    ];
    
    // prevod path na tabulku, kvuli predzjisteni prav
    // pozor, kazda z techto tabulek musi mit id nazvane c_uid a virtualni sloupec stor_name
    public $app_tables = [
       "core_profiles"       => 't_core_profiles',
       "core_domains"        => 't_core_domains',
       "store_items"         => 't_store_items',
       "store_subscriptions" => 't_store_subscriptions',
       "store_tickets"       => 't_store_tickets',
       "store_sellers"       => 't_store_sellers',
       "worklog"             => 't_worklog_items',
       "fin_trx"             => 't_fin_trx',
       "fin_costs"             => 't_fin_costs'
    ];
    
    public $mime_icons = [
        // Media
        'image' => 'fa-file-image-o',
        'audio' => 'fa-file-audio-o',
        'video' => 'fa-file-video-o',
        // Documents
        'application/pdf' => 'fa-file-pdf-o',
        'application/msword' => 'fa-file-word-o',
        'application/vnd.ms-word' => 'fa-file-word-o',
        'application/vnd.oasis.opendocument.text' => 'fa-file-word-o',
        'application/vnd.openxmlformats-officedocument.wordprocessingml' => 'fa-file-word-o',
        'application/vnd.ms-excel' => 'fa-file-excel-o',
        'application/vnd.openxmlformats-officedocument.spreadsheetml' => 'fa-file-excel-o',
        'application/vnd.oasis.opendocument.spreadsheet' => 'fa-file-excel-o',
        'application/vnd.ms-powerpoint' => 'fa-file-powerpoint-o',
        'application/vnd.openxmlformats-officedocument.presentationml' => 'fa-file-powerpoint-o',
        'application/vnd.oasis.opendocument.presentation' => 'fa-file-powerpoint-o',
        'text/plain' => 'fa-file-text-o',
        'text/html' => 'fa-file-code-o',
        'application/json' => 'fa-file-code-o',
        // Archives
        'application/gzip' => 'fa-file-archive-o',
        'application/zip' => 'fa-file-archive-o',
        'application/x-zip-compressed' => 'fa-file-archive-o',
        // Misc
        'application/octet-stream' => 'fa-file-o',
      ];
        
    // cti tagy ke dvojici tabulka uid
    public function read_tags($table, $uid) {
        $pole_tagu = array();
        
        $this->db->where("c_table", $table);
        $this->db->where("c_uid", $uid);
        $bills = $this->db->get('t_tag_assignments');
        if (count($bills) > 0) {
            foreach ($bills as $data) {
                $pole_tagu[] = array('name' => $data['c_tagname'], 'value' => $data['c_tagvalue']);
            }
        }
        return $pole_tagu;
    }
    
    // cti hodnotu tagu
    public function read_tag_value($table, $uid, $tagname) {
        $this->db->where("c_table", $table);
        $this->db->where("c_uid", $uid);
        $this->db->where("c_tagname", $tagname);
        $data = $this->db->getOne('t_tag_assignments');
        
        if (count($data) == 0) {
            return false;
        }
        else {
            return $data['c_tagvalue'];
        }
    }
    
    // vloz tag
    public function insert_tag($table, $uid, $tagname, $tagvalue, $system = 0) {
        if ($this->read_tag_value($table, $uid, $tagname) !== false) {
            $data = Array ("c_table" => $table, "c_uid" => $uid, "c_tagname" => $tagname, "c_tagvalue" => $tagvalue, "c_system" => $system);
            $insert = $this->db->insert('t_tag_assignments', $data);
            if ($insert) {
                return true;
            }
            else {
                return false;
            }
        }
        else {
            return false;
        }
    }
    
    // prevede mime na fontawesome ikonu
    public function get_mime_icon( $mime_type ) {
      // jestlize to tam mame cele
      if (isset($this->mime_icons[ $mime_type ])) {
        return $this->mime_icons[ $mime_type ];
      }
      else {    // jinak se podivame jestli mame aspon prvni cast
          $mime_parts = explode('/', $mime_type, 2);
          $mime_group = $mime_parts[0];
          if (isset($this->mime_icons[ $mime_group ])) {
            return $this->mime_icons[ $mime_group ];
          }
          else {
            return "fa-file-o"; // default na ktery spadne vse neurcene
          }
      }
    }
    
    public function human_readable_size($raw) {
        $size_names = array('Byte','KB','MB','GB','TB','PB','EB','ZB','YB','NB','DB');
        $name_id = 0;
        while ($raw>=1024 && ($name_id<count($size_names)-1)) {
            $raw /= 1024;
            $name_id++;
        }
        $ret = round($raw,1).' '.$size_names[$name_id];
        return $ret;
    }
    
    // funkce ktera nacte adresu souboru z jeho link id
    // TODO toto je stara funkce, kterou ale chceme pouzit v budoucnu a je treba ji upravit na novou syntaxi containeru
    public function read_stor_file_info($link_id) {
        
        // nacteme sha512
        $this->db->where ("c_uid", $link_id);
        $file_link = $this->db->getOne("t_stor_links");
        
        // nacteme path
        $sloupce = array("doc->>'$.data.storage[0].path' as path");
        $this->db->where("sha512", $file_link['c_sha512']);
        $file_data = $this->db->getOne("t_stor_objects", $sloupce);
        
        $fullpath = $file_data['path'].'/'.$file_link['c_sha512'];
        
        $data['filename'] = $file_link['c_filename'];
        $data['fullpath'] = $fullpath;
        
        return $data;
    }
    
    // funkce na smazani souboru, vraci pole s tim co se stalo 
    public function delete_stor_file($link_id) {
        
        $data['success'] = false;
        $data['message'] = '';
        
        // nacteme si link a jeho sha512
        $this->db->where("c_uid", $link_id);
        $link_data = $this->db->getOne('t_stor_links');
        if ($this->db->count == 0) { // TODO, asi misto countu pouzit nejaky test $link_data
            $data['success'] = false;
            $data['message'] = 'pruser, soubor neexistuje, nevim na co jste klikli, ale jste tu spatne';
        }
        else {
            $hash = $link_data['c_sha512'];
            
            // spocitame kolik mame linku s timto hasem
            $this->db->where("c_sha512", $hash);
            $links = $this->db->get('t_stor_links');
            
            //pokud mame jen jeden, smazeme i objekt
            if (count($links) == 1) {
                // nejdriv smazem z links
                $this->db->where("c_uid", $link_id);
                if ($this->db->delete('t_stor_links')) {
                    // nacteme si z object cestu ke smazani souboru, i kdz, sla by odvodit, ale muze tam byt prave jiny driver a pak cesta neni dana hashem, TODO
                    // zatim predpokladame driver fs, [0] znamena prvni prvek pole storage, coz je objekt takze za tim zase zaciname teckou
                    // rawQuery v joshcam vraci vzdy pole, i kdyz je vysledek jen jeden
                    $objects = $this->db->rawQuery(" SELECT `c_json`->>'$.data.storage[0].path' AS path FROM t_stor_objects WHERE c_sha512 = ? ", Array ($hash));
                    // TODO, kontrola jestli je jeden vysledek a jestli neni path prazdna
                    $file_to_delete = $objects[0]['path'].'/'.$hash;
                    unlink($file_to_delete);
                    // mazani z objects
                    $this->db->where("c_sha512", $hash);
                    if ($this->db->delete('t_stor_objects')) {
                        $data['success'] = true;
                        $data['message'] = 'soubor '.$file_to_delete.' byl komplet smazan z links i object.';
                    }
                    else {
                        // tady je jen polovicni success
                        $data['success'] = true;
                        $data['message'] = 'soubor '.$file_to_delete.' byl smazan z links, ale zrejme nejakou systemovou chybou zustal v objects a neodkazuje ted na nej zadny link.';
                    }
                }
                else {
                    $data['success'] = false;
                    $data['message'] = 'smazani se nepovedlo';
                }
            }
            else if (count($links) > 1) {
                $this->db->where("c_uid", $link_id);
                if ($this->db->delete('t_stor_links')) {
                    $data['success'] = true;
                    $data['message'] = 'link na soubor byl smazan, ale bylo jich vic, takze soubor zustava';
                }
                else {
                    $data['success'] = false;
                    $data['message'] = 'smazani se nepovedlo';
                }
            }
            else {
                $data['success'] = false;
                $data['message'] = 'hash souboru neexistuje, zahadna chyba';
            }
        }
        
        return $data;
    }
    
    // interni php api funkce
    
    /*
    vstup:
    $newfile
    
    vystup
    $file_object_data['sha512'] - hash, klic v objects tabulce
    $file_object_data['new_id'] - klic v links tabulce
    $file_object_data['insert'] - 0 uz byl v objects, 1 bylo vlozeno i do objects (tyka se tabulky objects)
    $file_object_data['linked'] - 0 nebylo pridano do links (uz tam je soubor se stejnym nazvem a hashem) 1 bylo pridano do links (tyka se tabulky links)
    $file_object_data['size'] - aktualni hodnota size v objects tabulce
    $file_object_data['mime'] - aktualni hodnota mime v objects tabulce
    */
    
    public function internal_create($tmp_path, $newfile, $user_id, $inherit_table, $inherit_object) {
        // pripravime si hash
        $sha512 = hash_file('sha512', $tmp_path);
        
        $atributes = array();
        $atributes['filename'] = $newfile->getClientFilename();
        $atributes['size'] = $newfile->getSize();
        $atributes['mime'] = $newfile->getClientMediaType();
        
        // navratova data
        $file_object_data = array();
        $file_object_data['sha512'] = $sha512;
        
        // zjistime jestli soubor se stejnym hashem uz mame
        $this->db->where("c_sha512", $sha512);
        $file_object = $this->db->getOne('t_stor_objects');
        if ($this->db->count == 0) {
            
            // vytvorime tomu adresar
            $dir1 = substr($sha512, 0, 1);
            $dir2 = substr($sha512, 1, 1);
            $dir3 = substr($sha512, 2, 1);
            $dir4 = substr($sha512, 3, 1);
            
            $cilovy_dir = __ROOT__.'/private/data/stor/'.$dir1.'/'.$dir2.'/'.$dir3.'/'.$dir4;
            
            if (!is_dir($cilovy_dir)) { mkdir($cilovy_dir, 0777, true); }
            
            // presuneme
            $newfile->moveTo($cilovy_dir.'/'.$sha512);
            
            // pripravime c_json pro vlozeni
            $new_file_array = array();
            $new_file_array['_v'] = '1';
            $new_file_array['sha512'] = $sha512;
            $new_file_array['size'] = $atributes['size'];
            $new_file_array['mime'] = $atributes['mime'];
            $new_file_array['checked'] = false;
            $new_file_array['ts_created'] = time();
            $new_file_array['storage'] = array(array("driver" => "fs", "path" => $cilovy_dir));
            
            $new_data_array = array();
            $new_data_array['data'] = $new_file_array;
            
            $json_string = json_encode($new_data_array);
            
            // pozor, spojit dve vkladani pres commit, TODO
            
            // vlozime do objects
            $data = Array ("c_json" => $json_string);
            $this->db->insert ('t_stor_objects', $data);
            
            // protoze je novy v objects, nemuze byt se stejnym hashem v links, takze vzdy vkladame i do links
            $data = Array (
            "c_sha512" => $sha512,
            "c_user_id" => $user_id,
            "c_filename" => $atributes['filename'],
            "c_inherit_table" => $inherit_table,
            "c_inherit_object" => $inherit_object
            );
            $new_id = $this->db->insert ('t_stor_links', $data);
            
            // navratova data
            $file_object_data['new_id'] = $new_id;
            $file_object_data['insert'] = 1;
            $file_object_data['linked'] = 1;
            $file_object_data['size'] = $atributes['size'];
            $file_object_data['mime'] = $atributes['mime'];
        }
        else {
            // soubor uz existuje v objects
            
            // nejdriv vytvorime cast dat z existujiciho souboru
            $file_data = json_decode($file_object['c_json'], true);
            $file_object_data['insert'] = 0;
            $file_object_data['size'] = $file_data['data']['size'];
            $file_object_data['mime'] = $file_data['data']['mime'];
            
            // pokud v links nahodou uz je soubor se stejnym nazvem a hashem, tak preskocime i vlozeni do links
            $this->db->where("c_sha512", $sha512);
            $this->db->where("c_inherit_table", $inherit_table);
            $this->db->where("c_inherit_object", $inherit_object);
            $this->db->where("c_filename", $atributes['filename']);
            $file_link = $this->db->getOne('t_stor_links');
            if ($this->db->count == 0) {
                $data = Array (
                "c_sha512" => $sha512,
                "c_user_id" => $user_id,
                "c_filename" => $atributes['filename'],
                "c_inherit_table" => $inherit_table,
                "c_inherit_object" => $inherit_object
                );
                $new_id = $this->db->insert ('t_stor_links', $data);
                
                // doplnime dalsi navratova data
                $file_object_data['new_id'] = $new_id;
                $file_object_data['linked'] = 1;
            }
            else {
                // doplnime dalsi navratova data
                $file_object_data['new_id'] = $file_link['c_uid'];
                $file_object_data['linked'] = 0;
            }
        }
        
        return $file_object_data;
    }

   public function internal_upload($newfile, $object_table, $object_id) {

        if ($newfile->getError() === UPLOAD_ERR_OK) {
            $filename = $newfile->getClientFilename();
            // ziskame tmp path ktere je privatni vlastnost $newfile, jeste zanorene v Stream, takze nejde normalne precist
            // vypichneme si stream a pouzijeme na to reflection
            $stream = $newfile->getStream();
            $reflectionProperty = new \ReflectionProperty(\Nyholm\Psr7\Stream::class, 'uri');
            $reflectionProperty->setAccessible(true);
            $tmp_path = $reflectionProperty->getValue($stream);
            // zavolame funkci, ktera to vlozi. vysledek je pole dulezitych dat. nove id v tabulce links je $file_object_data['new_id']
            $file_object_data = $this->internal_create($tmp_path, $newfile, $GLOBALS['_GLUED']['authn']['user_id'], $this->app_tables[$object_table], $object_id);
            return $file_object_data;
        }

   }

  
}
