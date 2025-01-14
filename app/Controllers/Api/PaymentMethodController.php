<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use App\Models\PaymentMethodModel;



class PaymentMethodController extends ResourceController
{
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);
        $this->db = \Config\Database::connect();
        $this->paymentMethodModel = new PaymentMethodModel();
    }


    // get list of payment methods
    public function getPaymentMethods($userID = null){
        
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale($currentLang);

        $userId = $userID ?? auth()->id(); 

        // $url  = $this->request->getServer('REQUEST_URI');
        // $params = getQueryString($url);

        $paymentMethod = $this->paymentMethodModel
                    ->where('user_id', $userId);

        // if ($params && !empty($params['search'])) {
        //     $coupons = $coupons
        //         ->like('title', $params['search'])
        //         ->orLike('coupons.description', $params['search']);
        // }

        $paymentMethod = $paymentMethod->orderBy('id', 'DESC')
                        ->findAll();
        //echo ' >>>>>>>>>>>>>>>>>>>> getLastQuery >>>>>> '. $this->db->getLastQuery();    

        if($paymentMethod){
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => ['paymentMethod' => $paymentMethod]
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
