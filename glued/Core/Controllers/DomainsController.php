<?php


declare(strict_types=1);

namespace Glued\Core\Controllers;
use Glued\Core\Classes\Json\JsonResponseBuilder;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as v;

class DomainsController extends AbstractTwigController

{

    public function list($request, $response)
    {
        $this->auth->signout();
        return $response->withRedirect($this->routerParser->urlFor('core.web'));
    }


    public function create($request, $response)
    {
        $validation = $this->validator->validate($request, [
            'name' => v::notEmpty()->alnum(),
        ]);

        // TODO consider making domain names unique (duplicate domains will fuck up teams etc.) - probably just add an additional unique nonce
        // TODO do propper validation here
        if (!isset($GLOBALS['_GLUED']['authn']['user_id'])) { die('login first'); }

        if ($validation->failed()) {
            $reseed = $this->validator->reseed($request, [ 'name' ]);
            $payload = $builder->withValidationError($validation->messages())
                               ->withValidationReseed($reseed)
                               ->build();
            return $response->withJson($payload, 400);
        } else {
            $json = $request->getParsedBody();
            $json['_v'] = 1;
            $json['_s'] = 'core/domains';
            $json = json_encode($json);
            $row = [
                'c_name' => $request->getParam('name'),
                'c_user_id' => $GLOBALS['_GLUED']['authn']['user_id'],
                'c_json' => $json
            ];
            print_r($row);
            $id = $this->db->insert('t_core_domains', $row);
            if ($id)
                $msg = 'Domain '.$row['c_name'].' was created (id '.$id.')';
            else
                $msg = 'Domain creation failed: ' . $this->db->getLastError();
            return $response->withRedirect($this->routerParser->urlFor('core.domains'));
        }
    }


    public function ui_manage($request, $response)
    {
        $domains = $this->db->get("t_core_domains");
        return $this->view->render($response, 'Core/Views/domains.twig', [ 'domains' => $domains ]);
    }
    


}