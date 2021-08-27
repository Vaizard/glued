<?php
namespace Glued\Core\Classes\Utils;
//use Respect\Validation\Validator as v;
//use UnexpectedValueException;
use Exception;

class Utils
{


    protected $db;
    protected $settings;


    public function __construct($db, $settings) {
        $this->db = $db;
        $this->settings = $settings;
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

}
