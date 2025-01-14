<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use App\Models\RepresentatorRoleModel;

class RepresentatorRoleController extends ResourceController
{
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);
        
        $this->db               = \Config\Database::connect();
        $this->representatorRoleModel        = new RepresentatorRoleModel();
    }

    public function getRepresentatorRoles(){

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale($currentLang);

        $roles = $this->representatorRoleModel->findAll();

        if($roles){

            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => ['roles' => $roles ]
            ];
        } else {
            $response = [
                "status"    => false,
                "message"   => lang('App.noDataFound'),
                "data"      => []
            ];
        }

        return $this->respondCreated($response);
    }
}
