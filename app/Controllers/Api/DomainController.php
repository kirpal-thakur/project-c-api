<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use App\Models\DomainModel;

class DomainController extends ResourceController
{
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);
        
        $this->db               = \Config\Database::connect();
        $this->domainModel      = new DomainModel();
    }

    public function getDomains(){

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale($currentLang);

        $flagPath = base_url() . 'uploads/logos/';

        $domains = $this->domainModel
                    ->select('
                        domains.*, 
                        c.country_flag
                    ')
                    ->join('countries c', 'c.id = domains.country_id', 'INNER')
                    ->findAll(); 

        if($domains){

            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => [
                    'domains' => $domains, 
                    'flag_path' => $flagPath
                ]
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


    public function getUserDomains(){

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale($currentLang);

        $flagPath = base_url() . 'uploads/logos/';

        $domains = $this->domainModel
                    ->select('
                        domains.*, 
                        c.country_flag
                    ')
                    ->join('countries c', 'c.id = domains.country_id', 'INNER')
                    ->findAll(); 

        if($domains){

            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => [
                    'domains' => $domains, 
                    'flag_path' => $flagPath
                ]
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


    public function getCurrencies(){

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale($currentLang);

        $flagPath = base_url() . 'uploads/logos/';

        $currencyInfo = $this->domainModel
                    ->select('DISTINCT(domains.currency)')
                    ->findAll(); 
        
        if($currencyInfo){
            $currencies = array_column($currencyInfo, 'currency');

            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => [
                    'currencies' => $currencies, 
                ]
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
