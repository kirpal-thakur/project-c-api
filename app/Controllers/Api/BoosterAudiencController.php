<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use App\Models\BoosterAudienceModel;
use App\Models\UserSubscriptionModel;


class BoosterAudiencController extends ResourceController
{
   
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);
        $this->db = \Config\Database::connect();

        // Include the Composer autoload file
        require_once APPPATH . '../vendor/autoload.php';
        
        $this->db->boosterAudienceModel     = new BoosterAudienceModel();
        $this->db->userSubscriptionModel    = new UserSubscriptionModel();
    
    }

    public function updateBoosterAudience($user_id = null) {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $rules = [
            'booster_audience.*'   => 'required|integer'
        ];

        $inputData = $this->request->getVar();

        if (! $this->validateData($inputData, $rules)) {
            
            $response = [
                "status"    => false,
                "message"   => 'Error',
                "data"      => ['error' => $this->validator->getErrors()]
            ];

        } else {

            $user_id = $user_id ?? auth()->id();
            $new_audience = $this->request->getVar('booster_audience');

            $boosterPackages = [3,4];
            $boosterData = $this->db->userSubscriptionModel->whereIn('package_id', $boosterPackages)
                                                        ->where('status', 'active')
                                                        ->where('user_id', $user_id)
                                                        ->first();

            if($boosterData){
                $subscription_id = $boosterData['stripe_subscription_id'];
                $getAudiences = $this->db->boosterAudienceModel->where('subscription_id', $subscription_id)->where('user_id', $user_id)->findAll();

                $target_roles = array_column($getAudiences, 'target_role');

                $addAudiences = array_diff($new_audience, $target_roles);
                $delAudiences = array_diff($target_roles, $new_audience);

                if($delAudiences){
                    $this->db->boosterAudienceModel->where('subscription_id', $subscription_id)->where('user_id', $user_id)->whereIn('target_role', $delAudiences)->delete();
                }

                if($addAudiences){
                    $save_data = [];
                    foreach($addAudiences as $audience){
                        $save_data[] = [
                            'subscription_id'   => $subscription_id,
                            'user_id'           => $user_id,
                            'target_role'       => $audience
                        ];
                    }
                    if(count($save_data) > 0){
                        $this->db->boosterAudienceModel->insertBatch($save_data);
                    }
                }

                $response = [
                    "status"    => true,
                    "message"   => lang('App.AudienceUpdated'),       
                    // "data"      => []
                ]; 
            } else {
                $response = [
                    "status"    => false,
                    "message"   => lang('App.noActivePackage'), 
                    // "data"      => []
                ]; 
            }  
        }

        return $this->respondCreated($response);
        
    }

}
