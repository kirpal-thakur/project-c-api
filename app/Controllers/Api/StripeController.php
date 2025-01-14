<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use App\Models\PackageDetailModel;
use App\Models\UserSubscriptionModel;
use App\Models\PackageModel;
use App\Models\PaymentMethodModel;
use App\Models\BoosterAudienceModel;
// use App\Models\DomainModel;

// use CodeIgniter\Shield\Models\UserModel;
use Mpdf;




class StripeController extends ResourceController
{
    
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);
        $this->db = \Config\Database::connect();

       // Include the Composer autoload file
       require_once APPPATH . '../vendor/autoload.php';
        
       // Set your secret key. Remember to switch to your live secret key in production.
       Stripe::setApiKey(getenv('stripe.secret.key'));

       $this->db->packageDetailModel        = new PackageDetailModel();
       $this->db->paymentMethodModel        = new PaymentMethodModel();
       $this->db->userSubscriptionModel     = new UserSubscriptionModel();
       $this->db->boosterAudienceModel      = new BoosterAudienceModel();
        //    $this->db->domainModel               = new DomainModel();
       
       
    }

    /* public function createPaymentIntent()
    {
        $response = [];

        try {
            $amount = $this->request->getVar('amount');
            $currency = $this->request->getVar('currency') ?? 'usd';

            // Create a PaymentIntent with the order amount and currency
            $paymentIntent = PaymentIntent::create([
                'amount' => $amount,
                'currency' => $currency,
            ]);

            $response = [
                'clientSecret' => $paymentIntent->client_secret,
                'paymentIntent' => $paymentIntent,
            ];
        } catch (\Exception $e) {
            $response = [
                'error' => $e->getMessage(),
            ];
        }

        return $this->response->setJSON($response);
    } */


    public function addPaymentMethod() {

        $stripe = new \Stripe\StripeClient(getenv('stripe.secret.key'));

        //echo serialize(['email' => 'dev.player.cts@yopmail.com']); exit; 

        $customer = $stripe->customers->search([
            'query'     => "email:'dev.player.cts@yopmail.com'",
        ]);

        // echo '>>>>>>>>>>>>>>>>> customer response >>>>>>>>>>>>>>>>>>';
        // echo '<pre>';
        // print_r($customer);
        // echo '</pre>';
        $customerId = $customer['data'][0]->id;
        // echo '>>>>>>>>>>>>>>>>> customer customerId >>>>>>>>>>>>>>>>>> ' . $customerId;

        if($customer){

            $response['customer'] = $customer;

            $paymentMethod = $stripe->paymentMethods->create([
                'type' => 'card',
                'card' => [
                'number' => '4242424242424242',
                'exp_month' => 4,
                'exp_year' => 2026,
                'cvc' => '123',
                ],
            ]);

            // echo '>>>>>>>>>>>>>>>>> paymentMethod >>>>>>>>>>>>>>>>>>';
            // echo '<pre>';
            // print_r($paymentMethod);
            // echo '</pre>'; 

            $response['paymentMethod'] = $paymentMethod;

        }

        return $this->response->setJSON($response);
    }


    public function upgradeSubscription() {
        // $stripe = new \Stripe\StripeClient(getenv('stripe.secret.key'));
        $userSubscriptionModel = new UserSubscriptionModel();
        $packageModel = new PackageModel();    
        $packageDetailModel = new PackageDetailModel();
        $response = [];

        $inputData = $this->request->getVar();
        // echo '>>>>>>>>>>> auth >>>>> ' . auth()->id(); exit;


        $rules = [
            'subscription_id'   => 'required|integer',
            'new_package_id'    => 'required|integer'
        ];

        if (! $this->validateData($inputData, $rules)) {
            
            $response = [
                "status"    => false,
                "message"   => lang('App.error'),
                "data"      => ['error' => $this->validator->getErrors()]
            ];

        } else {
            // echo '>>>>>>>>>>>>>> $subscription_id >>> ' . $subscription_id;
            $oldSubscriptionData = $userSubscriptionModel->select('stripe_subscription_id, sub_item_id')
                                                        ->where('id', $this->request->getVar('subscription_id'))
                                                        ->where('user_id', auth()->id())
                                                        ->first();

            // echo '>>>>>>> oldSubscriptionData getLastQuery >>>>>>>>> ' . $this->db->getLastQuery() ; 
            // echo '>>>>>>> oldSubscriptionData >>>>>>>>> ' ; 
            // pr($oldSubscriptionData);    exit; 

            if($oldSubscriptionData){
                $sub_item_id            = $oldSubscriptionData['sub_item_id'];
                $stripe_subscription_id = $oldSubscriptionData['stripe_subscription_id'];
            }

            $newPackageInfo = $packageDetailModel->select('stripe_plan_id')
                                                ->where('id', $this->request->getVar('new_package_id'))
                                                ->first();

                                                
            // echo '>>>>>>>>>>> newPackageInfo >>>>> ' ; 
            // pr($newPackageInfo);    exit; 

            if($newPackageInfo){
                $stripe_plan_id = $newPackageInfo['stripe_plan_id'];
            }

            if($sub_item_id && $stripe_plan_id){

                try {
                    $stripe = new \Stripe\StripeClient(getenv('stripe.secret.key'));

                    $subscription = $stripe->subscriptions->update(
                        $stripe_subscription_id,
                        [
                            'items' => [
                                [
                                'id'    => $sub_item_id,
                                'price' => $stripe_plan_id,
                                ],
                            ],

                            'metadata'  => [
                                'user_id'       => auth()->id(),
                                'package_id'    => $this->request->getVar('new_package_id'),
                                'user_email'    => auth()->user()->email
                            ],
                        ]
                    );

                    if($subscription){
                        // return redirect()->to('/success')->with('message', 'Subscription created successfully');

                        $response = [
                            "status"    => true,
                            "message"   => lang('App.subscriptionUpdatedSuccess'),
                            "data"      => ['res' => $subscription ]
                        ];
                    } else {
                        // return redirect()->to('/error')->with('error', $e->getMessage());
                        $response = [
                            "status"    => false,
                            "message"   => lang('App.subscriptionUpdateFailed'),
                            "data"      => ['error' => $e->getMessage()]
                        ];
                    } 

                    //sub_1QCw5LRu80loAFQX8BDHOtUQ
                    // $new_package_id     = $this->request->getVar('new_package_id'); // Plan ID from the request
                    // $subscription_id    = $this->request->getVar('subscription_id');  
                    // $sub_item_id        = $this->request->getVar('sub_item_id');  
                    
                    /* $packageInfo = $packageDetailModel->where('stripe_plan_id', $stripe_plan_id)->first();
                    if($packageInfo){
                        $package_id  = $packageInfo['package_id'];
                    }

                    */

                } catch (\Exception $e) {
                    // return redirect()->to('/error')->with('error', $e->getMessage());
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.stripeError'),
                        "data"      => ['error' => $e->getMessage()]
                    ];
                }
            } else {
                $response = [
                    "status"    => false,
                    "message"   => 'Invalid data provided',
                    "data"      => []
                ];
            }
        }

        return $this->respondCreated($response);
    }


    public function cancelSubscription() {

        $userSubscriptionModel = new UserSubscriptionModel();
        $packageModel = new PackageModel();

        $inputData = $this->request->getVar();

        $rules = [
            'subscription_id'   => 'required|integer'
        ];

        if (! $this->validateData($inputData, $rules)) {
            
            $response = [
                "status"    => false,
                "message"   => lang('App.error'),
                "data"      => ['error' => $this->validator->getErrors()]
            ];

        } else {

            try {

                $stripe = new \Stripe\StripeClient(getenv('stripe.secret.key'));

                $oldSubscriptionData = $userSubscriptionModel
                                            ->where('id', $this->request->getVar('subscription_id'))
                                            ->where('user_id', auth()->id())
                                            ->first();

                if($oldSubscriptionData){

                    $subscription = $stripe->subscriptions->update(
                        $oldSubscriptionData['stripe_subscription_id'],
                        ['cancel_at_period_end' => true]
                    );
                    $packageName = '';
                    if(!empty($oldSubscriptionData['stripe_plan_id'])){
                        $stripe_plan_id = $oldSubscriptionData['stripe_plan_id'];
                        $packageDetailModel = new PackageDetailModel();
                        $packageInfo = $packageDetailModel->where('stripe_plan_id', $stripe_plan_id)->first();
                        if(!empty($packageInfo['description'])){
                            $packageName = trim($packageInfo['description']);
                        }
                    }
                    if($subscription){
                        $response = [
                            "status"    => true,
                            "message"   => lang('App.subscriptionCancelledSuccess'),
                            "data"      => ['res' => $subscription ]
                        ];
                    } else {
                        $response = [
                            "status"    => false,
                            "message"   => lang('App.subscriptionCancelFailed'),
                            "data"      => ['error' => $e->getMessage()]
                        ];
                    }

                } else {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.subscriptioncancelError'),
                        "data"      => ['error' =>  lang('App.subscriptionNotFound')]
                    ];
                }

            } catch (\Exception $e) {
                // return redirect()->to('/error')->with('error', $e->getMessage());
                $response = [
                    "status"    => false,
                    "message"   => lang('App.stripeError'),
                    "data"      => ['error' => $e->getMessage()]
                ];
            }
        }

        return $this->respondCreated($response);
    }

    public function upgradeSubscription_web($subscription_id = null) {
        $stripe = new \Stripe\StripeClient(getenv('stripe.secret.key'));
        $userSubscriptionModel = new UserSubscriptionModel();
        $packageModel = new PackageModel();    
        $packageDetailModel = new PackageDetailModel();

        try {
            $stripe = new \Stripe\StripeClient(getenv('stripe.secret.key'));

            $stripe_plan_id = $this->request->getVar('plan_id'); // Plan ID from the request
            $subscription_id = $this->request->getVar('subscription_id');  
            $sub_item_id = $this->request->getVar('sub_item_id');  

            if($stripe_plan_id){
                
                $packageInfo = $packageDetailModel->where('stripe_plan_id', $stripe_plan_id)->first();
                if($packageInfo){
                    $package_id  = $packageInfo['package_id'];
                }

                $subscription = $stripe->subscriptions->update(
                    $subscription_id,
                    [
                      'items' => [
                        [
                          'id' => $sub_item_id,
                          'price' => $stripe_plan_id,
                        ],
                      ],
                    ]
                  );

                if($subscription){
                    // return redirect()->to('/success')->with('message', 'Subscription created successfully');

                    $response = [
                        "status"    => true,
                        "message"   => lang('App.subscriptionCreatedSuccess'),
                        "data"      => ['res' => $subscription ]
                    ];
                } else {
                    // return redirect()->to('/error')->with('error', $e->getMessage());
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.error'),
                        "data"      => ['error' => $e->getMessage()]
                    ];
                }
            }

        } catch (\Exception $e) {
            // return redirect()->to('/error')->with('error', $e->getMessage());
            $response = [
                "status"    => false,
                "message"   => lang('App.stripeError'),
                "data"      => ['error' => $e->getMessage()]
            ];
        }
    }


    public function createStripeCustomer() {
        $stripe = new \Stripe\StripeClient(getenv('stripe.secret.key'));
        
        $email = auth()->user()->email;
        $name = auth()->user()->first_name . ' ' . auth()->user()->last_name;

        $customerExist =  $stripe->customers->search(
            [
             'query' => 'email:\''.$email.'\'',
             'limit' => 1
            ]
            
         );
         
         // check if customer exist
         if( $customerExist && (sizeof($customerExist->data) > 0)){    

            $customerID = $customerExist->data[0]->id;

            $response = [
                "status"    => true,
                "message"   => lang('App.customerExist'),
                "data"      => [
                    'customer_id'    => $customerID
                ]
            ];

         } else {

            // create new customer if not exist
            $newCustomerData = [
                'email'     => $email,
                'name'      => $name,
                // 'payment_method' => $paymentMethod,
                // 'invoice_settings' => [
                //     'default_payment_method' => $paymentMethod,
                // ],
                'metadata' => ['soccer_user_id' => auth()->id() ],
            ];

            // Create a new customer
            $customer = $stripe->customers->create($newCustomerData);
            $customerID = $customer->id;

            $users = auth()->getProvider();

            $user = auth()->user();
            $user->fill([
                'stripe_customer_id' => $customerID,
            ]);
            $users->save($user);

            $response = [
                "status"    => true,
                "message"   => lang('App.customerCreatedSuccess'),
                "data"      => [
                    'customer_id'    => $customerID
                ]
            ];
        }

        return $this->respondCreated($response);
    }

    public function createPaymentIntent($package_id = null) {
            
            // code to get user currency by user location 
            // $ipData = @json_decode(file_get_contents( "http://www.geoplugin.net/json.gp?ip=" . $_SERVER['REMOTE_ADDR'] )); 
            // $domain_country_code = $this->db->domainModel->select('domain_country_code')->findAll();
            // $country_codes = array_column($domain_country_code, 'domain_country_code');
            // $currency = 'CHF';
            // if($ipData){
            //     if(in_array($ipData->geoplugin_countryCode, $country_codes)){
            //         $currency = $ipData->geoplugin_currencyCode;
            //     }
            // } exit;

        if($package_id){

            $isPackageActive = $this->db->userSubscriptionModel->where('package_id', $package_id)
                                                            ->where('user_id', auth()->id())
                                                            ->where('status', 'active')
                                                            ->first();
            if($isPackageActive){
                $response = [
                    "status"    => false,
                    "message"   => lang('App.packageAlreadyActivated'),
                    "data"      => [
                        'package_detail'    => $isPackageActive
                    ]
                ];
            } else {                                               
                $packageInfo = $this->db->packageDetailModel->where('id', $package_id)->first();
                if($packageInfo){

                    try {

                        // get customer info, create if not exist 
                        $customerData = $this->createStripeCustomer();
                        $responseBody = $customerData->getBody(); // Get the body of the response

                        // Decode the JSON string into an associative array
                        $customerInfo = json_decode($customerData->getBody(), true);
                        $customerID = $customerInfo['data']['customer_id'];
                        $currency = getUserCurrency(auth()->id());
                    
                        $stripe = new \Stripe\StripeClient(getenv('stripe.secret.key'));

                        // create session data
                        $intentData = [
                            'client_reference_id'   => $customerID,
                            'success_url'   => $this->request->getVar('successUrl') ?? null,
                            'cancel_url'    => $this->request->getVar('cancelUrl') ?? null,
                            // 'allow_promotion_codes' => true,
                            'line_items'    => [
                                [
                                'price'     => $packageInfo['stripe_plan_id'],
                                'quantity'  => 1
                                ],
                            ],
                            'mode'      => 'subscription',
                            'currency'  => $currency,
                            'subscription_data' => [  // Here we attach metadata directly to the subscription object
                                'metadata' => [
                                    'user_id'           => auth()->id(),  // Add necessary metadata
                                    'package_id'        => $package_id,
                                    'user_currency'     => $currency,
                                    'user_email'        => auth()->user()->email,
                                ]
                            ],
                            'customer' => $customerID,
                        ];

                        //apply coupon
                        if($this->request->getVar('coupon_code')){
                            $coupon_code = $this->request->getVar('coupon_code');
                            $isValidCoupon = isValidCoupon($coupon_code);       // check if valid coupon
                            if($isValidCoupon && $isValidCoupon['isValid'] == true){
                                $intentData['discounts'][]['coupon'] = $coupon_code;
                            } else {
                                $response = [
                                    "status"    => false,
                                    "message"   => lang('App.invalidCouponApplied'),
                                    "data"      => [
                                        'error' => $isValidCoupon['message']
                                    ]
                                ];
                                return $this->respondCreated($response);
                            }
                        }

                        // add booster target audience details
                        if(in_array($package_id, BOOSTER_PACKAGES) && !empty($this->request->getVar('booster_audience'))){
                            $intentData['subscription_data']['metadata']['booster_audience'] = $this->request->getVar('booster_audience');
                        }

                        // create session
                        $paymentIntent = $stripe->checkout->sessions->create($intentData);

                        if($paymentIntent){
                            $response = [
                                "status"    => true,
                                "message"   => lang('App.intentCreatedSuccess'),
                                "data"      => ['payment_intent' => $paymentIntent]
                            ]; 
                        } else {
                            $response = [
                                "status"    => false,
                                "message"   => lang('App.intentCreateFailed'),
                                "data"      => []
                            ]; 
                        }

                    } catch (\Exception $e) {
                        // return redirect()->to('/error')->with('error', $e->getMessage());
                        $response = [
                            "status"    => false,
                            "message"   => lang('App.stripeError'),
                            "data"      => ['error' => $e->getMessage()]
                        ];
                    }
                } else {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.invalidPackageId'),
                        "data"      => []
                    ]; 
                }
            }
        } else {
            $response = [
                "status"    => false,
                "message"   => lang('App.providePackageId'),
                "data"      => []
            ]; 
        }
        return $this->respondCreated($response);
    }


    public function validateCoupon(){

        $inputData = $this->request->getVar();
        $rules = [ 'coupon_code'   => 'required' ];

        if (! $this->validateData($inputData, $rules)) {
            
            $response = [
                "status"    => false,
                "message"   => lang('App.error'),
                "data"      => ['error' => $this->validator->getErrors()]
            ];

        } else {

            $isValidCoupon = isValidCoupon($this->request->getVar('coupon_code'));

            if($isValidCoupon && $isValidCoupon['isValid'] == true){
                $response = [
                    "status"    => true,
                    "message"   => lang('App.couponValid')
                ];
            } else {
                $response = [
                    "status"    => false,
                    "message"   => lang('App.invalidCouponApplied'),
                    "data"      => [
                        'error' => $isValidCoupon['message']
                    ]
                ];
            }
        }

        return $this->respondCreated($response);
    }


    public function handle()
    {

        $payload = @file_get_contents('php://input');
        $event = null;
        $this->db = db_connect();
        $userSubscriptionModel = new UserSubscriptionModel();
        $packageModel = new PackageModel();
        $paymentMethodModel = new PaymentMethodModel();


        try {
            $event = \Stripe\Event::constructFrom(
                json_decode($payload, true)
            );
        } catch(\UnexpectedValueException $e) {
            // Invalid payload
            http_response_code(400);
            exit();
        }

        // Handle the event
        switch ($event->type) {

            /* case 'invoice.payment_succeeded':

                $invoice = $event->data->object;
                $invoiceData = [];
                $fileName = $content = '';
                $adminMessage = '';
                $adminSubject = 'invoice.payment_succeeded '. $invoice->id;

                if($invoice){

                        // get latest subscription detail from DB
                        $subscriptionData           =  $userSubscriptionModel->where('latest_invoice', $invoice->id)->first();
                        // $adminMessage .= '>>> subscriptionData >>>> <pre>'.print_r($subscriptionData, true).'</pre>' ;

                        $payment_method  = $invoice->payment_intent;
                        // echo '>>>>>>>>>>>>>>>>>>>>>>>>>>>>> payment_method >>>>>>  ' . $payment_method;
                        $adminMessage .= '>>> payment_method >>>> '.$payment_method ;


                        $subscriptionID             = $subscriptionData['id'];
                        $user_id                    = $subscriptionData['user_id'];

                        $invoice_status             = $invoice['status'];
                        $invoice_number             = $invoice['number'];
                        $amount_paid                = $invoice['amount_paid'] / 100;
                        $amount_paid_currency       = $invoice['currency'];

                        $lines = $invoice->lines->data; // Assuming $stripeCollection is the Stripe\Collection object
                        $total_count = $invoice->total_count; 
                        
                        $hasProration = false; // Initialize a flag
                        $proration = [];
                        foreach ($lines as $lineItem) {
                            if (isset($lineItem->proration) && $lineItem->proration == 1) {
                                $hasProration = true;
                                $proration = $lineItem;
                                break; // Stop loop if proration is found
                            }
                        }

                        if($invoice->discount != NULL){
                            $coupon_used                = $invoice->discount->coupon->id;
                        }

                        $invoiceData = array( 
                            'id'                        => $subscriptionID,
                            'invoice_status'            => $invoice_status,
                            'invoice_number'            => $invoice_number,
                            'amount_paid'               => $amount_paid,
                            'amount_paid_currency'      => $amount_paid_currency
                        );


                        if($invoice_status == 'paid' ){
                            $invoiceData['status'] = 'active';
                        }

                        // save coupon if used
                        if(!empty($coupon_used)){
                            $invoiceData['coupon_used'] = $coupon_used;
                        }

                        if($proration){ 
                            $proration_amount                       = $proration->amount/100;
                            $proration_currency                     = $proration->currency;
                            $proration_description                  = $proration->description;

                            $invoiceData['proration_amount']        = $proration_amount;
                            $invoiceData['proration_currency']      = $proration_currency;
                            $invoiceData['proration_description']   = $proration_description;
                        }

                        $userSubscriptionModel->save($invoiceData);
                    //}

                    $fileName = 'invoice_'. $subscriptionID .'_' .$user_id . '.pdf';

                

               
                    // $adminMessage .= ' >>> invoice >>>>  <pre>'.print_r($invoice, true).'</pre>' ;
                    // $adminMessage .= '>>> proration >>>> <pre>'.print_r($proration, true).'</pre>' ;
                    // $adminMessage .= ' >>> invoiceData >>>>  <pre>'.print_r($invoiceData, true).'</pre>' ;
                    


                    // Generate PDF
                    $defaultConfig = (new Mpdf\Config\ConfigVariables())->getDefaults();
                    $fontDirs = $defaultConfig['fontDir'];

                    $defaultFontConfig = (new Mpdf\Config\FontVariables())->getDefaults();
                    $fontData = $defaultFontConfig['fontdata'];

                    $mpdf_data = [
                        'debug' => true,
                        // 'allow_output_buffering' => true,
                        // 'showImageErrors'        => true,
                        // 'text_input_as_HTML'     => true,
                        
                        'fontDir' => array_merge($fontDirs, [
                            FCPATH . '/public/assets/fonts',
                        ]),
                        'fontdata' => $fontData + [ // lowercase letters only in font key
                            'roboto' => [
                                'R' => 'Roboto-Regular.ttf',
                                'I' => 'Roboto-Regular.ttf',
                                'B' => 'Roboto-Bold.ttf',
                            ]
                        ],
                        'default_font' => 'roboto'
                    
                    ];
                    $mpdf = new \Mpdf\Mpdf($mpdf_data);

                    $content = getInvoicePdfContent($subscriptionID);

                    //$stylesheet = file_get_contents(FCPATH .'public/assets/css/user-profile-pdf.css'); // external css

                    $mpdf->WriteHTML($content);

                    $mpdf->Output(WRITEPATH . 'uploads/documents/'.$fileName, "F"); 
                    
                    $userSubscriptionModel->update($subscriptionID, ['invoice_file' => $fileName]);                 
                    
                    $adminEmailData = [
                        'fromEmail'     => FROM_EMAIL,
                        'fromName'      => FROM_NAME,
                        'toEmail'       => 'pratibhaprajapati.cts@gmail.com',
                        'subject'       => $adminSubject,
                        'message'       => $adminMessage,
                    ];
                    sendEmail($adminEmailData);
                }
            break; */


            case 'invoice.payment_succeeded':
                
                $invoice = $event->data->object;
                $invoiceData = [];
                $fileName = $content = '';
                $adminMessage = '';
                $adminSubject = 'invoice.payment_succeeded '. $invoice->id;

                if($invoice){

                    // get latest subscription detail from DB
                    $subscriptionData           =  $userSubscriptionModel->where('latest_invoice', $invoice->id)->first();
                    $adminMessage .= '>>> subscriptionData >>>> <pre>'.print_r($subscriptionData, true).'</pre>' ;

                    // $payment_method  = $invoice->payment_intent;
                    // // echo '>>>>>>>>>>>>>>>>>>>>>>>>>>>>> payment_method >>>>>>  ' . $payment_method;
                    // $adminMessage .= '>>> payment_method >>>> '.$payment_method ;
                    

                    $invoice_status             = $invoice['status'];
                    $invoice_number             = $invoice['number'];
                    $amount_paid                = $invoice['amount_paid'] / 100;
                    $amount_paid_currency       = $invoice['currency'];

                    $stripe_subscription_id     = $invoice['subscription'];
                    $invoice_metadata           = $invoice['subscription_details']['metadata'];
                    $package_id                 = $invoice_metadata['package_id'];
                    $payer_email                = $invoice_metadata['user_email'];
                    $user_id                    = $invoice_metadata['user_id'];
                    $stripe_customer_id         = $invoice['customer'];

                    $lines = $invoice->lines->data; // Assuming $stripeCollection is the Stripe\Collection object
                    // $total_count = $invoice->total_count; 
                    

                    $hasProration = false; // Initialize a flag
                    $proration = [];
                    foreach ($lines as $lineItem) {
                        if (isset($lineItem->proration) && $lineItem->proration == 1) {
                            $hasProration = true;
                            $proration = $lineItem;
                            break; // Stop loop if proration is found
                        }
                    } 
                    
                    // $invoiceData = array( 
                    //     'id'                        => $subscriptionID,
                    //     'invoice_status'            => $invoice_status,
                    //     'invoice_number'            => $invoice_number,
                    //     'amount_paid'               => $amount_paid,
                    //     'amount_paid_currency'      => $amount_paid_currency
                    // );


                    // 'plan_amount'                   => $plan_amount, 
                    // 'plan_amount_currency'          => $plan_amount_currency, 
                    // 'plan_interval'                 => $plan_interval, 
                    // 'plan_interval_count'           => $plan_interval_count, 
                    // 'plan_period_start'             => $plan_period_start, 
                    // 'plan_period_end'               => $plan_period_end, 
                    // sub_item_id

                    $invoiceData['invoice_status']              = $invoice_status;
                    $invoiceData['invoice_number']              = $invoice_number;
                    $invoiceData['amount_paid']                 = $amount_paid;
                    $invoiceData['amount_paid_currency']        = $amount_paid_currency;

                    $invoiceData['stripe_subscription_id']      = $stripe_subscription_id;
                    $invoiceData['latest_invoice']              = $invoice->id;
                    $invoiceData['stripe_customer_id']          = $stripe_customer_id;
                    $invoiceData['user_id']                     = $user_id; 

                    $stripePlanData = $lines[0]['plan'];
                    $adminMessage .= '>>> stripePlanData >>>> <pre>'.print_r($stripePlanData, true).'</pre>' ;

                    // $invoiceData['stripe_plan_id']              = $stripePlanData['id'];
                    // $invoiceData['plan_amount']                 = $stripePlanData['amount']/100;
                    // $invoiceData['plan_amount_currency']        = $stripePlanData['currency'];
                    // $invoiceData['plan_interval']               = $stripePlanData['interval'];
                    // $invoiceData['plan_interval_count']         = $stripePlanData['interval_count'];
                    // $invoiceData['plan_period_start']           = date("Y-m-d H:i:s", $invoice['period_start']);
                    // $invoiceData['plan_period_end']             = date("Y-m-d H:i:s", $invoice['period_end']);
                    // $invoiceData['sub_item_id']                 = $lines[0]['subscription_item'];

                    if( ($subscriptionData && $subscriptionData['status'] == 'incomplete') &&  $invoice_status == 'paid' ){
                        $invoiceData['status'] = 'active';
                    }

                    // save coupon if used
                    if($invoice->discount != NULL){
                        $coupon_used    = $invoice->discount->coupon->id;

                        if(!empty($coupon_used)){
                            $invoiceData['coupon_used'] = $coupon_used;
                        }
                    }
                    

                    if($proration){ 
                        $proration_amount                       = $proration->amount/100;
                        $proration_currency                     = $proration->currency;
                        $proration_description                  = $proration->description;

                        $invoiceData['proration_amount']        = $proration_amount;
                        $invoiceData['proration_currency']      = $proration_currency;
                        $invoiceData['proration_description']   = $proration_description;
                    }

                    if($subscriptionData){
                        $subscriptionID     = $subscriptionData['id'];

                       

                        $invoiceData['id']  = $subscriptionID;
                    }
                    
                    // save booster audience data if "booster_audience" key receved in metadata
                    if($invoice_metadata['booster_audience']){

                        $booster_audience = explode(",", $invoice_metadata['booster_audience']);
                        $audienceData = [];
                        foreach($booster_audience as $audience){

                            $getAudience = $this->db->boosterAudienceModel->where('subscription_id', $stripe_subscription_id)
                                                                        ->where('user_id', $user_id)
                                                                        ->where('target_role', $audience)
                                                                        ->first();
                            if(!$getAudience){
                                $audienceData[] = [
                                    'subscription_id'   => $stripe_subscription_id,
                                    'user_id'           => $user_id,
                                    'target_role'       => $audience
                                ];
                            }
                            
                        }

                        if(count($audienceData) > 0){
                            $this->db->boosterAudienceModel->insertBatch($audienceData);
                        }
                    }

                    $userSubscriptionModel->save($invoiceData);

                    $fileName = 'invoice_'. $subscriptionID .'_' .$user_id . '.pdf';

                

               
                    // $adminMessage .= ' >>> invoice >>>>  <pre>'.print_r($invoice, true).'</pre>' ;
                    // $adminMessage .= '>>> proration >>>> <pre>'.print_r($proration, true).'</pre>' ;
                    // $adminMessage .= ' >>> invoiceData >>>>  <pre>'.print_r($invoiceData, true).'</pre>' ;
                    


                    // Generate PDF
                    $defaultConfig = (new Mpdf\Config\ConfigVariables())->getDefaults();
                    $fontDirs = $defaultConfig['fontDir'];

                    $defaultFontConfig = (new Mpdf\Config\FontVariables())->getDefaults();
                    $fontData = $defaultFontConfig['fontdata'];

                    $mpdf_data = [
                        'debug' => true,
                        // 'allow_output_buffering' => true,
                        // 'showImageErrors'        => true,
                        // 'text_input_as_HTML'     => true,
                        
                        'fontDir' => array_merge($fontDirs, [
                            FCPATH . '/public/assets/fonts',
                        ]),
                        'fontdata' => $fontData + [ // lowercase letters only in font key
                            'roboto' => [
                                'R' => 'Roboto-Regular.ttf',
                                'I' => 'Roboto-Regular.ttf',
                                'B' => 'Roboto-Bold.ttf',
                            ]
                        ],
                        'default_font' => 'roboto'
                    
                    ];
                    $mpdf = new \Mpdf\Mpdf($mpdf_data);

                    $content = getInvoicePdfContent($subscriptionID);

                    //$stylesheet = file_get_contents(FCPATH .'public/assets/css/user-profile-pdf.css'); // external css

                    $mpdf->WriteHTML($content);

                    $mpdf->Output(WRITEPATH . 'uploads/documents/'.$fileName, "F"); 
                    
                    $userSubscriptionModel->update($subscriptionID, ['invoice_file' => $fileName]);                 
                    
                    $adminEmailData = [
                        'fromEmail'     => FROM_EMAIL,
                        'fromName'      => FROM_NAME,
                        'toEmail'       => 'pratibhaprajapati.cts@gmail.com',
                        'subject'       => $adminSubject,
                        'message'       => $adminMessage,
                    ];
                    sendEmail($adminEmailData);
                }
            break;


            case 'invoice.payment_failed':
                $invoice = $event->data->object;
                $invoiceData = [];

                if($invoice){
                        $subscriptionData           =  $userSubscriptionModel->where('latest_invoice', $invoice->id)->first();
                        $subscriptionID             = $subscriptionData['id'];
                        $invoice_status             = $invoice['status'];
                        $invoice_number             = $invoice['number'];
                        $amount_paid                = $invoice['amount_paid'] / 100;
                        $amount_paid_currency       = $invoice['currency'];

                        if($invoice->discount != NULL){
                            $coupon_used                = $invoice->discount->coupon->id;
                        }


                        $invoiceData = array( 
                            'id'                        => $subscriptionID,
                            'invoice_status'            => $invoice_status,
                            'invoice_number'            => $invoice_number,
                            'amount_paid'               => $amount_paid,
                            'amount_paid_currency'      => $amount_paid_currency
                        );

                        // save coupon if used
                        if(!empty($coupon_used)){
                            $invoiceData['coupon_used'] = $coupon_used;
                        }

                        $userSubscriptionModel->save($invoiceData);
                    //}
                }

                $adminMessage = '';
                $adminSubject = 'invoice.payment_failed '. $invoice->id;
                //$adminMessage .= '>>> invoice >>>> <pre>'.print_r($subscription, true).'</pre>' ;
                $adminMessage .= ' >>> invoice >>>>  <pre>'.print_r($invoice, true).'</pre>' ;
                $adminMessage .= ' >>> invoiceData >>>>  <pre>'.print_r($invoiceData, true).'</pre>' ;
                
                //$adminMessage .= ' >>> stripe_subscription_id status >>>>  ' . $subscription->status ;
                // $adminMessage .= ' >>> plan >>>>  <pre>'.print_r($packageInfo, true).'</pre>' ;
                // $adminMessage .= ' >>> last_query >>>>  <pre>'.$getLastQuery.'</pre>' ;
                 
                
                $adminEmailData = [
                    'fromEmail'     => FROM_EMAIL,
                    'fromName'      => FROM_NAME,
                    'toEmail'       => 'pratibhaprajapati.cts@gmail.com',
                    'subject'       => $adminSubject,
                    'message'       => $adminMessage,
                ];
                sendEmail($adminEmailData);

            break;


            case 'customer.subscription.created':
                $subscription = $event->data->object;
                $event_id = $event->id;
                $adminMessage = '';

                if($subscription){
                    
                    // Subscription info 
                    $stripe_subscription_id             = $subscription['id']; 
                    $stripe_customer_id                 = $subscription['customer']; 
                    $stripe_plan_id                     = $subscription['plan']['id']; 
                    $plan_amount                        = ($subscription['plan']['amount']/100); 
                    $plan_amount_currency               = $subscription['plan']['currency']; 
                    $plan_interval                      = $subscription['plan']['interval']; 
                    $plan_interval_count                = $subscription['plan']['interval_count']; 
                    $subscription_created_at            = date("Y-m-d H:i:s", $subscription['created']); 
                    $plan_period_start                  = date("Y-m-d H:i:s", $subscription['current_period_start']); 
                    $plan_period_end                    = date("Y-m-d H:i:s", $subscription['current_period_end']); 
                    $status                             = $subscription['status']; 
                    $sub_item_id                        = $subscription['items']['data'][0]->id; 
                    
                    
                    $latest_invoice                     = $subscription['latest_invoice']; 
                    $user_id                            = $subscription['metadata']['user_id']; 
                    $package_id                         = $subscription['metadata']['package_id']; 
                    $user_email                         = $subscription['metadata']['user_email']; 

                    
                    // Insert transaction data into the database 
                    $subscripData = array( 
                        'user_id'                       => $user_id, 
                        'package_id'                    => $package_id, 
                        'stripe_subscription_id'        => $stripe_subscription_id, 
                        'stripe_customer_id'            => $stripe_customer_id, 
                        'stripe_plan_id'                => $stripe_plan_id, 
                        'plan_amount'                   => $plan_amount, 
                        'plan_amount_currency'          => $plan_amount_currency, 
                        'plan_interval'                 => $plan_interval, 
                        'plan_interval_count'           => $plan_interval_count, 
                        'plan_period_start'             => $plan_period_start, 
                        'plan_period_end'               => $plan_period_end, 
                        'payer_email'                   => $user_email, 
                        'subscription_created_at'       => $subscription_created_at, 
                        'event_id'                      => $event_id,
                        'latest_invoice'                => $latest_invoice,
                        // 'invoice_status'                => $status,  // not recieving in created webhook
                        'status'                        => $status, 
                        'sub_item_id'                   => $sub_item_id
                    ); 

                    // check if subscription data already inserted
                    $subscriptionExist = $userSubscriptionModel->where('latest_invoice', $subscription['latest_invoice'])
                                                            ->orWhere('stripe_subscription_id', $subscription['id'])
                                                            ->first();
                                                            
                    if($subscriptionExist){
                        $subscripData['id'] = $subscriptionExist['id'];
                    }

                    $adminMessage .= ' >>> subscripData >>>>>>>> <pre>'.print_r($subscripData, true).'</pre>' ;
                    $userSubscriptionModel->save($subscripData);

                    // add target audience data for booster plan
                    if($subscription['metadata']['booster_audience']){
                        $booster_audience = explode(",", $subscription['metadata']['booster_audience']);
                        $audienceData = [];
                        foreach($booster_audience as $audience){

                            $getAudience = $this->db->boosterAudienceModel->where('subscription_id', $stripe_subscription_id)
                                                            ->where('user_id', $user_id)
                                                            ->where('target_role', $audience)
                                                            ->first();
                            if(!$getAudience){
                                $audienceData[] = [
                                    'subscription_id'   => $stripe_subscription_id,
                                    'user_id'           => $user_id,
                                    'target_role'       => $audience
                                ];
                            }
                        }

                        if(count($audienceData) > 0){
                            $this->db->boosterAudienceModel->insertBatch($audienceData);
                        }
                    }

                    if($subscription['status'] == 'active'){
                        // create Activity log
                        $user_info = getUserByID($user_id);
                        $package_info = getPackageInfo($package_id);
                        // $adminMessage .= ' >>> package_info >>>>>>> <pre>'.print_r($package_info, true).'</pre>' ;

                        $activity = 'purchased ' . $package_info->package_name . ' ( '. $package_info->interval .' ) plan. Subscription ID: ' . $stripe_subscription_id;
                        $activity_data = [
                            'user_id'               => $user_id,
                            'activity_type_id'      => 1,        // created
                            'activity'              => $activity,
                            'ip'                    => $this->request->getIPAddress()
                        ];
                        createActivityLog($activity_data);
                    }
                    

                    $adminSubject = 'customer.subscription.created - '. $subscription->id;
                    $adminMessage .= ' >>> subscription >>>>>>> <pre>'.print_r($subscription, true).'</pre>' ;
                    // $adminMessage .= ' >>> items Data >>>>>>>> <pre>'.print_r($subscription['items']['data'], true).'</pre>' ;
                    
                    $adminEmailData = [
                        'fromEmail'     => FROM_EMAIL,
                        'fromName'      => FROM_NAME,
                        'toEmail'       => 'pratibhaprajapati.cts@gmail.com',
                        'subject'       => $adminSubject,
                        'message'       => $adminMessage,
                    ];
                    sendEmail($adminEmailData);
                } 

                

            break;
            

            case 'customer.subscription.updated':
                $subscription = $event->data->object;
                $event_id = $event->id;
                $adminMessage = '';

                //previous_attributes
                $previous_attributes = $event->data->previous_attributes;
                // $adminMessage .= ' >>> subscription->previous_attributes >>>>>>>> <pre>'.print_r($previous_attributes, true).'</pre>' ;
                

                if($subscription){
                    $adminSubject = 'customer.subscription.updated - '. $subscription->id;
                    $adminMessage .= ' >>> subscription >>>>>>> <pre>'.print_r($subscription, true).'</pre>' ;

                    $stripe_subscription_id             = $subscription['id']; 
                    $stripe_customer_id                 = $subscription['customer']; 
                    $stripe_plan_id                     = $subscription['plan']['id']; 
                    $plan_amount                        = ($subscription['plan']['amount']/100); 
                    $plan_amount_currency               = $subscription['plan']['currency']; 
                    $plan_interval                      = $subscription['plan']['interval']; 
                    $plan_interval_count                = $subscription['plan']['interval_count']; 
                    $subscription_created_at            = date("Y-m-d H:i:s", $subscription['created']); 
                    $plan_period_start                  = date("Y-m-d H:i:s", $subscription['current_period_start']); 
                    $plan_period_end                    = date("Y-m-d H:i:s", $subscription['current_period_end']); 
                    $status                             = $subscription['status']; 
                    $default_payment_method             = $subscription['default_payment_method']; 
                    // $cancel_at_period_end               = $subscription['cancel_at_period_end']; 
                    
                    
                    $latest_invoice                     = $subscription['latest_invoice']; 
                    $user_id                            = $subscription['metadata']['user_id']; 
                    $package_id                         = $subscription['metadata']['package_id']; 
                    $user_email                         = $subscription['metadata']['user_email'];  
                    $sub_item_id                        = $subscription['items']['data'][0]->id;


                   


                    // CASE : 1
                    // update "incomplete" status to final status for first time
                    // this data will be recieved when subscription status will be updated for first time
                    if($previous_attributes && $previous_attributes['status'] == "incomplete"){
                        $adminMessage .= ' >>>>>>>>>>>>>>>>>>>>>>>>>>>> CASE : 1 triggered >>>>>>>>>>>>>>>>>>>>>>>>>' ;

                        // check latest subscription with "incomplete" status
                        $pendingSubscription = $userSubscriptionModel
                                                    ->where('user_id', $user_id)
                                                    ->where('stripe_subscription_id', $subscription['id'] )
                                                    // ->where('package_id', $package_id)
                                                    ->where('latest_invoice', $latest_invoice)
                                                    ->where('status', 'incomplete')
                                                    ->first();

                        // update final status
                        if($pendingSubscription){  
                            $adminMessage .= ' >>> CASE : 1 last pendingSubscription >>>>>>>> <pre>'.print_r($pendingSubscription, true).'</pre>' ;

                            // $subscripData = array( 
                            //     'id'                        => $pendingSubscription['id'],
                            //     'payment_method_id'         => $default_payment_method,
                            //     'status'                    => $status 
                            // );

                            $subscripData = array( 
                                'id'                            => $pendingSubscription['id'],
                                'user_id'                       => $user_id, 
                                'package_id'                    => $package_id, 
                                'stripe_subscription_id'        => $stripe_subscription_id, 
                                'stripe_customer_id'            => $stripe_customer_id, 
                                'stripe_plan_id'                => $stripe_plan_id, 
                                'plan_amount'                   => $plan_amount, 
                                'plan_amount_currency'          => $plan_amount_currency, 
                                'plan_interval'                 => $plan_interval, 
                                'plan_interval_count'           => $plan_interval_count, 
                                'plan_period_start'             => $plan_period_start, 
                                'plan_period_end'               => $plan_period_end, 
                                'payer_email'                   => $user_email, 
                                'subscription_created_at'       => $subscription_created_at, 
                                'event_id'                      => $event_id,
                                'latest_invoice'                => $latest_invoice,
                                'payment_method_id'             => $default_payment_method,
                                'sub_item_id'                   => $sub_item_id,
                                'status'                        => $status 
                            ); 

                            // $subscripData['id']                 = $pendingSubscription['id'];
                            // $subscripData['payment_method_id']  = $default_payment_method;
                            // $subscripData['status']             = $status;

                            $userSubscriptionModel->save($subscripData);

                            // create Activity log
                            // $user_info = getUserByID($user_id);
                            // $package_info = getPackageInfo($package_id);

                            // $activity = 'purchased ' . $package_info->package_name . ' ( '. $package_info->interval .' ) plan. Subscription ID: ' . $stripe_subscription_id;
                            // $activity_data = [
                            //     'user_id'               => $user_id,
                            //     'activity_type_id'      => 1,        // created
                            //     'activity'              => $activity,
                            //     'ip'                    => $this->request->getIPAddress()
                            // ];
                            // createActivityLog($activity_data);
                        }
                    }

                    // CASE : 2
                    // insert all subscription data whenever subscription renews 
                    if($previous_attributes && !empty($previous_attributes['latest_invoice'])){
                        $adminMessage .= ' >>>>>>>>>>>>>>>>>>>>>>>>>>>> CASE : 2 triggered >>>>>>>>>>>>>>>>>>>>>>>>>' ;

                        // update status to expired if already have any active package
                        $lastSubscription = $userSubscriptionModel
                                                ->where('user_id', $user_id)
                                                ->where('stripe_subscription_id', $subscription['id'] )
                                                ->where('latest_invoice', $previous_attributes['latest_invoice'])
                                                ->where('status', 'active')
                                                ->first();

                        $adminMessage .= ' >>> CASE : 2 lastSubscription query >>>>>>>> '. $this->db->getLastQuery() ;

                        // set status = expired if found active subscription
                        if($lastSubscription){
                            $adminMessage .= ' >>> CASE : 2 last subscripData >>>>>>>> <pre>'.print_r($lastSubscription, true).'</pre>' ;
    
                            $lastId = $lastSubscription['id'];
                            $upData = ['status' => 'expired'];
                            $userSubscriptionModel->update($lastId, $upData);
                        }

                        // Insert transaction data into the database 
                        $subscripData = array( 
                            'user_id'                       => $user_id, 
                            'package_id'                    => $package_id, 
                            'stripe_subscription_id'        => $stripe_subscription_id, 
                            'stripe_customer_id'            => $stripe_customer_id, 
                            'stripe_plan_id'                => $stripe_plan_id, 
                            'plan_amount'                   => $plan_amount, 
                            'plan_amount_currency'          => $plan_amount_currency, 
                            'plan_interval'                 => $plan_interval, 
                            'plan_interval_count'           => $plan_interval_count, 
                            'plan_period_start'             => $plan_period_start, 
                            'plan_period_end'               => $plan_period_end, 
                            'payer_email'                   => $user_email, 
                            'subscription_created_at'       => $subscription_created_at, 
                            'event_id'                      => $event_id,
                            'latest_invoice'                => $latest_invoice,
                            'payment_method_id'             => $default_payment_method,
                            'sub_item_id'                   => $sub_item_id,
                            'status'                        => $status 
                        ); 

                        $pendingSubscription = $userSubscriptionModel
                                                ->where('latest_invoice', $latest_invoice)
                                                ->first();

                        if($pendingSubscription){
                            $subscripData['id']     = $pendingSubscription['id'];
                        }

                        // add upgrade_downgrade_status details
                        if($previous_attributes && !empty($previous_attributes['plan']['id'])){

                            $previous_package_id = $previous_attributes['metadata']['package_id'];
                            // $user_currency = $previous_attributes['metadata']['user_currency'];
                            // $user_currency = 'GBP';

                            $previous_package_info = $this->db->packageDetailModel
                                                        ->select('package_details.*, pp.price as price, pp.currency as currency')
                                                        ->join('package_prices pp', 'pp.package_detail_id = package_details.id AND pp.currency = "'.$plan_amount_currency.'"')
                                                        ->where('package_details.id', $previous_package_id)
                                                        ->first();

                            // echo  ' >>> CASE : 2 previous_package_info query >>>>>>>> '. $this->db->getLastQuery() ;
                            $adminMessage .= ' >>> CASE : 2 previous_package_info query >>>>>>>> '. $this->db->getLastQuery() ;

                            $adminMessage .= ' >>> CASE : 2 previous_package_info >>>>>>>> <pre>'.print_r($previous_package_info, true).'</pre>' ;

                            if($previous_package_info){
                                $previousPlanPrice = $previous_package_info['price'];
                            }

                            $subscripData['previous_package_id']        = $previous_package_id;
                            $subscripData['upgrade_downgrade_status']   = $plan_amount > $previousPlanPrice ? 'upgraded' : 'downgraded';
                        }

                        $adminMessage .= ' >>> CASE : 2 subscripData >>>>>>>> <pre>'.print_r($subscripData, true).'</pre>' ;

                        $userSubscriptionModel->save($subscripData);
                    }


                    // CASE : 3
                    // Cancle subscription  
                    if($subscription['cancel_at_period_end'] && $subscription['cancel_at_period_end'] == true){ 

                        // check latest subscription with "incomplete" status
                        $lastSubscription = $userSubscriptionModel
                                                ->where('user_id', $user_id)
                                                ->where('stripe_subscription_id', $subscription['id'] )
                                                ->where('latest_invoice', $latest_invoice)
                                                ->first();

                        $adminMessage .= ' >>> cancalData getLastQuery >>>>>>>> ' . $this->db->getLastQuery;

                        $cancalData = [
                            'id'                            => $lastSubscription['id'],
                            'stripe_cancel_at'              => date("Y-m-d H:i:s", $subscription['cancel_at']),
                            'stripe_canceled_at'            => date("Y-m-d H:i:s", $subscription['stripe_canceled_at']),
                            'canceled_at'                   => date('Y-m-d H:i:s'),
                            'cancellation_reason'           => $subscription['cancellation_details']->reason,
                            'status'                        => $status,
                        ];

                        $adminMessage .= ' >>> CASE : 3 cancalData lastSubscription >>>>>>>> <pre>'.print_r($cancalData, true).'</pre>' ;
                        //$adminMessage .= ' >>> cancalData getLastQuery >>>>>>>> ' . $getLastQuery;
                        $userSubscriptionModel->save($cancalData);


                    }
                }


                $adminEmailData = [
                    'fromEmail'     => FROM_EMAIL,
                    'fromName'      => FROM_NAME,
                    'toEmail'       => 'pratibhaprajapati.cts@gmail.com',
                    'subject'       => $adminSubject,
                    'message'       => $adminMessage,
                ];
                sendEmail($adminEmailData);

            break;


            case 'customer.subscription.deleted':
                $subscription = $event->data->object;
                $event_id = $event->id;
                
                $adminSubject = 'customer.subscription.deleted';
                $adminMessage = '';
                $adminMessage .= '<pre>'.print_r($subscription, true).'</pre>' ;

                if($subscription){

                    $stripe_subscription_id     = $subscription['id']; 
                    $latest_invoice             = $subscription['latest_invoice']; 
                    $stripe_canceled_at         = date("Y-m-d H:i:s", $subscription['canceled_at']); 
                    $cancellation_reason        = $subscription['cancellation_details']['reason']; 
                    $stripe_cancel_at           = ($subscription['cancel_at'] != null ? date("Y-m-d H:i:s", $subscription['cancel_at']) : null); 

                    // check latest subscription
                    $existingSubscription = $userSubscriptionModel
                                            ->where('stripe_subscription_id', $stripe_subscription_id)
                                            ->where('latest_invoice', $latest_invoice)
                                            ->first();
                    
                    $adminMessage .= ' >>>>>>>>>> existingSubscription >>>>>>>>>>>> <pre>'.print_r($existingSubscription, true).'</pre>' ;
                    if($existingSubscription){

                        $save_data = [
                            'id'                    => $existingSubscription['id'],
                            'status'                => $subscription['status'],
                            'stripe_canceled_at'    => $stripe_canceled_at,
                            'stripe_cancel_at'      => $stripe_cancel_at,
                            'cancellation_reason'   => $cancellation_reason,
                            'canceled_at'           => date('Y-m-d H:i:s'),
                            // 'canceled_at'           
                        ];

                        // $adminMessage .= ' >>>>>>>>>> save_data >>>>>>>>>>>> <pre>'.print_r($save_data, true).'</pre>' ;

                        $userSubscriptionModel->save($save_data);
                        // $query = $this->db->getLastQuery();
                        // $adminMessage .= ' >>>>>>>>>> getLastQuery >>>>>>>>>>>> '. $this->db->getLastQuery() ;

                    }
                }


                $adminEmailData = [
                    'fromEmail'     => FROM_EMAIL,
                    'fromName'      => FROM_NAME,
                    'toEmail'       => 'pratibhaprajapati.cts@gmail.com',
                    'subject'       => $adminSubject,
                    'message'       => $adminMessage,
                ];
                sendEmail($adminEmailData);

            break;

            case 'customer.subscription.paused':
                $subscription = $event->data->object;

                $adminSubject = 'customer.subscription.paused';
                $adminMessage = '<pre>'.print_r($subscription, true).'</pre>' ;

                $adminEmailData = [
                    'fromEmail'     => FROM_EMAIL,
                    'fromName'      => FROM_NAME,
                    'toEmail'       => 'pratibhaprajapati.cts@gmail.com',
                    'subject'       => $adminSubject,
                    'message'       => $adminMessage,
                ];
                sendEmail($adminEmailData);

            break;


            case 'payment_intent.succeeded':
                $payment_intent = $event->data->object;

                $adminSubject = 'payment_intent.succeeded';
                $adminMessage = '';
                $adminMessage .= '<pre>'.print_r($payment_intent, true).'</pre>' ;

                if($payment_intent){
                    $latest_invoice = $payment_intent->invoice;
                    $payment_method = $payment_intent->payment_method;

                    $lastSubscription = $userSubscriptionModel->where('latest_invoice', $latest_invoice)->first();
                    $adminMessage .= ' >>>>>>>>>>>> lastSubscription >>>>>>>> <pre>'.print_r($lastSubscription, true).'</pre>' ;

                    $save_data = [
                        'payment_method_id' => $payment_method,
                        'latest_invoice'    => $latest_invoice
                    ];

                    if($lastSubscription){
                        $save_data['id']    = $lastSubscription['id'];
                    } 

                    $userSubscriptionModel->save($save_data);

                }

                $adminEmailData = [
                    'fromEmail'     => FROM_EMAIL,
                    'fromName'      => FROM_NAME,
                    'toEmail'       => 'pratibhaprajapati.cts@gmail.com',
                    'subject'       => $adminSubject,
                    'message'       => $adminMessage,
                ];
                sendEmail($adminEmailData);

            break;


            case 'payment_method.attached':
                $payment_method = $event->data->object;
                $adminMessage = '';
                $adminSubject = 'payment_method.attached';
                $adminMessage .= '<pre>'.print_r($payment_method, true).'</pre>';

                if($payment_method){

                    $isRecordExist =  $paymentMethodModel->where('stripe_payment_method_id', $payment_method->id)->first();
                    $adminMessage .= ' >>> isRecordExist query >>>>>>>> '. $this->db->getLastQuery() ;
                    $adminMessage .= ' >>>>>>>>>>>>>>> isRecordExist >>> <pre>'.print_r($isRecordExist, true).'</pre>';

                    if(!$isRecordExist){
                    
                        $stripe = new \Stripe\StripeClient(getenv('stripe.secret.key'));
                        $customerInfo = $stripe->customers->retrieve($payment_method->customer, []);
                        $adminMessage .= ' >>>>>>>>>>>>>>> customerInfo >>> <pre>'.print_r($customerInfo, true).'</pre>';

                        $user_id = 0;
                        if($customerInfo){
                            $user_id = $customerInfo->metadata->soccer_user_id;
                        }

                        $saveData = [
                            'user_id'                           => $user_id,
                            'stripe_payment_method_id'          => $payment_method->id,
                            'type'                              => $payment_method->type,
                            'brand'                             => $payment_method->card->brand,
                            'country'                           => $payment_method->card->country,
                            'display_brand'                     => $payment_method->card->display_brand,
                            'exp_month'                         => $payment_method->card->exp_month,
                            'exp_year'                          => $payment_method->card->exp_year,
                            'last4'                             => $payment_method->card->last4,
                            'funding'                           => $payment_method->card->funding,
                            'event_id'                          => $event->id,
                            'stripe_created_at'                 => date("Y-m-d H:i:s", $payment_method->created),
                        ];

                        // if no payment method added already, then set this as default
                        $paymentMethods = $paymentMethodModel->where('user_id', $user_id)->first();
                        if(!$paymentMethods){
                            $saveData['is_default'] = 1 ;
                        }

                        $paymentMethodModel->save($saveData);

                        $adminMessage .= ' >>>>>>>>>>>>>>> saveData >>> <pre>'.print_r($saveData, true).'</pre>';
                    }

                }


                $adminEmailData = [
                    'fromEmail'     => FROM_EMAIL,
                    'fromName'      => FROM_NAME,
                    'toEmail'       => 'pratibhaprajapati.cts@gmail.com',
                    'subject'       => $adminSubject,
                    'message'       => $adminMessage,
                ];
                sendEmail($adminEmailData);

            break;

            case 'payment_method.updated':
                $payment_method = $event->data->object;

                $adminMessage = '';
                $adminSubject = 'payment_method.updated >>>>>>> ' . $payment_method->id;
                $adminMessage .= '<pre>'.print_r($payment_method, true).'</pre>' ;

                if($payment_method){
                    $stripe = new \Stripe\StripeClient(getenv('stripe.secret.key'));
                    $customerInfo = $stripe->customers->retrieve($payment_method->customer, []);
                    $adminMessage .= ' >>>>>>>>>>>>>>> customerInfo >>> <pre>'.print_r($customerInfo, true).'</pre>';

                    $user_id = 0;
                    if($customerInfo){
                        $user_id = $customerInfo->metadata->soccer_user_id;
                    }


                    $saveData = [
                        'user_id'                           => $user_id,
                        'stripe_payment_method_id'          => $payment_method->id,
                        'type'                              => $payment_method->type,
                        'brand'                             => $payment_method->card->brand,
                        'country'                           => $payment_method->card->country,
                        'display_brand'                     => $payment_method->card->display_brand,
                        'exp_month'                         => $payment_method->card->exp_month,
                        'exp_year'                          => $payment_method->card->exp_year,
                        'last4'                             => $payment_method->card->last4,
                        'funding'                           => $payment_method->card->funding,
                        'event_id'                          => $event->id,
                        //'is_default'        => ,
                        'stripe_created_at'                 => date("Y-m-d H:i:s", $payment_method->created),
                    ];

                    // if no payment method added already, then set this as default
                    $paymentMethods = $paymentMethodModel->where('user_id', $user_id)->first();
                    if(!$paymentMethods){
                        $saveData['is_default'] = 1 ;
                    }

                    //  IF exist, then update same payment method 
                    $isExit = $paymentMethodModel->where('stripe_payment_method_id', $payment_method->id)->first();
                    if($isExit){
                        $saveData['id'] = $isExit['id'] ;
                    }

                    $adminMessage .= ' >>>>>>>>>>>>>>> saveData >>> <pre>'.print_r($saveData, true).'</pre>';
                    $paymentMethodModel->save($saveData);
                    $getLastQuery = $this->db->getLastQuery();
                    $adminMessage .= ' >>>>>>>>>>>>>>> getLastQuery >>> <pre>'.$getLastQuery.'</pre>';

                }

                $adminEmailData = [
                    'fromEmail'     => FROM_EMAIL,
                    'fromName'      => FROM_NAME,
                    'toEmail'       => 'pratibhaprajapati.cts@gmail.com',
                    'subject'       => $adminSubject,
                    'message'       => $adminMessage,
                ];
                sendEmail($adminEmailData);

            break;

            case 'payment_method.detached':
                $subscription = $event->data->object;

                $adminSubject = 'payment_method.detached';
                $adminMessage = '<pre>'.print_r($subscription, true).'</pre>' ;

                $adminEmailData = [
                    'fromEmail'     => FROM_EMAIL,
                    'fromName'      => FROM_NAME,
                    'toEmail'       => 'pratibhaprajapati.cts@gmail.com',
                    'subject'       => $adminSubject,
                    'message'       => $adminMessage,
                ];
                sendEmail($adminEmailData);

            break;

            case 'payment_method.card_automatically_updated':
                $subscription = $event->data->object;

                $adminSubject = 'payment_method.card_automatically_updated';
                $adminMessage = '<pre>'.print_r($subscription, true).'</pre>' ;

                $adminEmailData = [
                    'fromEmail'     => FROM_EMAIL,
                    'fromName'      => FROM_NAME,
                    'toEmail'       => 'pratibhaprajapati.cts@gmail.com',
                    'subject'       => $adminSubject,
                    'message'       => $adminMessage,
                ];
                sendEmail($adminEmailData);

            break;

            case 'checkout.session.completed':
                $checkout_session = $event->data->object;

                $adminSubject = 'checkout.session.completed';
                $adminMessage = '';
                $adminMessage .= '<pre>'.print_r($checkout_session, true).'</pre>' ;

                if($checkout_session){
                    $latest_invoice         = $checkout_session->invoice;
                    $stripe_subscription_id = $checkout_session->subscription;

                    $adminMessage .= '>>>>>>>>>>>>>>> latest_invoice >>>>>>>>>>>> ' .  $latest_invoice ;
                    $adminMessage .= '>>>>>>>>>>>>>>> stripe_subscription_id >>>>>>>>>>>> ' .  $stripe_subscription_id ;

                    $subscriptionExist = $userSubscriptionModel->where('stripe_subscription_id', $stripe_subscription_id )
                                                                ->where('latest_invoice', $latest_invoice)
                                                                ->first();
                    
                    $adminMessage .= ' >>>>>>>>>>>>>>> subscriptionExist >>>>>>>>>> <pre>'.print_r($subscriptionExist, true).'</pre>' ;

                    if(!$subscriptionExist){
                        $subscripData = [ 
                            'stripe_subscription_id'    => $stripe_subscription_id,
                            'latest_invoice'            => $latest_invoice, 
                            'status'                    => 'incomplete' 
                        ];
                        $userSubscriptionModel->save($subscripData);
                    }
                }

                $adminEmailData = [
                    'fromEmail'     => FROM_EMAIL,
                    'fromName'      => FROM_NAME,
                    'toEmail'       => 'pratibhaprajapati.cts@gmail.com',
                    'subject'       => $adminSubject,
                    'message'       => $adminMessage,
                ];
                sendEmail($adminEmailData);

            break;


            case 'customer.updated':
                $customerData = $event->data->object;

                $adminMessage = '';
                $adminSubject = 'customer.updated';
                $adminMessage .= '<pre>'.print_r($customerData, true).'</pre>' ;

                $user_id = $customerData->metadata->soccer_user_id;
                $default_payment_method = $customerData->invoice_settings->default_payment_method;

                $paymentMethods = $paymentMethodModel->where('user_id', $user_id)->findAll();
                if($paymentMethods){
                    $ids = array_column($paymentMethods, 'id');

                    $saveData = [ 'is_default' => 0 ];
    
                    // set "is_default" to 0 for all cards
                    $paymentMethodModel->whereIn('id', $ids)
                                        ->set([ 'is_default' => 0 ])
                                        ->update();
                }
                

                // set "is_default" to 1 for default card retuned by webhook
                $paymentMethodModel->where('stripe_payment_method_id', $default_payment_method)
                                    ->set([ 'is_default' => 1 ])
                                    ->update();


                $adminEmailData = [
                    'fromEmail'     => FROM_EMAIL,
                    'fromName'      => FROM_NAME,
                    'toEmail'       => 'pratibhaprajapati.cts@gmail.com',
                    'subject'       => $adminSubject,
                    'message'       => $adminMessage,
                ];
                sendEmail($adminEmailData);

            break;

            case 'coupon.created':
                $couponData = $event->data->object;

                $adminMessage = '';
                $adminSubject = 'coupon.created';
                $adminMessage .= '<pre>'.print_r($couponData, true).'</pre>' ;

                $adminEmailData = [
                    'fromEmail'     => FROM_EMAIL,
                    'fromName'      => FROM_NAME,
                    'toEmail'       => 'pratibhaprajapati.cts@gmail.com',
                    'subject'       => $adminSubject,
                    'message'       => $adminMessage,
                ];
                sendEmail($adminEmailData);
            break;



            default:
                //echo 'Received unknown event type ' . $event->type;
                // 
                $adminSubject = 'unknown event type ' . $event->type;
                $adminMessage = '<pre>'.print_r($event->type, true).'</pre>' ;

                $adminEmailData = [
                    'fromEmail'     => FROM_EMAIL,
                    'fromName'      => FROM_NAME,
                    'toEmail'       => 'pratibhaprajapati.cts@gmail.com',
                    'subject'       => $adminSubject,
                    'message'       => $adminMessage,
                ];
                sendEmail($adminEmailData);
        }

        http_response_code(200);
    }
}
