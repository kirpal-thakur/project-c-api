<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use App\Models\TeamModel;

class TeamController extends ResourceController
{
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);
        
        $this->db           = \Config\Database::connect();
        $this->session      = \Config\Services::session();
        $this->teamModel    = new TeamModel();
    }

    public function getTeams($club_id = null) {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale($currentLang);

        $getQueryString = getQueryString();

        $imagePath = base_url() . 'uploads/';

        $teamsQuery = $this->teamModel
                            ->select('
                                    teams.*, 
                                    cb.club_name as team_name, 
                                    cb.club_logo as team_club_logo, 
                                    CONCAT("'.$imagePath.'", cb.club_logo ) AS team_club_logo_path,
                                    c.country_name
                                ')
                            ->join('clubs cb', 'cb.id = teams.club_id', 'INNER')
                            ->join('countries c', 'c.id = teams.country_id', 'INNER');

        if (!is_null($club_id)) {
            $teamsQuery->where('cb.taken_by', $club_id);
        }

        if(isset($getQueryString['search'])){
            $teamsQuery->like('cb.club_name', $getQueryString['search']);
        }

        $teams = $teamsQuery->findAll();
        // echo '>>>>>>>>>>> >>>>>>>>>>>> ' . $this->db->getLastQuery(); //exit;

        if($teams){
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => [
                    'logo_path' => $imagePath,
                    'teams'     => $teams
                    ]
            ];
        } else {
            $response = [
                "status"    => true,
                "message"   => lang('App.noDataFound'),
                "data"      => []
            ];
        }
        return $this->respondCreated($response);
    }


    /* public function getClubTeams($club_id = null) {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale($currentLang);

        $getQueryString = getQueryString();

        $imagePath = base_url() . 'uploads/';

        $teamsQuery = $this->teamModel
                            ->select('
                            teams.*, 
                            cb.club_name as team_name, 
                            cb.club_logo as team_club_logo, 
                            CONCAT("'.$imagePath.'", cb.club_logo ) AS team_club_logo_path,
                            c.country_name
                        ')
                    // ->join('(SELECT user_id, meta_value FROM user_meta WHERE meta_key = "club_name" AND user_id = '.$club_id.' ) um', 'um.user_id = teams.club_id', 'INNER')
                    // ->join('user_meta um2', 'um2.user_id = teams.club_id AND um2.meta_key = "profile_image"', 'INNER')
                    ->join('clubs cb', 'cb.id = teams.club_id', 'INNER')
                    ->join('countries c', 'c.id = teams.country_id', 'INNER');


        if($club_id != null){
            $teamsQuery->where('cb.taken_by', $club_id);
        }

        if(isset(($getQueryString['search']))){
            $teamsQuery->like('um.meta_value', $getQueryString['search']);
        }

        $teams = $teamsQuery->findAll();
        // echo '>>>>>>>>>>> >>>>>>>>>>>> ' . $this->db->getLastQuery(); //exit;

        if($teams){
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => ['teams' => $teams]
            ];
        } else {
            $response = [
                "status"    => true,
                "message"   => lang('App.noDataFound'),
                "data"      => []
            ];
        }
        return $this->respondCreated($response);
    } */

    /* public function getTeams() {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale($currentLang);

        $imagePath = base_url() . 'uploads/';

        $teamsQuery = $this->teamModel
                            ->select('
                                    teams.*, 
                                    um.meta_value as team_name, 
                                    um2.meta_value as team_club_logo, 
                                    CONCAT("'.$imagePath.'", um2.meta_value ) AS team_club_logo_path,
                                    c.country_name
                                ')
                            ->join('(SELECT user_id, meta_value FROM user_meta WHERE meta_key = "club_name") um', 'um.user_id = teams.club_id', 'LEFT')
                            ->join('(SELECT user_id, meta_value FROM user_meta WHERE meta_key = "profile_image") um2', 'um2.user_id = teams.club_id', 'LEFT')
                            ->join('countries c', 'c.id = teams.country_id', 'INNER');

        $teams = $teamsQuery->findAll();

        if($teams){
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => ['teams' => $teams]
            ];
        } else {
            $response = [
                "status"    => false,
                "message"   => lang('App.noDataFound'),
                "data"      => []
            ];
        }
        return $this->respondCreated($response);
    } */
}
