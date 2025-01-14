<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use App\Models\CouponModel;
use App\Models\CouponDiscountAmountModel;


class CouponController extends ResourceController
{
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);
        $this->db = \Config\Database::connect();
        $this->couponModel = new CouponModel();
        $this->couponDiscountAmountModel = new CouponDiscountAmountModel();
    }

    // Add Coupon

    public function addCoupon(){
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            $rules = [
                'title'             => 'required|max_length[256]',
                'coupon_code'       => 'required|is_unique[coupons.coupon_code]',
                // 'discount'          => 'required',
                'discount_type'     => 'required',
                // 'duration'          => 'required',
                'valid_from'        => 'required|valid_date',
                'status'            => 'required',
            ];

            // if ($this->request->getVar('duration') === 'repeating') {
            //     $rules['duration_in_months'] = 'required|integer|greater_than[0]';
            // }

            if ($this->request->getVar('discount_type') === 'amount_off') {
                $rules['amounts.*.currency'] = 'required';
                $rules['amounts.*.discount'] = 'required|integer|greater_than[0]';
            }

            if ($this->request->getVar('discount_type') === 'percent_off') {
                $rules['discount'] = 'required';
            }
        

            if(!$this->validate($rules)){
                $response = [
                    "status"    => false,
                    "message"   => lang('App.error'),
                    "data"      => ['error' => $this->validator->getErrors() ]
                ];
            } else {
                // pr($this->request->getVar());  exit;

                // Set your secret key. Remember to switch to your live secret key in production.
                // See your keys here: https://dashboard.stripe.com/apikeys
                $stripe = new \Stripe\StripeClient(getenv('stripe.secret.key'));

                $couponData = [
                    // 'duration'          => $this->request->getVar("duration"),
                    'id'                => $this->request->getVar("coupon_code"),
                    'name'              => $this->request->getVar("title"),
                    // 'max_redemptions'   => $this->request->getVar("limit"),
                    // 'redeem_by'         => strtotime($this->request->getVar("valid_to")) ?? NULL,
                ];

                if($this->request->getVar("discount_type") == 'percent_off'){
                    $couponData['percent_off'] = $this->request->getVar("discount");
                } else {
                    // $couponData['amount_off'] = $this->request->getVar("discount") * 100;
                    // $couponData['currency'] = 'CHF';

                    $currency_options = [];
                    $discount_amount = 0;
                    $discount_currency = '';

                    if($this->request->getVar('amounts')){
                        foreach($this->request->getVar('amounts') as $key => $amount){
                            if($key == 0 ){
                                $discount_amount    = $amount['discount'] * 100;
                                $discount_currency  = $amount['currency'];
                                continue;
                            }

                            $currency_options[$amount['currency']]  = ['amount_off' => $amount['discount'] * 100 ];
                        }
                    }

                    $couponData['amount_off']   = $discount_amount;
                    $couponData['currency']     = $discount_currency;

                    if(count($currency_options) > 0){
                        $couponData['currency_options']     = $currency_options;
                    }
                }

                if($this->request->getVar("valid_to")){
                    $couponData['redeem_by'] = strtotime($this->request->getVar("valid_to"));
                }

                if($this->request->getVar("limit") ){
                    $couponData['max_redemptions'] = $this->request->getVar("limit");
                }

                // if($this->request->getVar("duration") == "repeating"){
                //     $couponData['duration_in_months'] = $this->request->getVar("duration_in_months");
                // }

                try {
                    $couponRes = $stripe->coupons->create($couponData);

                    if($couponRes){
                        $save_data = [
                            'user_id'       => auth()->id(),
                            'title'         => $this->request->getVar("title"),
                            'coupon_code'   => $this->request->getVar("coupon_code"),
                            'discount'      => $this->request->getVar("discount"),
                            'discount_type' => $this->request->getVar("discount_type"),
                            // 'duration'      => $this->request->getVar("duration"),
                            'valid_from'    => $this->request->getVar("valid_from"),
                            'status'        => $this->request->getVar("status")
                        ];
            
                        if($this->request->getVar("description")){
                            $save_data['description'] = $this->request->getVar("description");
                        }
                        if($this->request->getVar("no_validity")){
                            $save_data['no_validity'] = $this->request->getVar("no_validity");
                        }
                        if($this->request->getVar("valid_to")){
                            $save_data['valid_to'] = $this->request->getVar("valid_to");
                        }
                        if($this->request->getVar("is_limit")){
                            $save_data['is_limit'] = $this->request->getVar("is_limit");
                        }
                        if($this->request->getVar("limit")){
                            $save_data['limit'] = $this->request->getVar("limit");
                        }
                        if($this->request->getVar("limit_per_user")){
                            $save_data['limit_per_user'] = $this->request->getVar("limit_per_user");
                        }
            
                        if($this->couponModel->save($save_data)){

                            $lastInsertID = $this->couponModel->getInsertID();
                            
                            if($this->request->getVar('amounts')){
                                $amountData = [];
                                foreach($this->request->getVar('amounts') as $amount){
                                    $amountData[] = [
                                        'coupon_id'         => $lastInsertID,
                                        'currency'          => $amount['currency'],
                                        'discount_amount'   => $amount['discount'] * 100,
                                    ];
                                }

                                if(count($amountData) > 0){
                                    $this->couponDiscountAmountModel->insertBatch($amountData);
                                }
                            }
            
                            // create Activity log
                            // $activity_data = [
                            //     'user_id'               => auth()->id(),
                            //     'activity_type_id'      => 1,      // added 
                            //     'activity'              => 'added coupon',
                            //     'old_data'              => serialize($this->request->getVar()),
                            //     'ip'                    => $this->request->getIPAddress()
                            // ];
                            $activity_data = array();
                            $languageService = \Config\Services::language();
                            $languages = ['en', 'de', 'fr', 'it', 'es', 'pt', 'da', 'sv'];
                            $activity_data = [
                                'user_id'               => auth()->id(),
                                'activity_type_id'      => 7, // Register activity type
                                'ip'                    => $this->request->getIPAddress()
                            ];
                            foreach ($languages as $lang) {
                                $languageService->setLocale($lang);
                                $translated_message = lang('App.couponAdded');
                                $activity_data['activity_' . $lang] = $translated_message;
                            }
                            createActivityLog($activity_data);

                            $response = [
                                "status"    => true,
                                "message"   => lang('App.couponAdded'),
                                "data"      => [ 
                                    // 'couponRes' => $couponRes,  
                                ]
                            ];
                        } else {
                            $response = [
                                "status"    => false,
                                "message"   => lang('App.couponAddFailed'),
                                "data"      => []
                            ];
                        }
                    }
                } catch (\Stripe\Exception\ApiErrorException $e) {
                    // Log the error message
                    log_message('error', 'Stripe API error: ' . $e->getMessage());
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.couponAddFailed'),
                        "data"      => [ 'error' => $e->getMessage()]
                    ];

                    // Handle the error (e.g., return a response, show an error message)
                } 
                
                /* catch (Exception $e) {
                    // Log other exceptions
                    log_message('error', 'General error: ' . $e->getMessage());
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.couponAddFailed'),
                        "data"      => [ 'error' => $e->getMessage()]
                    ];
                } */
            }
        } else {
            $response = [
                "status"    => false,
                "message"   => lang('App.permissionDenied'),
                "data"      => []
            ];
        }
        return $this->respondCreated($response);
    }

    // Edit Coupon
    public function editCoupon( $id = null ){
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP) || hasAccess(auth()->user(), EDITOR_ACCESS)) {

            $rules = [
                'title'             => 'required|max_length[256]',
                'coupon_code'       => 'required|is_unique[coupons.coupon_code]',
                'discount'          => 'required',
                'discount_type'     => 'required',
                'valid_from'        => 'required|valid_date',
                'status'            => 'required',
            ];

            if(!$this->validate($rules)){
                $response = [
                    "status"    => false,
                    "message"   => lang('App.error'),
                    "data"      => ['error' => $this->validator->getErrors() ]
                ];
            } else {
                $save_data = [
                    'id'            => $id,
                    'title'         => $this->request->getVar("title"),
                    'coupon_code'   => $this->request->getVar("coupon_code"),
                    'discount'      => $this->request->getVar("discount"),
                    'discount_type' => $this->request->getVar("discount_type"),
                    'valid_from'    => $this->request->getVar("valid_from"),
                    'status'        => $this->request->getVar("status")
                ];

                if($this->request->getVar("description")){
                    $save_data['description'] = $this->request->getVar("description");
                }
                if($this->request->getVar("no_validity")){
                    $save_data['no_validity'] = $this->request->getVar("no_validity");
                }
                if($this->request->getVar("valid_to")){
                    $save_data['valid_to'] = $this->request->getVar("valid_to");
                }
                if($this->request->getVar("is_limit")){
                    $save_data['is_limit'] = $this->request->getVar("is_limit");
                }
                if($this->request->getVar("limit")){
                    $save_data['limit'] = $this->request->getVar("limit");
                }
                if($this->request->getVar("limit_per_user")){
                    $save_data['limit_per_user'] = $this->request->getVar("limit_per_user");
                }

                if($this->couponModel->save($save_data)){

                    // create Activity log
                    // $activity_data = [
                    //     'user_id'               => auth()->id(),
                    //     'activity_type_id'      => 2,      // updated 
                    //     'activity'              => 'updated coupon',
                    //     'old_data'              => serialize($this->request->getVar()),
                    //     'ip'                    => $this->request->getIPAddress()
                    // ];
                    // createActivityLog($activity_data);
                    $activity_data = array();
                    $languageService = \Config\Services::language();
                    $languages = ['en', 'de', 'fr', 'it', 'es', 'pt', 'da', 'sv'];
                    $activity_data = [
                        'user_id'               => auth()->id(),
                        'activity_type_id'      => 7, // Register activity type
                        'ip'                    => $this->request->getIPAddress()
                    ];
                    foreach ($languages as $lang) {
                        $languageService->setLocale($lang);
                        $translated_message = lang('App.couponAdded');
                        $activity_data['activity_' . $lang] = $translated_message;
                    }
                    createActivityLog($activity_data);

                    $response = [
                        "status"    => true,
                        "message"   => lang('App.couponUpdated'),
                        "data"      => []
                    ];
                } else {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.couponUpdateFailed'),
                        "data"      => []
                    ];
                }
            }
        } else {
            $response = [
                "status"    => false,
                "message"   => lang('App.permissionDenied'),
                "data"      => []
            ];
        }

        return $this->respondCreated($response);
    }

    // get list of coupons
    public function getCoupons(){
        
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $userId = auth()->id(); 

        $url  = $this->request->getServer('REQUEST_URI');
        $params = getQueryString($url);

        $coupons = $this->couponModel
                    ->select('coupons.*, 
                        
                        (SELECT JSON_ARRAYAGG(
                                    JSON_OBJECT(
                                        "currency", cd.currency ,
                                        "discount_amount", ROUND(cd.discount_amount / 100, 2)
                                    )
                                )
                                FROM coupon_discount_amounts cd
                                WHERE cd.coupon_id = coupons.id
                            ) As coupon_discount_amounts
                    ')
                    ->where('is_deleted', null);

        if ($params && !empty($params['search'])) {
            $coupons = $coupons
                ->like('title', $params['search'])
                // ->orLike('coupons.description', $params['search'])
                ->orLike('coupons.coupon_code', $params['search']);
        }
        /* ##### Code By Amrit ##### */
        if(isset($params['whereClause']) && count($params['whereClause']) > 0){
            $whereClause = $params['whereClause'];
            if (isset($whereClause) && count($whereClause) > 0) {
                foreach ($whereClause as $key => $value) {
                    if ($key == 'discount_type') {
                        $coupons->where("coupons.discount_type", $value);  
                    } elseif ($key == 'status') {
                        $coupons->where("coupons.status", strtolower($value));  
                    }
                }
            }
        }
        /* ##### End Code By Amrit ##### */

        $coupons = $coupons->orderBy('id', 'DESC')
                        ->findAll();

        if($coupons){
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => ['coupons' => $coupons]
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

    // get single coupons
    public function getCoupon( $id = null ){
            
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $coupon = $this->couponModel
                        ->select('
                            coupons.*,
                            (SELECT JSON_ARRAYAGG(
                                    JSON_OBJECT(
                                        "currency", cd.currency ,
                                        "discount_amount", ROUND(cd.discount_amount / 100, 2)
                                    )
                                )
                                FROM coupon_discount_amounts cd
                                WHERE cd.coupon_id = coupons.id
                            ) As coupon_discount_amounts
                        ')
                        ->where('id', $id)
                        ->where('is_deleted', null)
                        ->first();

        if($coupon){
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => ['coupon' => $coupon]
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

    // Delete coupons
    public function deleteCoupon() {
            
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            $couponIds = $this->request->getVar("id");
            if(!empty($couponIds) && count($couponIds) > 0){

                // get server time
                $deleted_at = $this->db->query('SELECT CURRENT_TIMESTAMP() as "CURRENT_TIMESTAMP"')->getRow()->CURRENT_TIMESTAMP;

                foreach($couponIds as $id){
                    $couponInfo = $this->couponModel->select('id,coupon_code')->where('id', $id)->first();
                    $stripe = new \Stripe\StripeClient(getenv('stripe.secret.key')); 
                    $stripe->coupons->delete($couponInfo['coupon_code'], []);            // delete coupon from stripe    
                
                    if($this->couponModel->update($id, ['is_deleted' => $deleted_at ])){

                        // create Activity log
                        $activity_data = [
                            'user_id'               => auth()->id(),
                            'activity_on_id'        => $id,      
                            'activity_type_id'      => 3,      // deleted 
                            'activity'              => 'deleted coupon',
                            'activity_en'           => 'deleted coupon',
                            'activity_de'           => 'Gutschein gelöscht',
                            'activity_it'           => 'coupon eliminato',
                            'activity_fr'           => 'coupon supprimé',
                            'activity_es'           => 'cupón eliminado',
                            'activity_pt'           => 'cupom excluído',
                            'activity_da'           => 'slettet kupon',
                            'activity_sv'           => 'raderade kupong',
                            'ip'                    => $this->request->getIPAddress()
                        ];
                        createActivityLog($activity_data);
                        
                        $response = [
                            "status"    => true,
                            "message"   => lang('App.couponDeleted'),
                            "data"      => []
                        ];
                    } else {
                        $response = [
                            "status"    => false,
                            "message"   => lang('App.couponDeleteFailed'),
                            "data"      => []
                        ];
                    }
                }

            } else {
                $response = [
                    "status"    => false,
                    "message"   => lang('App.provideDeleteData'),
                    "data"      => []
                ];
            }
        } else {
            $response = [
                "status"    => false,
                "message"   => lang('App.permissionDenied'),
                "data"      => []
            ];
        }
        
        return $this->respondCreated($response);
    }

    // Publish coupons
    public function publishCoupon() {
            
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            $id = $this->request->getVar("id");

            if(!empty($id) && count($id) > 0){

                if($this->couponModel
                        ->whereIn('id', $id)
                        ->set(['status' => 2])   //  2 = Published
                        ->update()){

                    // create Activity log
                    // $activity = 'updated coupons status to published';
                    // $activity_data = [
                    //     'user_id'               => auth()->id(),
                    //     'activity_type_id'      => 2,      // updated 
                    //     'activity'              => $activity,
                    //     'ip'                    => $this->request->getIPAddress()
                    // ];
                    // createActivityLog($activity_data);
                    $activity_data = array();
                    $languageService = \Config\Services::language();
                    $languages = ['en', 'de', 'fr', 'it', 'es', 'pt', 'da', 'sv'];
                    $activity_data = [
                        'user_id'               => auth()->id(),
                        'activity_type_id'      => 7, // Register activity type
                        'ip'                    => $this->request->getIPAddress()
                    ];
                    foreach ($languages as $lang) {
                        $languageService->setLocale($lang);
                        $translated_message = lang('App.couponPublished');
                        $activity_data['activity_' . $lang] = $translated_message;
                    }
                    createActivityLog($activity_data);
                    
                    $response = [
                        "status"    => true,
                        "message"   => lang('App.couponPublished'),
                        "data"      => []
                    ];
                } else {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.couponPublishFailed'),
                        "data"      => []
                    ];
                }

            } else {
                $response = [
                    "status"    => false,
                    "message"   => lang('App.provideValidData'),
                    "data"      => []
                ];
            }
        } else {
            $response = [
                "status"    => false,
                "message"   => lang('App.permissionDenied'),
                "data"      => []
            ];
        }
        
        return $this->respondCreated($response);
    }

    // Draft coupons
    public function draftCoupon() {
            
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            $id = $this->request->getVar("id");

            if(!empty($id) && count($id) > 0){

                if($this->couponModel
                        ->whereIn('id', $id)
                        ->set(['status' => 1])   //  1 = Draft
                        ->update()){

                    // create Activity log
                    // $activity = 'updated coupons status to Draft';
                    // $activity_data = [
                    //     'user_id'               => auth()->id(),
                    //     'activity_type_id'      => 2,      // updated 
                    //     'activity'              => $activity,
                    //     'ip'                    => $this->request->getIPAddress()
                    // ];
                    // createActivityLog($activity_data);
                    $activity_data = array();
                    $languageService = \Config\Services::language();
                    $languages = ['en', 'de', 'fr', 'it', 'es', 'pt', 'da', 'sv'];
                    $activity_data = [
                        'user_id'               => auth()->id(),
                        'activity_type_id'      => 7, // Register activity type
                        'ip'                    => $this->request->getIPAddress()
                    ];
                    foreach ($languages as $lang) {
                        $languageService->setLocale($lang);
                        $translated_message = lang('App.couponDraft');
                        $activity_data['activity_' . $lang] = $translated_message;
                    }
                    createActivityLog($activity_data);
                    
                    $response = [
                        "status"    => true,
                        "message"   => lang('App.couponDraft'),
                        "data"      => []
                    ];
                } else {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.couponDraftFailed'),
                        "data"      => []
                    ];
                }

            } else {
                $response = [
                    "status"    => false,
                    "message"   => lang('App.provideValidData'),
                    "data"      => []
                ];
            }
        } else {
            $response = [
                "status"    => false,
                "message"   => lang('App.permissionDenied'),
                "data"      => []
            ];
        }
        
        return $this->respondCreated($response);
    }

    // Expired coupons
    public function expireCoupon() {
            
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            $id = $this->request->getVar("id");

            if(!empty($id) && count($id) > 0){

                if($this->couponModel
                        ->whereIn('id', $id)
                        ->set(['status' => 3])   //  3 = Expired
                        ->update()){

                    // create Activity log
                    // $activity = 'updated coupons status to Expired';
                    // $activity_data = [
                    //     'user_id'               => auth()->id(),
                    //     'activity_type_id'      => 2,      // updated 
                    //     'activity'              => $activity,
                    //     'ip'                    => $this->request->getIPAddress()
                    // ];
                    // createActivityLog($activity_data);
                    $activity_data = array();
                    $languageService = \Config\Services::language();
                    $languages = ['en', 'de', 'fr', 'it', 'es', 'pt', 'da', 'sv'];
                    $activity_data = [
                        'user_id'               => auth()->id(),
                        'activity_type_id'      => 7, // Register activity type
                        'ip'                    => $this->request->getIPAddress()
                    ];
                    foreach ($languages as $lang) {
                        $languageService->setLocale($lang);
                        $translated_message = lang('App.couponExpired');
                        $activity_data['activity_' . $lang] = $translated_message;
                    }
                    createActivityLog($activity_data);
                    
                    $response = [
                        "status"    => true,
                        "message"   => lang('App.couponExpired'),
                        "data"      => []
                    ];
                } else {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.couponExpireFailed'),
                        "data"      => []
                    ];
                }

            } else {
                $response = [
                    "status"    => false,
                    "message"   => lang('App.provideValidData'),
                    "data"      => []
                ];
            }

        } else {
            $response = [
                "status"    => false,
                "message"   => lang('App.permissionDenied'),
                "data"      => []
            ];
        }
        
        return $this->respondCreated($response);
    }
}
