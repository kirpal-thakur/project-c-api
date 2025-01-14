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
use App\Models\StripeWebhookEventModel;
use App\Models\CouponModel;



use Mpdf;


class StripeWebhookEventController extends ResourceController
{
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);
        $this->db               = \Config\Database::connect();
        
        // Set your secret key. Remember to switch to your live secret key in production.
       Stripe::setApiKey(getenv('stripe.secret.key'));

       $this->db->packageDetailModel        = new PackageDetailModel();
       $this->db->paymentMethodModel        = new PaymentMethodModel();
       $this->db->userSubscriptionModel     = new UserSubscriptionModel();
       $this->db->boosterAudienceModel      = new BoosterAudienceModel();
       $this->db->stripeWebhookEventModel   = new StripeWebhookEventModel();
       $this->db->userSubscriptionModel     = new UserSubscriptionModel();
       $this->db->couponModel               = new CouponModel();

    }


    public function handle()
    {

        $payload = @file_get_contents('php://input');
        $event = null;
        $this->db = db_connect();
        // $userSubscriptionModel      = new UserSubscriptionModel();
        // $packageModel               = new PackageModel();
        // $paymentMethodModel         = new PaymentMethodModel();
        // $stripeWebhookEventModel    = new StripeWebhookEventModel();
        


        try {
            $event = \Stripe\Event::constructFrom(
                json_decode($payload, true)
            );

            // Save the event in the database
            $this->db->stripeWebhookEventModel->save([
                'event_id' => $event->id,
                'event_type' => $event->type,
                'payload' => json_encode($event), // Save event payload
                'stripe_created_at' => date('Y-m-d H:i:s', $event->created)
            ]);

            // Process unprocessed events in the correct order
            $this->processPendingEvents();

        } catch(\UnexpectedValueException $e) {
            // Invalid payload
            // http_response_code(400);
            log_message('error', 'Stripe Webhook Error: ' . $e->getMessage());
            return $this->respond(['status' => 'error', 'message' => $e->getMessage()], 400);
            exit();
        }
        // http_response_code(200);
    }

    public function processPendingEventsLater(){

        // $tet = [ 'test', 'tests2'];
        // pr($tet); exit;
        // Process unprocessed events in the correct order
        $this->processPendingEvents();

        $response = [
            "status"    => true,
            "message"   => 'Events processed successfully',
            "data"      => [
            ]
        ];
        return $this->respondCreated($response);
    }

    private function processPendingEvents()
    {
        log_message('info', " >>>>>>>>>>>>>>> processPendingEvents function called >>>>>>>>>>>>> ");
        
        // Fetch all unprocessed events sorted by creation time
        $events = $this->db->stripeWebhookEventModel
                        // ->select('id, event_id, event_type')
                        // ->where('event_type', 'customer.subscription.created')
                        // ->where('event_type', 'payment_intent.succeeded')
                        // ->where('event_type', 'checkout.session.completed')
                        // ->where('event_type', 'payment_method.attached')
                        // ->where('event_type', 'invoice.payment_succeeded')
                        // ->where('event_type', 'customer.subscription.updated')
                        
                        ->where('processed_at', null)
                        ->orderBy(
                            "CASE
                                WHEN event_type = 'customer.subscription.created' THEN 1
                                WHEN event_type = 'payment_method.attached' THEN 2
                                WHEN event_type = 'payment_intent.succeeded' THEN 3
                                WHEN event_type = 'invoice.payment_succeeded' THEN 4
                                WHEN event_type = 'customer.subscription.updated' THEN 5
                                ELSE 6
                            END, stripe_created_at ASC"
                            )
                        ->findAll();

        $getLastQuery = $this->db->getLastQuery();
        log_message('info', "stripeWebhookEvent sequence order query: {$getLastQuery}");

        // echo '>>>>>>>>>>> events >>>>>>>>>>> ' . $this->db->getLastQuery(); 
        // echo '<pre>'; pr($events); echo '</pre>'; exit;

        foreach ($events as $event) {
            try {
                $payload = json_decode($event['payload'], true);
                // $payload = json_decode($event['payload']);

                // echo '>>>>>>>>>>> events >>>>>>>>>>> ' . $event['event_type']; 
                // echo '<pre>'; pr($event); echo '</pre>'; exit;

                // Handle event based on its type
                switch ($event['event_type']) {

                    case 'payment_intent.succeeded':
                        // Handle successful payment

                        $res = $this->handlePaymentIntentSucceeded($payload);
                        // if($res){
                        //     $processed_at = $this->db->query('SELECT CURRENT_TIMESTAMP() as "CURRENT_TIMESTAMP"')->getRow()->CURRENT_TIMESTAMP;
                        //     $this->db->stripeWebhookEventModel->update($event['id'], ['processed_at' => $processed_at ]);
                        // }
                        log_message('info', "Payment Intent succeeded: {$payload['id']}");
                        // return $this->response->setJSON($res);

                        break;

                    case 'invoice.payment_succeeded':
                        // Handle payment failure
                        $res = $this->handleInvoicePaymentSucceeded($payload);

                        // if($res){
                        //     $processed_at = $this->db->query('SELECT CURRENT_TIMESTAMP() as "CURRENT_TIMESTAMP"')->getRow()->CURRENT_TIMESTAMP;
                        //     $this->db->stripeWebhookEventModel->update($event['id'], ['processed_at' => $processed_at ]);
                        // }
                        log_message('info', " invoice Payment succeeded: {$payload['id']}");
                        // return $this->response->setJSON($res);

                        break;
                        
                    case 'invoice.payment_failed':
                        // Handle payment failure
                        $this->handleInvoicePaymentFailed($payload);
                        log_message('info', "Payment failed for Invoice ID: {$payload['id']}");
                        break;
                    
                    case 'customer.subscription.created':
                        // Handle payment failure
                        $res = $this->handleCustomerSubscriptionCreated($payload);
                        log_message('info', "customer.subscription.created ID: {$payload['id']}");
                        break;

                    case 'customer.subscription.updated':
                        // Handle payment failure
                        $res = $this->handleCustomerSubscriptionUpdated($payload);
                        log_message('info', "customer.subscription.updated ID: {$payload['id']}");
                        break;  

                    case 'customer.subscription.deleted':
                        // Handle payment failure
                        $this->handleCustomerSubscriptionDeleted($payload);
                        log_message('info', "customer subscription deleted ID: {$payload['id']}");
                        break;     
                        
                    case 'payment_method.attached':

                        // Handle payment failure
                        $res = $this->handlePaymentMethodAttached($payload);
                        log_message('info', "payment method attached ID: {$payload['id']}");

                        break;     

                    case 'checkout.session.completed':
                        $res = $this->handleCheckoutSessionCompleted($payload);
                        log_message('info', " checkout session completed : {$payload['id']}");
                        break; 

                    case 'customer.updated':

                        $res = $this->handleCustomerUpdated($payload);
                        log_message('info', " customer.updated : {$payload['id']}");

                        break; 


                    case 'coupon.created':

                        $res = $this->handleCouponCreated($payload);
                        log_message('info', " coupon.created : {$payload['id']}");

                        break; 

                    case 'coupon.deleted':

                        $this->handleCouponDeleted($payload);
                        log_message('info', " coupon.deleted : {$payload['id']}");

                        break;
                        
                        
                    default:
                        $this->handleDefault($event);
                        log_message('info', "Unhandled event type: {$event['event_type']}");
                }

                // Mark the event as processed
                // get server time
                // $processed_at = $this->db->query('SELECT CURRENT_TIMESTAMP() as "CURRENT_TIMESTAMP"')->getRow()->CURRENT_TIMESTAMP;
                $this->db->stripeWebhookEventModel->update($event['id'], ['processed_at' => getDBCurrentTime() ]);
                // $this->db->stripeWebhookEventModel->update($event['id'], ['processed_at' => date('Y-m-d H:i:s')]);
                log_message('info', "processPendingEvents processed Processing Event ID: {$event['event_id']}");

            } catch (\Exception $e) {
                log_message('error', "Error Processing Event ID: {$event['event_id']}");
                log_message('error', "Error Processing Event ID: {$e}");
            }
        }
    }

    // invoice.payment_succeeded
    private function handleInvoicePaymentSucceeded($event)
    {
        // Extract relevant data
        $invoice = $event['data']['object'];
        $customerId = $invoice['customer'];
        $amountPaid = $invoice['amount_paid'];

        log_message('info', " handleInvoicePaymentSucceeded called invoiceID: {$invoice['id']}");


        // $invoice = $event->data->object;
        $invoiceData = [];
        $fileName = $content = '';
        $adminMessage = '';
        $adminSubject = 'invoice.payment_succeeded '. $invoice['id'];

        // get latest subscription detail from DB
        $subscriptionData           =  $this->db->userSubscriptionModel->where('latest_invoice', $invoice['id'])->first();
        $adminMessage .= '>>> subscriptionData >>>> <pre>'.print_r($subscriptionData, true).'</pre>';
        

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

        $lines = $invoice['lines']['data']; // Assuming $stripeCollection is the Stripe\Collection object
        // $total_count = $invoice->total_count; 
        

        $hasProration = false; // Initialize a flag
        $proration = [];
        foreach ($lines as $lineItem) {
            if (isset($lineItem['proration']) && $lineItem['proration'] == 1) {
                $hasProration = true;
                $proration = $lineItem;
                break; // Stop loop if proration is found
            }
        } 

        $invoiceData['invoice_status']              = $invoice_status;
        $invoiceData['invoice_number']              = $invoice_number;
        $invoiceData['amount_paid']                 = $amount_paid;
        $invoiceData['amount_paid_currency']        = $amount_paid_currency;

        $invoiceData['stripe_subscription_id']      = $stripe_subscription_id;
        $invoiceData['latest_invoice']              = $invoice['id'];
        $invoiceData['stripe_customer_id']          = $stripe_customer_id;
        $invoiceData['user_id']                     = $user_id; 

        $stripePlanData = $lines[0]['plan'];
        $adminMessage .= '>>> stripePlanData >>>> <pre>'.print_r($stripePlanData, true).'</pre>' ;

        if( ($subscriptionData && $subscriptionData['status'] == 'incomplete') &&  $invoice_status == 'paid' ){
            $invoiceData['status'] = 'active';
        }

        // save coupon if used
        if($invoice['discount'] != NULL){
            $coupon_used    = $invoice['discount']['coupon']['id'];

            if(!empty($coupon_used)){
                $invoiceData['coupon_used'] = $coupon_used;
            }
        }
        
        if($proration){ 
            $proration_amount                       = $proration['amount']/100;
            $proration_currency                     = $proration['currency'];
            $proration_description                  = $proration['description'];

            $invoiceData['proration_amount']        = $proration_amount;
            $invoiceData['proration_currency']      = $proration_currency;
            $invoiceData['proration_description']   = $proration_description;
        }

        $subscriptionDate = '';
        if($subscriptionData){
            $subscriptionID         = $subscriptionData['id'];
            $subscriptionDate       = formatDate($subscriptionData['subscription_created_at']);
            $invoiceData['id']      = $subscriptionID;
        }
        
        // save booster audience data if "booster_audience" key receved in metadata
        /* if($invoice_metadata['booster_audience']){

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
        } */

        $this->db->userSubscriptionModel->save($invoiceData);

        $getLastQuery = $this->db->getLastQuery();
        log_message('info', " handleInvoicePaymentSucceeded called query : { $getLastQuery }");

        $fileName = 'invoice_'. $subscriptionID .'_' .$user_id . '.pdf';
        $invoicePath = WRITEPATH . 'uploads/documents/'.$fileName;

        // get user info
        $users = auth()->getProvider();
        $userInfo = $users->findById($user_id);

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

        $content = getInvoicePdfContent($subscriptionID, getLanguageCode($userInfo->lang));

        // echo '>>>> content > ' . $content;
        // $content = "<h1>Test PDF Content</h1>";
        //$stylesheet = file_get_contents(FCPATH .'public/assets/css/user-profile-pdf.css'); // external css

        $mpdf->WriteHTML($content);
        // $mpdf->Output(WRITEPATH . 'uploads/documents/'.$fileName, "F"); $invoicePath
        $mpdf->Output($invoicePath, "F"); 
        $this->db->userSubscriptionModel->update($subscriptionID, ['invoice_file' => $fileName]);         
        

        // get package detail
        $packageInfo = getPackageInfo($package_id);
        $attachments = [$invoicePath];

        // Create Activity log
        if($subscriptionData['upgrade_downgrade_status'] == 'purchased' || $subscriptionData['upgrade_downgrade_status'] == 'upgraded' || $subscriptionData['upgrade_downgrade_status'] == 'downgraded'){
            $package_id     = $subscriptionData['package_id'];
            $activity       = "purchased {PACKAGE_ID_$package_id}";
            $activity_en    = "purchased {PACKAGE_ID_$package_id}";
            $activity_de    = "gekauft {PACKAGE_ID_$package_id} Plan";
            $activity_it    = "acquistato {PACKAGE_ID_$package_id} piano";
            $activity_fr    = "achetés {PACKAGE_ID_$package_id} plan";
            $activity_es    = "comprado {PACKAGE_ID_$package_id} plan";
            $activity_pt    = "adquirido {PACKAGE_ID_$package_id} plano";
            $activity_da    = "købt {PACKAGE_ID_$package_id} plan";
            $activity_sv    = "köpt {PACKAGE_ID_$package_id} plan";

            $adminEmailType = 'New Membership purchased';

            // admin email
            $getAdminEmailTemplate = getEmailTemplate($adminEmailType, 2, ADMIN_ROLES );

            if($getAdminEmailTemplate){

                $replacements = [
                    '{userName}'        => $userInfo->first_name,
                    '{packageName}'     => $packageInfo->package_name,
                    '{date}'            => $subscriptionDate,
                ]; 
                
                $subjectReplacements = [
                    '{packageName}'    => $packageInfo->package_name,
                ]; 

                // Replace placeholders with actual values in the email body
                $subject = strtr($getAdminEmailTemplate['subject'], $subjectReplacements);
                $content = strtr($getAdminEmailTemplate['content'], $replacements);
                $message = view('emailTemplateView' , ['message' => $content, 'disclaimerText' => getEmailDisclaimer(2), 'footer' => getEmailFooter(2)]);

                $toEmail = 'pratibhaprajapati.cts@gmail.com';
                $adminEmailData = [];
                $adminEmailData = [
                    'fromEmail'     => FROM_EMAIL,
                    'fromName'      => FROM_NAME,
                    'toEmail'       => $toEmail,
                    // 'toEmail'       => ADMIN_EMAIL,
                    'subject'       => $subject,
                    'message'       => $message,
                    'attachments'   => $attachments
                ];
                sendEmail($adminEmailData);
            }


            // user email

            $getUserEmailTemplate = getEmailTemplate($adminEmailType, 2, [$userInfo->role] );
            // $getUserEmailTemplate = getEmailTemplate($userEmailType, $userInfo->lang, [$userInfo->role] );

            if($getUserEmailTemplate){

                $replacements = [
                    '{userName}'        => $userInfo->first_name,
                    '{packageName}'     => $packageInfo->package_name,
                    '{loginLink}'       => '<a href="">login</a>'
                ]; 
                
                $subjectReplacements = [
                    '{packageName}'    => $packageInfo->package_name,
                ]; 

                // Replace placeholders with actual values in the email body
                $subject = strtr($getUserEmailTemplate['subject'], $subjectReplacements);
                $content = strtr($getUserEmailTemplate['content'], $replacements);
                $message = view('emailTemplateView' , ['message' => $content, 'disclaimerText' => getEmailDisclaimer($userInfo->lang), 'footer' => getEmailFooter($userInfo->lang)]);

                $toEmail = $userInfo->email;
                $userEmailData = [
                    'fromEmail'     => FROM_EMAIL,
                    'fromName'      => FROM_NAME,
                    'toEmail'       => $toEmail,
                    'subject'       => $subject,
                    'message'       => $message,
                    'attachments'   => $attachments
                ];
                sendEmail($userEmailData);
            }

        }

        if($subscriptionData['upgrade_downgrade_status'] == 'renewed'){
            $package_id = $subscriptionData['package_id'];
            $activity       = "renewed {PACKAGE_ID_$package_id}";
            $activity_en    = "renewed {PACKAGE_ID_$package_id}";
            $activity_de    = "erneuert {PACKAGE_ID_$package_id} Plan";
            $activity_it    = "rinnovato {PACKAGE_ID_$package_id} piano";
            $activity_fr    = "renouvelé {PACKAGE_ID_$package_id} plan";
            $activity_es    = "renovada {PACKAGE_ID_$package_id} plan";
            $activity_pt    = "renovado {PACKAGE_ID_$package_id} plano";
            $activity_da    = "fornyet {PACKAGE_ID_$package_id} plan";
            $activity_sv    = "förnyad {PACKAGE_ID_$package_id} plan";


            $renewalEmailType = 'Membership renewal';

            // admin email
            $getAdminEmailTemplate = getEmailTemplate($renewalEmailType, 2, ADMIN_ROLES );

            if($getAdminEmailTemplate){

                $replacements = [
                    '{userName}'        => $userInfo->first_name,
                    '{packageName}'     => $packageInfo->package_name,
                    '{date}'            => $subscriptionDate,
                ]; 
                
                $subjectReplacements = [
                    '{packageName}'    => $packageInfo->package_name,
                ]; 

                // Replace placeholders with actual values in the email body
                $subject = strtr($getAdminEmailTemplate['subject'], $subjectReplacements);
                $content = strtr($getAdminEmailTemplate['content'], $replacements);
                $message = view('emailTemplateView' , ['message' => $content, 'disclaimerText' => getEmailDisclaimer(2), 'footer' => getEmailFooter(2)]);

                $toEmail = 'pratibhaprajapati.cts@gmail.com';
                $renewalAdminEmailData = [
                    'fromEmail'     => FROM_EMAIL,
                    'fromName'      => FROM_NAME,
                    'toEmail'       => $toEmail,
                    // 'toEmail'       => ADMIN_EMAIL,
                    'subject'       => $subject,
                    'message'       => $message,
                    'attachments'   => $attachments
                ];
                sendEmail($renewalAdminEmailData);
            }


            // $getUserEmailTemplate = getEmailTemplate($renewalEmailType, 2, [$userInfo->role] );
            $getUserEmailTemplate = getEmailTemplate($renewalEmailType, $userInfo->lang, $userInfo->role );

            if($getUserEmailTemplate){

                $replacements = [
                    '{userName}'        => $userInfo->first_name,
                    '{packageName}'     => $packageInfo->package_name,
                    '{loginLink}'       => '<a href="">login</a>',
                    '{plansLink}'       => '<a href="">plans</a>',
                ]; 
                
                $subjectReplacements = [
                    '{packageName}'    => $packageInfo->package_name,
                ]; 

                // Replace placeholders with actual values in the email body
                $subject = strtr($getUserEmailTemplate['subject'], $subjectReplacements);
                $content = strtr($getUserEmailTemplate['content'], $replacements);
                $message = view('emailTemplateView' , ['message' => $content, 'disclaimerText' => getEmailDisclaimer($userInfo->lang), 'footer' => getEmailFooter($userInfo->lang)]);

                $toEmail = $userInfo->email;
                $renewalUserEmailData = [
                    'fromEmail'     => FROM_EMAIL,
                    'fromName'      => FROM_NAME,
                    'toEmail'       => $toEmail,
                    'subject'       => $subject,
                    'message'       => $message,
                    'attachments'   => $attachments
                ];
                sendEmail($renewalUserEmailData);
            }
        }

        $activity_data = [
            'user_id'               => $user_id,
            'old_data'              => $event['id'],
            'activity'              => $activity,
            'activity_en'           => $activity_en,
            'activity_de'           => $activity_de,
            'activity_it'           => $activity_it,
            'activity_fr'           => $activity_fr,
            'activity_es'           => $activity_es,
            'activity_pt'           => $activity_pt,
            'activity_da'           => $activity_da,
            'activity_sv'           => $activity_sv,
            'ip'                    => $this->request->getIPAddress()
        ];

        if($subscriptionData['upgrade_downgrade_status'] == 'renewed'){
            $activity_data['activity_type_id'] = 12;
        } else if($subscriptionData['upgrade_downgrade_status'] == 'upgraded'){
            $activity_data['activity_type_id'] = 14;
        } else if($subscriptionData['upgrade_downgrade_status'] == 'downgraded'){
            $activity_data['activity_type_id'] = 15;
        } else {
            $activity_data['activity_type_id'] = 11;
        }
        
        createActivityLog($activity_data);



        $adminEmailData11 = [];
        $adminEmailData11 = [
            'fromEmail'     => FROM_EMAIL,
            'fromName'      => FROM_NAME,
            'toEmail'       => 'pratibhaprajapati.cts@gmail.com',
            'subject'       => $adminSubject,
            'message'       => $adminMessage,
        ];
        sendEmail($adminEmailData11);
        // $emailstatus =  sendEmail($adminEmailData);
        // echo '>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> emailstatus >>> ' . $emailstatus;

        // Update payment status in the database
        log_message('info', "Payment succeeded for customer $customerId. Amount paid: $amountPaid.");

        // return $emailstatus;
        return true;
    }

    // invoice.payment_failed
    private function handleInvoicePaymentFailed($event)
    {
        // Extract relevant data
        $invoice = $event['data']['object'];
        $customerId = $invoice['customer'];
        $amountPaid = $invoice['amount_paid'];

        log_message('info', " handleInvoicePaymentFailed called invoiceID: {$invoice['id']}");


        // $invoice = $event->data->object;
        $invoiceData = [];
        $fileName = $content = '';
        $adminMessage = '';
        $adminSubject = 'invoice.payment_failed '. $invoice['id'];

        // get latest subscription detail from DB
        $subscriptionData           =  $this->db->userSubscriptionModel->where('latest_invoice', $invoice['id'])->first();
        $adminMessage .= '>>> subscriptionData >>>> <pre>'.print_r($subscriptionData, true).'</pre>';
        

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

        $lines = $invoice['lines']['data']; // Assuming $stripeCollection is the Stripe\Collection object
        // $total_count = $invoice->total_count; 
        

        $hasProration = false; // Initialize a flag
        $proration = [];
        foreach ($lines as $lineItem) {
            if (isset($lineItem['proration']) && $lineItem['proration'] == 1) {
                $hasProration = true;
                $proration = $lineItem;
                break; // Stop loop if proration is found
            }
        } 

        $invoiceData['invoice_status']              = $invoice_status;
        $invoiceData['invoice_number']              = $invoice_number;
        $invoiceData['amount_paid']                 = $amount_paid;
        $invoiceData['amount_paid_currency']        = $amount_paid_currency;

        $invoiceData['stripe_subscription_id']      = $stripe_subscription_id;
        $invoiceData['latest_invoice']              = $invoice['id'];
        $invoiceData['stripe_customer_id']          = $stripe_customer_id;
        $invoiceData['user_id']                     = $user_id; 

        $stripePlanData = $lines[0]['plan'];
        $adminMessage .= '>>> stripePlanData >>>> <pre>'.print_r($stripePlanData, true).'</pre>' ;

        if( ($subscriptionData && $subscriptionData['status'] == 'incomplete') &&  $invoice_status == 'paid' ){
            $invoiceData['status'] = 'active';
        }

        // save coupon if used
        if($invoice['discount'] != NULL){
            $coupon_used    = $invoice['discount']['coupon']['id'];

            if(!empty($coupon_used)){
                $invoiceData['coupon_used'] = $coupon_used;
            }
        }
        
        if($proration){ 
            $proration_amount                       = $proration['amount']/100;
            $proration_currency                     = $proration['currency'];
            $proration_description                  = $proration['description'];

            $invoiceData['proration_amount']        = $proration_amount;
            $invoiceData['proration_currency']      = $proration_currency;
            $invoiceData['proration_description']   = $proration_description;
        }

        $subscriptionDate = '';
        if($subscriptionData){
            $subscriptionID         = $subscriptionData['id'];
            $subscriptionDate       = formatDate($subscriptionData['subscription_created_at']);
            $invoiceData['id']      = $subscriptionID;
        }
        
        $this->db->userSubscriptionModel->save($invoiceData);

        $getLastQuery = $this->db->getLastQuery();
        log_message('info', " handleInvoicePaymentFailed called query : { $getLastQuery }");

        if($subscriptionData && $subscriptionData['upgrade_downgrade_status'] == "renewed"){

            // get user info
            $users = auth()->getProvider();
            $userInfo = $users->findById($user_id);
            $this->request->setLocale(getLanguageCode($userInfo->lang));


            // admin email
            $failedEmailType = 'Membership renewal failed';
            $getAdminEmailTemplate = getEmailTemplate($failedEmailType, 2, ADMIN_ROLES );

            if($getAdminEmailTemplate){

                $replacements = [
                    '{username}'    => $userInfo->username,
                ]; 
                
                // Replace placeholders with actual values in the email body
                $subject = $getAdminEmailTemplate['subject'];
                $content = strtr($getAdminEmailTemplate['content'], $replacements);
                $message = view('emailTemplateView' , ['message' => $content, 'disclaimerText' => getEmailDisclaimer(2), 'footer' => getEmailFooter(2)]);

                $toEmail = 'pratibhaprajapati.cts@gmail.com';
                $renewalAdminEmailData = [
                    'fromEmail'     => FROM_EMAIL,
                    'fromName'      => FROM_NAME,
                    'toEmail'       => $toEmail,
                    // 'toEmail'       => ADMIN_EMAIL,
                    'subject'       => $subject,
                    'message'       => $message,
                    // 'attachments'   => $attachments
                ];
                sendEmail($renewalAdminEmailData);
            }
            

            // email to users
            $getUserEmailTemplate = getEmailTemplate($failedEmailType, $userInfo->lang, [$userInfo->role] );

            if($getUserEmailTemplate){

                $replacements = [
                    '{username}'            => $userInfo->first_name,
                    '{loginLink}'           => '<a href="'.getPageInUserLanguage($userInfo->user_domain, $userInfo->lang).'">'. lang('Email.login') .'</a>',
                    '{faqLink}'             => '<a href="'.getPageInUserLanguage($userInfo->user_domain, $userInfo->lang, "faq").'">faq</a>',
                    '{helpEmail}'           => '<a href="mailto:'. HELP_EMAIL .'">'. HELP_EMAIL .'</a>',
                ]; 

                $stripe = new \Stripe\StripeClient(getenv('stripe.secret.key'));
                $chargeInfo = $stripe->charges->retrieve($invoice['charge'], []);
                // $stripe->charges->retrieve('ch_3MmlLrLkdIwHu7ix0snN0B15', []);

                if($chargeInfo){

                    $failure_code = $chargeInfo->failure_code;
                    $replacements['{failureIssue}'] = lang('PaymentFailure.' . $failure_code);
                }
                
                // Replace placeholders with actual values in the email body
                $subject = $getUserEmailTemplate['subject'];
                $content = strtr($getUserEmailTemplate['content'], $replacements);
                $message = view('emailTemplateView' , ['message' => $content, 'disclaimerText' => getEmailDisclaimer($userInfo->lang), 'footer' => getEmailFooter($userInfo->lang)]);

                $toEmail = $userInfo->email;
                $renewalAdminEmailData = [
                    'fromEmail'     => FROM_EMAIL,
                    'fromName'      => FROM_NAME,
                    'toEmail'       => $toEmail,
                    'subject'       => $subject,
                    'message'       => $message,
                    // 'attachments'   => $attachments
                ];
                sendEmail($renewalAdminEmailData);
            }
        }

        $adminEmailData = [];
        $adminEmailData = [
            'fromEmail'     => FROM_EMAIL,
            'fromName'      => FROM_NAME,
            'toEmail'       => 'pratibhaprajapati.cts@gmail.com',
            'subject'       => $adminSubject,
            'message'       => $adminMessage,
        ];
        sendEmail($adminEmailData);

        // Notify the customer about payment failure
        log_message('error', "Payment failed for invoice {$invoice['id']}." );
    }

    // customer.subscription.created
    private function handleCustomerSubscriptionCreated($event)
    {
        $subscription = $event['data']['object'];

        $customerId = $subscription['customer'];
        $status = $subscription['status'];
        $planId = $subscription['plan']['id'];
        $event_id = $event['id'];

        $adminMessage = '';
            
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
        // $sub_item_id                        = $subscription['items']['data'][0]->id; 
        $sub_item_id                        = $subscription['items']['data'][0]['id']; 
        $default_payment_method             = $subscription['default_payment_method'];             
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
            'event_id'                      => $event_id, 
            'subscription_created_at'       => $subscription_created_at, 
            'payment_method_id'             => $default_payment_method, 
            'latest_invoice'                => $latest_invoice,
            'status'                        => $status, 
            'sub_item_id'                   => $sub_item_id,
            'upgrade_downgrade_status'      => 'purchased', 
        ); 

        // check if subscription data already inserted
        $subscriptionExist = $this->db->userSubscriptionModel->where('latest_invoice', $subscription['latest_invoice'])
                                                ->orWhere('stripe_subscription_id', $subscription['id'])
                                                ->first();
                                                
        if($subscriptionExist){
            $subscripData['id'] = $subscriptionExist['id'];
        }

        // $adminMessage .= ' >>> subscripData >>>>>>>> <pre>'.print_r($subscripData, true).'</pre>' ;
        $this->db->userSubscriptionModel->save($subscripData);

        // add target audience data for booster plan
        if(isset($subscription['metadata']['booster_audience'])){
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
        // return $subscripData; //exit;

        /* if($subscription['status'] == 'active'){
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
        } */
        

        // $adminSubject = 'customer.subscription.created - '. $subscription['id'];
        // $adminMessage .= ' >>> subscription >>>>>>> <pre>'.print_r($subscription, true).'</pre>' ;
        // // $adminMessage .= ' >>> items Data >>>>>>>> <pre>'.print_r($subscription['items']['data'], true).'</pre>' ;
        
        // $adminEmailData = [
        //     'fromEmail'     => FROM_EMAIL,
        //     'fromName'      => FROM_NAME,
        //     'toEmail'       => 'pratibhaprajapati.cts@gmail.com',
        //     'subject'       => $adminSubject,
        //     'message'       => $adminMessage,
        // ];
        // sendEmail($adminEmailData);

        // Save to database or perform actions
        log_message('info', "Subscription created for customer $customerId with plan $planId.");

        return true;
    }

    // customer.subscription.updated
    private function handleCustomerSubscriptionUpdated($event)
    {
        // Extract relevant data
        $subscription = $event['data']['object'];
        $subscriptionId = $subscription['id'];
        $event_id = $event['id'];

        $adminMessage = '';
        //previous_attributes
        $previous_attributes = $event['data']['previous_attributes'];
        $adminMessage .= ' >>> subscription->previous_attributes >>>>>>>> <pre>'.print_r($previous_attributes, true).'</pre>' ;
        
        $adminSubject = 'customer.subscription.updated - '. $subscription['id'];
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
        $sub_item_id                        = $subscription['items']['data'][0]['id'];


        // CASE : 1
        // update "incomplete" status to final status for first time
        // this data will be recieved when subscription status will be updated for first time
        if($previous_attributes && isset($previous_attributes['status']) && $previous_attributes['status'] == "incomplete"){
            $adminMessage .= ' >>>>>>>>>>>>>>>>>>>>>>>>>>>> CASE : 1 triggered >>>>>>>>>>>>>>>>>>>>>>>>>' ;

            // check latest subscription with "incomplete" status
            $pendingSubscription = $this->db->userSubscriptionModel
                                        ->where('user_id', $user_id)
                                        ->where('stripe_subscription_id', $subscription['id'] )
                                        ->where('package_id', $package_id)
                                        ->where('latest_invoice', $latest_invoice)
                                        ->where('status', 'incomplete')
                                        ->first();
            // pr($pendingSubscription);

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
                    'status'                        => $status,
                    'upgrade_downgrade_status'      => 'purchased',  
                ); 

                $subscripData['id']                 = $pendingSubscription['id'];
                // $subscripData['payment_method_id']  = $default_payment_method;
                // $subscripData['status']             = $status;
                // pr($subscripData);
                $this->db->userSubscriptionModel->save($subscripData);

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
            $lastSubscription = $this->db->userSubscriptionModel
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
                $this->db->userSubscriptionModel->update($lastId, $upData);
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
                // 'event_id'                      => $event_id,
                'latest_invoice'                => $latest_invoice,
                'payment_method_id'             => $default_payment_method,
                'sub_item_id'                   => $sub_item_id,
                'status'                        => $status,
                'upgrade_downgrade_status'      => 'renewed', 
            ); 

            $pendingSubscription = $this->db->userSubscriptionModel
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

            $this->db->userSubscriptionModel->save($subscripData);
        }


        // CASE : 3
        // Cancle subscription  
        if($subscription['cancel_at_period_end'] && $subscription['cancel_at_period_end'] == true){ 

            // check latest subscription with "incomplete" status
            $lastSubscription = $this->db->userSubscriptionModel
                                    ->where('user_id', $user_id)
                                    ->where('stripe_subscription_id', $subscription['id'] )
                                    ->where('latest_invoice', $latest_invoice)
                                    ->first();

            $adminMessage .= ' >>> cancalData getLastQuery >>>>>>>> ' . $this->db->getLastQuery;

            $cancalData = [
                'id'                            => $lastSubscription['id'],
                'stripe_cancel_at'              => date("Y-m-d H:i:s", $subscription['cancel_at']),
                'stripe_canceled_at'            => date("Y-m-d H:i:s", $subscription['canceled_at']),
                'canceled_at'                   => date('Y-m-d H:i:s'),
                'cancellation_reason'           => $subscription['cancellation_details']['reason'],
                'status'                        => $status,
                'upgrade_downgrade_status'      => 'none',
            ];

            $adminMessage .= ' >>> CASE : 3 cancalData lastSubscription >>>>>>>> <pre>'. print_r($cancalData, true) . '</pre>' ;
            //$adminMessage .= ' >>> cancalData getLastQuery >>>>>>>> ' . $getLastQuery;

            if($this->db->userSubscriptionModel->save($cancalData)){
                // get user info
                $users = auth()->getProvider();
                $userInfo = $users->findById($user_id);

                // get package detail
                $packageInfo = getPackageInfo($package_id);

                if( in_array($package_id, PREMIUM_PACKAGES) ){
                    $cancellationEmailType = 'Membership Premium cancellation';
                } else if( in_array($package_id, BOOSTER_PACKAGES) ){
                    $cancellationEmailType = 'Membership Boost cancellation';
                } else if( in_array($package_id, COUNTRY_PACKAGES) ){
                    $cancellationEmailType = 'Membership Multi-Country cancellation';
                } else {
                    $cancellationEmailType = 'Membership Premium cancellation';
                }

                $activity       = "cancelled {PACKAGE_ID_$package_id} plan";
                $activity_en    = "cancelled {PACKAGE_ID_$package_id} plan";
                $activity_de    = "abgesagt {PACKAGE_ID_$package_id} Plan";
                $activity_it    = "annullato {PACKAGE_ID_$package_id} piano";
                $activity_fr    = "annulé {PACKAGE_ID_$package_id} plan";
                $activity_es    = "cancelado {PACKAGE_ID_$package_id} plan";
                $activity_pt    = "cancelado {PACKAGE_ID_$package_id} plano";
                $activity_da    = "aflyst {PACKAGE_ID_$package_id} plan";
                $activity_sv    = "avbruten {PACKAGE_ID_$package_id} plan";
                
                $activity_data = [
                    'user_id'               => $user_id,
                    'activity_type_id'      => 13,        // created
                    'activity'              => $activity,
                    'activity_en'           => $activity_en,
                    'activity_de'           => $activity_de,
                    'activity_it'           => $activity_it,
                    'activity_fr'           => $activity_fr,
                    'activity_es'           => $activity_es,
                    'activity_pt'           => $activity_pt,
                    'activity_da'           => $activity_da,
                    'activity_sv'           => $activity_sv,
                    'ip'                    => $this->request->getIPAddress()
                ];
                createActivityLog($activity_data);

                // admin email
                $getAdminEmailTemplate = getEmailTemplate($cancellationEmailType, 2, ADMIN_ROLES );

                if($getAdminEmailTemplate){

                    $replacements = [
                        '{userName}'        => $userInfo->first_name,
                    ]; 
                    
                    // Replace placeholders with actual values in the email body
                    $subject = $getAdminEmailTemplate['subject'];
                    $content = strtr($getAdminEmailTemplate['content'], $replacements);
                    $message = view('emailTemplateView' , ['message' => $content, 'disclaimerText' => getEmailDisclaimer(2), 'footer' => getEmailFooter(2)]);

                    $toEmail = 'pratibhaprajapati.cts@gmail.com';
                    $renewalAdminEmailData = [
                        'fromEmail'     => FROM_EMAIL,
                        'fromName'      => FROM_NAME,
                        'toEmail'       => $toEmail,
                        // 'toEmail'       => ADMIN_EMAIL,
                        'subject'       => $subject,
                        'message'       => $message,
                        // 'attachments'   => $attachments
                    ];
                    sendEmail($renewalAdminEmailData);
                }


                // $getUserEmailTemplate = getEmailTemplate($cancellationEmailType, 2, [$userInfo->role] );
                $getUserEmailTemplate = getEmailTemplate($userEmailType, $userInfo->lang, $userInfo->role );

                if($getUserEmailTemplate){

                    $replacements = [
                        '{userName}'    => $userInfo->first_name,
                        '{faqLink}'     => '<a href="'.getPageInUserLanguage($userInfo->user_domain, $userInfo->lang, "faq").'">faq</a>',
                    ]; 
                    

                    // Replace placeholders with actual values in the email body
                    $subject = $getUserEmailTemplate['subject'];
                    $content = strtr($getUserEmailTemplate['content'], $replacements);
                    $message = view('emailTemplateView' , ['message' => $content, 'disclaimerText' => getEmailDisclaimer($userInfo->lang), 'footer' => getEmailFooter($userInfo->lang)]);

                    $toEmail = $userInfo->email;
                    $renewalUserEmailData = [
                        'fromEmail'     => FROM_EMAIL,
                        'fromName'      => FROM_NAME,
                        'toEmail'       => $toEmail,
                        'subject'       => $subject,
                        'message'       => $message,
                        // 'attachments'   => $attachments
                    ];
                    sendEmail($renewalUserEmailData);
                }
            }

        }

        $adminEmailData = [];
        $adminEmailData = [
            'fromEmail'     => FROM_EMAIL,
            'fromName'      => FROM_NAME,
            'toEmail'       => 'pratibhaprajapati.cts@gmail.com',
            'subject'       => $adminSubject,
            'message'       => $adminMessage,
        ];
        sendEmail($adminEmailData);

        // Update subscription in database
        log_message('info', "Subscription $subscriptionId updated. New status: $status.");
        return true;
    }

    // customer.subscription.deleted
    private function handleCustomerSubscriptionDeleted($event)
    {
        $subscription = $event['data']['object'];
        $subscriptionId = $subscription['id'];

        // check latest subscription
        $lastSubscription = $this->db->userSubscriptionModel
                                    // ->where('user_id', $user_id)
                                    ->where('stripe_subscription_id', $subscription['id'] )
                                    ->where('latest_invoice', $subscription['latest_invoice'])
                                    ->first();

        if($lastSubscription){
            $cancalData = [
                'id'                            => $lastSubscription['id'],
                'stripe_cancel_at'              => date("Y-m-d H:i:s", $subscription['cancel_at']),
                'stripe_canceled_at'            => date("Y-m-d H:i:s", $subscription['canceled_at']),
                'canceled_at'                   => getDBCurrentTime(),
                'cancellation_reason'           => $subscription['cancellation_details']['reason'],
                'status'                        => $subscription['status'],
            ];

            $this->db->userSubscriptionModel->save($cancalData);
        }

        // Update subscription status in the database
        log_message('info', "Subscription $subscriptionId deleted.");
    }

    // customer.subscription.paused
    // payment_intent.succeeded
    private function handlePaymentIntentSucceeded($event)
    {
        // Extract relevant data
        $payment_intent = $event['data']['object'];
        $invoice = $payment_intent['invoice'];

        $adminSubject = 'payment_intent.succeeded >>>>>> ' . $invoice ;
        $adminMessage = '';
        $adminMessage .= '<pre>'.print_r($payment_intent, true).'</pre>' ;

        if($payment_intent){
            $latest_invoice = $payment_intent['invoice'];
            $payment_method = $payment_intent['payment_method'];

            $lastSubscription = $this->db->userSubscriptionModel->where('latest_invoice', $latest_invoice)->first();
            $adminMessage .= ' >>>>>>>>>>>> lastSubscription >>>>>>>> <pre>'.print_r($lastSubscription, true).'</pre>' ;

            $save_data = [
                'payment_method_id' => $payment_method,
                'latest_invoice'    => $latest_invoice
            ];

            if($lastSubscription){
                $save_data['id']    = $lastSubscription['id'];
            } 

            $this->db->userSubscriptionModel->save($save_data);

        }

        $adminEmailData = [];
        $adminEmailData = [
            'fromEmail'     => FROM_EMAIL,
            'fromName'      => FROM_NAME,
            'toEmail'       => 'pratibhaprajapati.cts@gmail.com',
            'subject'       => $adminSubject,
            'message'       => $adminMessage,
        ];
        sendEmail($adminEmailData);

        // Save to database or perform actions
        log_message('info', "Payment intent $invoice succeeded.");

        return true;
    }

    // payment_method.attached
    private function handlePaymentMethodAttached($event)
    {

        $payment_method = $event['data']['object'];
        $adminMessage = '';
        $adminSubject = 'payment_method.attached';
        $adminMessage .= '<pre>'.print_r($payment_method, true).'</pre>';


        $isRecordExist =  $this->db->paymentMethodModel->where('stripe_payment_method_id', $payment_method['id'])->first();
        $adminMessage .= ' >>> isRecordExist query >>>>>>>> '. $this->db->getLastQuery() ;
        $adminMessage .= ' >>>>>>>>>>>>>>> isRecordExist >>> <pre>'.print_r($isRecordExist, true).'</pre>';

        if(!$isRecordExist){
        
            $stripe = new \Stripe\StripeClient(getenv('stripe.secret.key'));
            $customerInfo = $stripe->customers->retrieve($payment_method['customer'], []);
            $adminMessage .= ' >>>>>>>>>>>>>>> customerInfo >>> <pre>'.print_r($customerInfo, true).'</pre>';

            $user_id = 0;
            if($customerInfo){
                $user_id = $customerInfo->metadata->soccer_user_id;
            }

            $saveData = [
                'user_id'                           => $user_id,
                'stripe_payment_method_id'          => $payment_method['id'],
                'type'                              => $payment_method['type'],
                'brand'                             => $payment_method['card']['brand'],
                'country'                           => $payment_method['card']['country'],
                'display_brand'                     => $payment_method['card']['display_brand'],
                'exp_month'                         => $payment_method['card']['exp_month'],
                'exp_year'                          => $payment_method['card']['exp_year'],
                'last4'                             => $payment_method['card']['last4'],
                'funding'                           => $payment_method['card']['funding'],
                // 'event_id'                          => $event['id'],
                'stripe_created_at'                 => date("Y-m-d H:i:s", $payment_method['created']),
            ];

            // if no payment method added already, then set this as default
            $paymentMethods = $this->db->paymentMethodModel->where('user_id', $user_id)->first();
            if(!$paymentMethods){
                $saveData['is_default'] = 1 ;
            }

            $this->db->paymentMethodModel->save($saveData);

            $adminMessage .= ' >>>>>>>>>>>>>>> saveData >>> <pre>'.print_r($saveData, true).'</pre>';
        } 

        $adminEmailData = [];
        $adminEmailData = [
            'fromEmail'     => FROM_EMAIL,
            'fromName'      => FROM_NAME,
            'toEmail'       => 'pratibhaprajapati.cts@gmail.com',
            'subject'       => $adminSubject,
            'message'       => $adminMessage,
        ];
        sendEmail($adminEmailData);
        // $emailstatus =  sendEmail($adminEmailData);

        $payment_method_id = $payment_method['id'];
        // Save to database or perform actions
        log_message('info', "Payment method $payment_method_id added.");
        return true;
    }

    // payment_method.updated
    // payment_method.detached
    // payment_method.card_automatically_updated

    // checkout.session.completed
    private function handleCheckoutSessionCompleted($event)
    {
        $checkout_session = $event['data']['object'];
        // Extract relevant data
        $invoice = $checkout_session['invoice'];
        $adminSubject = 'checkout.session.completed >>>>>> ' ;
        $adminMessage = '';
        $adminMessage .= '<pre>'.print_r($checkout_session, true).'</pre>' ;
        
        $adminEmailData = [];
        $adminEmailData = [
            'fromEmail'     => FROM_EMAIL,
            'fromName'      => FROM_NAME,
            'toEmail'       => 'pratibhaprajapati.cts@gmail.com',
            'subject'       => $adminSubject,
            'message'       => $adminMessage,
        ];
        sendEmail($adminEmailData);

        // Save to database or perform actions
        log_message('info', "checkout session completed with $invoice .");

        // return $emailstatus;
        return true;
    }

    // customer.updated
    private function handleCustomerUpdated($event)
    {
        // Extract relevant data
        $customer_updated = $event['data']['object'];

        $event_type = $event['type'];
        $adminSubject = 'customer.updated';
        $adminMessage = '<pre>'.print_r($customer_updated, true).'</pre>' ;

        $adminEmailData = [];
        $adminEmailData = [
            'fromEmail'     => FROM_EMAIL,
            'fromName'      => FROM_NAME,
            'toEmail'       => 'pratibhaprajapati.cts@gmail.com',
            'subject'       => $adminSubject,
            'message'       => $adminMessage,
        ];
        sendEmail($adminEmailData);

        // Save to database or perform actions
        log_message('info', "customer.updated event occured");
        // return $this->response->setJSON($event); exit;
        return true;
    }

    // coupon.created
    private function handleCouponCreated($event)
    {
        // Extract relevant data
        $couponData = $event['data']['object'];
        $adminSubject = 'coupon.created';
        $adminMessage = '<pre>'.print_r($couponData, true).'</pre>' ;

        $adminEmailData = [];
        $adminEmailData = [
            'fromEmail'     => FROM_EMAIL,
            'fromName'      => FROM_NAME,
            'toEmail'       => 'pratibhaprajapati.cts@gmail.com',
            'subject'       => $adminSubject,
            'message'       => $adminMessage,
        ];
        sendEmail($adminEmailData);

        // Save to database or perform actions
        log_message('info', "coupon.created event occuredID: " . $couponData['id'] );
        // return $this->response->setJSON($event); exit;
        return true;
    }

    // coupon.deleted
    private function handleCouponDeleted($event)
    {
        // Extract relevant data
        $couponData = $event['data']['object'];

        $adminSubject = 'coupon.deleted';
        $adminMessage = '<pre>'.print_r($couponData, true).'</pre>' ;

        $this->db->couponModel
            ->where('coupon_code', $couponData['id'])
            ->set(['is_deleted' => getDBCurrentTime()])
            ->update();

        $getLastQuery = $this->db->getLastQuery();
        log_message('info', "stripeWebhookEvent handleCouponDeleted query: {$getLastQuery}");

        $adminEmailData = [];
        $adminEmailData = [
            'fromEmail'     => FROM_EMAIL,
            'fromName'      => FROM_NAME,
            'toEmail'       => 'pratibhaprajapati.cts@gmail.com',
            'subject'       => $adminSubject,
            'message'       => $adminMessage,
        ];
        sendEmail($adminEmailData);

        // Save to database or perform actions
        log_message('info', "coupon.deleted event occured ID: " . $couponData['id']);
        // return $this->response->setJSON($event); exit;
    }
    

    

    // Default
    private function handleDefault($event)
    {
        // Extract relevant data
        // $invoice = $checkout_session->invoice;
        $event_type = $event['type'];
        $adminSubject = 'unknown event type ' . $event['type'];
        $adminMessage = '<pre>'.print_r($event['type'], true).'</pre>' ;

        $adminEmailData = [];
        $adminEmailData = [
            'fromEmail'     => FROM_EMAIL,
            'fromName'      => FROM_NAME,
            'toEmail'       => 'pratibhaprajapati.cts@gmail.com',
            'subject'       => $adminSubject,
            'message'       => $adminMessage,
        ];
        sendEmail($adminEmailData);

        // Save to database or perform actions
        log_message('info', "Received unknown event type $event_type");
        // return $this->response->setJSON($event); exit;
        return true;
    }
}
