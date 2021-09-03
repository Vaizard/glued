<?php

declare(strict_types=1);
namespace Glued\Contacts\Classes;
use Symfony\Component\DomCrawler\Crawler;
use Glued\Fin\Classes\Utils as FinUtils;

class CZ
{

    protected $c;


    public function __construct($c) {
        $this->c = $c;
    }


    public function __get($property) {
        if ($this->c->get($property)) {
            return $this->c->get($property);
        }
    }


    public function urikey(string $what, string $id) :? array {
        $pairs = [
            'ids_justice' => [
                'uri' => ($uri = 'https://or.justice.cz/ias/ui/rejstrik-$firma?jenPlatne=PLATNE&ico='.$id.'&polozek=500'),
                'key' => 'contacts.cz_ids.justice.'.md5($uri),
            ],
            'ids_adisrws' => [
                'uri' => ($uri = 'http://adisrws.mfcr.cz/adistc/axis2/services/rozhraniCRPDPH.rozhraniCRPDPHSOAP?wsdl'),
                'key' => 'contacts.cz_ids.adisrws.'.md5($uri."&getStatusNespolehlivyPlatceRozsireny&".$id),
            ],
            'ids_vreo' => [
                'uri' => ($uri = 'https://wwwinfo.mfcr.cz/cgi-bin/ares/darv_vreo.cgi?ico='.$id.'&jazyk=cz'),
                'key' => 'contacts.cz_ids.vreo.'.md5($uri),
            ],
            'ids_rzp' => [
                'uri' => ($uri = 'https://wwwinfo.mfcr.cz/cgi-bin/ares/darv_rzp.cgi?ico='.$id.'&xml=0&rozsah=2'),
                'key' => 'contacts.cz_ids.rzp.'.md5($uri),
            ],
            'names_justice' => [
                'uri' => ($uri = 'https://or.justice.cz/ias/ui/rejstrik-$firma?jenPlatne=PLATNE&nazev='.$id.'&polozek=500'),
                'key' => 'contacts.cz_names.justice.'.md5($uri),
            ],
            'names_rzp' => [
                'uri' => ($uri = 'http://www.rzp.cz/cgi-bin/aps_cacheWEB.sh?VSS_SERV=ZVWSBJFND&Action=Search&PRESVYBER=0&PODLE=subjekt&ICO=&OBCHJM='.$id.'&VYPIS=1'),
                'key' => 'contacts.cz_names.rzp.'.md5($uri),
            ],
            'names_ares' => [
                'uri' => ($uri = 'http://wwwinfo.mfcr.cz/cgi-bin/ares/ares_es.cgi?obch_jm='.urlencode($id)),
                'key' => 'contacts.cz_names.ares.'.md5($uri),
            ],
            'ids_ares' => [
                'uri' => ($uri = 'http://wwwinfo.mfcr.cz/cgi-bin/ares/ares_es.cgi?ico='.$id),
                'key' => 'contacts.cz_ids.ares.'.md5($uri),
            ],
        ];
        return ($pairs[$what] ?: null);
    }


    //////////////////////////////////////////////////////////////////
    // HELPERS ///////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////

    private function date_to_english(string $date) : string {
        $m_in = [ 'ledna', 'února', 'března', 'dubna', 'května', 'června', 'července', 'srpna', 'září', 'října', 'listopadu', 'prosince' ];
        $m_out = [ 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' ];
        $date = str_replace($m_in, $m_out, $date);
        $date = str_replace(".", "", $date);
        return $date;
    }

    //////////////////////////////////////////////////////////////////
    // NAMES /////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////

    public function names_ares(string $id, &$result_raw = null) :? array {
        $result = null;
        $uri = $this->urikey(__FUNCTION__, $id)['uri'];

        try {
            $data = $this->utils->fetch_uri($uri);
        } catch (\Exception $e) {
            $result_raw = $e->getMessage();
            return $result;
        }
        $result_raw = $data;

        $xml = new \SimpleXMLElement($data);
        $ns = $xml->getDocNamespaces();
        $data = $xml->children($ns['are']) ?? null;
        if (!$data) return [];
        $all = $data->children($ns['dtt'])->V;
        $all = json_decode(json_encode($all), true);

        if (is_array($all['S'])) $all = $all['S'];
        if (is_array($all)) {

            $zu = $all;
            if ($zu['ojm'] ?? null != null) {
                $rzp['fn'] = $zu['ojm'];
                $rzp['nat'][0]['regid'] = $zu['ico'];
                $rzp['nat'][0]['vatid'] = str_replace('dic=', 'CZ', $zu['p_dph'] ?? '') ?? null;
                $rzp['addr'][0]['unstructured'] = $zu['jmn'];
                $rzp['nat'][0]['country'] = 'CZ';
                $result[] = $rzp;
            } else {
                foreach ($all as $zu) {
                    if ($zu['ojm'] ?? null != null) {
                        $rzp['fn'] = $zu['ojm'];
                        $rzp['nat'][0]['regid'] = $zu['ico'];
                        $rzp['nat'][0]['vatid'] = str_replace('dic=', 'CZ', $zu['p_dph'] ?? '') ?? null;
                        $rzp['addr'][0]['unstructured'] = $zu['jmn'];
                        $rzp['nat'][0]['country'] = 'CZ';
                        $result[] = $rzp;
                    }
                }
            }
        }
        return $result;
    }


    public function names_justice(string $id, &$result_raw = null) :? array {
      $result = null;
      $uri = $this->urikey(__FUNCTION__, $id)['uri'];
      $crawler = $this->goutte->request('GET', $uri);
      $crawler->filter('div.search-results > ol > li.result')->each(function (Crawler $table) use (&$result) {
          $r['fn'] = $table->filter('div > table > tbody > tr:nth-child(1) > td:nth-child(2) > strong')->text();
          $r['nat'][0]['regid'] = $table->filter('div > table > tbody > tr:nth-child(1) > td:nth-child(4) > strong')->text();
          $r['addr'][0]['unstructured'] = $table->filter('div > table > tbody > tr:nth-child(3) > td:nth-child(2)')->text();
          $r['nat'][0]['regby'] = $table->filter('div > table > tbody > tr:nth-child(2) > td:nth-child(2)')->text();
          $r['nat'][0]['regdt'] = $table->filter('div > table > tbody > tr:nth-child(2) > td:nth-child(4)')->text();
          $r['nat'][0]['country'] = 'CZ';
          $result[$r['nat'][0]['regid']] = $r;
      });
      $result_raw = $this->goutte->getResponse()->getContent() ?? null;
      return $result;
    }


    public function names_rzp(string $id, &$result_raw = null) :? array {
      $result = null;
      $uri = $this->urikey(__FUNCTION__, $id)['uri'];
      $crawler = $this->goutte->request('GET', $uri);
      $crawler->filter('div#obsah > div.blok.data.subjekt')->each(function (Crawler $table) use (&$result) {
          $r['fn'] = $table->filter('h3')->text();
          $r['fn'] = preg_replace('/^[0-9]{1,2}. /', "", $r['fn']);
          if ($table->filter('dd:nth-child(2)')->text() == 'Fyzická osoba') $r['type'] = 'n';
          if ($table->filter('dd:nth-child(2)')->text() == 'Právnická osoba') $r['type'] = 'l';
          $r['addr'][0]['unstructured'] = $table->filter('dd:nth-child(4)')->text();
          $r['nat'][0]['regid'] = $table->filter('dd:nth-child(8)')->text();
          $r['nat'][0]['country'] = 'CZ';
          $result[ $r['nat'][0]['regid'] ] = $r;

      });
      $result_raw = $this->goutte->getResponse()->getContent() ?? null;
      return $result;
    }


    //////////////////////////////////////////////////////////////////
    // IDS ///////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////

    public function ids_justice(string $id, &$result_raw = null) :? array {
        $result = null;
        $uri = $this->urikey(__FUNCTION__, $id)['uri'];
        $crawler = $this->goutte->request('GET', $uri);
        
        $crawler->filter('div.search-results > ol > li.result')->each(function (Crawler $table) use (&$result, &$id) {
            $r['addr'][0]['kind']['main'] = 1;
            $r['addr'][0]['kind']['billing'] = 1;
            $r['fn'] = $table->filter('div > table > tbody > tr:nth-child(1) > td:nth-child(2) > strong')->text();
            $r['nat'][0]['country'] = 'CZ';
            $r['nat'][0]['regid'] = $table->filter('div > table > tbody > tr:nth-child(1) > td:nth-child(4) > strong')->text();
            $r['addr'][0]['unstructured'] = $table->filter('div > table > tbody > tr:nth-child(3) > td:nth-child(2)')->text();
            $r['addr'][0]['unstructured'] = str_replace( [ '\r\n', '\r', '\n' ], ', ', $r['addr'][0]['unstructured'] ); 
            $r['nat'][0]['regby'] = $table->filter('div > table > tbody > tr:nth-child(2) > td:nth-child(2)')->text();
            $r['nat'][0]['regdt'] = $table->filter('div > table > tbody > tr:nth-child(2) > td:nth-child(4)')->text();
            $r['nat'][0]['regdt'] = date("Ymd",strtotime($this->date_to_english($r['nat'][0]['regdt'])));
            // TODO: Fetch link to company details on justice filtered from the company listing.
            // Since parsing the data from the detail is a pain, this is here for future S&M
            // enthusiasts.
            // $r['link'] = $table->filter('div > ul.result-links > li:nth-child(1) > a')->attr('href');
            $result = $r;
        });
        $result_raw = $this->goutte->getResponse()->getContent() ?? null;
        return $result;
    }


    public function ids_adisrws(string $id, string &$result_raw = null) :? array  {
        $result = null;
        $finutils = new FinUtils();
        $opts = [ 
            'trace' => true,
            //'connection_timeout' => 2000,
            //'cache_wsdl' => WSDL_CACHE_NONE,
        ];
        $uri = $this->urikey(__FUNCTION__, $id)['uri'];
        //ini_set("default_socket_timeout", "2");
        try {
            $soap = new \SoapClient($uri, $opts);
            $data = $soap->__call("getStatusNespolehlivyPlatceRozsireny", array(0 => array($id)));
        } catch (\Exception $e) {
            $result_raw = $e->getMessage();
            return $result;
        }

        $arr = json_decode(json_encode($data), true);
        $status = (int)$arr['status']['statusCode'];
        if (($status === 0) and isset($arr['statusPlatceDPH']['adresa'])) {
            $result_raw = $arr;
            $r['addr'][0]['street'] = $arr['statusPlatceDPH']['adresa']['uliceCislo'];
            $r['addr'][0]['locacity'] = mb_convert_case($arr['statusPlatceDPH']['adresa']['mesto'], MB_CASE_TITLE);
            $r['addr'][0]['zip'] = $arr['statusPlatceDPH']['adresa']['psc'];
            $r['addr'][0]['country'] = $arr['statusPlatceDPH']['adresa']['stat'];
            $r['addr'][0]['kind']['main'] = 1;
            $r['addr'][0]['kind']['billing'] = 1;
            // TODO - add $arr['statusPlatceDPH']['nespolehlivyPlatce'];
            $i = 0;            
            foreach ($arr['statusPlatceDPH']['zverejneneUcty']['ucet'] as $ucet) {
                if (isset($ucet['standardniUcet'])) {
                    $acc[$i]['bank_code'] = $ucet['standardniUcet']['kodBanky'];
                    $acc[$i]['account_number'] = $ucet['standardniUcet']['cislo'];
                    $acc[$i]['account_iban'] = $finutils->cz_number_to_iban($ucet['standardniUcet']['cislo'].'/'.$ucet['standardniUcet']['kodBanky']);
                    $acc[$i]['src']['adisrws.mfcr.cz']['published'] = date("Ymd",strtotime($ucet['datumZverejneni']));
                    $acc[$i]['src']['adisrws.mfcr.cz']['seen'] = date("Ymd",time());
                    $i++;
                }
            }
            $result = $r;
            $result['acc'] = $acc ?? null;
        }
        return $result;
    }

    public function ids_rzp(string $id, &$result_raw = null) :? array {
        $result = null;
        $uri = $this->urikey(__FUNCTION__, $id)['uri'];
        try {
            $data = $this->utils->fetch_uri($uri);
        } catch (\Exception $e) {
            $result_raw = $e->getMessage();
            return $result;
        }
        $result_raw = $data;
        //print_r($result_raw);
        $xml = new \SimpleXMLElement($data);
        $ns = $xml->getDocNamespaces();
        $data = $xml->children($ns['are']) ?? null;
        if (!$data) return [];
        $zu = $data->children($ns['D'])->Vypis_RZP;
        $zu = json_decode(json_encode($zu), true);
        if (!is_null($zu)) {
            $rzp['nat'][0]['country'] = 'CZ';
            $rzp['nat'][0]['regid'] = $zu['ZAU']['ICO'];
            $rzp['fn'] = $zu['ZAU']['OF'];
            $rzp['addr'][0]['kind']['main'] = 1;
            $rzp['addr'][0]['kind']['billing'] = 1;
            $rzp['addr'][0]['country'] = 'Czech republic';
            $rzp['addr'][0]['zip'] = $zu['Adresy']['A']['PSC'];
            $rzp['addr'][0]['region'] = null;
            $rzp['addr'][0]['district'] = null;
            $rzp['addr'][0]['locacity'] = $zu['Adresy']['A']['N'] ?? null;
            $rzp['addr'][0]['quarter'] = $zu['Adresy']['A']['NCO'] ?? null;
            $rzp['addr'][0]['street'] = $zu['Adresy']['A']['NU'] ?? null;
            $rzp['addr'][0]['streetnumber'] = $zu['Adresy']['A']['CO'] ?? null;
            $rzp['addr'][0]['conscriptionnumber'] = $zu['Adresy']['A']['CD'];
            $vreo['addr'][0]['doornumber'] = null;
            $vreo['addr'][0]['floor'] = null;
            $nr = $this->utils->concat('/', [ $rzp['addr'][0]['conscriptionnumber'], $rzp['addr'][0]['streetnumber'] ]);
            $ql = $this->utils->concat('-', [ $rzp['addr'][0]['locacity'], $rzp['addr'][0]['quarter'] ]);
            if (!is_null($rzp['addr'][0]['street'])) {
                $st = $this->utils->concat(' ', [ $rzp['addr'][0]['street'], $nr ]);
            } else {
                $st = $this->utils->concat(' ', [ $rzp['addr'][0]['quarter'], $nr ]);
                $ql = $rzp['addr'][0]['locacity'];
            }
            $rzp['addr'][0]['full'] = $this->utils->concat(', ',  [ $st , $ql , $rzp['addr'][0]['zip'] , $rzp['addr'][0]['country'] ]);
            $result = $rzp;
        }
        return $result;
    }


    public function ids_vreo(string $id, &$result_raw = null) :? array {
        $result = null;
        $uri = $this->urikey(__FUNCTION__, $id)['uri'];
        try {
            $data = $this->utils->fetch_uri($uri);
        } catch (\Exception $e) {
            $result_raw = $e->getMessage();
            return $result;
        }
        $result_raw = $data;
        $xml = new \SimpleXMLElement($data);
        $ns = $xml->getNamespaces(true);
        $are = $xml->children($ns['are']) ?? null;
        if (!$are) return [];
        $zu = json_decode(json_encode($are->Odpoved->Vypis_VREO->Zakladni_udaje), true);
        if (!is_null($zu)) {
            // Get company data
            $vreo['nat'][0]['regid'] = $zu['ICO'];
            $vreo['addr'][0]['kind']['main'] = 1;
            $vreo['addr'][0]['kind']['billing'] = 1;
            $vreo['addr'][0]['country'] = 'Czech republic';
            $vreo['addr'][0]['zip'] = $zu['Sidlo']['psc'] ?? null;
            $vreo['addr'][0]['region'] = null;
            $vreo['addr'][0]['district'] = $zu['Sidlo']['okres'] ?? null;
            $vreo['addr'][0]['locacity'] = $zu['Sidlo']['obec'] ?? null;
            $vreo['addr'][0]['quarter'] = $zu['Sidlo']['castObce'] ?? null;
            $vreo['addr'][0]['street'] = $zu['Sidlo']['ulice'] ?? null;
            $vreo['addr'][0]['streetnumber'] = $zu['Sidlo']['cisloOr'] ?? null;
            $vreo['addr'][0]['conscriptionnumber'] = $zu['Sidlo']['cisloPop'] ?? null;
            $vreo['addr'][0]['doornumber'] = null;
            $vreo['addr'][0]['floor'] = null;
            $nr = $this->utils->concat('/', [ $vreo['addr'][0]['conscriptionnumber'], $vreo['addr'][0]['streetnumber'] ]);
            $ql = $this->utils->concat('-', [ $vreo['addr'][0]['locacity'], $vreo['addr'][0]['quarter'] ]);
            if (!is_null($vreo['addr'][0]['street'])) {
                $st = $this->utils->concat(' ', [ $vreo['addr'][0]['street'], $nr ]);
            } else {
                $st = $this->utils->concat(' ', [ $vreo['addr'][0]['quarter'], $nr ]);
                $ql = $vreo['addr'][0]['locacity'];
            }
            $vreo['addr'][0]['full'] = $this->utils->concat(', ',  [ $st , $ql , $vreo['addr'][0]['zip'] , $vreo['addr'][0]['country'] ]);
            $vreo['addr'][0]['ext']['cz.ruian'] = $zu['Sidlo']['ruianKod'];

            // Get people
            $people = [];
            foreach ($are->Odpoved->Vypis_VREO->Statutarni_organ as $key => $item) {
                $dates = null;
                $role = null;
                $person = null;
                $helper = null;
                // atrributes: dza (datum zapisu / date of registration), dvy (datum vymazu / date of removal)
                
                // simplifying things here by ignoring people deleted from the orgization structure
                // (we'll work only with people who have role 'dvy' unset)
                //if (!isset($dates['dvy'])) {
                    $role = json_decode(json_encode($item), true);
                    foreach ($item->Clen as $obj_person) {
                    //foreach ($role['Clen'] as $person) {
                        $person = json_decode(json_encode($obj_person), true);
                        $helper = null;
                        $dates = null;
                        $dates = json_decode(json_encode($obj_person->attributes()), true)['@attributes']; 
                        if (isset($person['fosoba'])) {
                            $helper['n']['given'] = mb_convert_case($person['fosoba']['jmeno'] ?? '', MB_CASE_TITLE) ?? null;
                            $helper['n']['family'] = mb_convert_case($person['fosoba']['prijmeni'] ?? '', MB_CASE_TITLE) ?? null;
                            $helper['n']['prefix'] = $person['fosoba']['titulPred'] ?? null;
                            $helper['n']['suffix'] = $person['fosoba']['titulZa'] ?? null;
                            $helper['fn'] = trim($helper['n']['prefix'] .' '.$helper['n']['given'] .' '.$helper['n']['family'].' '.$helper['n']['suffix']);
                            if ($person['fosoba']['adresa']['stat'] ?? null == 203) { $person['fosoba']['adresa']['stat'] = 'Czech republic'; }
                            $helper['addr'][0] = [
                                'ext' => [ 'cz.ruian' => $person['fosoba']['adresa']['ruianKod'] ?? null ],
                                'country' => $person['fosoba']['adresa']['stat'] ?? null,
                                'zip' => $person['fosoba']['adresa']['psc'] ?? null,
                                'locacity' => $person['fosoba']['adresa']['obec'] ?? null,
                                'quarter' => $person['fosoba']['adresa']['castObce'] ?? null,
                                'street' => $person['fosoba']['adresa']['ulice'] ?? null,
                                'streetnumber' => $person['fosoba']['adresa']['cisloPop'] ?? null,
                                'conscriptionnumber' => $person['fosoba']['adresa']['cisloOr'] ?? null,
                                'kind' => ['permanent' => 1],
                            ];
                            $nr = $this->utils->concat('/', [ $helper['addr'][0]['conscriptionnumber'], $helper['addr'][0]['streetnumber'] ]);
                            $ql = $this->utils->concat('-', [ $helper['addr'][0]['locacity'], $helper['addr'][0]['quarter'] ]);
                            if (!is_null($helper['addr'][0]['street'])) {
                                $st = $this->utils->concat(' ', [ $helper['addr'][0]['street'], $nr ]);
                            } else {
                                $st = $this->utils->concat(' ', [ $helper['addr'][0]['quarter'], $nr ]);
                                $ql = $helper['addr'][0]['locacity'];
                            }
                            $helper['addr'][0]['full'] = $this->utils->concat(', ', [ $st , $ql , $helper['addr'][0]['zip'] , $helper['addr'][0]['country'] ]);
                            // The XML api returns how people are assigned to roles (board members, CEO).
                            // Since we want to map out how roles are assigned to people in relation to
                            // an organization, we need to identify people with multiple roles attached
                            // to them. Since we only have peoples names, honorific prefixes and suffixes
                            // and their permanent address, we'll simplify the decision if a new role should
                            // be assigned to an already encountered person, or if a new person and should
                            // be created by hashing their name and address (the hash is used as a 
                            // pseudounique id). This will fail in situations like when organizational officers
                            // are two people with the same family name and given name living at the same address,
                            // as the code will thing these two people are one and the same person. While the two
                            // could be discerned by their birthdates (this could be fetched with ids_justice()),
                            // we don't have that yet, so the expected failures.
                            $hash = md5($helper['fn'].'~'.$helper['addr'][0]['locacity'].'~'.$helper['addr'][0]['street'].'~'.$helper['addr'][0]['streetnumber']);
                            if (array_key_exists($hash, $people)) {
                                // Adding a role to an existing person. Test for dupes is required due to the registry
                                // also showing botched entries.
                                $dupe = false;
                                foreach ($people[$hash]['role'] as $existingrole) {
                                    if (($existingrole['name'] == $role['Nazev']) and ($existingrole['dt_from'] == $dates['dza'])) $dupe = true;
                                }
                                if (!$dupe) {
                                    $people[$hash]['role'][] = [
                                        'name' => $role['Nazev'],
                                        'dt_from' => $dates['dza'],
                                        'dt_till' => $dates['dvy'] ?? null,
                                    ];
                                }
                            } else {
                                $people[$hash] = $helper;
                                $people[$hash]['role'][] = [
                                    'name' => $role['Nazev'],
                                    'dt_from' => $dates['dza'],
                                    'dt_till' => $dates['dvy'] ?? null,
                                ];
                            }
                        }
                    }
                //}
            }

            foreach ($are->Odpoved->Vypis_VREO->Jiny_organ as $key => $item) {
                $dates = null;
                $role = null;
                $person = null;
                $helper = null;
                // atrributes: dza (datum zapisu / date of registration), dvy (datum vymazu / date of removal)
                $dates = json_decode(json_encode($item->attributes()), true)['@attributes']; 
                // simplifying things here by ignoring people deleted from the orgization structure
                // (we'll work only with people who have role 'dvy' unset)
                //if (!isset($dates['dvy'])) {
                    $role = json_decode(json_encode($item), true);
                    foreach ($item->Clen as $obj_person) {
                        $person = json_decode(json_encode($obj_person), true);
                        $helper = null;
                        $dates = json_decode(json_encode($obj_person->attributes()), true)['@attributes']; 
                        if (isset($person['fosoba'])) {
                            $helper['n']['given'] = mb_convert_case($person['fosoba']['jmeno'], MB_CASE_TITLE);
                            $helper['n']['family'] = mb_convert_case($person['fosoba']['prijmeni'], MB_CASE_TITLE);
                            $helper['n']['prefix'] = $person['fosoba']['titulPred'] ?? null;
                            $helper['n']['suffix'] = $person['fosoba']['titulZa'] ?? null;
                            $helper['fn'] = trim($helper['n']['prefix'] .' '.$helper['n']['given'] .' '.$helper['n']['family'].' '.$helper['n']['suffix']);
                            if ($person['fosoba']['adresa']['stat'] == 203) { $person['fosoba']['adresa']['stat'] = 'Czech republic'; }
                            $helper['addr'][0] = [
                                'ext' => [ 'cz.ruian' => $person['fosoba']['adresa']['ruianKod'] ?? null ],
                                'country' => $person['fosoba']['adresa']['stat'] ?? null,
                                'zip' => $person['fosoba']['adresa']['psc'] ?? null,
                                'locacity' => $person['fosoba']['adresa']['obec'] ?? null,
                                'quarter' => $person['fosoba']['adresa']['castObce'] ?? null,
                                'street' => $person['fosoba']['adresa']['ulice'] ?? null,
                                'streetnumber' => $person['fosoba']['adresa']['cisloPop'] ?? null,
                                'conscriptionnumber' => $person['fosoba']['adresa']['cisloOr'] ?? null,
                                'kind' => ['permanent' => 1],
                            ];
                            $nr = $this->utils->concat('/', [ $helper['addr'][0]['conscriptionnumber'], $helper['addr'][0]['streetnumber'] ]);
                            $ql = $this->utils->concat('-', [ $helper['addr'][0]['locacity'], $helper['addr'][0]['quarter'] ]);
                            if (!is_null($helper['addr'][0]['street'])) {
                                $st = $this->utils->concat(' ', [ $helper['addr'][0]['street'], $nr ]);
                            } else {
                                $st = $this->utils->concat(' ', [ $helper['addr'][0]['quarter'], $nr ]);
                                $ql = $helper['addr'][0]['locacity'];
                            }
                            $helper['addr'][0]['full'] = $this->utils->concat(', ', [ $st , $ql , $helper['addr'][0]['zip'] , $helper['addr'][0]['country'] ]);
                            // The XML api returns how people are assigned to roles (board members, CEO).
                            // Since we want to map out how roles are assigned to people in relation to
                            // an organization, we need to identify people with multiple roles attached
                            // to them. Since we only have peoples names, honorific prefixes and suffixes
                            // and their permanent address, we'll simplify the decision if a new role should
                            // be assigned to an already encountered person, or if a new person and should
                            // be created by hashing their name and address (the hash is used as a 
                            // pseudounique id). This will fail in situations like when organizational officers
                            // are two people with the same family name and given name living at the same address,
                            // as the code will thing these two people are one and the same person. While the two
                            // could be discerned by their birthdates (this could be fetched with ids_justice()),
                            // we don't have that yet, so the expected failures.
                            $hash = md5($helper['fn'].'~'.$helper['addr'][0]['locacity'].'~'.$helper['addr'][0]['street'].'~'.$helper['addr'][0]['streetnumber']);
                            if (array_key_exists($hash, $people)) {
                                // Adding a role to an existing person. Test for dupes is required due to the registry
                                // also showing botched entries.
                                $dupe = false;
                                foreach ($people[$hash]['role'] as $existingrole) {
                                    if (($existingrole['name'] == $role['Nazev']) and ($existingrole['dt_from'] == $dates['dza'])) $dupe = true;
                                }
                                if (!$dupe) {
                                    $people[$hash]['role'][] = [
                                        'name' => $role['Nazev'],
                                        'dt_from' => $dates['dza'],
                                    ];
                                }
                            } else {
                                $people[$hash] = $helper;
                                $people[$hash]['role'][] = [
                                    'name' => $role['Nazev'],
                                    'dt_from' => $dates['dza'],
                                ];
                            }
                        }
                    }
                //}
            }
 
            // Get rid of the hashes
            $i = 0;
            foreach ($people as $reindex) {
                $repeople[$i] = $reindex;
                $i++;
            }
            $vreo['people'] = $repeople;
            $result = $vreo;
        }
        return $result;
    }

    public function ids_ares(string $id, &$result_raw = null) :? array {
        $result = null;
        $uri = $this->urikey(__FUNCTION__, $id)['uri'];

        try {
            $data = $this->utils->fetch_uri($uri);
        } catch (\Exception $e) {
            $result_raw = $e->getMessage();
            return $result;
        }
        $result_raw = $data;

        $xml = new \SimpleXMLElement($data);
        $ns = $xml->getDocNamespaces();
        $data = $xml->children($ns['are']) ?? null;
        if (!$data) return [];
        $all = $data->children($ns['dtt'])->V;
        $all = json_decode(json_encode($all), true);
        if (is_array($all['S'])) $all = $all['S'];
        if (is_array($all)) {
            $zu = $all;
            if ($zu['ojm'] ?? null != null) {
                $rzp['fn'] = $zu['ojm'];
                $rzp['nat'][0]['country'] = 'CZ';
                $rzp['nat'][0]['regid'] = $zu['ico'];
                $rzp['nat'][0]['vatid'] = str_replace('dic=', 'CZ', $zu['p_dph'] ?? '') ?? null;
                $rzp['addr']['kind']['main'] = 1;
                $rzp['addr']['kind']['billing'] = 1;
                $rzp['addr']['unstructured'] = $zu['jmn'];
                $result = $rzp;
            } 
        }
        return $result;
    }


    public function ids(string $id) :? array {

      $result = [];
      $services = [ 'ids_ares', 'ids_justice', 'ids_adisrws', 'ids_vreo', 'ids_rzp' ];
      foreach ($services as $svc) {

          $cnf = $this->urikey($svc, $id); $raw_result = null;
          if ($this->fscache->has($cnf['key'])) {
              $new = $this->fscache->get($cnf['key']);
              $result = array_replace_recursive($result, $new ?? []);            
          } else {
              $new = $this->$svc($id, $raw_result);
              $result = array_replace_recursive($result, $new ?? []);
              if (!is_null($new)) $this->fscache->set($cnf['key'], $new, 3600); // 60 minutes
          }

          if (is_null($new)) { 
              if ($svc == 'ids_adisrws') { $msg = $raw_result; } else { $msg = ''; }
              $query[$svc] = [ 'status' => 'Error', 'message' => $msg ]; 
            } else { 
              $query[$svc]['status'] = 'OK'; 
            }

      }
      // Debug
      // $result['query'] = $query;
      return $result;
    }

}