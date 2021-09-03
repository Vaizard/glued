<?php

declare(strict_types=1);
namespace Glued\Contacts\Classes;
use Symfony\Component\DomCrawler\Crawler;
use Glued\Fin\Classes\Utils as FinUtils;
use DragonBe\Vies\Vies;
use DragonBe\Vies\ViesException;
use DragonBe\Vies\ViesServiceException;

class EU
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
            'vies' => [
                'uri' => null,
                'key' => 'contacts.eu_vies'.md5($id),
            ],
        ];
        return ($pairs[$what] ?: null);
    }


    public function validate_vat($id) : bool {
        $vieshandle = new Vies();
        $split = $vieshandle->splitVatId($id);
        return $vieshandle->validateVatSum($split['country'], $split['id']);
    }


    public function vies(string $id, &$result_raw = null) :? array {
        $result = null;
        $vieshandle = new Vies();
        if (false === $vieshandle->getHeartBeat()->isAlive()) {
            $result_raw = 'VIES service is temporarily unaccessible.';
        } else {
            $split = $vieshandle->splitVatId($id);
            $vatResult = $vieshandle->validateVat($split['country'], $split['id']);
            if ($vatResult->isValid()) {
                $vies['nat'][0]['country'] = $split['country'];
                $vies['nat'][0]['vatid'] = $id;
                $vies['fn'] = (string)$vatResult->getName();
                $vies['addr'][0]['unstructured'] = preg_replace ( '#\n#' , ', ' , (string)trim($vatResult->getAddress()) );
                $result = $vies;
            }
        }
        return $result;
    }


}