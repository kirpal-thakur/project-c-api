<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use App\Models\ClubModel;

class ClubController extends ResourceController
{
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);
        $this->db = \Config\Database::connect();
        $this->clubModel = new ClubModel();
    }

    public function getClubs(){
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale($currentLang);

        $searchParams = getQueryString();
        
        $club = $this->clubModel
                    ->select('clubs.*, c.country_name') 
                    ->join('countries c', 'c.id = clubs.country_id', 'INNER');

        if(isset($searchParams)){
            if(!empty($searchParams['country'])){
                $club = $club->where('clubs.country_id', $searchParams['country']);
            }

            if(!empty($searchParams['is_taken'])){
                $club = $club->where('clubs.is_taken', $searchParams['is_taken']);
            }

            if(!empty($searchParams['club_name'])){
                $club = $club->like('clubs.club_name', $searchParams['club_name']);
            }
        }

        $clubs = $club->findAll();
        $imagePath = base_url() . 'uploads/';

        if($clubs){
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => [
                    'logo_path' => $imagePath, 
                    'clubs' => $clubs 
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


    /* public function getClubs() {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale($currentLang);

        $url =  isset($_SERVER['HTTPS']) && 
        $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";   
        $url .= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];     
        $parse_url = parse_url($url);
        $uri = new \CodeIgniter\HTTP\URI($url);

        $queryString =  $uri->getQuery();
        parse_str($queryString, $searchParams);

        $builder = $this->db->table('users');
        $builder->select( 'users.*, 
                            user_roles.role_name as role_name,
                            languages.language as language,
                            domains.domain_name as domain_name,
                            domains.location as user_location,
                            auth.secret as email,
                            um.meta_value as club_name, 
                            c.country_name as country
                            
                        ',);
        $builder->join('(SELECT user_id, secret FROM auth_identities WHERE type = "email_password") auth ', 'auth.user_id = users.id', 'LEFT');
        $builder->join('user_roles', 'user_roles.id = users.role', 'INNER');
        $builder->join('languages', 'languages.id = users.lang', 'INNER');
        $builder->join('domains', 'domains.id = users.user_domain', 'INNER');
        $builder->join('(SELECT user_id, meta_value FROM user_meta WHERE meta_key = "club_name") um', 'um.user_id = users.id', 'LEFT');
        $builder->join('user_nationalities un', 'un.user_id = users.id', 'LEFT');
        $builder->join('countries c', 'c.id = un.country_id', 'LEFT');
        $builder->where('users.deleted_at', NULL);
        $builder->where('users.role', 2);

        if(isset($searchParams) && !empty($searchParams['club_name'])){
            $builder->like('um.meta_value', $searchParams['club_name']);
        }

        if(isset($searchParams) && !empty($searchParams['country'])){
            $builder->where('un.country_id', $searchParams['country']);
        }

        $builder->orderBy('club_name', 'ASC');

        $query = $builder->get();
        // echo '>>>>>>>> getLastQuery >>>>> '. $this->db->getLastQuery();  //exit;
    
        $clubs = $query->getResultArray();


        if( $clubs){
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => [ 
                        'clubs'      => $clubs
                    ]
            ];
        } else {
            $response = [
                "status"    => false,
                "message"   => lang('App.noDataFound'),
                "data"      => []
            ];
        }

        $activity_data = [
            'user_id'               => auth()->id(),
            'activity_type_id'      => 9,      // viewed
            'activity'              => 'viewed user',
            'ip'                    => $this->request->getIPAddress()
        ];
        createActivityLog($activity_data);
        
        return $this->respondCreated($response);
    } */

}
