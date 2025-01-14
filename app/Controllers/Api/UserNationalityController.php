<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use App\Models\UserNationalityModel;


class UserNationalityController extends ResourceController
{
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);
        $this->db = \Config\Database::connect();
        $this->userNationalityModel = new UserNationalityModel();

    }

    public function deleteUserNationality( $countryID = null, $userId = null){

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale($currentLang);
 
        $user_id = $userId ?? auth()->id();
        
        $isExist =  $this->userNationalityModel->where('user_id', $user_id)->where('country_id', $countryID )->first();

        if($isExist){

            $this->userNationalityModel->delete($isExist['id']);

            $response = [
                "status"    => true,
                "message"   => 'Nationlity Deleted',
                "data"      => []
            ];
        } else {
            $response = [
                "status"    => false,
                "message"   => 'Nationlity not Deleted',
                "data"      => []
            ];
        }
        
        return $this->respondCreated($response);

    }
}
