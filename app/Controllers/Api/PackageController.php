<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use App\Models\PackageModel;
use App\Models\PackageDetailModel;


class PackageController extends ResourceController
{
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);

        $this->db                       = \Config\Database::connect();
        $this->packageModel             = new PackageModel();
        $this->packageDetailModel       = new PackageDetailModel();
    }


    public function getPackages_old($interval = null){

        $builder = $this->db->table('package_details pd');
        $builder->select('pd.*,
                    p.title as package_name,
                    p.domain_id,
                    d.location as location,
                ');

        if(auth()->id()){
            $builder->select('us.status as is_package_active');
        }   

        $builder->join('packages p', 'p.id = pd.package_id AND p.status = "active"', 'LEFT');
        $builder->join('domains d', 'd.id = p.domain_id', 'LEFT');

        if(auth()->id()){
            $builder->join('user_subscriptions us', 'us.package_id = pd.id AND us.user_id = '.auth()->id().' AND us.status = "active"', 'LEFT');
        }

        $builder->where('pd.status', 'active');
        if($interval){
            $builder->where('pd.interval', $interval);
        }

        // Exclude user's current location
        if(auth()->id()){
            $builder->whereNotIn(' p.domain_id', [auth()->user()->user_domain]);
        }  

        $builder->orderBy('pd.id', 'ASC');

        $query = $builder->get();
        $packages = $query->getResultArray();
        // echo ' >>>>>>>>>>>>>>>>>> getLastQuery >>>>> '. $this->db->getLastQuery(); exit;
                
        if ($packages) {
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => [
                    $packages
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


    public function getPackages($interval = null){

        $packages = [];
        $premiumPackages = $this->getPackageInfo( PREMIUM_PACKAGES,  $interval );
        $boosterPackages = $this->getPackageInfo( BOOSTER_PACKAGES,  $interval );
        $countryPackages = $this->getPackageInfo( COUNTRY_PACKAGES,  $interval );
        $demoPackages    = $this->getPackageInfo( DEMO_PACKAGES,  $interval );

        $premium_package = [];
        if($premiumPackages){

            $premium_package['package_name']        = $premiumPackages[0]['package_name'];
            $premium_package['active_interval']     = null;
            $premium_package['active_plan_id']      = null;

            $filteredData = array_filter($premiumPackages, function($item) {
                return isset($item['is_package_active']) && $item['is_package_active'] === "active";
            });

            if($filteredData){
                $filteredData = array_values($filteredData);
                $premium_package['active_interval'] = $filteredData[0]['interval'];
                $premium_package['active_plan_id'] = $filteredData[0]['id'];
            }
            $premium_package['type'] = 'single';
            $premium_package['plans'] = $premiumPackages;
        }

        $booster_package = [];
        if($boosterPackages){

            $booster_package['package_name']        = $boosterPackages[0]['package_name'];
            $booster_package['active_interval']     = null;
            $booster_package['active_plan_id']      = null;

            $filteredData = array_filter($boosterPackages, function($item) {
                return isset($item['is_package_active']) && $item['is_package_active'] === "active";
            });

            if($filteredData){
                $filteredData = array_values($filteredData);
                $booster_package['active_interval'] = $filteredData[0]['interval'];
                $booster_package['active_plan_id'] = $filteredData[0]['id'];
            }
            $booster_package['type'] = 'single';
            $booster_package['plans'] = $boosterPackages;
        }

        $demo_package = [];
        if($demoPackages){

            $demo_package['package_name']       = $demoPackages[0]['package_name'];
            $demo_package['active_interval']    = null;
            $demo_package['active_plan_id']     = null;

            $filteredData = array_filter($demoPackages, function($item) {
                return isset($item['is_package_active']) && $item['is_package_active'] === "active";
            });

            if($filteredData){
                $filteredData = array_values($filteredData);
                $demo_package['package_name'] = $filteredData[0]['package_name'];
                $demo_package['active_interval'] = $filteredData[0]['interval'];
                $demo_package['active_plan_id'] = $filteredData[0]['id'];
            }
            
            $demo_package['type'] = 'single';
            $demo_package['plans'] = $demoPackages;
        }

        $country_package = [];
        if($countryPackages){
            $country_package['package_name'] = $countryPackages[0]['package_name'];
            $country_package['type'] = 'multi';
            $country_package['plans'] = $countryPackages;
        } 

        $packages['premium'] =  $premium_package;
        $packages['booster'] =  $booster_package;
        $packages['country'] =  $country_package;
        $packages['demo'] =  $demo_package;

        if ($packages) {
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => $packages,
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


    public function getPackagesByDomain($interval = null, $domain_id = null, $lang_id = null){

        $packages = [];
        $premiumPackages = $this->getPackageInfoByDomain( PREMIUM_PACKAGES,  $interval, $domain_id, $lang_id );
        $boosterPackages = $this->getPackageInfoByDomain( BOOSTER_PACKAGES,  $interval, $domain_id, $lang_id );
        $countryPackages = $this->getPackageInfoByDomain( COUNTRY_PACKAGES,  $interval, $domain_id, $lang_id );
        $demoPackages    = $this->getPackageInfoByDomain( DEMO_PACKAGES,     $interval, $domain_id, $lang_id );

        $premium_package = [];
        if($premiumPackages){

            $premium_package['package_name']        = $premiumPackages[0]['package_name'];
            // $premium_package['active_interval']     = null;
            // $premium_package['active_plan_id']      = null;

            // $filteredData = array_filter($premiumPackages, function($item) {
            //     return isset($item['is_package_active']) && $item['is_package_active'] === "active";
            // });

            // if($filteredData){
            //     $filteredData = array_values($filteredData);
            //     // $premium_package['active_interval'] = $filteredData[0]['interval'];
            //     // $premium_package['active_plan_id'] = $filteredData[0]['id'];
            // }
            $premium_package['type'] = 'single';
            $premium_package['plans'] = $premiumPackages;
        }

        $booster_package = [];
        if($boosterPackages){

            $booster_package['package_name']        = $boosterPackages[0]['package_name'];
            // $booster_package['active_interval']     = null;
            // $booster_package['active_plan_id']      = null;

            // $filteredData = array_filter($boosterPackages, function($item) {
            //     return isset($item['is_package_active']) && $item['is_package_active'] === "active";
            // });

            // if($filteredData){
            //     $filteredData = array_values($filteredData);
            //     $booster_package['active_interval'] = $filteredData[0]['interval'];
            //     $booster_package['active_plan_id'] = $filteredData[0]['id'];
            // }
            $booster_package['type'] = 'single';
            $booster_package['plans'] = $boosterPackages;
        }

        $demo_package = [];
        if($demoPackages){

            $demo_package['package_name']       = $demoPackages[0]['package_name'];
            // $demo_package['active_interval']    = null;
            // $demo_package['active_plan_id']     = null;

            // $filteredData = array_filter($demoPackages, function($item) {
            //     return isset($item['is_package_active']) && $item['is_package_active'] === "active";
            // });

            // if($filteredData){
            //     $filteredData = array_values($filteredData);
            //     $demo_package['package_name'] = $filteredData[0]['package_name'];
            //     $demo_package['active_interval'] = $filteredData[0]['interval'];
            //     $demo_package['active_plan_id'] = $filteredData[0]['id'];
            // }
            
            $demo_package['type'] = 'single';
            $demo_package['plans'] = $demoPackages;
        }

        $country_package = [];
        if($countryPackages){
            $country_package['package_name'] = $countryPackages[0]['package_name'];
            $country_package['type'] = 'multi';
            $country_package['plans'] = $countryPackages;
        } 

        $packages['premium'] =  $premium_package;
        $packages['booster'] =  $booster_package;
        $packages['country'] =  $country_package;
        $packages['demo'] =  $demo_package;

        if ($packages) {
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => $packages,
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

    private function getPackageInfo( $packages = null,  $interval = null) {

        $builder = $this->db->table('package_details pd');
        $builder->select('pd.*,
                    p.title as package_name,
                    p.domain_id,
                    d.location as location,
                    
                ');

                // pp.price as price, 
                // pp.currency as currency,
                

        if(auth()->id()){
            $builder->select('pp.price as price, pp.currency as currency, us.status as is_package_active');
        }   

        $builder->join('packages p', 'p.id = pd.package_id AND p.status = "active"', 'LEFT');
        $builder->join('domains d', 'd.id = p.domain_id', 'LEFT');

        if(auth()->id()){
            $user_domain = auth()->user()->user_domain;
            $builder->join('user_subscriptions us', 'us.package_id = pd.id AND us.user_id = '.auth()->id().' AND us.status = "active"', 'LEFT');
            $builder->join('package_prices pp', 'pp.package_detail_id = pd.id AND pp.currency = (SELECT currency FROM domains WHERE id = '.$user_domain.')', 'LEFT');
            // LEFT JOIN package_prices pp ON pp.package_detail_id = pd.id AND pp.currency = 'DKK'
        }

        $builder->whereIn('pd.id', $packages);
        $builder->where('pd.status', 'active');
        if($interval){
            $builder->where('pd.interval', $interval);
        }

        // Exclude user's current location
        if(auth()->id()){
            $builder->whereNotIn(' p.domain_id', [auth()->user()->user_domain]);
        }  

        $builder->orderBy('pd.id', 'ASC');

        $query = $builder->get();
        $packages = $query->getResultArray();
        // echo ' >>>>>>>>>>>>>>>>>> getLastQuery >>>>> '. $this->db->getLastQuery(); exit;
        return $packages;
    }


    private function getPackageInfoByDomain( $packages = null,  $interval = null, $domain_id = null, $lang_id = null) {

        $domainInfo = getDomainInfo($domain_id);
        $domainCurrency = $domainInfo->currency;
        $lang_id = $lang_id ??  $domainInfo->default_lang_id;
        $getlanguageInfo = getLanguageByID($lang_id);
        $languageCode = $getlanguageInfo->slug;

        $builder = $this->db->table('package_details pd');
        $builder->select('
                    pd.id,
                    pd.package_id,
                    pd.interval,
                    pd.description_'.$languageCode.' as description,
                    pd.interval_'.$languageCode.' as interval_name,
                    p.title_'.$languageCode.' as package_name,
                    p.domain_id,
                    d.location as location,
                    pp.price as price, 
                    pp.currency as currency,
                ');

        $builder->join('packages p', 'p.id = pd.package_id AND p.status = "active"', 'LEFT');
        $builder->join('domains d', 'd.id = p.domain_id', 'LEFT');
        $builder->join('package_prices pp', 'pp.package_detail_id = pd.id AND pp.currency = "'. $domainCurrency . '"' , 'LEFT');

        $builder->whereIn('pd.id', $packages);
        $builder->where('pd.status', 'active');
        if($interval){
            $builder->where('pd.interval', $interval);
        }

        $builder->orderBy('pd.id', 'ASC');

        $query = $builder->get();
        $packages = $query->getResultArray();
        // echo ' >>>>>>>>>>>>>>>>>> getLastQuery >>>>> '. $this->db->getLastQuery(); //exit;

        return $packages;
    }

    public function getActiveDomains(){

        $country_packages = [5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24];

        $flagPath = base_url() . 'uploads/logos/';

        $userDomainId = auth()->user()->user_domain;

        $builder = $this->db->table('package_details pd');
        $builder->select('pd.*,
                    p.title as package_name,
                    p.domain_id,
                    d.location as location,
                    d.domain_flag,
                    us.status as is_package_active,
                    us.stripe_subscription_id as stripe_subscription_id,
                    (CASE
                        WHEN p.domain_id = ' . $userDomainId . ' THEN TRUE
                        ELSE FALSE
                    END) as is_default, 
                    ROW_NUMBER() OVER(PARTITION BY p.domain_id ORDER BY us.status = "active" DESC ) AS row_num  
                ');  

        $builder->join('packages p', 'p.id = pd.package_id AND p.status = "active"', 'LEFT');
        $builder->join('domains d', 'd.id = p.domain_id', 'LEFT');
        // $builder->join('countries c', 'c.id = d.country_id', 'LEFT');    // to get country flag
        $builder->join('user_subscriptions us', 'us.package_id = pd.id AND us.user_id = '.auth()->id().' AND us.status = "active"', 'LEFT');
        $builder->where('pd.status', 'active');
        $builder->whereIn('pd.id', $country_packages);
        
        // $builder->groupBy('p.domain_id');
        // $builder->orderBy('p.domain_id', 'ASC');

        // $query = $builder->get();
        // $packages = $query->getResultArray();

        $newBuilder  = $this->db->newQuery()->fromSubquery($builder, 'newTable');
        $newBuilder->where('row_num', 1);
        $newBuilder->orderBy('domain_id', 'ASC');
        $query    = $newBuilder->get();
        $packages = $query->getResultArray();

        // echo ' >>>>>>>>>>>>>>>>>> getLastQuery >>>>> '. $this->db->getLastQuery(); //exit;
                
        if ($packages) {
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => [
                    'domains'   => $packages,
                    'logo_path' => $flagPath
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

    /* public function getActiveDomains(){

        $country_packages = [5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24];

        $flagPath = base_url() . 'uploads/logos/';

        $userDomainId = auth()->user()->user_domain;

        $builder = $this->db->table('package_details pd');
        $builder->select('pd.*,
                    p.title as package_name,
                    p.domain_id,
                    d.location as location,
                    d.domain_flag,
                    us.status as is_package_active,
                    us.stripe_subscription_id as stripe_subscription_id,
                    (CASE
                        WHEN p.domain_id = ' . $userDomainId . ' THEN TRUE
                        ELSE FALSE
                    END) as is_default
                ');  

        $builder->join('packages p', 'p.id = pd.package_id AND p.status = "active"', 'LEFT');
        $builder->join('domains d', 'd.id = p.domain_id', 'LEFT');
        // $builder->join('countries c', 'c.id = d.country_id', 'LEFT');    // to get country flag
        $builder->join('user_subscriptions us', 'us.package_id = pd.id AND us.user_id = '.auth()->id().' AND us.status = "active"', 'LEFT');
        $builder->where('pd.status', 'active');
        $builder->whereIn('pd.id', $country_packages);
        
        $builder->groupBy('p.domain_id');
        $builder->orderBy('p.domain_id', 'ASC');

        $query = $builder->get();
        $packages = $query->getResultArray();
        // echo ' >>>>>>>>>>>>>>>>>> getLastQuery >>>>> '. $this->db->getLastQuery(); //exit;
                
        if ($packages) {
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => [
                    'domains'   => $packages,
                    'logo_path' => $flagPath
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

    } */
}
