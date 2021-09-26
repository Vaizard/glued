<?php
namespace Glued\Core\Classes\Utils;
//use Respect\Validation\Validator as v;
//use UnexpectedValueException;
use Exception;

class Utils
{


    protected $db;
    protected $settings;
    protected $routecollector;


    public function __construct($db, $settings, $routecollector) {
        $this->db = $db;
        $this->settings = $settings;
        $this->routecollector = $routecollector;
    }

    public function default_locale(string $language): string {
        $def_locales = [
            "af" => "af_ZA",
            "ar" => "ar",
            "bg" => "bg_BG",
            "ca" => "ca_AD",
            "cs" => "cs_CZ",
            "cy" => "cy_GB",
            "da" => "da_DK",
            "de" => "de_DE",
            "el" => "el_GR",
            "en" => "en_US",
            "es" => "es_ES",
            "et" => "et_EE",
            "eu" => "eu",
            "fa" => "fa_IR",
            "fi" => "fi_FI",
            "fr" => "fr_FR",
            "he" => "he_IL",
            "hi" => "hi_IN",
            "hr" => "hr_HR",
            "hu" => "hu_HU",
            "id" => "id_ID",
            "is" => "is_IS",
            "it" => "it_IT",
            "ja" => "ja_JP",
            "km" => "km_KH",
            "ko" => "ko_KR",
            "la" => "la",
            "lt" => "lt_LT",
            "lv" => "lv_LV",
            "mn" => "mn_MN",
            "nb" => "nb_NO",
            "nl" => "nl_NL",
            "nn" => "nn_NO",
            "pl" => "pl_PL",
            "pt" => "pt_PT",
            "ro" => "ro_RO",
            "ru" => "ru_RU",
            "sk" => "sk_SK",
            "sl" => "sl_SI",
            "sr" => "sr_RS",
            "sv" => "sv_SE",
            "th" => "th_TH",
            "tr" => "tr_TR",
            "uk" => "uk_UA",
            "vi" => "vi_VN",
            "zh" => "zh_CN"
        ];
        if(isset($def_locales[$language])) { return $def_locales[$language]; }
        else { return 'en_US'; }

    }


    public function sql_insert_with_json($table, $row) {
        $this->db->startTransaction(); 
        $id = $this->db->insert($table, $row);
        $err = $this->db->getLastErrno();
        if ($id) {
          $updt = $this->db->rawQuery("UPDATE `".$table."` SET `c_json` = JSON_SET(c_json, '$.id', ?) WHERE c_uid = ?", Array ((int)$id, (int)$id));
          $err += $this->db->getLastErrno();
        }
        if ($err === 0) { $this->db->commit(); } else { $this->db->rollback(); throw new \Exception(__('Database error: ')." ".$err." ".$this->db->getLastError()); }
        return (int)$id;
    }


    public function fetch_uri($uri, $extra_opts = []) {
        $curl_handle = curl_init();
        $extra_opts[CURLOPT_URL] = $uri; 
        $curl_options = array_replace( $this->settings['curl'], $extra_opts );
        curl_setopt_array($curl_handle, $curl_options);
        $data = curl_exec($curl_handle);
        curl_close($curl_handle);
        return $data;
    }


    // will concatenate array items with delimeter, then trim the result
    public function concat($delimeter, array $arrayOfStrings): string {
      return trim(implode($delimeter, array_filter(array_map('trim',$arrayOfStrings))));
    }


    public function array_unflatten($collection) {
        $collection = (array) $collection;
        $output = array();
        foreach ($collection as $key => $value) {
            $this->array_set( $output, $key, $value );
            if (is_array($value) && !strpos($key, '.')) {
                $nested = array_unflatten( $value );
                $output[$key] = $nested;
            }
        }
        return $output;
    }


    public function array_set( &$array, $key, $value )  {
        if (is_null($key)) { return $array = $value; }
        $keys = explode( '.', $key );
        while (count($keys) > 1) {
            $key = array_shift( $keys );
            // If the key doesn't exist at this depth, we will just create an empty array
            // to hold the next value, allowing us to create the arrays to hold final
            // values at the correct depth. Then we'll keep digging into the array.
            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = array();
            }
            $array =& $array[$key];
        }
        $array[array_shift($keys)] = $value;
        return $array;
    }

    /**
     * String replace nth occurrence
     * 
     * @param type $search      Search string
     * @param type $replace     Replace string
     * @param type $subject     Source string
     * @param type $occurrence  Nth occurrence
     * @return type         Replaced string
     */
    public function str_replace_n($search, $replace, $subject, $occurrence): string {
        $search = preg_quote($search);
        return preg_replace("/^((?:(?:.*?$search){".--$occurrence."}.*?))$search/", "$1$replace", $subject);
    }

    public function get_routes($named = false): array {
        $routes = $this->routecollector->getRoutes();
        foreach ($routes as $route) {
            $i = $route->getPattern();
            $data[$i]['pattern'] = $route->getPattern();
            $data[$i]['methods'] = array_merge($data[$i]['methods'] ?? [], $route->getMethods());
            if ($route->getName()) $data[$i]['name'] = $route->getName();
            if ($data[$i]['name'] != false) {
                try {
                    $data[$i]['url'] = $this->routecollector->getRouteParser()->urlFor($data[$i]['name']);
                } catch (\InvalidArgumentException $e) { $data[$i]['argsrequired'] = true; }
                $data[$i]['meta'] = $this->settings['routes'][$data[$i]['name']] ?? null;
            } 
        }
        ksort($data, SORT_NATURAL);
        return array_values($data);
    }

    public function get_named_routes($named = false): array {
        $routes = $this->get_routes();
        foreach ($routes as $route) {
            if ($route['name']) { $res[$route['name']] = $route; }
        }
        return $res;
    }

    public function get_navigation(): array {
        $routes = $this->get_named_routes(true);
        foreach ($routes as $k => $v) {
            // 3rd tier nodes (leafs)
            $path = explode( '.', $k);
            $item = $v;
            $item['name'] = $k;
            $item['label'] = $this->settings['routes'][$k]['label'] ?? null;
            $item['icon'] = $this->settings['routes'][$k]['icon'] ?? null;
            $r[$path[0]]['children'][$path[1]]['children'][]['node'] = $item;
            // 2nd tier nodes
            $item = null;
            $item['label'] = $this->settings['routes'][$path[0].'.'.$path[1]]['label'] ?? null;
            $item['icon'] = $this->settings['routes'][$path[0].'.'.$path[1]]['icon'] ?? null;
            $item['type'] = 'routegroup';
            $r[$path[0]]['children'][$path[1]]['node'] = $item;            
            // 1st tier nodes
            $item = null;
            $item['label'] = $this->settings['routes'][$path[0]]['label'] ?? null;
            $item['icon'] = $this->settings['routes'][$path[0]]['icon'] ?? null;
            $item['type'] = 'routegroup';
            $r[$path[0]]['node'] = $item;   
        }
        return $r;
    }


}
