<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use App\Models\LanguageModel;

class LanguageController extends ResourceController
{
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);
        
        $this->db               = \Config\Database::connect();
        $this->languageModel        = new LanguageModel();
    }

    public function getLanguages(){

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale($currentLang);

        $languages = $this->languageModel->findAll();

        if($languages){

            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => ['languages' => $languages ]
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


    public function getLanguage($id = null){
        $language = getLanguageByID($id);

        if($language){

            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => ['language' => $language ]
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
