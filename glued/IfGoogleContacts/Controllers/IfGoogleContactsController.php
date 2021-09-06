<?php

declare(strict_types=1);

namespace Glued\IfGoogleContacts\Controllers;

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

class IfGoogleContactsController extends AbstractTwigController
{

    /**
     * Returns an authorized API client.
     * @return Google_Client the authorized client object
     */
    private function getClient() {

        $client = new \Google_Client();
        $client->setApplicationName('Google Sheets and PHP');
        $client->setScopes([\Google_Service_PeopleService::CONTACTS_READONLY]);
        $client->setAccessType('offline');
        $client->setAuthConfig(__ROOT__ . '/private/apikeys/IfGoogleContacts/credentials.json');
        $client->setPrompt('select_account consent');
        
        // Load previously authorized token from a file, if it exists.
        // The file token.json stores the user's access and refresh tokens, and is
        // created automatically when the authorization flow completes for the first
        // time.
        $tokenPath = __ROOT__ . '/private/apikeys/IfGoogleContacts/token.json';
        if (file_exists($tokenPath)) {
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $client->setAccessToken($accessToken);
        }

        // If there is no previous token or it's expired.
        if ($client->isAccessTokenExpired()) {
            // Refresh the token if possible, else fetch a new one.
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            } else {
                // Request authorization from the user.
                $authUrl = $client->createAuthUrl();
                printf("Open the following link in your browser:\n%s\n", $authUrl);
                print 'Enter verification code: ';
                $authCode = trim(fgets(STDIN));

                // Exchange authorization code for an access token.
                $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
                $client->setAccessToken($accessToken);

                // Check to see if there was an error.
                if (array_key_exists('error', $accessToken)) {
                    throw new Exception(join(', ', $accessToken));
                }
            }
            // Save the token to a file.
            if (!file_exists(dirname($tokenPath))) {
                mkdir(dirname($tokenPath), 0700, true);
            }
            file_put_contents($tokenPath, json_encode($client->getAccessToken()));
        }
        return $client;
    }


    /**
     * @param Request  $request
     * @param Response $response
     * @param array    $args
     *
     * @return Response
     */

     public function api01_get(Request $request, Response $response, array $args = []): Response {

    // Get the API client and construct the service object.
    $client = $this->getClient();
    $service = new Google_Service_PeopleService($client);

    // Print the names for up to 10 connections.
    $optParams = array(
      'pageSize' => 10,
      'personFields' => 'names,emailAddresses',
    );
    $results = $service->people_connections->listPeopleConnections('people/me', $optParams);

    if (count($results->getConnections()) == 0) {
      print "No connections found.\n";
    } else {
      print "People:\n";
      foreach ($results->getConnections() as $person) {
        if (count($person->getNames()) == 0) {
          print "No names found for this connection\n";
        } else {
          $names = $person->getNames();
          $name = $names[0];
          printf("%s\n", $name->getDisplayName());
        }
      }
    }

    return $response;

/*
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
*/
    }



    

}

