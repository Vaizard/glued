<?php

declare(strict_types=1);

namespace Glued\Core\Controllers;

//use Glued\Core\Classes\Auth\Auth;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpBadRequestException as BadRequest;
use Slim\Exception\HttpNotFoundException as NotFound;
use Throwable;

class Integrations extends AbstractTwigController
{
  
    public function google(Request $request, Response $response, array $args = []): Response {
        // https://www.srijan.net/blog/integrating-google-sheets-with-php-is-this-easy-know-how
        $client = new \Google_Client();
        $client->setApplicationName('Google Sheets and PHP');
        $client->setScopes([\Google_Service_Sheets::SPREADSHEETS]);
        $client->setAccessType('offline');
        $client->setAuthConfig(__ROOT__ . '/private/api/glued-dev-91338368ae7d.json');
        $service = new \Google_Service_Sheets($client);

        // START WITH URI
        
        $uri = 'https://docs.google.com/spreadsheets/d/14y4sJZ1cCUlIvTmq021hGwSl4em6Iv-6Cr-DHOrY5fs/edit#gid=607165653';
        echo 'uri '.$uri."<br>";

        // GET DOCUMENT ID
        $regex = '/^.*\/d\/(.*)\/.*$/';
        $regex = '{/spreadsheets/d/([a-zA-Z0-9-_]+)}';
        $result = preg_match($regex, $uri, $matches);
        $spreadsheetId = $matches[1];
        echo 'id '.$spreadsheetId."<br>";

        // GET SHEET ID (GID)
        $regex = '{[#&]gid=([0-9]+)}';
        $result = preg_match($regex, $uri, $matches);
        $spreadsheetGid = $matches[1];
        echo 'gid '.$spreadsheetGid."<br>";

        // GET ALL SHEETS
        function getsheets($service,$spreadsheetID) {
            $spreadSheet = $service->spreadsheets->get($spreadsheetID);
            $sheets = $spreadSheet->getSheets();

            foreach($sheets as $sheet) {
                $sheets_short[] = [
                    'SheetId' => $sheet->properties->sheetId,
                    'title' => $sheet->properties->title
                ];
            }   
            return $sheets_short;
        }

        // Postup: formular se zepta na link
        // overime, zda mame pristup, pokud ne tak rekneme uzivateli aby sdiell doc s mailem appky
        // overime znovu, pokud mame pristu
        // zjistime, jestli tam je sheet v linku
        // pokud ne, dame na vyber sheety
        // uzivatel si vybere sheet
        // zobrazime uzvateli zahlavi - vyplneny prvni radek a zatrha, ktere sloupecky vzdy musi byt
        // zobrazime uzvateli zahlavi - vyplneny prvni radek a vybere sloupecek, podle ktereho se pocita hash
        // 
        // t_int_objects - json:
        // 
        // "id": "",
        // "_n": "int.objects",
        // "_v": 1,
        // "provider": "google",
        // "service": "spreadsheets",
        // "uri": "https://docs.google.com/spreadsheets/d/14y4sJZ1cCUlIvTmq021hGwSl4em6Iv-6Cr-DHOrY5fs/edit#gid=607165653",
        // "name": "nazev souboru",
        // "attributes": {
        //   "spreadsheetId": "14y4sJZ1cCUlIvTmq021hGwSl4em6Iv-6Cr-DHOrY5fs", // povinne
        //   "sheetId": "607165653", // nepovinne, jen pokud je v url
        //   "actions": [
        //      "sheets.checkmeta": {       // php funkce, ktera kontroluje, zda existuji predepsane zahlavi sloupcu (v radku definovanem pomoci "meta")
        //         "meta": "Orig!A1:G1",
        //         "reqs": [ "DÚZP", "VS", "VS2" ]
        //      }
        //      "sheets.rowcache": {       // php funkce, ktera cachene data do nasi tabulky - nejdriv udela ze vseho ve sloupecku A md5 a testne, ze jsou hashe fakt unikatni
        //         "meta": "Orig!A1:G1",
        //         "data": "Orig!A2:G5",
        //         "fuid": "A",
        //       },
        //       "sheets.costimport": {    // php funkce, ktera importne zatim nenaimportovane radky do jsonu v t_fin_costs tabulce
        //         "DÚZP": "dt-supply",
        //         "Vystaveno": "dt-issued",
        //       }
        //   ]
        // }
        // 
        
        // 
        // 1. identifikace unikatniho radku ... ktere sloupecky se k tomu pouziji si ulozime do tabulky
        //    k tomu, ze kdyz po case znovunactem ten samy dokument, budeme vedet, ktere radky jsou nove a ktere ne
        // 2. ulozime radky do jine tabulky - tam bude
        // do db se ulozi $spreadsheetId a $spreadsheetGid a nazev dokumentu (nevim ted jak se jmenuje promenna)
        // 

        echo 'sheets <br>';
        $sheets = getsheets($service,$spreadsheetId);
        print("<pre>".print_r($sheets,true)."</pre>");
        

        $get_range = "Orig!A1:G5";
        $gresponse = $service->spreadsheets_values->get($spreadsheetId, $get_range);
        $values = $gresponse->getValues();
        print_r($values);

/*
1 - getSheets() - gets all the sheets in the current spreadsheet. From this, you can find the sheet name AND the ID of each sheet.

2 - The ID is obtained using getSheetId() - returns the ID of a sheet which you can compare to your list.

3 - The sheet name is obtained using getSheetName() - returns the sheet name which you can use in the method getSheetByName.

4 - getSheetByName(name) -
*/
        // https://drive.google.com/file/d/1pPezoLPc2s8BIuIXl3l24PDhrViLjRdU/view?usp=sharing
        $client->addScope(\Google_Service_Drive::DRIVE);
        $service = new \Google_Service_Drive($client);
        $fileId = "1pPezoLPc2s8BIuIXl3l24PDhrViLjRdU"; // Google File ID

        // Retrieve filename.
        $file = $service->files->get($fileId);
        $fileName = $file->getName();

        // Download a file.
        $content = $service->files->get($fileId, array("alt" => "media"));
        $handle = fopen(__ROOT__ . '/private/cache/'.$fileName, "w+"); // Modified
        while (!$content->getBody()->eof()) { // Modified
            fwrite($handle, $content->getBody()->read(1024)); // Modified
        }
        fclose($handle);
        echo "success";

        // List files
        echo "<br>folder view (add id to code):";
        $folderId = "1cw3a9jhPmJUbmU7-cp7j0y1h54eK6VY5";
        if ($folderId != "") {
            $parameters = array(
            'pageSize' => 100,
                  'q' => "'".$folderId."' in parents"
                );
            $results = $service->files->listFiles($parameters);
            echo '<table>';
            foreach($results as $file){
                print("<pre>".print_r($file,true)."</pre>");
                echo '<tr><td>ico <img src="'. $file->iconLink .'"/> file ' . $file->name . ' ' . $file->id . ' ' . $file->mimeType . ' ' . $file->md5Checksum . ' ' . $file->size. ' ' . $file->parents . '</td></tr>';
            }
            echo '</table>';
        }
die();


        return $this->render($response, 'Core/Views/integrations.google.twig', []);
    }

    public function fio_cz(Request $request, Response $response, array $args = []): Response {
        return $this->render($response, 'Core/Views/integrations.fio_cz.twig', []);
    }

    public function google_app(Request $request, Response $response, array $args = []): Response {
        return $this->render($response, 'Core/Views/integrations.google.twig', []);
    }


}


