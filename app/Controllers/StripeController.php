<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\UserSubscriptionModel;
use App\Models\PackageModel;
use App\Models\PaymentMethodModel;
use App\Models\PackageDetailModel;
//Use CodeIgniter\Shield\Models\UserModel;
use CodeIgniter\Shield\Entities\User;
use Mpdf;


// use Config\Stripe;
// use Stripe\Stripe as StripeClient;
// use Stripe\Customer;
// use Stripe\Subscription;
// use Stripe\Webhook;
// use Stripe\PaymentMethod;

class StripeController extends BaseController
{


    public function emailTesting() {
        
        $data = [
            'message' => 'test message'
        ];
        return view('emailTemplateView', $data);
        
    }

    public function subscriptionForm(){

        //getInvoicePdfContent(246); exit;
        $packageModel = new PackageModel();

        $packages = $packageModel
                    ->select('packages.*, 
                                pd.price as price, 
                                pd.currency as currency,
                                pd.interval as interval,
                                pd.stripe_plan_id as stripe_plan_id,
                                pd.status as status,
                            ')
                    ->join('package_details pd', 'pd.package_id = packages.id')
                    ->findAll();
        $data = ['packages' => $packages];
        return view('subscription_form_1', $data);
    }

    public function upgradeSubscriptionForm(){

        //getInvoicePdfContent(246); exit;
        $packageModel = new PackageModel();

        $packages = $packageModel
                    ->select('packages.*, 
                                pd.price as price, 
                                pd.currency as currency,
                                pd.interval as interval,
                                pd.stripe_plan_id as stripe_plan_id,
                                pd.status as status,
                            ')
                    ->join('package_details pd', 'pd.package_id = packages.id')
                    ->findAll();
        $data = ['packages' => $packages];
        return view('update_subscription_form', $data);
    }

    public function subscribe()
    {
        $userSubscriptionModel = new UserSubscriptionModel();
        $packageModel = new PackageModel();    
        $packageDetailModel = new PackageDetailModel();

        try {

            $stripe = new \Stripe\StripeClient(getenv('stripe.secret.key'));

            $email = $this->request->getPost('email');
            //$user_id = $this->request->getPost('user_id');
            $paymentMethod = $this->request->getPost('payment_method');
           // $package_id = $this->request->getPost('plan_id'); // Plan ID from the request
            $stripe_plan_id = $this->request->getPost('plan_id'); // Plan ID from the request
            //$planId = $this->request->getPost('plan_id'); // Plan ID from the request
            $coupon_code = $this->request->getPost('coupon_code'); // Plan ID from the request


            $users = auth()->getProvider();
            // Find by the user email
            $user = $users->findByCredentials(['email' => $email ]);
            $user_id = $user->id;

            if($stripe_plan_id){
                // $packageInfo = $packageModel->where('id', $package_id)->first();
                // if($packageInfo){
                //     $planId = $packageInfo['stripe_plan_id'];
                // }
                //dev6.cts@yopmail.com
                //dev.cts123@yopmail.com
                
                $packageInfo = $packageDetailModel->where('stripe_plan_id', $stripe_plan_id)->first();
                if($packageInfo){
                    $package_id  = $packageInfo['package_id'];
                }

                 $customerExist =  $stripe->customers->search(
                   [
                    'query' => 'email:\''.$email.'\'',
                    'limit' => 1
                   ]
                   
                );
                
                // check if customer exist
                if( $customerExist && (sizeof($customerExist->data) > 0)){    

                    $customerID = $customerExist->data[0]->id;
                } else {

                    // create new customer if not exist
                    $newCustomerData = [
                        'email' => $email,
                        'payment_method' => $paymentMethod,
                        'invoice_settings' => [
                            'default_payment_method' => $paymentMethod,
                        ],
                        'metadata' => ['soccer_user_id' => $user_id],
                    ];

                    // Create a new customer
                    $customer = $stripe->customers->create($newCustomerData);
                    $customerID = $customer->id;
                }

                if($customerID){

                    $subscriptionData = [
                        //'customer' => $customer->id,
                        'customer' => $customerID,
                        'items' => [[
                            'price' => $stripe_plan_id,
                            // 'metadata' => [
                            //     'user_id'       => $user_id,
                            //     'package_id'    => $package_id,
                            //     'user_email'    => $email
                            // ],
                        ]],
                        'expand' => ['latest_invoice.payment_intent'],
                        'metadata' => [
                            'user_id'       => $user_id,
                            'package_id'    => $package_id,
                            'user_email'    => $email
                        ],
                    ];

                    if( !empty($coupon_code)){
                        $subscriptionData['discounts'] = [['coupon' => $coupon_code]];
                    }

                    // Create a subscription
                    $subscription = $stripe->subscriptions->create($subscriptionData);

                    if($subscription){
                        return redirect()->to('/success')->with('message', 'Subscription created successfully');
                    } else {
                        return redirect()->to('/error')->with('error', $e->getMessage());
                    }

                    /* if($subscription){
                        // Check whether the subscription activation is successful 
                        if($subscription['status'] == 'active'){ 
                            // Subscription info 
                            $stripe_subscription_id = $subscription['id']; 
                            $stripe_customer_id = $subscription['customer']; 
                            $stripe_plan_id = $subscription['plan']['id']; 
                            $plan_amount = ($subscription['plan']['amount']/100); 
                            $plan_amount_currency = $subscription['plan']['currency']; 
                            $plan_interval = $subscription['plan']['interval']; 
                            $plan_interval_count = $subscription['plan']['interval_count']; 
                            $subscription_created_at = date("Y-m-d H:i:s", $subscription['created']); 
                            $plan_period_start = date("Y-m-d H:i:s", $subscription['current_period_start']); 
                            $plan_period_end = date("Y-m-d H:i:s", $subscription['current_period_end']); 
                            $status = $subscription['status']; 
                            
                            // Insert tansaction data into the database 
                            $subscripData = array( 
                                'user_id' => $user_id, 
                                'package_id' => $package_id, 
                                'stripe_subscription_id' => $stripe_subscription_id, 
                                'stripe_customer_id' => $stripe_customer_id, 
                                'stripe_plan_id' => $stripe_plan_id, 
                                'plan_amount' => $plan_amount, 
                                'plan_amount_currency' => $plan_amount_currency, 
                                'plan_interval' => $plan_interval, 
                                'plan_interval_count' => $plan_interval_count, 
                                'plan_period_start' => $plan_period_start, 
                                'plan_period_end' => $plan_period_end, 
                                'payer_email' => $email, 
                                'subscription_created_at' => $subscription_created_at, 
                                'status' => $status 
                            ); 

                            if($userSubscriptionModel->save($subscripData)){
                                return redirect()->to('/success')->with('message', 'Subscription created successfully');
                            } else {
                                return redirect()->to('/error')->with('error', $e->getMessage());
                            }
                        }
                    } */
                }
            }

        } catch (\Exception $e) {
            return redirect()->to('/error')->with('error', $e->getMessage());
        }

        //return $this->response->setJSON($subscription);
    }
    

    public function upgradeSubscription()
    {
        $userSubscriptionModel = new UserSubscriptionModel();
        $packageModel = new PackageModel();    
        $packageDetailModel = new PackageDetailModel();

        try {
            $stripe = new \Stripe\StripeClient(getenv('stripe.secret.key'));

            $stripe_plan_id = $this->request->getPost('plan_id'); // Plan ID from the request
            $subscription_id = $this->request->getPost('subscription_id');  
            $sub_item_id = $this->request->getPost('sub_item_id');  

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
                    return redirect()->to('/success')->with('message', 'Subscription created successfully');
                } else {
                    return redirect()->to('/error')->with('error', $e->getMessage());
                }
            }

        } catch (\Exception $e) {
            return redirect()->to('/error')->with('error', $e->getMessage());
        }
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
            case 'invoice.payment_succeeded':
                $invoice = $event->data->object;
                $invoiceData = [];
                $fileName = $content = '';
                $adminMessage = '';
                $adminSubject = 'invoice.payment_succeeded '. $invoice->id;

                if($invoice){
                        $subscriptionData           =  $userSubscriptionModel->where('latest_invoice', $invoice->id)->first();
                        $adminMessage .= '>>> subscriptionData >>>> <pre>'.print_r($subscriptionData, true).'</pre>' ;

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

                

               
                    $adminMessage .= ' >>> invoice >>>>  <pre>'.print_r($invoice, true).'</pre>' ;
                    $adminMessage .= '>>> proration >>>> <pre>'.print_r($proration, true).'</pre>' ;

                    $adminMessage .= ' >>> invoiceData >>>>  <pre>'.print_r($invoiceData, true).'</pre>' ;
                    
                    //$adminMessage .= ' >>> stripe_subscription_id status >>>>  ' . $subscription->status ;
                    // $adminMessage .= ' >>> plan >>>>  <pre>'.print_r($packageInfo, true).'</pre>' ;
                    // $adminMessage .= ' >>> last_query >>>>  <pre>'.$getLastQuery.'</pre>' ;


                    // Generate PDF
                    $defaultConfig = (new Mpdf\Config\ConfigVariables())->getDefaults();
                    $fontDirs = $defaultConfig['fontDir'];

                    $defaultFontConfig = (new Mpdf\Config\FontVariables())->getDefaults();
                    $fontData = $defaultFontConfig['fontdata'];

                    $mpdf_data = [
                        'debug' => true,
                        // 'allow_output_buffering' => true,
                        // 'showImageErrors'  => true,
                        // 'text_input_as_HTML' => true,
                        
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


                if($subscription){
                    // Check whether the subscription activation is successful 
                    //if($subscription['status'] == 'active'){ 
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
                        
                        $latest_invoice     = $subscription['latest_invoice']; 
                        $user_id            = $subscription['metadata']['user_id']; 
                        $package_id         = $subscription['metadata']['package_id']; 
                        $user_email         = $subscription['metadata']['user_email']; 
                        
                        // Insert tansaction data into the database 
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
                            'invoice_status'                => 'pending',
                            'status'                        => $status 
                        ); 
                        $userSubscriptionModel->save($subscripData);
                    //}
                }

                $adminMessage = '';
                $adminSubject = 'customer.subscription.created - '. $subscription->id;
                $adminMessage .= ' >>> subscription >>>>>>> <pre>'.print_r($subscription, true).'</pre>' ;
                $adminMessage .= ' >>> subscripData >>>>>>>> <pre>'.print_r($subscripData, true).'</pre>' ;

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

                $adminSubject = 'customer.subscription.deleted';
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
            case 'customer.subscription.updated':
                    $subscription = $event->data->object;
                    $event_id = $event->id;
                    $adminMessage = '';
                    

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
                        
                        $latest_invoice                     = $subscription['latest_invoice']; 
                        $user_id                            = $subscription['metadata']['user_id']; 
                        $package_id                         = $subscription['metadata']['package_id']; 
                        $user_email                         = $subscription['metadata']['user_email']; 

                        // update status to expired if already have any active package
                        $lastSubscription = $userSubscriptionModel->where('user_id', $user_id)
                                                ->where('package_id', $package_id)
                                                ->where('status', 'active')
                                                ->orderBy('id', 'desc')
                                                ->findAll(1);
                        
                                                 
                        // Check whether the subscription activation is successful 
                        if(!empty($subscription['cancel_at'])){ 
                            $cancalData = [
                                'id'                            => $lastSubscription[0]['id'],
                                'stripe_cancel_at'              => date("Y-m-d H:i:s", $subscription['cancel_at']),
                                'stripe_canceled_at'            => date("Y-m-d H:i:s", $subscription['stripe_canceled_at']),
                                'canceled_at'                   => date('Y-m-d H:i:s'),
                                'cancellation_reason'           => $subscription['cancellation_details']->reason,
                            ];

                            $adminMessage .= ' >>> cancalData lastSubscription >>>>>>>> <pre>'.print_r($cancalData, true).'</pre>' ;
                            //$adminMessage .= ' >>> cancalData getLastQuery >>>>>>>> ' . $getLastQuery;
                            $userSubscriptionModel->save($cancalData);
                        }

                        if($lastSubscription){
                            $lastId = $lastSubscription[0]['id'];
                            $upData = ['status' => 'expired'];
                            $userSubscriptionModel->update($lastId, $upData);
                        }
                            
                            // Insert tansaction data into the database 
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
                                //'invoice_status'                => 'pending',
                                'status'                        => $status 
                            ); 
                            $userSubscriptionModel->save($subscripData);
                            // $getLastQuery = $this->db->getLastQuery();

                            $adminMessage .= ' >>> subscripData >>>>>>>> <pre>'.print_r($subscripData, true).'</pre>' ;
                            $adminMessage .= ' >>> last subscripData >>>>>>>> <pre>'.print_r($lastSubscription, true).'</pre>' ;
                         
                        // } else {
                        //     $adminMessage .= ' >>> cancalData user_id >>>>>>>> ' . $user_id;

                        //     $lastSubscription = $userSubscriptionModel->where('user_id', $user_id)
                        //                         ->where('package_id', $package_id)
                        //                         ->where('status', 'active')
                        //                         ->orderBy('id', 'desc')
                        //                         ->findAll(1,0);
                        //      $getLastQuery = $this->db->getLastQuery();
                            
                        //     $adminMessage .= ' >>> cancalData lastSubscription >>>>>>>> <pre>'.print_r($lastSubscription, true).'</pre>' ;
                        //     $adminMessage .= ' >>> cancalData getLastQuery >>>>>>>> ' . $getLastQuery;
                             
                        //     if($lastSubscription){            
                        //         // update cancled data
                        //         $cancalData = [
                        //             'id'                            => $lastSubscription[0]['id'],
                        //             'stripe_cancel_at'              => date("Y-m-d H:i:s", $subscription['cancel_at']),
                        //             'stripe_canceled_at'            => date("Y-m-d H:i:s", $subscription['stripe_canceled_at']),
                        //             'canceled_at'                   => date('Y-m-d H:i:s'),
                        //             'cancellation_reason'           => $subscription['cancellation_details']->reason,
                        //         ];
                        //         $userSubscriptionModel->save($cancalData);
                        //         $adminMessage .= ' >>> cancalData >>>>>>>> <pre>'.print_r($cancalData, true).'</pre>' ;
                        //     }
                        // }
                    }

                    
                    
                    //$adminMessage .= ' >>> getLastQuery >>>>>>>> '. $getLastQuery .'</pre>' ;

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

            default:
                //echo 'Received unknown event type ' . $event->type;

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

    public function cancel()
    {
        $userSubscriptionModel = new UserSubscriptionModel();
        $packageModel = new PackageModel();

        
        try {

            $stripe = new \Stripe\StripeClient(getenv('stripe.secret.key'));

            $email = $this->request->getPost('email');
            //$user_id = $this->request->getPost('user_id');
            $subscription_id = $this->request->getPost('subscription_id');

            // $paymentMethod = $this->request->getPost('payment_method');
            // $package_id = $this->request->getPost('plan_id'); // Plan ID from the request
            //$planId = $this->request->getPost('plan_id'); // Plan ID from the request

            $users = auth()->getProvider();
            // Find by the user email
            $user = $users->findByCredentials(['email' => $email ]);
            $user_id = $user->id;

            $subscription = $stripe->subscriptions->update(
                $subscription_id,
                ['cancel_at_period_end' => true]
            );

            echo '<pre>'; print_r($subscription); echo '</pre>'; exit('>>>>>>>>>>>> cancel Subscription >>>>>>>>>>>>>>>');
            // if($subscription){
            //     return redirect()->to('/success')->with('message', 'Subscription created successfully');
            // } else {
            //     return redirect()->to('/error')->with('error', $e->getMessage());
            // }

        } catch (\Exception $e) {
            return redirect()->to('/error')->with('error', $e->getMessage());
        }
    }


    
    public function cancelForm()
    {
        return view('stripe_cancel_form');  
    }

    public function success()
    {
        return view('stripe_response');  
    }

    public function error()
    {
        return view('error');  
    }


    

    // https://docs.stripe.com/billing/subscriptions/build-subscriptions?lang=php
    //https://github.com/stripe-samples/subscription-use-cases/tree/main/fixed-price-subscriptions
}
