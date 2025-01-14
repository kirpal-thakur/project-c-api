<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use App\Models\BoosterStatisticModel;


class BoosterStatisticController extends ResourceController
{
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);
        $this->db = \Config\Database::connect();

        // Include the Composer autoload file
        require_once APPPATH . '../vendor/autoload.php';
        $this->db->boosterStatisticModel = new BoosterStatisticModel();
    
    }


    public function addBoosterAction() {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $rules = [
            'user_id'           => 'required|integer',
            'profile_viewed.*'  => 'required|integer',
            'action'            => 'required'
        ];

        $inputData = $this->request->getVar();

        if (! $this->validateData($inputData, $rules)) {
            
            $response = [
                "status"    => false,
                "message"   => 'Error',
                "data"      => ['error' => $this->validator->getErrors()]
            ];

        } else {
            $save_data = [];
            foreach($this->request->getVar('profile_viewed') as $profile){
                $save_data[] =  [
                    'user_id'           =>  $this->request->getVar('user_id'),
                    'profile_viewed'    =>  $profile,
                    'action'            =>  $this->request->getVar('action'),
                    'ip'                =>  $this->request->getIPAddress()
                ];
            }

            if($this->db->boosterStatisticModel->insertBatch($save_data)){
                $response = [
                    "status"    => true,
                    "message"   => lang('App.boosterDataAdded'),
                ];
            } else {
                $response = [
                    "status"    => false,
                    "message"   => lang('App.boosterDataAddFailed'),
                ];
            }
        }

        return $this->respondCreated($response);
    }


    public function getBoosterAction($user_id = null) {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $user_id = $user_id ?? auth()->id();
        $profilePath = base_url() . 'uploads/';

        // Create the subquery for booster_data
        $subquery = $this->db->table('booster_statistics AS bdata')
                        ->select('bdata.*, ROW_NUMBER() OVER (PARTITION BY profile_viewed ORDER BY id DESC) AS rn', false)
                        ->getCompiledSelect();

        // Main query using the subquery and additional joins and conditions
        $query = $this->db->table('(' . $subquery . ') AS bs')
            ->select('bs.*, 
                        u.first_name, 
                        u.last_name, 
                        um.meta_value AS profile_image
                    ')
            ->select("(CASE
                        WHEN TIMESTAMPDIFF(SECOND, bs.created_at, NOW()) < 60 THEN 
                            CONCAT(TIMESTAMPDIFF(SECOND, bs.created_at, NOW()), ' secs ago')
                        WHEN TIMESTAMPDIFF(MINUTE, bs.created_at, NOW()) < 60 THEN 
                            CONCAT(TIMESTAMPDIFF(MINUTE, bs.created_at, NOW()), ' mins ago')
                        WHEN TIMESTAMPDIFF(HOUR, bs.created_at, NOW()) < 24 THEN 
                            CONCAT(TIMESTAMPDIFF(HOUR, bs.created_at, NOW()), ' hours ago')
                        WHEN TIMESTAMPDIFF(DAY, bs.created_at, NOW()) < 30 THEN 
                            CONCAT(TIMESTAMPDIFF(DAY, bs.created_at, NOW()), ' days ago')
                        WHEN TIMESTAMPDIFF(MONTH, bs.created_at, NOW()) < 12 THEN 
                            CONCAT(TIMESTAMPDIFF(MONTH, bs.created_at, NOW()), ' months ago')
                        ELSE 
                            CONCAT(TIMESTAMPDIFF(YEAR, bs.created_at, NOW()), ' years ago')
                    END) AS last_activity", false)
            ->join('users AS u', 'u.id = bs.profile_viewed', 'left')
            ->join('user_meta AS um', 'um.user_id = bs.profile_viewed AND um.meta_key = "profile_image"', 'left')

            ->where('bs.user_id', $user_id)
            ->where('rn', 1)
            ->orderBy('id', 'DESC')
            ->get();

        // Fetch the results
        $getBoosterData = $query->getResult();
        
        $viewsCount = $this->getBoosterActionCount($user_id, 'view');
        $clickCount = $this->getBoosterActionCount($user_id, 'click');

        $audienceBuilder = $this->db->table('booster_audiences ba');
        $audienceBuilder->select('ba.*, ur.role_name');
        $audienceBuilder->where('user_id', $user_id);
        $audienceBuilder->join('user_roles ur', 'ur.id = ba.target_role', 'LEFT');
        $audience = $audienceBuilder->get()->getResult();
            // pr($audience);

        if($getBoosterData){
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => [
                    'views_count'       => $viewsCount,
                    'click_count'       => $clickCount,
                    'profile_path'      => $profilePath,
                    'get_booster_data'  => $getBoosterData,
                    'booster_audience'  => $audience
                ]
            ];
        } else {
            $response = [
                "status"    => false,
                "message"   => lang('App.noDataFound'),
            ];
        }

        return $this->respondCreated($response);

    }


    private function getBoosterActionCount($user_id = null, $action = null) {

        $user_id = $user_id ?? auth()->id();
        $profile_path = base_url() . 'uploads/';

        $builder = $this->db->table('booster_statistics bs');

        $countResult = $builder->select('count(*) as count')->where('user_id', $user_id)->where('action', $action)->get()->getRow();
        return $countResult->count;

    }


    
}
