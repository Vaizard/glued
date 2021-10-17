<?php

declare(strict_types=1);

namespace Glued\Contacts\Controllers;

use Carbon\Carbon;
use Defr\Ares;
use DragonBe\Vies\Vies;
use DragonBe\Vies\ViesException;
use DragonBe\Vies\ViesServiceException;
use Glued\Contacts\Classes\CZ as CZ;
use Glued\Contacts\Classes\EU;
use Glued\Core\Classes\Json\JsonResponseBuilder;
use Glued\Core\Classes\Utils\Utils;
use Glued\Core\Controllers\AbstractTwigController;
use Phpfastcache\CacheManager;
use Phpfastcache\Config\Config;
use Phpfastcache\Helper\Psr16Adapter;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as v;
use Sabre\VObject;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpInternalServerErrorException;
use Symfony\Component\DomCrawler\Crawler;

class ContactsController extends AbstractTwigController
{

    // src = zdroj
    // link = prilinkovany dokument
    // ext = data ve spesl formatu
    // -----------------
    // kind
    //   legal = 0/1
    //   natural = 0/1
    // -----------------
    // fn
    // n
    //   prefix
    //   given
    //   family
    //   maiden
    //   suffix
    //   prior[] (prior family names)
    //   trade (trading name)
    //   intl[] / translated name { "en": "vaizard institute"}
    // nickname[]
    // -------------------
    // gender
    // dob / birthday
    // dod / deathday
    // photo
    // marital_status
    // -------------------
    // addr[]
    //   kind=(main,billing,permanent,temporary,shipping)
    //   label
    //   unstructured
    //   full (correctly structured)
    //   street
    //   locacity
    //   zip
    //   country
    //   district
    //   locacity
    //   quarter
    //   streetnumber
    //   conscriptionnumber
    //   doornumber
    //   floor
    //   pobox
    //   ref['cz.ruian']
    //  ------------------
    // acc[]
    //   bank_code
    //   bank_bic
    //   bank_name
    //   bank_addr
    //   account_number
    //   account_iban
    //   account_currency
    //   account_holder
    //   ref['source-name']
    //  --------------------
    // domicile // for taxation puroposses
    // nat[] // nationality
    //   country
    //   regby
    //   regdt
    //   regid
    //   vatid
    //   natid
    //   docid
    //     kind={personal-id|passport|...}
    //     docid
    //     issby
    //     issdt
    //     expdt
    // ---------------
    // email
    //   label
    //   kind
    //   valid
    //   value
    // tel
    // uri
    // social
    // impp / Defines an instant messenger handle. This was added to the official vCard specification in version 4.0.
    // ---------------
    // language
    // anniversary { labeel, date }
    // expertise / A professional subject area that the person has knowledge of. (field|level[value=expert,assessed_by=who-says-contact-has-expert-level])
    // hobby / A recreational activity that the person actively engages in.
    // interest / A recreational activity that the person is interested in, but does not necessarily take part in.
    // education
    // licenses / Certifications and licences
    // occupation
    // ---------------
    // rel: // relationships
    //   kind: assistant, brother, child, domestic-partner, father, friend, manager, mother, business-partner, relative, sister, spouse, acquantance (personal)
    //         employee, employer, board member, officer, volunteer (professional)
    //         referred-by (other)
    //   label: i.e. CEO
    //         
    //               
    // street address




    // source
    // tz





    /**
     * @param Request  $request
     * @param Response $response
     * @param array    $args
     *
     * @return Response
     */

     public function contacts_get_api(Request $request, Response $response, array $args = []): Response {
      $q = $request->getQueryParams();
      $builder = new JsonResponseBuilder('contacts', 1);
      $uid = $args['uid'] ?? null;
      $filter = $q['filter'] ?? null;
      $data = null;

        if ($uid) {
            $result = json_encode($this->contacts_get_sql($args));    
            $data = json_decode($result);
        } else {
            $json = "t_contacts_objects.c_json";
            if ($filter) {
                $this->db->where('c_fn', "%$filter%", 'LIKE');
            }
            $result = $this->db->get('t_contacts_objects', null, [ $json ]) ?? null;
            if ($result) {
              $key = array_keys($result[0])[0];
              foreach ($result as $obj) $data[] = json_decode($obj[$key]);
            }
        }     
      $payload = $builder->withData((array)$data)->withCode(200)->build();
      return $response->withJson($payload);
    }


    public function contacts_post_api(Request $request, Response $response, array $args = []): Response {
        // Init stuff
        $builder = new JsonResponseBuilder('contacts', 1);
        $req = (array)$request->getParsedBody();
        $do = [ 'fn' => false, 'fl' => false ];
        $defs['user'] = $GLOBALS['_GLUED']['authn']['user_id'];
        $defs['id'] = 0;
        $defs['_v'] = (int) 1;
        $defs['_s'] = 'contacts';
        $type = 0;
        $row = array (
            'c_domain_id' => 1, // TODO change to domain (int)$req['domain'], 
            'c_user_id' => (int)$GLOBALS['_GLUED']['authn']['user_id'],
            'c_attr' => '{}'
        );

        // Get form data (natural person)
        if (($req['contacts_items_create_n_given'] ?? false) or ($req['contacts_items_create_n_family'] ?? false) or ($req['contacts_items_create_n_email'] ?? false) or ($req['contacts_items_create_n_phone'] ?? false)) {
            $fn['n']['prefix'] = $req['contacts_items_create_n_prefix'];
            $fn['n']['given'] = $req['contacts_items_create_n_given'];
            $fn['n']['family'] = $req['contacts_items_create_n_family'];
            $fn['n']['suffix'] = $req['contacts_items_create_n_suffix'];
            $fn['fn'] = $this->utils->concat(' ', [ $fn['n']['prefix'],$fn['n']['given'],$fn['n']['family'],$fn['n']['suffix'] ]);
            $fn['email'][0]['value'] = $req['contacts_items_create_n_email'];
            $fn['phone'][0]['value'] = $req['contacts_items_create_n_phone'];
            $fn['addr'][0]['unstructured'] = $req['contacts_items_create_n_addr'];
            $fn['dob'] = $req['contacts_items_create_n_dob'];
            $fn['note'] = $req['contacts_items_create_n_note'];
            $fn['role'][0]['name'] = $req['contacts_items_create_n_role'] ?? '';
            $fn['role'][0]['dt_from'] = '';
            $fn['role'][0]['dt_till'] = '';
            $do['fn'] = true;
        }

        // Get form data (legal person / company)
        if ($req['contacts_items_create_l_name'] ?? false) {
            $fl['fn'] = $req['contacts_items_create_l_name'];
            $fl['addr'][0]['unstructured'] = $req['contacts_items_create_l_addr'];
            $fl['nat'][0]['country'] = $req['contacts_items_create_l_nat'];
            $fl['nat'][0]['regid'] = $req['contacts_items_create_l_regid'];
            $fl['nat'][0]['vatid'] = $req['contacts_items_create_l_vatid'];
            $fl['nat'][0]['regby'] = $req['contacts_items_create_l_regby'];
            $fl['uri'][0]['value'] = $req['contacts_items_create_l_web'];
            $fl['uri'][0]['label'] = ($req['contacts_items_create_l_web']) ? 'website' : null;
            $fl['note'] = $req['contacts_items_create_l_note'];
            $do['fl'] = true;
        }
        // Get additional data about the legal person
        if ($do['fl']) {

            // Czech registers
            if ($fl['nat'][0]['country'] == 'CZ') {
                // If submitted company data doesn't have a regid, guess it
                if ($fl['nat'][0]['regid']=='') $fl['nat'][0]['regid'] = substr($fl['nat'][0]['vatid'], 2);
                // Get data from registers according to regid ()
                $cz = new CZ($this->c);
                $full = $cz->ids($fl['nat'][0]['regid']);
                // get rid of unicode &nbsp; for comparison and excessive whitespace, translate unicode to chars
                $p1 = '/\x{00A0}|\x{000D}|\x{000C}|\x{0085}/u';
                $p2 = "/\s+/u";
                $test_fl = utf8_decode(implode(json_decode('["' . preg_replace($p2, ' ', preg_replace($p1, ' ', $fl['fn'])) . '"]')));
                $test_full = utf8_decode(implode(json_decode('["' . preg_replace($p2, ' ', preg_replace($p1, ' ', $full['fn'])) . '"]')));
                if ($test_full == $test_fl) {
                    // If {submitted company name} == {company name in registers}
                    // Override submitted form data with data from registers
                    $l = $full;
                    $l['uri'] = $fl['uri'];
                    $l['note'] = $fl['note'];
                    $n = $full['people'];
                    if ($do['fn']) $n[] = $fn;
                    if (is_array($n)) $do['fn'] = true;
                    unset($l['people']);
                } else {
                    $l = $fl;
                    if ($do['fn']) $n[0] = $fn;
                }
                $l['kind']['l'] = 1;
            }

        // No company data, only natural person
        } else {
            if ($do['fn']) $n[0] = $fn;          
        }


      try { 
          if (true) { // TODO replace true with validation check against schema ($result->isValid())
              if ($do['fl']) {

                $ins = $l ?? $fl;
                $row['c_json'] = json_encode($ins);

                // check if legal person already in database
                if (isset($ins['nat'][0]['vatid'])) $this->db->orwhere('c_vatid', $ins['nat'][0]['vatid'] ?? null);
                if (isset($ins['nat'][0]['regid'])) $this->db->orwhere('c_regid', $ins['nat'][0]['regid'] ?? null);
                if (isset($ins['nat'][0]['natid'])) $this->db->orwhere('c_natid', $ins['nat'][0]['natid'] ?? null);
                $present = $this->db->get('t_contacts_objects', null, 'c_uid');

                // if present, only add natural person from form, 
                // drop data about people sourced from the registers
                if ($present) { $n = null; $n[0] = $fn ?? null; $l_req['id'] = $present[0]['c_uid']; }

                // if absent, insert
                else $l_req['id'] = $this->utils->sql_insert_with_json('t_contacts_objects', $row); 
              }

              // if there's anything to insert
              if ($do['fn'] and isset($n[0])) {
                foreach ($n as $person) {
                  $ins = $person;
                  $ins['kind']['n'] = 1;
                  unset($ins['role']);
                  $row['c_json'] = json_encode($ins);
                  $n_req['id'] = $this->utils->sql_insert_with_json('t_contacts_objects', $row); 
                  if ($do['fn'] and $do['fl']) {
                      foreach ($person['role'] as $rel) {
                          $rrow1 = [
                              "c_uid1" => $l_req['id'],
                              "c_uid2" => $n_req['id'],
                              "c_type" => $type,
                              "c_label" => $rel['name'] ?? null,
                              "c_dt_from" => $rel['dt_from'] ?? null,
                              "c_dt_till" => $rel['dt_till'] ?? null
                          ];
                          $rrow2 = [
                              "c_uid1" => $n_req['id'],
                              "c_uid2" => $l_req['id'],
                              "c_type" => -$type,
                              "c_label" => $rel['name'] ?? null,
                              "c_dt_from" => $rel['dt_from'] ?? null,
                              "c_dt_till" => $rel['dt_till'] ?? null
                          ];
                          $this->db->insert('t_contacts_rels',$rrow1);
                          $this->db->insert('t_contacts_rels',$rrow2); 
                      }
                  }
                }
              }
            $payload = $builder->withData((array)$req)->withCode(200)->build();
            return $response->withJson($payload, 200);
            } else {
                $reseed = $request->getParsedBody();
                $payload = $builder->withValidationReseed($reseed)
                                   ->withValidationError($result->getErrors())
                                   ->withCode(400)
                                   ->build();
                return $response->withJson($payload, 400);
            }
      } catch (Exception $e) { 
          throw new HttpInternalServerErrorException($request, $e->getMessage());  
      }        
    }

    private function contacts_get_sql(array $args = []): array {
      $uid = (int)$args['uid'] ?? null;
      $data = [];

      // Merge the t_contacts_objects.c_json with a json object computed
      // out of files marked in t_stor_links as belonging to each of
      // t_contacts_objects rows.
      $merge_files = "t_contacts_objects.c_json";
      
      $merge_files =  "JSON_MERGE( 
                  t_contacts_objects.c_json, 
                  JSON_OBJECT( 
                    'files', 
                    JSON_ARRAYAGG(  
                      JSON_OBJECT( 'name', t_stor_links.c_filename, 'uri', CONCAT('/stor/get/', t_stor_links.c_uid ) )
                    )
                  )
                ) as jsondoc";
      
      // If $args['uid'] is set, select only this one row. Furhter on,
      // the single row branch of the code is prepended by if ($uid).
      $cond = ($uid > 0) ? "AND t_contacts_objects.c_uid = ?" : "";

      // TODO add `WHERE t_contacts_objects.c_uid IN (<domains-accessed-by-user>)
      $query = "
        SELECT $merge_files FROM t_contacts_objects LEFT JOIN t_stor_links
        ON (t_contacts_objects.c_uid = t_stor_links.c_inherit_object)
        WHERE (t_stor_links.c_inherit_object IS NOT NULL $cond)
        GROUP BY t_contacts_objects.c_uid
        UNION
        SELECT t_contacts_objects.c_json FROM t_contacts_objects LEFT JOIN t_stor_links
        ON (t_contacts_objects.c_uid = t_stor_links.c_inherit_object)
        WHERE (t_stor_links.c_inherit_object IS NULL $cond)";
        
      if ($uid) {
        $select = " JSON_ARRAYAGG(  
                      JSON_OBJECT( 'uid', c_uid2, 'type', c_type, 'label', c_label, 'dt_from', c_dt_from, 'dt_till', c_dt_till, 'fn', c_json->>'$.fn', 'email', c_json->>'$.email[0].value', 'phone', c_json->>'$.phone[0].value' )
                    ) as jsondoc";
        $query_rels = "
          SELECT $select FROM `t_contacts_rels` LEFT JOIN `t_contacts_objects`
          ON (t_contacts_rels.c_uid2 = t_contacts_objects.c_uid)
          WHERE t_contacts_rels.c_uid1 = ?";
        // TODO add group by (same person can have multiplre relationships with)
        $result = $this->db->rawQuery($query, [(int)$uid, (int)$uid]);
        $result_rels = $this->db->rawQuery($query_rels, [(int)$uid]);
        $jsondoc = json_decode($result[0]['jsondoc'] ?? "{}", true);
        $jsondoc_rels = json_decode($result_rels[0]['jsondoc'] ?? "{}", true);
        $jsondoc['rels'] = $jsondoc_rels;
        $result[0]['jsondoc'] = json_encode($jsondoc);
    } else $result = $this->db->rawQuery($query);

      // Rename $key to integers
      if ($result) {
        $key = array_keys($result[0])[0];
        foreach ($result as $obj) $data[] = json_decode($obj[$key]);
      }
      // Unnest if returning only a single line
      if ($uid) $data = (array)$data[0];
      return $data;
    }

    public function contacts_get_app(Request $request, Response $response, array $args = []): Response
    {
        $uid = $args['uid'] ?? null;
        $data = [];
        $domains = $this->db->get('t_core_domains');
        $routes = $this->utils->get_navigation( $this->utils->get_current_route($request) );


        if ($uid) {
            $this->db->where('c_uid', $uid);
            $data = $this->db->get('t_contacts_objects');
            return $this->render($response, 'Contacts/Views/object.twig', [
                'domains' => $domains,
                'data' => json_decode(json_encode($this->contacts_get_sql($args)),true),
            ]);          
        }
        return $this->render($response, 'Contacts/Views/collection.twig', [
            'domains' => $domains,
            'routes' => $routes,
        ]);
    }



//SELECT JSON_ARRAYAGG(JSON_OBJECT('uid', c_uid2, 'label', c_label, 'dt_from', c_dt_from)) from t_contacts_rels where c_uid1 = 29;
//SELECT c_uid2, JSON_arrayAGG(JSON_OBJECT('uid', c_uid2, 'label', c_label, 'dt_from', c_dt_from)) from t_contacts_rels where c_uid1 = 29 GROUP BY c_uid2;
//SELECT JSON_OBJECT( c_uid2, JSON_ARRAYAGG(JSON_OBJECT('uid', c_uid2, 'label', c_label, 'dt_from', c_dt_from))) from t_contacts_rels where c_uid1 = 29 GROUP BY c_uid2;

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function cz_names(Request $request, Response $response, array $args = []): Response {
        $cz = new CZ($this->c);
        $builder = new JsonResponseBuilder('contacts.search', 1);
        $search_string = $args['name'];
        $result = [];
        
      if (strlen($search_string) < 3) {
         $payload = $builder->withMessage('Please use at least 3 characters for your search.')->withCode(200)->build();
         return $response->withJson($payload);
      }

      // Query $services (or get a cached response if available) and store
      // it in $new. Normalize $new by using `regid` as a key ($indexed).
      // Merge data with array_replace_recursive().
      $services = [ 'names_rzp', 'names_ares', 'names_justice' ];
      foreach ($services as $svc) {
          $cnf = $cz->urikey($svc, $search_string); $raw_result = null;
          if ($this->fscache->has($cnf['key'])) {
              $new = $this->fscache->get($cnf['key']);
              if (is_array($new)) foreach ($new as $item) $indexed[ $item['nat'][0]['regid'] ] = $item; 
              $result = array_replace_recursive($result, $indexed ?? []);
          } else {
              $new = $cz->$svc($search_string, $raw_result);
              if (is_array($new)) foreach ($new as $item)  $indexed[ $item['nat'][0]['regid'] ] = $item; 
              $result = array_replace_recursive($result, $indexed ?? []);
              if (!is_null($new) and ($new != false)) $this->fscache->set($cnf['key'], $indexed, 3600); // 60 minutes
          }
      }

      // Drop the regid as a key
      $final = [];
      foreach ($result as $item) $final[] = $item;
     
      $payload = $builder->withData((array)$final)->withCode(200)->build();
      return $response->withJson($payload);
    }
    
    // TODO: search by reg-id (ičo) fails because cz_ids gives now data in quite a different format to that of cz_names

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


    public function cz_ids(Request $request, Response $response, array $args = []): Response {

      $builder = new JsonResponseBuilder('contacts.search', 1);
      $cz = new CZ($this->c);
      $id = $args['id'];

      if (strlen($id) != 8) {
         $payload = $builder->withMessage('Czech company IDs are 8 numbers in total.')->withCode(200)->build();
         return $response->withJson($payload);
      }

      $result[0] = $cz->ids($id);
      $payload = $builder->withData((array)$result)->withCode(200)->build();
      return $response->withJson($payload);
      
    }


    public function eu_ids(Request $request, Response $response, array $args = []): Response {
      // TODO this function is pretty slow. look where we loose speed.
      $builder = new JsonResponseBuilder('contacts.search', 1);
      $eu = new EU($this->c);
      $id = mb_strtoupper($args['id']);

      if ((strlen($id) < 6) or ($eu->validate_vat($id) === false)) {
         $payload = $builder->withMessage('Not a valid EU VAT-ID.')->withCode(200)->build();
         return $response->withJson($payload);
      }
      $result = [];
      
      $cnf = $eu->urikey('vies', $id); $raw_result = null;
      if ($this->fscache->has($cnf['key'].'d')) {
          $new = $this->fscache->get($cnf['key']);
          $result = array_replace_recursive($result, $new ?? []);
      } else {
          $new = $eu->vies($id, $raw_result) ?? [];
          $result = array_replace_recursive($result, $new ?? []);
          if (!is_null($new)) $this->fscache->set($cnf['key'], $result, 3600); // 60 minutes
      }
      if (is_null($new)) { $query['vies']['status'] = 'Error'; } else { $query['vies']['status'] = 'OK'; }

      $nested[0] = $result;
      $payload = $builder->withData((array)$nested)->withCode(200)->build();
      return $response->withJson($payload);
    }






/*
    public function collection_ui(Request $request, Response $response, array $args = []): Response
    {
      $uribase = strtolower(parse_url((string)$request->getUri(), PHP_URL_SCHEME)).'://'.strtolower(parse_url((string)$request->getUri(), PHP_URL_HOST));

      $jsf_schema   = file_get_contents(__ROOT__.'/glued/Contacts/Controllers/Schemas/contacts.v1.schema');
      $jsf_uischema = file_get_contents(__ROOT__.'/glued/Contacts/Controllers/Schemas/contacts.v1.formui');
      $jsf_formdata = '{"data":{"ts_created":"'.time().'","ts_updated":"'.time().'"}}';
      $jsf_onsubmit = '
        $.ajax({
          url: "'.$uribase.$this->routerParser->urlFor('contacts.items.api01').'",
          dataType: "text",
          type: "POST",
          data: "stockdata=" + JSON.stringify(formData.formData),
          success: function(data) {
            // diky replacu nezustava puvodni adresa v historii, takze se to vice blizi redirectu
            // presmerovani na editacni stranku se vraci z toho ajaxu
            window.location.replace(data);
            /*
            ReactDOM.render((<div><h1>Thank you</h1><pre>{JSON.stringify(formData.formData, null, 2) }</pre></div>), 
                     document.getElementById("main"));
            */
           /*
          },
          error: function(xhr, status, err) {
            ReactDOM.render((<div><h1>Something goes wrong ! not saving.</h1><pre>{JSON.stringify(formData.formData, null, 2) }</pre></div>), 
                     document.getElementById("main"));
          }
        });
      ';

        // TODO add constrains on what domains a user can actually list
        //$domains = $this->db->get('t_core_domains');
        
        // TODO add default domain for each user - maybe base this on some stats?
        return $this->render($response, 'Contacts/Views/collection.twig', [
            //'domains' => $domains
            'json_schema_output' => $jsf_schema,
            'json_uischema_output' => $jsf_uischema,
            'json_formdata_output' => $jsf_formdata,
            'json_onsubmit_output' => $jsf_onsubmit


        ]);

    }
*/

    // show form for add new contact
    public function addContactForm($request, $response)
    {
        $form_output = '';
        $jsf_schema   = file_get_contents(__ROOT__.'/glued/Contacts/Controllers/Schemas/contacts.v1.schema');
        $jsf_uischema = file_get_contents(__ROOT__.'/glued/Contacts/Controllers/Schemas/contacts.v1.formui');
        $jsf_formdata = '{"data":{}}';
        $jsf_onsubmit = '
            $.ajax({
                url: "https://'.$this->settings['glued']['hostname'].$this->routerParser->urlFor('contacts.api.new').'",
                dataType: "text",
                type: "POST",
                data: "billdata=" + JSON.stringify(formData.formData),
                success: function(data) {
                    ReactDOM.render((<div><h1>Thank you</h1><pre>{JSON.stringify(formData.formData, null, 2) }</pre><h2>Final data</h2><pre>{data}</pre></div>), 
                         document.getElementById("main"));
                },
                error: function(xhr, status, err) {
                    alert(status + err + data);
                    ReactDOM.render((<div><h1>Something goes wrong ! not saving.</h1><pre>{JSON.stringify(formData.formData, null, 2) }</pre></div>), 
                         document.getElementById("main"));
                }
            });
        ';

        return $this->render($response, 'Core/Views/glued.twig', [
            'json_schema_output' => $jsf_schema,
            'json_uischema_output' => $jsf_uischema,
            'json_formdata_output' => $jsf_formdata,
            'json_onsubmit_output' => $jsf_onsubmit,
            'json_custom_widgets' => 1,
        ]);

        return $this->view->render($response, 'contacts/addcontact.twig', array(
        ));
    }

    

}

