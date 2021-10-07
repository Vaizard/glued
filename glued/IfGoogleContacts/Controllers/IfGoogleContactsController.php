<?php

declare(strict_types=1);

namespace Glued\IfGoogleContacts\Controllers;

/*use Carbon\Carbon;
use Defr\Ares;
use DragonBe\Vies\Vies;
use DragonBe\Vies\ViesException;
use DragonBe\Vies\ViesServiceException;
use Glued\Contacts\Classes\CZ as CZ;
use Glued\Contacts\Classes\EU;
use Glued\Core\Classes\Json\JsonResponseBuilder;
use Glued\Core\Classes\Utils\Utils;
use Phpfastcache\CacheManager;
use Phpfastcache\Config\Config;
use Phpfastcache\Helper\Psr16Adapter;*/
use Glued\Core\Controllers\AbstractTwigController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
/*use Respect\Validation\Validator as v;
use Sabre\VObject;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpInternalServerErrorException;
use Symfony\Component\DomCrawler\Crawler;*/

class IfGoogleContactsController extends AbstractTwigController
{

    /**
     * Returns an authorized API client.
     * @return Google_Client the authorized client object
     */
    private function getClient($request) {

// https://console.cloud.google.com/apis/credentials
// Create credentials -> OAuth Client ID

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
                echo 'Open the following link in your browser: <a href="'.$authUrl.'">'.$authUrl.'</a>';
                echo 'Enter verification code:';
                $authCode = $request->getQueryParam('code', null);
                if (is_null($authCode)) die('... the code!');


                //printf("Open the following link in your browser:\n%s\n", $authUrl);
                //print 'Enter verification code: ';
                //$authCode = trim(fgets(STDIN));

                // Exchange authorization code for an access token.
                $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
                $client->setAccessToken($accessToken);

                // Check to see if there was an error.
                if (array_key_exists('error', $accessToken)) {
                    throw new Exception(join(', ', $accessToken));
                };
            }
            // Save the token to a file.
            if (!file_exists(dirname($tokenPath))) {
                mkdir(dirname($tokenPath), 0700, true);
            }
            file_put_contents($tokenPath, json_encode($client->getAccessToken()));
        }
        return $client;
    }


private function isnt_null($v) {
   return (!is_null($v));
}

    /**
     * @param Request  $request
     * @param Response $response
     * @param array    $args
     *
     * @return Response
     */

     public function api01_get(Request $request, Response $response, array $args = []): Response {
        self::isnt_null(null); 
    // Get the API client and construct the service object.
    $client = $this->getClient($request);
    $service = new \Google_Service_PeopleService($client);

    // Print the names for up to 10 connections.
    $result = [];
    $pgcnt = 0;
    $pageToken = NULL;
    $optParams = [
      'pageSize' => 500,
      'personFields' => 'addresses,ageRanges,biographies,birthdays,calendarUrls,clientData,coverPhotos,emailAddresses,events,externalIds,genders,imClients,interests,locales,locations,memberships,metadata,miscKeywords,names,nicknames,occupations,organizations,phoneNumbers,photos,relations,sipAddresses,skills,urls,userDefined',
    ];
    //$results = $service->people_connections->listPeopleConnections('people/me', $optParams);


    do {
      try {
        if ($pageToken) {
          $optParams['pageToken'] = $pageToken;
        }

        $resultobj = $service->people_connections->listPeopleConnections('people/me', $optParams);
        $result = array_merge($result, (array)$resultobj->getConnections());
        /* echo $pgcnt.' ----------------<br><br>';
        foreach ($resultobj->getConnections() as $person) {
           echo $person->getNames()[0]->getDisplayName().'<br>';
        }*/
        $pageToken = $resultobj->getNextPageToken();
      } catch (\Exception $e) {
        print "An error occurred: " . $e->getMessage();
        $pageToken = NULL;
      }
      $pgcnt++;
    } while ($pageToken and ($pgcnt < 4));

    $result = json_decode(json_encode($result), true);
    $result = $this->denull((array)$result);

 
   return $response->withJson($result);
  // return $response;
  }



public function denull(array $data = []): array
{
    $data = array_map(function($value) {
        return is_array($value) ? $this->denull($value) : $value;
    }, $data);

    return array_filter($data, function($value) {
        return !empty($value);
    });
}

}

