<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use App\Models\LeagueModel;

class LeagueController extends ResourceController
{
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);
        
        $this->db           = \Config\Database::connect();
        $this->session      = \Config\Services::session();
        $this->leagueModel  = new LeagueModel();
    }

    public function getLeagues() {

        $currentLang = $this->request->getPost("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale($currentLang);

        $logoPath = base_url() . 'uploads/logos/';

        $leaguesQuery = $this->leagueModel
                            ->select('
                                    leagues.*, 
                                    CONCAT("'.$logoPath.'", leagues.league_logo ) AS league_logo_path,
                                    c.country_name
                                ')
                            ->join('countries c', 'c.id = leagues.country_id', 'INNER');
        $leagues = $leaguesQuery->findAll();

        if($leagues){
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => ['leagues' => $leagues]
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
