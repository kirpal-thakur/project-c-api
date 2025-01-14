<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use App\Models\RolePaymentTypeModel;

class RolePaymentTypeController extends ResourceController
{
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);
        $this->db = \Config\Database::connect();
        $this->rolePaymentTypeModel = new RolePaymentTypeModel();        
    }


    public function getRolePaymentTypes(){
            
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale($currentLang);

        $userTypes = $this->rolePaymentTypeModel
            ->select('role_payment_types.*, ur.role_name as role_name')
            ->join('user_roles ur', 'ur.id = role_payment_types.user_role_id') 
            ->findAll();

        if($userTypes){
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => ['userTypes' => $userTypes]
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
