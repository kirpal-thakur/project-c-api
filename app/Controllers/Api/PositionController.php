<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use App\Models\PositionModel;


class PositionController extends ResourceController
{
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);
        
        $this->db           = \Config\Database::connect();
        $this->positionModel      = new PositionModel();
    }

    public function getPositions(){

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale($currentLang);

        // $userId = auth()->id();
        // $logoPath = base_url() . 'uploads/logos/';
        // $profilePath = base_url() . 'uploads/';

        $positions = $this->positionModel->findAll();
        
        if($positions){
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => [ 'positions' => $positions ]
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
