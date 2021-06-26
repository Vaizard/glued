<?php

declare(strict_types=1);

namespace Glued\Core\Controllers;

use Glued\Core\Classes\Json\JsonResponseBuilder;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ProfilesApi extends AbstractJsonController
{
    /**
     * @param Request  $request
     * @param Response $response
     * @param array    $args
     *
     * @return Response
     */

    public $API_NAME = 'profiles';
    public $API_VERSION  = 1;

    public function list(Request $request, Response $response, array $args = []): Response
    {
        // DO RBAC HERE


        $collection = $this->db->get('t_core_profiles');

        if (($this->db->getLastErrno() === 0) and ($this->db->count > 0)) {
            foreach ($collection as $object) { 
                $item = json_decode($object['c_json'], true);
                $item['id'] = $object['c_uid'];
                $data[] = $item;
            }
        } else { $data = []; }
    
        $builder = new JsonResponseBuilder($this->API_NAME, $this->API_VERSION);
        if ($this->db->getLastErrno() === 0) { 
            $arr = $builder->withData($data)->build();
        } else {  
            $arr = $builder->withCode(500)->build(); 
        }

        return $response->withJson($arr);
    }

    public function create(Request $request, Response $response, array $args = []): Response
    { 
        return $response->withJson(['works create' => true]);
    }

}


