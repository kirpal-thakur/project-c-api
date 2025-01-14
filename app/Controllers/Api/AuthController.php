<?php

namespace App\Controllers\Api;

##use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\Shield\Models\UserModel;
use CodeIgniter\Shield\Entities\User;
use Google\Client;
use Google_Service_Oauth2;
use App\Models\UserMetaDataModel;
use App\Models\CountryModel;

use CodeIgniter\Shield\Models\UserIdentityModel;
use CodeIgniter\Shield\Authentication\Authenticators\Session;
use CodeIgniter\I18n\Time;
use CodeIgniter\Events\Events;

use App\Models\ClubApplicationModel;
use App\Models\UserNationalityModel;
use App\Models\LeagueClubModel;
use App\Models\TeamModel;
use App\Models\ClubModel;
use App\Models\LanguageModel;
use App\Models\EmailTemplateModel;



class AuthController extends ResourceController
{

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);

        $this->db           = \Config\Database::connect();
        $this->session      = \Config\Services::session();
    }


    public function register()
    {

        $userMetaDataModel      = new UserMetaDataModel();
        $clubModel              = new ClubModel();

        // echo '>>>>>>>>>>>> formatDate >>>> ' . formatDate(date('d-m-Y')); exit;

        $locale = $this->request->getLocale();
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        // validation
        $rules = [
            "username"      => "required|is_unique[users.username]",
            "email"         => "required|valid_email|is_unique[auth_identities.secret]",
            "first_name"    => "required",
            "last_name"     => "required",
            "role"          => "required",
            "lang"          => "required",
            "verification_link"    => "required",
            "user_domain"   => "required",
            'password'      => [
                'label' => 'Password',
                'rules' => [
                    'required',
                ],
                'errors' => [
                    'max_byte' => 'Auth.errorPasswordTooLongBytes',
                ]
            ],
            'password_confirm' => [
                'label' => 'Auth.passwordConfirm',
                'rules' => 'required|matches[password]',
            ],
        ];

        // Conditionally add 
        // if (in_array($this->request->getVar('role'), [2, 4])) {
        //     $rules['club_id'] = 'required';
        // }

        if (!$this->validate($rules)) {

            $response = [
                "status"    => false,
                "message"   => $this->validator->getErrors(),
                "data"      => []
            ];
        } else {

            $userEntity = [
                "username"      => $this->request->getVar("username"),
                "email"         => $this->request->getVar("email"),
                "password"      => $this->request->getVar("password"),
                "first_name"    => ucfirst($this->request->getVar("first_name")),
                "last_name"     => ucfirst($this->request->getVar("last_name")),
                "role"          => $this->request->getVar("role"),
                "lang"          => $this->request->getVar("lang"),
                "newsletter"    => $this->request->getVar("newsletter"),
                "user_domain"   => $this->request->getVar("user_domain"),
                "status"        => 1,
                //"active"        => 1,
            ];


            $role = $this->request->getVar("role");
            if ($role == 1) {
                $group = 'superadmin';
            } else if ($role == 2) {
                $group = 'club';
            } else if ($role == 3) {
                $group = 'scout';
            } else {
                $group = 'player';
            }

            // Get the User Provider (UserModel by default)
            $users = auth()->getProvider();

            $userData = new User($userEntity);
            $users->save($userData);

            $userInfo = $users->findByCredentials(['email' => $this->request->getVar("email")]); // find registered users
            $userInfo->addGroup($group);    // user user to group
            $userInfo->addPermission('admin.access');

            if ($this->request->getVar("newsletter")) {
                // add user to mailchimp
                $mailchimpData = [
                    'email'     => $this->request->getVar("email"),
                    'status'    => 'subscribed',
                    'firstname' => $this->request->getVar("first_name"),
                    'lastname'  => $this->request->getVar("last_name")
                ];
                $mailchimpRes = syncMailchimp($mailchimpData);
            }

            $meta_data = [];
            if ($role == 2) {
                if ($this->request->getVar("club_id")) {
                    $club_info = $clubModel->where('id', $this->request->getVar("club_id"))->first();
                    if ($club_info) {

                        $meta_data = [
                            [
                                'user_id'           => $userInfo->id,
                                'meta_key'          => 'pre_club_id',
                                'meta_value'        => $this->request->getVar("club_id"),
                            ],
                            [
                                'user_id'           => $userInfo->id,
                                'meta_key'          => 'club_name',
                                'meta_value'        => $club_info['club_name'],
                            ]
                        ];
                    }
                }
            }

            if ($role == 3) {
                if ($this->request->getVar("company_name")) {
                    $meta_data = [
                        [
                            'user_id'           => $userInfo->id,
                            'meta_key'          => 'company_name',
                            'meta_value'        => $this->request->getVar("company_name"),
                        ]
                    ];
                }
            }

            if ($role == 4) {
                // $group = 'player'; 
                if ($this->request->getVar("club_id")) {
                    $club_info = $clubModel->where('id', $this->request->getVar("club_id"));
                    if ($club_info) {

                        $meta_data = [
                            [
                                'user_id'           => $userInfo->id,
                                'meta_key'          => 'pre_club_id',
                                'meta_value'        => $this->request->getVar("club_id"),
                            ]
                        ];

                        if (!empty($this->request->getVar("club_taken_by"))) {
                            $meta_data[] = [
                                'user_id'           => $userInfo->id,
                                'meta_key'          => 'current_club',
                                'meta_value'        => $this->request->getVar("club_taken_by"),
                            ];
                        }
                    }
                }
            }

            if (count($meta_data) > 0) {
                // save data
                $userMetaDataModel->insertBatch($meta_data);
            }

            $emailTemplateModel = new EmailTemplateModel();

            $emailTypeAdmin = 'New user registration Admin';
            $adminRole = ADMIN_ROLES;
            $getEmailTemplateAdmin = $emailTemplateModel->where('type', $emailTypeAdmin)
                ->where('language', 2)          // 2 = german   
                ->whereIn('email_for', $adminRole)
                ->first();

            if ($getEmailTemplateAdmin) {

                $replacements = [
                    '{firstName}'           => $this->request->getVar("first_name"),
                    '{lastName}'            => $this->request->getVar("last_name"),
                    '{userName}'            => $this->request->getVar("username"),
                    '{userRole}'            => $group,
                    '{emailAdresse}'        => $this->request->getVar("email"),
                    '{registrationDate}'    => formatDate(date('d-m-Y')),
                    // '{link}' => '<a href="' . $tokenLink . '" target="_blank">link</a>'
                ];

                // echo ' >>>>>>>>>>>> replacements >>>>>>>>>>> ';
                // pr($replacements);

                $adminSubject = $getEmailTemplateAdmin['subject'];

                // Replace placeholders with actual values in the email body
                $content = strtr($getEmailTemplateAdmin['content'], $replacements);
                $adminMessage = view('emailTemplateView', ['message' => $content, 'disclaimerText' => getEmailDisclaimer(2), 'footer' => getEmailFooter(2)]);

                $adminEmailData = [
                    'fromEmail'     => FROM_EMAIL,
                    'fromName'      => FROM_NAME,
                    'toEmail'       => ADMIN_EMAIL,
                    'subject'       => $adminSubject,
                    'message'       => $adminMessage,
                ];
                sendEmail($adminEmailData);
            }




            // $emailType = 'Email Verification while registration';
            $emailType = 'Email Confirmation to users while registration';

            $getEmailTemplate = $emailTemplateModel->where('type', $emailType)
                ->where('language', $currentLang)
                // ->where('location', $user->user_domain)
                ->whereIn('email_for', [$role])
                ->first();
            if ($getEmailTemplate) {

                $token_01 = base64_encode($this->request->getVar("email"));
                $token_02 = base64_encode($userInfo->created_at);

                // $tokenLink = base_url() . 'api/verify-email/' . $token_01 . '/' . $token_02;

                // if($this->request->getVar("verification_link")){
                $tokenLink = $this->request->getVar("verification_link") . '?token=' . $token_01 . '&time=' . $token_02;
                // }
                $userDomain = $this->request->getVar("user_domain");
                $replacements = [
                    '{firstName}'           => $this->request->getVar("first_name"),
                    '{confirmationLink}'    => '<a href="' . $tokenLink . '" target="_blank">' . lang('Email.confirm') . '</a>',
                    '{loginLink}'           => '<a href="' . getPageInUserLanguage($userDomain, $currentLang) . '">' . lang('Email.login') . '</a>',

                ];

                // send email
                // $subject = 'Forgot Password on Succer You Sports AG';
                // $message = "Dear $firstName, <br>You have requested password reset on Succer You Sports AG. Please <a href=" . $magicLinkUrl . ">click on this link</a> and change your password";

                $subject = $getEmailTemplate['subject'];

                // Replace placeholders with actual values in the email body
                $content = strtr($getEmailTemplate['content'], $replacements);
                $message = view('emailTemplateView', ['message' => $content, 'disclaimerText' => getEmailDisclaimer($currentLang), 'footer' => getEmailFooter($currentLang)]);


                // $userSubject = 'Your account is registered on Succer You Sports AG';
                // $userMessage = '';
                // $userMessage .= 'Dear ' . $this->request->getVar("first_name") . ', <br>Your account is registered on Succer You Sports AG';
                // $userMessage .= '<br>Click <a href="' . $tokenLink . '">Here</a> to activate your account';
                $toEmail = $this->request->getVar("email");
                // $toEmail = 'pratibhaprajapati.cts@gmail.com';
                $userEmailData = [
                    'fromEmail'     => FROM_EMAIL,
                    'fromName'      => FROM_NAME,
                    'toEmail'       => $toEmail,
                    'subject'       => $subject,
                    'message'       => $message,
                ];
                sendEmail($userEmailData);
            }


            // create Activity log
            // $activity_data = [
            //     'user_id'               => $userInfo->id,
            //     'activity_type_id'      => 7,        // register
            //     'activity'              => 'user(' . $this->request->getVar("email") . ')  registered successfully',
            //     'ip'                    => $this->request->getIPAddress()
            // ];
            // $activity = lang('App.new_registration',['userEmail'=>$this->request->getVar("email")]);
            // $activity_data = [
            //     'user_id'               => $userInfo->id,
            //     'activity_type_id'      => 7,        // register
            //     'activity'              => $activity,
            //     'ip'                    => $this->request->getIPAddress()
            // ];
            // createActivityLog($activity_data);
            $activity_data = array();
            $languageService = \Config\Services::language();
            $languages = ['en', 'de', 'fr', 'it', 'es', 'pt', 'da', 'sv'];
            $activity_data = [
                'user_id'               => $userInfo->id,
                'activity_type_id'      => 7, // Register activity type
                'ip'                    => $this->request->getIPAddress()
            ];
            foreach ($languages as $lang) {
                $languageService->setLocale($lang);
                $translated_message = lang('App.new_registration', ['userEmail' => $this->request->getVar("email")]);
                $activity_data['activity_' . $lang] = $translated_message;
            }
            createActivityLog($activity_data);
            $response = [
                "status"    => true,
                "message"   => lang('App.register_success'),
                "data"      => [
                    // 'lang' => $locale
                    'userInfo' => $userInfo
                ]
            ];
        }

        return $this->respondCreated($response);
    }


    // Post
    /* public function register_new_(){
        $locale = $this->request->getLocale();
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        // validation
        $rules = [
                    "username"      => "required|is_unique[users.username]",
                    "email"         => "required|valid_email|is_unique[auth_identities.secret]",
                    "first_name"    => "required",
                    "last_name"     => "required",
                    "role"          => "required",
                    "lang"          => "required",
                    //"newsletter"    => "required",
                    "user_domain"   => "required",
                    'password'      => [
                            'label' => 'Password',
                            'rules' => [
                                'required',
                            ],
                            'errors' => [
                                'max_byte' => 'Auth.errorPasswordTooLongBytes',
                            ]
                        ],
                        'password_confirm' => [
                            'label' => 'Auth.passwordConfirm',
                            'rules' => 'required|matches[password]',
                        ],
                        // 'club_id'       => 'if_exist|required_with[role]|in_list[2]',
                ];

        // Conditionally add 
        if ($this->request->getVar('role') == 2) {
            $rules['club_id'] = 'required';
        } 

        if(!$this->validate($rules)){

            $response = [
                "status"    => false,
                "message"   => $this->validator->getErrors(),
                "data"      => []
            ];
        } else {

            if ($this->request->getVar('role') == 2) {
                $clubApplicationModel = new ClubApplicationModel();

                $isExist =  $clubApplicationModel->where('email', $this->request->getVar("email"))->orWhere('username', $this->request->getVar("username"))->first();

                if($isExist){
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.email_user_exist'),
                        "data"      => []
                    ];
                } else {
                    $clubData = [
                        "username"      => $this->request->getVar("username"),
                        "email"         => $this->request->getVar("email"),
                        "password"      => base64_encode($this->request->getVar("password")),
                        "first_name"    => $this->request->getVar("first_name"),
                        "last_name"     => $this->request->getVar("last_name"),
                        "role"          => $this->request->getVar("role"),
                        "lang"          => $this->request->getVar("lang"),
                        "newsletter"    => $this->request->getVar("newsletter"),
                        "user_domain"   => $this->request->getVar("user_domain"),
                        "club_id"       => $this->request->getVar("club_id"),
                    ];
                    if($clubApplicationModel->save($clubData)){

                        $tokenLink = base_url() . 'api/verify-club-email/'.base64_encode($this->request->getVar("email"));
    
                        $adminSubject = 'New User requested for club on Succer You Sports AG';
                        $adminMessage = 'Dear Admin, <br>New user('.$this->request->getVar("email").') is requested for club' ;
    
                        $adminEmailData = [
                            'fromEmail'     => FROM_EMAIL,
                            'fromName'      => FROM_NAME,
                            'toEmail'       => ADMIN_EMAIL,
                            'subject'       => $adminSubject,
                            'message'       => $adminMessage,
                        ];
                        sendEmail($adminEmailData);
    
                        $userMessage = '';
                        $userSubject = 'Your application submitted on Succer You Sports AG';
                        $userMessage .= 'Dear '.$this->request->getVar("first_name").', <br>Your application for the club registration is submitted on Succer You Sports AG' ;
                        $userMessage .= '<br>Click <a href="'.$tokenLink.'">Here</a> to activate your account';
    
                        $userEmailData = [
                            'fromEmail'     => FROM_EMAIL,
                            'fromName'      => FROM_NAME,
                            'toEmail'       => $this->request->getVar("email"),
                            'subject'       => $userSubject,
                            'message'       => $userMessage,
                        ];
                        sendEmail($userEmailData);
    
                        // create Activity log
                        $activity_data = [
                            'user_id'               => $clubApplicationModel->getInsertID(),
                            'activity_type_id'      => 7,        // register
                            'activity'              =>'user('.$this->request->getVar("email").')  applied for club role',
                            'ip'                    => $this->request->getIPAddress()
                        ];
                        createActivityLog($activity_data);
    
                        $response = [
                            "status"    => true,
                            "message"   => lang('App.club_application_success'),
                            "data"      => []
                        ];
                    } else {
                        $response = [
                            "status"    => false,
                            "message"   => lang('App.club_application_failed'),
                            "data"      => []
                        ];
                    }
                }
                 
            } else {
                $userEntity = [
                    "username"      => $this->request->getVar("username"),
                    "email"         => $this->request->getVar("email"),
                    "password"      => $this->request->getVar("password"),
                    "first_name"    => $this->request->getVar("first_name"),
                    "last_name"     => $this->request->getVar("last_name"),
                    "role"          => $this->request->getVar("role"),
                    "lang"          => $this->request->getVar("lang"),
                    "newsletter"    => $this->request->getVar("newsletter"),
                    "user_domain"   => $this->request->getVar("user_domain"),
                    "status"        => 1,
                    "active"        => 0,
                ];

                $role = $this->request->getVar("role");
                if( $role == 1 ){
                    $group = 'superadmin'; 
                } else if( $role == 2 ){
                    $group = 'club'; 
                } else if( $role == 3 ){
                    $group = 'scout'; 
                } else {
                    $group = 'player'; 
                }

                // Get the User Provider (UserModel by default)
                $users = auth()->getProvider();

                $userData = new User($userEntity);
                
                if($users->save($userData)){

                    $userInfo = $users->findByCredentials(['email' => $this->request->getVar("email")]); // find registered users
                    $userInfo->addGroup($group);    // user user to group

                    // $tokenData =  'email=.'$this->request->getVar("email").'&created_at='.$userInfo->created_at;
                    // $token = base64_encode($tokenData);
                    $tokenLink = base_url() . 'api/verify-email/'.base64_encode($this->request->getVar("email")).'/'.base64_encode($userInfo->created_at);

                    if($this->request->getVar("newsletter")){
                        // add user to mailchimp
                        $mailchimpData = [
                            'email'     => $this->request->getVar("email"),
                            'status'    => 'subscribed',
                            'firstname' => $this->request->getVar("first_name"),
                            'lastname'  => $this->request->getVar("last_name")
                        ];
                        $mailchimpRes = syncMailchimp($mailchimpData);
                    }
                

                    $adminSubject = 'New User registered on Succer You Sports AG';
                    $adminMessage = 'Dear Admin, <br>New user('.$this->request->getVar("email").') is registered' ;

                    $adminEmailData = [
                        'fromEmail'     => FROM_EMAIL,
                        'fromName'      => FROM_NAME,
                        'toEmail'       => ADMIN_EMAIL,
                        'subject'       => $adminSubject,
                        'message'       => $adminMessage,
                    ];
                    sendEmail($adminEmailData);

                    $userMessage = '';
                    $userSubject = 'Your account is registered on Succer You Sports AG';
                    $userMessage .= 'Dear '.$this->request->getVar("first_name").', <br>Your account is registered on Succer You Sports AG' ;
                    $userMessage .= '<br>Click <a href="'.$tokenLink.'">Here</a> to activate your account';

                    $userEmailData = [
                        'fromEmail'     => FROM_EMAIL,
                        'fromName'      => FROM_NAME,
                        'toEmail'       => $this->request->getVar("email"),
                        'subject'       => $userSubject,
                        'message'       => $userMessage,
                    ];
                    sendEmail($userEmailData);

                    // create Activity log
                    $activity_data = [
                        'user_id'               => $userInfo->id,
                        'activity_type_id'      => 7,        // register
                        'activity'              =>'user('.$this->request->getVar("email").')  registered successfully',
                        'ip'                    => $this->request->getIPAddress()
                    ];
                    createActivityLog($activity_data);
                    $response = [
                        "status"    => true,
                        "message"   => lang('App.register_success'),
                        "data"      => []
                    ];
                } else {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.register_failed'),
                        "data"      => []
                    ];
                }
            }
            
        } 

        return $this->respondCreated($response);

    } */

    public function verifyEmail($token = null, $time = null)
    {

        $users = auth()->getProvider();
        $user = $users->findByCredentials(['email' => base64_decode($token)]);
        // pr($user);

        if ($user) {

            if($user->is_email_verified == 1){
                $response = [
                    "status"    => false,
                    "message"   => lang('App.emailAlreadyVerified'),
                ];
            } else {

                $data = [
                    'id'    =>  $user->id,
                    'active' => 1,
                    'is_email_verified' => 1,
                ];

                if ($users->save($data)) {
                    $toEmail = $user->email;

                    $emailTemplateModel = new EmailTemplateModel();
                    $emailType = '';
                    /* if($user->role == 2){
                        $emailType = 'New User registration Club';
                    }

                    if($user->role == 3){
                        $emailType = 'New User registration Scout';
                    }

                    if($user->role == 4){
                        $emailType = 'New User registration Talent';
                    }

                    $userRole = [$user->role] ;
                    $getEmailTemplate = $emailTemplateModel->where('type', $emailType)
                        ->where('language', $user->lang)           
                        ->whereIn('email_for', $userRole)
                        ->first();

                    if($getEmailTemplate){

                        $replacements = [
                            '{firstName}'           => $user->first_name,
                            '{link}' => '<a href="' . site_url() . '" target="_blank">link</a>'
                        ];

                        $subject = $getEmailTemplate['subject'];

                        // Replace placeholders with actual values in the email body
                        $content = strtr($getEmailTemplate['content'], $replacements);
                        $message = view('emailTemplateView' , ['message' => $content,  'disclaimerText' => getEmailDisclaimer($user->lang), 'footer' => getEmailFooter($user->lang) ]);

                        $emailData = [
                            'fromEmail'     => FROM_EMAIL,
                            'fromName'      => FROM_NAME,
                            'toEmail'       => $toEmail,
                            'subject'       => $subject,
                            'message'       => $message,
                        ];
                        sendEmail($emailData);
                    } */


                    $adminEmailType = 'Email Confirmation to admin after user confirms their email';
                    // $adminEmailTemplate = $emailTemplateModel->where('type', $adminEmailType)
                    //     ->where('language', 2)
                    //     ->whereIn('email_for', ADMIN_ROLES)
                    //     ->first();

                    $adminEmailTemplate = getEmailTemplate($adminEmailType, 2, ADMIN_ROLES );


                    if ($adminEmailTemplate) {

                        $replacements = [
                            '{userName}'    => $user->username,
                            '{userRole}'    => getRoleNameByID($user->role),
                            '{userEmail}'   => $user->email,
                            '{createdDate}' => formatDate($user->created_at),
                        ];

                        $subject = $adminEmailTemplate['subject'];

                        // Replace placeholders with actual values in the email body
                        $content = strtr($adminEmailTemplate['content'], $replacements);
                        $message = view('emailTemplateView', ['message' => $content,  'disclaimerText' => getEmailDisclaimer($user->lang), 'footer' => getEmailFooter($user->lang)]);

                        $emailData = [
                            'fromEmail'     => FROM_EMAIL,
                            'fromName'      => FROM_NAME,
                            'toEmail'       => ADMIN_EMAIL,
                            'subject'       => $subject,
                            'message'       => $message,
                        ];
                        sendEmail($emailData);
                    }

                    // create Activity log
                    // $activity_data = [
                    //     'user_id'               => $user->id,
                    //     'activity_type_id'      => 2,        // updated
                    //     'activity'              => 'verified your email(' . $toEmail . ') successfully',
                    //     'ip'                    => $this->request->getIPAddress()
                    // ];
                    // // echo '<pre>'; print_r($activity_data); die;
                    // 
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
                        $translated_message = lang('App.verifyEmail', ['userEmail' => $toEmail]);
                        $activity_data['activity_' . $lang] = $translated_message;
                    }
                    createActivityLog($activity_data);
                    $response = [
                        "status"    => true,
                        "message"   => lang('App.email_verified'),
                    ];
                } else {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.email_notVerified'),
                    ];
                }
            }
        } else {
            $response = [
                "status"    => false,
                "message"   => lang('App.email_notExist'),
            ];
        }

        return $this->respondCreated($response);
    }

    public function verifyClubEmail($token = null)
    {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $clubApplicationModel = new ClubApplicationModel();
        $isExist = $clubApplicationModel->where('email', base64_decode($token))->first();

        if ($isExist) {

            $data = [
                'id'    =>  $isExist['id'],
                'is_email_verified' => 1,
            ];
            $clubApplicationModel->save($data);

            // create Activity log
            $activity_data = [
                'user_id'               => $isExist['id'],
                'activity_type_id'      => 2,        // updated
                'activity'              => 'Club Application (' . base64_decode($token) . ')  email verified successfully',
                'ip'                    => $this->request->getIPAddress()
            ];
            createActivityLog($activity_data);

            $response = [
                "status"    => true,
                "message"   => lang('App.email_verified'),
            ];
        } else {
            $response = [
                "status"    => false,
                "message"   => lang('App.email_notExist'),
            ];
        }

        return $this->respondCreated($response);
    }

    public function verifyClubApplication($app_id = null)
    {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));
        $clubApplicationModel = new ClubApplicationModel();


        $rules = ["status"    => "required"];

        if (!$this->validate($rules)) {

            $response = [
                "status"    => false,
                "message"   => $this->validator->getErrors(),
                "data"      => []
            ];
        } else {

            $isExist = $clubApplicationModel->where('id', $app_id)->first();

            if ($isExist) {
                $clubApplicationModel->update($isExist['id'], ['status' => $this->request->getVar("status")]);
                $response = [
                    "status"    => true,
                    "message"   => 'Club application status updated',
                    "data"      => []
                ];

                if ($this->request->getVar("status") == "Verified") {

                    // Get the User Provider (UserModel by default)
                    $users = auth()->getProvider();
                    $user = $users->findById($isExist["club_id"]);

                    $userEntity = [
                        "username"      => $isExist["username"],
                        "email"         => $isExist["email"],
                        "password"      => base64_decode($isExist["password"]),
                        "first_name"    => $isExist["first_name"],
                        "last_name"     => $isExist["last_name"],
                        "role"          => 2,
                        "lang"          => $isExist["lang"],
                        "newsletter"    => $isExist["newsletter"],
                        "user_domain"   => $isExist["user_domain"],
                        "status"        => 2,
                        "active"        => 1,
                        "is_email_verified"  => 1,
                        // 'created_at'       => date('Y-m-d H:i:s'),
                    ];

                    $user->fill($userEntity);
                    $users->save($user);
                    $user->addGroup('club');

                    $tokenLink = base_url() . 'api/verify-email/' . base64_encode($isExist["email"]) . '/' . base64_encode($user->created_at);

                    if ($isExist["newsletter"] == 1) {
                        // add user to mailchimp
                        $mailchimpData = [
                            'email'     => $isExist["email"],
                            'status'    => 'subscribed',
                            'firstname' => $isExist["first_name"],
                            'lastname'  => $isExist["last_name"]
                        ];
                        $mailchimpRes = syncMailchimp($mailchimpData);
                    }


                    $adminSubject = 'New User registered on Succer You Sports AG';
                    $adminMessage = 'Dear Admin, <br>New user(' . $isExist["email"] . ') is verified as club on Succer You Sports AG';

                    $adminEmailData = [
                        'fromEmail'     => FROM_EMAIL,
                        'fromName'      => FROM_NAME,
                        'toEmail'       => ADMIN_EMAIL,
                        'subject'       => $adminSubject,
                        'message'       => $adminMessage,
                    ];
                    sendEmail($adminEmailData);

                    $userMessage = '';
                    $userSubject = 'Your account is verified as club on Succer You Sports AG';
                    $userMessage .= 'Dear ' . $isExist["first_name"] . ', <br>Your account is verified as club on Succer You Sports AG';
                    $userMessage .= '<br>Click <a href="' . $tokenLink . '">Here</a> to activate your account';

                    $userEmailData = [
                        'fromEmail'     => FROM_EMAIL,
                        'fromName'      => FROM_NAME,
                        'toEmail'       => $isExist["email"],
                        'subject'       => $userSubject,
                        'message'       => $userMessage,
                    ];
                    sendEmail($userEmailData);

                    // create Activity log
                    $activity_data = [
                        'user_id'               => $user->id,
                        'activity_type_id'      => 7,        // register
                        'activity'              => 'user(' . $isExist["email"] . ')  is verified as club successfully',
                        'ip'                    => $this->request->getIPAddress()
                    ];
                    createActivityLog($activity_data);
                    $response = [
                        "status"    => true,
                        "message"   => lang('App.register_success'),
                        "data"      => []
                    ];
                }
            } else {
                $response = [
                    "status"    => false,
                    "message"   => 'Application does not exist',
                    "data"      => []
                ];
            }
        }
        return $this->respondCreated($response);
    }


    public function addClub()
    {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $userMetaDataModel      = new UserMetaDataModel();
        $userNationalityModel   = new UserNationalityModel();
        $leagueClubModel        = new LeagueClubModel();
        $teamModel              = new TeamModel();


        // pr($this->request->getVar()); exit;

        // validation
        $rules = [
            "username"      => "required|is_unique[users.username]",
            "email"         => "required|valid_email|is_unique[auth_identities.secret]",
            "first_name"    => "required",
            "last_name"     => "required",
            //"role"          => "required",
            "lang"          => "required",
            "user_domain"   => "required",
            "club_name"     => "required",
            "country"       => "required",
            "league"        => "required",
            'password'      => [
                'label' => 'Password',
                'rules' => [
                    'required',
                ],
                'errors' => [
                    'max_byte' => 'Auth.errorPasswordTooLongBytes',
                ]
            ]
        ];

        $validationRule = [
            'logo' => [
                'label' => 'Upload logo',
                'rules' => [
                    'uploaded[logo]',
                    'ext_in[logo,jpg,jpeg,png]',
                    'max_size[logo,200000]',
                ],
                'errors' => [
                    'uploaded'  => lang('App.uploaded', ['file' => 'jpg, jpeg, png']),
                    'ext_in'   => lang('App.ext_in', ['file' => 'jpg, jpeg, png']),
                    'max_size'  => lang('App.max_size', ['size' => '200000']),
                ],
            ],
        ];

        if ((!$this->validate($rules) || !$this->validateData([], $validationRule))) {
            $errors = $this->validator->getErrors();

            // pr($errors);

            $response = [
                "status"    => false,
                "message"   => lang('App.provideValidData'),
                "data"      => ['errors' => $errors]
            ];
        } else {

            $userEntity = [
                "username"      => $this->request->getVar("username"),
                "email"         => $this->request->getVar("email"),
                "password"      => $this->request->getVar("password"),
                "first_name"    => $this->request->getVar("first_name"),
                "last_name"     => $this->request->getVar("last_name"),
                "role"          => 2,
                "lang"          => $this->request->getVar("lang"),
                "user_domain"   => $this->request->getVar("user_domain"),
                "status"        => 1,
                "active"        => 0,
            ];

            // Get the User Provider (UserModel by default)
            $users = auth()->getProvider();

            $userData = new User($userEntity);
            $users->save($userData);

            $userInfo = $users->findByCredentials(['email' => $this->request->getVar("email")]); // find registered users
            $userInfo->addGroup('club');    // user user to group

            $logo = $this->request->getFile('logo');

            if (! $logo->hasMoved()) {
                $filepath = WRITEPATH . 'uploads/' . $logo->store('');

                $save_data = [
                    [
                        'user_id'           => $userInfo->id,
                        'meta_key'          => 'profile_image',
                        'meta_value'        => $logo->getName(),
                    ],
                    [
                        'user_id'           => $userInfo->id,
                        'meta_key'          => 'club_name',
                        'meta_value'        => $this->request->getVar('club_name'),
                    ]
                ];
                // save data
                $userMetaDataModel->insertBatch($save_data);
            }

            // add club country
            $userNationalityModel->save(['user_id' => $userInfo->id, 'country_id' => $this->request->getVar('country')]);

            // add club league
            $leagueData = [
                'club_id'   => $userInfo->id,
                'league_id' => $this->request->getVar('league')
            ];
            $leagueClubModel->save($leagueData);


            $team_data = [
                [
                    'club_id'           => $userInfo->id,
                    'team_type'         => 'A',
                    'country_id'        => $this->request->getVar('country'),
                ],
                [
                    'club_id'           => $userInfo->id,
                    'team_type'         => 'B',
                    'country_id'        => $this->request->getVar('country'),
                ],
                [
                    'club_id'           => $userInfo->id,
                    'team_type'         => 'U23',
                    'country_id'        => $this->request->getVar('country'),
                ],
                [
                    'club_id'           => $userInfo->id,
                    'team_type'         => 'U21',
                    'country_id'        => $this->request->getVar('country'),
                ],
                [
                    'club_id'           => $userInfo->id,
                    'team_type'         => 'U19',
                    'country_id'        => $this->request->getVar('country'),
                ],
                [
                    'club_id'           => $userInfo->id,
                    'team_type'         => 'U17',
                    'country_id'        => $this->request->getVar('country'),
                ],
            ];

            // save data
            $teamModel->insertBatch($team_data);

            // create Activity log
            $activity_data = [
                'user_id'               => $userInfo->id,
                'activity_type_id'      => 7,        // register
                'activity'              => 'Club (' . $this->request->getVar("email") . ')  added successfully',
                'ip'                    => $this->request->getIPAddress()
            ];
            createActivityLog($activity_data);

            $response = [
                "status"    => true,
                "message"   => lang('App.register_success'),
                "data"      => []
            ];
        }

        return $this->respondCreated($response);
    }


    // Post
    /* public function register_old(){
        $locale = $this->request->getLocale();
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        // validation
        $rules = [
                    "username"      => "required|is_unique[users.username]",
                    "email"         => "required|valid_email|is_unique[auth_identities.secret]",
                    "first_name"    => "required",
                    "last_name"     => "required",
                    "role"          => "required",
                    "lang"          => "required",
                    //"newsletter"    => "required",
                    "user_domain"   => "required",
                    'password'      => [
                            'label' => 'Password',
                            'rules' => [
                                'required',
                            ],
                            'errors' => [
                                'max_byte' => 'Auth.errorPasswordTooLongBytes',
                            ]
                        ],
                        'password_confirm' => [
                            'label' => 'Auth.passwordConfirm',
                            'rules' => 'required|matches[password]',
                        ],
                ];

        if(!$this->validate($rules)){

            $response = [
                "status"    => false,
                "message"   => $this->validator->getErrors(),
                "data"      => []
            ];
        } else {

            $userEntity = [
                "username"      => $this->request->getVar("username"),
                "email"         => $this->request->getVar("email"),
                "password"      => $this->request->getVar("password"),
                "first_name"    => $this->request->getVar("first_name"),
                "last_name"     => $this->request->getVar("last_name"),
                "role"          => $this->request->getVar("role"),
                "lang"          => $this->request->getVar("lang"),
                "newsletter"    => $this->request->getVar("newsletter"),
                "user_domain"   => $this->request->getVar("user_domain"),
                "status"        => 1,
                "active"        => 1,
            ];

            
            $role = $this->request->getVar("role");
            if( $role == 1 ){
                $group = 'superadmin'; 
            } else if( $role == 2 ){
                $group = 'club'; 
            } else if( $role == 3 ){
                $group = 'scout'; 
            } else {
                $group = 'player'; 
            }

            // Get the User Provider (UserModel by default)
            $users = auth()->getProvider();

            $userData = new User($userEntity);
            $users->save($userData);

            $userInfo = $users->findByCredentials(['email' => $this->request->getVar("email")]); // find registered users
            $userInfo->addGroup($group);    // user user to group

            if($this->request->getVar("newsletter")){
                // add user to mailchimp
                $mailchimpData = [
                    'email'     => $this->request->getVar("email"),
                    'status'    => 'subscribed',
                    'firstname' => $this->request->getVar("first_name"),
                    'lastname'  => $this->request->getVar("last_name")
                ];
                $mailchimpRes = syncMailchimp($mailchimpData);
            }
           

            $adminSubject = 'New User registered on Succer You Sports AG';
            $adminMessage = 'Dear Admin, <br>New user('.$this->request->getVar("email").') is registered' ;

            $adminEmailData = [
                'fromEmail'     => FROM_EMAIL,
                'fromName'      => FROM_NAME,
                'toEmail'       => ADMIN_EMAIL,
                'subject'       => $adminSubject,
                'message'       => $adminMessage,
            ];
            sendEmail($adminEmailData);


            $userSubject = 'Your account is registered on Succer You Sports AG';
            $userMessage = 'Dear '.$this->request->getVar("first_name").', <br>Your account is registered on Succer You Sports AG' ;

            $userEmailData = [
                'fromEmail'     => FROM_EMAIL,
                'fromName'      => FROM_NAME,
                'toEmail'       => $this->request->getVar("email"),
                'subject'       => $userSubject,
                'message'       => $userMessage,
            ];
            sendEmail($userEmailData);

            // create Activity log
            $activity_data = [
                'user_id'               => $userInfo->id,
                'activity_type_id'      => 7,        // register
                'activity'              =>'user('.$this->request->getVar("email").')  registered successfully',
                'ip'                    => $this->request->getIPAddress()
            ];
            createActivityLog($activity_data);

            $response = [
                "status"    => true,
                "message"   => lang('App.register_success'),
                "data"      => ['lang' => $locale]
            ];
        } 

        return $this->respondCreated($response);

    } */

    // Post
    public function login()
    {

        if (auth()->loggedIn()) {
            auth()->logout();
        }

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $domain_id = $lang_id = 0;
        if ($this->request->getHeader('Domain')) {
            $domainHeader = $this->request->getHeader('Domain');
            $domain_id =  $domainHeader->getValue();
        }

        if ($this->request->getHeader('Lang')) {
            $langHeader = $this->request->getHeader('Lang');
            $lang_id =  $langHeader->getValue();
        }

        // login via form
        // validation
        $rules = [
            "email"     => "required|valid_email",
            "password"  => "required"
        ];

        if (!$this->validate($rules)) {
            $response = [
                "status"    => false,
                "message"   => $this->validator->getErrors(),
                "data"      => []
            ];
        } else {

            $credentials = [
                'email'    => $this->request->getVar('email'),
                'password' => $this->request->getVar('password')
            ];

            $loginAttempt = auth()->attempt($credentials);
            if (!$loginAttempt->isOK()) {

                $activity_data = [
                    'activity_type_id'      => 6,
                    'activity'              => 'user(' . $this->request->getVar("email") . ')  log in failed',
                    'ip'                    => $this->request->getIPAddress()
                ];
                createActivityLog($activity_data);

                $response = [
                    "status"    => false,
                    "message"   => lang('App.login_invalidCredentials'),
                    "data"      => ['error' => $loginAttempt->reason()]
                ];
            } else {

                $userMetaDataModel = new UserMetaDataModel();

                $users = auth()->getProvider();
                $user = $users->findById(auth()->id());

                // Valid domain check
                // if($user->user_domain == $domain_id){

                if ($user->is_email_verified != 1) {
                    auth()->logout();

                    $response = [
                        "status"    => false,
                        "message"   => lang('App.loginEmailNotVerify'),
                        "data"      => ['error' => lang('App.loginEmailNotVerify')]
                    ];
                    return $this->respondCreated($response);
                }

                // Update last login date time after successfull login
                $user->fill([
                    // 'last_active' => date('Y-m-d H:i:s')
                    'last_active' => getDBCurrentTime()
                ]);
                $users->save($user);

                // if(!empty($user->status) && in_array($user->status,[1,3])){
                //     $this->logout();
                //     $response = [
                //         "status"    => false,
                //         // "message"   => lang('App.login_invalidCredentials'),
                //         "message"   => 'Your Profile is not Verified Yet.',
                //         "data"      => ['error' => $loginAttempt->reason()]
                //     ];
                //     return $this->respondCreated($response);
                // }

                $imagePath = base_url() . 'uploads/';

                $profileImage = $userMetaDataModel->where('user_id', auth()->id())->where('meta_key', 'profile_image')->first();
                if ($profileImage) {
                    $user->profile_image = $profileImage['meta_value'];
                    $user->profile_image_path = $imagePath . $profileImage['meta_value'];
                }

                $token = $user->generateAccessToken(SECRET_KEY);
                $auth_token = $token->raw_token;

                // create Activity log
                // $activity = lang('App.login_success');
                // create Activity log
                // $activity_data = [
                //     'user_id'               => auth()->id(),
                //     'activity_type_id'      => 5,        // login
                //     'activity'              => $activity,
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
                    $translated_message = lang('App.login_success');
                    $activity_data['activity_' . $lang] = $translated_message;
                }
                // createActivityLog($activity_data);
                createActivityLog($activity_data);

                $response = [
                    "status"    => true,
                    "message"   => lang('App.login_success'),
                    "data"      => [
                        "token"     => $auth_token,
                        "user_data" => $user
                    ]
                ];
                // } else {
                //     $activity_data = [
                //         'activity_type_id'      => 6,
                //         'activity'              => 'user(' . $this->request->getVar("email") . ')  log in failed',
                //         'ip'                    => $this->request->getIPAddress()
                //     ];
                //     createActivityLog($activity_data);

                //     $response = [
                //         "status"    => false,
                //         "message"   => lang('App.login_invalidCredentials'),
                //         "data"      => ['error' => lang('App.loginInvalidDomain') ]
                //     ];
                // }
            }
        }
        return $this->respondCreated($response);
    }

    public function googleLogin()
    {
        require_once APPPATH . 'Libraries/vendor/autoload.php';

        //$client = new Google_Client();
        $client = new Client();
        $client->setClientId(getenv('google.client_id'));
        $client->setClientSecret(getenv('google.client_secret'));
        $client->setRedirectUri(getenv('google.redirect_uri'));
        $client->addScope("email");
        $client->addScope("profile");

        $authUrl = $client->createAuthUrl();
        return redirect()->to($authUrl);
    }

    public function googleCallback()
    {
        $response = [];
        require_once APPPATH . 'Libraries/vendor/autoload.php';

        //$client = new Google_Client();
        $client = new Client();
        $client->setClientId(getenv('google.client_id'));
        $client->setClientSecret(getenv('google.client_secret'));
        $client->setRedirectUri(getenv('google.redirect_uri'));

        $url =  isset($_SERVER['HTTPS']) &&
            $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
        $url .= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $parse_url = parse_url($url);
        $uri = new \CodeIgniter\HTTP\URI($url);

        $googleString =  $uri->getQuery(['only' => ['code']]);
        parse_str($googleString, $googleData);

        $googleCode = $googleData['code'];
        if (isset($googleCode) && !empty($googleCode)) {

            $token = $client->fetchAccessTokenWithAuthCode($googleCode);
            $client->setAccessToken($token);

            $oauth2 = new Google_Service_Oauth2($client);
            $google_account_info = $oauth2->userinfo->get();

            if ($google_account_info) {
                $email = $google_account_info->email;
                $first_name = $google_account_info->givenName;
                $last_name = $google_account_info->familyName;
                $oauth_id = $google_account_info->id;
                $picture = $google_account_info->picture;

                $users = auth()->getProvider();
                $email_exist = $users->findByCredentials(['email' => $email]);

                if ($email_exist) {

                    $token = $email_exist->generateAccessToken(SECRET_KEY);
                    $auth_token = $token->raw_token;

                    // create Activity log
                    $activity_data = [
                        'user_id'               => $email_exist->id,
                        'activity_type_id'      => 5,        // login
                        'activity'              => 'logged in successfully via Google Login',
                        'ip'                    => $this->request->getIPAddress()
                    ];
                    createActivityLog($activity_data);

                    // Return a success message as a JSON response
                    $response = [
                        "status"    => true,
                        "message"   => lang('App.login_success'),
                        "data"      => [
                            'token' => $auth_token,
                            "user_data" => $email_exist
                        ]
                    ];
                } else {
                    // create Activity log
                    $activity_data = [
                        'activity_type_id'      => 6,      // failed login
                        'activity'              => 'user(' . $email . ')  log in failed via Google Login',
                        'ip'                    => $this->request->getIPAddress()
                    ];
                    createActivityLog($activity_data);

                    $response = [
                        "status"    => false,
                        "message"   => lang('App.login_invalidCredentials'),
                        "data"      => []
                    ];
                }
            }
        } else {

            $response = [
                "status"    => true,
                "message"   => lang('App.login_invalidCredentials'),
                "data"      => []
            ];
        }

        return $this->respondCreated($response);
    }

    public function googleSignIn()
    {
        //$response = [];

        if ($this->request->getVar("email")) {

            $users = auth()->getProvider();
            $email_exist = $users->findByCredentials(['email' => $this->request->getVar("email")]);

            if ($email_exist) {

                $token = $email_exist->generateAccessToken(SECRET_KEY);
                $auth_token = $token->raw_token;

                // create Activity log
                $activity_data = [
                    'user_id'               => $email_exist->id,
                    'activity_type_id'      => 5,        // login
                    'activity'              => 'logged in successfully via Google Login',
                    'ip'                    => $this->request->getIPAddress()
                ];
                createActivityLog($activity_data);

                // Return a success message as a JSON response
                $response = [
                    "status"    => true,
                    "message"   => lang('App.login_success'),
                    "data"      => [
                        'token' => $auth_token,
                        "user_data" => $email_exist
                    ]
                ];
            } else {
                // create Activity log
                $activity_data = [
                    'activity_type_id'      => 6,      // failed login
                    'activity'              => 'user(' . $this->request->getVar("email") . ')  log in failed via Google Login',
                    'ip'                    => $this->request->getIPAddress()
                ];
                createActivityLog($activity_data);

                $response = [
                    "status"    => false,
                    "message"   => lang('App.login_invalidCredentials'),
                    "data"      => []
                ];
            }

            return $this->respondCreated($response);
        }
    }

    // To reset password
    // Post
    public function resetPassword()
    {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $response = [];
        // validation
        $rules = [
            "new_password"      => "required",
            "new_con_password"  => "required|matches[new_password]"
        ];

        if (!$this->validate($rules)) {
            $response = [
                "status"    => false,
                "message"   => $this->validator->getErrors(),
                "data"      => []
            ];
        } else {

            // Update password
            $newPassword = $this->request->getVar('new_password');
            $users = auth()->getProvider();

            $user = $users->findById(auth()->id());
            $user->fill([
                'password' => $newPassword
            ]);
            $users->save($user);

            // create Activity log
            // $activity_data = [
            //     'user_id'               => auth()->id(),
            //     'activity_type_id'      => 2,      // updated
            //     'activity'              => 'Password updated',
            //     'ip'                    => $this->request->getIPAddress()
            // ];

            $activity_data = [
                'user_id'               => auth()->id(),
                'activity_type_id'      =>  2,      // updated
                'activity'              => 'Password updated',
                'activity_en'           => 'Password updated',
                'activity_de'           => 'Passwort aktualisiert',
                'activity_it'           => 'Password aggiornata',
                'activity_fr'           => 'Mise à jour du mot de passe',
                'activity_es'           => 'Contraseña actualizada',
                'activity_pt'           => 'Palavra-passe actualizada',
                'activity_da'           => 'Adgangskode opdateret',
                'activity_sv'           => 'Lösenord uppdaterat',
                'ip'                    => $this->request->getIPAddress()
            ];

            createActivityLog($activity_data);

            $response = [
                "status"    => true,
                "message"   => lang('App.password_changeSuccess'),
                "data"      => []
            ];
        }

        return $this->respondCreated($response);
    }


    // Post
    public function changePassword($userId = null)
    {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP) || hasAccess(auth()->user(), EDITOR_ACCESS)) {
            $response = [];
            // validation
            $rules = [
                //"old_password"      => "required",
                "new_password"      => "required",
                "new_con_password"  => "required|matches[new_password]"
            ];

            if (!$this->validate($rules)) {
                $response = [
                    "status"    => false,
                    "message"   => $this->validator->getErrors(),
                    "data"      => []
                ];
            } else {

                $userId = $userId ?? auth()->id();
                $users = auth()->getProvider();
                $user = $users->findById($userId);

                if ($user) {

                    // Update password
                    $newPassword = $this->request->getVar('new_password');
                    $user->fill([
                        'password' => $newPassword
                    ]);

                    if ($users->save($user)) {

                        $emailTemplateModel = new EmailTemplateModel();

                        $emailType = '';
                        if (auth()->user()->role == 1) {
                            $emailType = 'Admin Changed User Password';
                        } else {
                            $emailType = 'Change Password';
                        }

                        $userRole = [$user->role];
                        $getEmailTemplate = $emailTemplateModel->where('type', $emailType)
                            ->where('language', $user->lang)
                            // ->where('language', 2)           
                            ->whereIn('email_for', [0])
                            ->first();

                        if ($getEmailTemplate) {

                            $replacements = [
                                '{firstName}' => $user->first_name,
                            ];

                            $subject = $getEmailTemplate['subject'];

                            // Replace placeholders with actual values in the email body
                            $content = strtr($getEmailTemplate['content'], $replacements);
                            $message = view('emailTemplateView', ['message' => $content, 'disclaimerText' => getEmailDisclaimer($user->lang), 'footer' => getEmailFooter($user->lang)]);

                            $toEmail = $user->email;
                            // $toEmail = 'pratibhaprajapati.cts@gmail.com';

                            $emailData = [
                                'fromEmail'     => FROM_EMAIL,
                                'fromName'      => FROM_NAME,
                                'toEmail'       => $toEmail,
                                'subject'       => $subject,
                                'message'       => $message,
                            ];
                            sendEmail($emailData);
                        }

                        // create Activity log
                        // $activity_data = [
                        //     'user_id'               => auth()->id(),
                        //     'activity_type_id'      => 2,      // updated
                        //     'activity'              => 'update the password',
                        //     'ip'                    => $this->request->getIPAddress()
                        // ];

                        $activity_data = [
                            'user_id'               => auth()->id(),
                            'activity_type_id'      =>  2,      // updated
                            'activity'              => 'Password updated',
                            'activity_en'           => 'Password updated',
                            'activity_de'           => 'Passwort aktualisiert',
                            'activity_it'           => 'Password aggiornata',
                            'activity_fr'           => 'Mise à jour du mot de passe',
                            'activity_es'           => 'Contraseña actualizada',
                            'activity_pt'           => 'Palavra-passe actualizada',
                            'activity_da'           => 'Adgangskode opdateret',
                            'activity_sv'           => 'Lösenord uppdaterat',
                            'ip'                    => $this->request->getIPAddress()
                        ];

                        createActivityLog($activity_data);

                        $response = [
                            "status"    => true,
                            "message"   => lang('App.password_changeSuccess'),
                        ];
                    } else {
                        $response = [
                            "status"    => false,
                            "message"   => lang('App.password_changeFailed'),
                        ];
                    }
                } else {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.noDataFound'),
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


    public function forgotPassword()
    {
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));


        $rules = [
            "email"                 => "required|valid_email",
            "confirmation_link"     => "required"
        ];

        if (!$this->validate($rules)) {
            $response = [
                "status"    => false,
                "message"   => $this->validator->getErrors(),
                "data"      => []
            ];
        } else {

            $userEmail = $this->request->getVar('email');
            $users = auth()->getProvider();

            $user = $users->findByCredentials(['email' => $userEmail]);
            // echo '>>>>>>>>>>>>>>>>>> ' . $this->db->getLastQuery(); exit;

            if ($user) {

                $userIdentityModel = new UserIdentityModel();
                $firstName = $user->first_name;

                // Delete any previous magic-link identities
                $userIdentityModel->deleteIdentitiesByType($user, Session::ID_TYPE_MAGIC_LINK);

                // Generate the code and save it as an identity
                helper('text');
                $token = random_string('crypto', 20);

                // save token in DB
                $userIdentityModel->insert([
                    'user_id' => $user->id,
                    'type'    => Session::ID_TYPE_MAGIC_LINK,
                    'secret'  => $token,
                    'expires' => Time::now()->addSeconds(setting('Auth.magicLinkLifetime')),
                ]);

                // Store the token in the user's session or any temporary storage
                $session = session();
                $session->set('magic_link_token', $token);

                // Generate the magic link URL
                // $magicLinkUrl = site_url("api/magic-login/{$token}");
                $magicLinkUrl = $this->request->getVar('confirmation_link') . $token;

                $emailTemplateModel = new EmailTemplateModel();
                $getEmailTemplate = $emailTemplateModel->where('type', 'Forgot Password')
                    ->where('language', $user->lang)
                    // ->where('location', $user->user_domain)
                    ->whereIn('email_for', [0, $user->role])
                    ->first();
                // pr($getEmailTemplate);
                // echo '>>>>>>>>>>> forgotPassword  getLastQuery >>> ' . $this->db->getLastQuery(); exit;
                if ($getEmailTemplate) {
                    $replacements = [
                        '{firstName}' => $firstName,
                        '{link}' => '<a href="' . $magicLinkUrl . '" target="_blank">link</a>'
                    ];

                    // send email
                    // $subject = 'Forgot Password on Succer You Sports AG';
                    // $message = "Dear $firstName, <br>You have requested password reset on Succer You Sports AG. Please <a href=" . $magicLinkUrl . ">click on this link</a> and change your password";

                    $subject = $getEmailTemplate['subject'];

                    // Replace placeholders with actual values in the email body
                    $message = strtr($getEmailTemplate['content'], $replacements);
                    $content = view('emailTemplateView', ['message' => $message,  'disclaimerText' => getEmailDisclaimer($user->lang), 'footer' => getEmailFooter($user->lang)]);

                    // $userEmail = 'pratibhaprajapati.cts@gmail.com';

                    $emailData = [
                        'fromEmail'     => FROM_EMAIL,
                        'fromName'      => FROM_NAME,
                        'toEmail'       => $userEmail,
                        'subject'       => $subject,
                        'message'       => $content,
                        // 'message'       => $message,
                    ];
                    sendEmail($emailData);
                }

                // create Activity log
                // $activity_data = [
                //     'user_id'               => $user->id,
                //     'activity_type_id'      => 2,      // updated
                //     'activity'              => 'requested to password reset',
                //     'ip'                    => $this->request->getIPAddress()
                // ];

                $activity_data = [
                    'user_id'               => $user->id,
                    'activity_type_id'      => 2,      // updated
                    'activity'              => 'requested to password reset',
                    'activity_en'           => 'requested to password reset',
                    'activity_de'           => 'zum Zurücksetzen des Passworts aufgefordert',
                    'activity_it'           => 'richiesta di reimpostazione della password',
                    'activity_fr'           => 'demande de réinitialisation du mot de passe',
                    'activity_es'           => 'solicitud de restablecimiento de contraseña',
                    'activity_pt'           => 'pedido de reposição da palavra-passe',
                    'activity_da'           => 'Anmodet om nulstilling af adgangskode',
                    'activity_sv'           => 'begärde återställning av lösenord',
                    'ip'                    => $this->request->getIPAddress()
                ];
                createActivityLog($activity_data);

                $response = [
                    "status"    => true,
                    "message"   => lang('App.forgot_success'),
                    "data"      => [
                        'magic_link_url'    => $magicLinkUrl,
                        'token'             => $token
                    ]
                ];
            } else {

                // create Activity log
                // $activity_data = [
                //     'activity_type_id'      => 2,      // updated
                //     'activity'              => 'User (' . $userEmail . ') requested forgot password',
                //     'ip'                    => $this->request->getIPAddress()
                // ];

                $activity_data = [
                    'activity_type_id'      => 2,      // updated
                    'activity'              => 'User (' . $userEmail . ') requested forgot password',
                    'activity_en'           => 'User (' . $userEmail . ') requested forgot password',
                    'activity_de'           => 'Benutzer (' . $userEmail . ') hat Passwort vergessen',
                    'activity_it'           => 'L\'utente (' . $userEmail . ') ha richiesto di dimenticare la password',
                    'activity_fr'           => 'L\'utilisateur (' . $userEmail . ') a demandé un mot de passe oublié',
                    'activity_es'           => 'Usuario (' . $userEmail . ') solicitó contraseña olvidada',
                    'activity_pt'           => 'O utilizador (' . $userEmail . ') pediu para se esquecer da palavra-passe',
                    'activity_da'           => 'Bruger (' . $userEmail . ') anmodede om glemt adgangskode',
                    'activity_sv'           => 'Användare (' . $userEmail . ') begärde glömt lösenord',
                    'ip'                    => $this->request->getIPAddress()
                ];

                createActivityLog($activity_data);

                $response = [
                    "status"    => false,
                    "message"   => lang('App.email_notExist'),
                    "data"      => []
                ];
            }
        }

        return $this->respondCreated($response);
    }


    public function magicLogin($token)
    {
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $userIdentityModel = new UserIdentityModel();
        $identity = $userIdentityModel->getIdentityBySecret(Session::ID_TYPE_MAGIC_LINK, $token);

        $identifier = $token ?? '';

        // No token found?
        if ($identity === null) {
            // Invalid token
            $response = [
                "status"    => false,
                "message"   => 'Token not found',
                "data"      => []
            ];
            return $this->respondCreated($response);
        }

        // Delete the db entry so it cannot be used again.
        $userIdentityModel->delete($identity->id);

        // Token expired?
        if (Time::now()->isAfter($identity->expires)) {

            // Invalid token
            $response = [
                "status"    => false,
                "message"   => 'Token Expired',
                "data"      => []
            ];
            return $this->respondCreated($response);
        }
        $users = auth()->getProvider();
        $user = $users->findById($identity->user_id);

        if ($user) {

            // Update last login date time after successfull login
            $user->fill([
                'last_active' => date('Y-m-d H:i:s')
            ]);
            $users->save($user);

            $token = $user->generateAccessToken(SECRET_KEY);
            $auth_token = $token->raw_token;

            // create Activity log
            // $activity_data = [
            //     'user_id'               => auth()->id(),
            //     'activity_type_id'      => 5,      // login
            //     'activity'              => 'User logged in via magic login',
            //     'ip'                    => $this->request->getIPAddress()
            // ];

            $activity_data = [
                'user_id'               => auth()->id(),
                'activity_type_id'      => 5,      // login
                'activity'              => 'logged in via magic login',
                'activity_en'           => 'logged in via magic login',
                'activity_de'           => 'eingeloggt über Magic Login',
                'activity_it'           => 'accesso tramite magic login',
                'activity_fr'           => 'connecté via magic login',
                'activity_es'           => 'conectado a través de magic login',
                'activity_pt'           => 'iniciou sessão através do login mágico',
                'activity_da'           => 'logget ind via magisk login',
                'activity_sv'           => 'inloggad via magisk inloggning',
                'ip'                    => $this->request->getIPAddress()
            ];
            createActivityLog($activity_data);

            // Return a success message as a JSON response
            $response = [
                "status"    => true,
                "message"   => lang('App.login_success'),
                "data"      => [
                    'token' => $auth_token,
                    'user' => $user
                ]
            ];
            return $this->respondCreated($response);
        } else {
            // Invalid token
            $response = [
                "status"    => false,
                "message"   => lang('App.invalid_link'),
                "data"      => []
            ];

            return $this->respondCreated($response);
        }
    }


    public function dashboard()
    {
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $response = [
            "status"    => true,
            "message"   => 'Welcome user',
            "data"      => []
        ];

        return $this->respondCreated($response);
    }

    // get profile data
    /* public function profile_old() {
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $userMetaObject = new UserMetaDataModel();
        $countryModel = new CountryModel();
        
        
        $builder = $this->db->table('users');
        $builder->select( 'users.*, 
                            user_roles.role_name as role_name,
                            languages.language as language,
                            domains.domain_name as user_domain,
                            domains.location as user_location',
                        );
        $builder->join('user_roles', 'user_roles.id = users.role', 'LEFT');
        $builder->join('languages', 'languages.id = users.lang', 'LEFT');
        $builder->join('domains', 'domains.id = users.user_domain', 'LEFT');
        $builder->where('users.id', auth()->id());
        $userData   = $builder->get()->getRow();
        //echo ' >>>>>>>>>>>>>>>>>>>> getLastQuery >>>>>> '. $this->db->getLastQuery();     exit;

        if ($userData) {
            // add user's meta data to user info
            $metaData = $userMetaObject->where('user_id', auth()->id())->findAll();
            $meta = [];
            $nationalityData = '';
            $internationalPalyerData = ''; 

            if($metaData && count($metaData) > 0){
                foreach($metaData as $data){
                    $meta[$data['meta_key']] = $data['meta_value'];

                    if($data['meta_key'] == 'nationality'){
                        $nationalityData =   $data['meta_value'];
                    }

                    if($data['meta_key'] == 'international_palyer'){
                        $internationalPalyerData =   $data['meta_value'];
                    }
                }
            }

            $userData->email = auth()->user()->getEmail();
            $userData->meta = $meta;

            $logoPath = base_url() . 'uploads/logos/';
            
            if(!empty($nationalityData)){
                // Get nationality details
                $nationalityIds = unserialize($nationalityData);

                if(count($nationalityIds) > 0){
                    $countryInfo = $countryModel
                                    ->select("country_name, 
                                            country_flag, 
                                            CONCAT('".$logoPath."',country_flag) as country_flag_path ")
                                    ->whereIn('id', $nationalityIds)
                                    ->findAll();
                    $userData->nationality = $countryInfo;              // add nationality data to userData
                }
            }

            if(!empty($internationalPalyerData)){
                $internationalPalyerInfo = $countryModel
                                            ->select("country_name, 
                                            country_flag, 
                                            CONCAT('".$logoPath."',country_flag) as country_flag_path ")
                                            ->where('id', $internationalPalyerData)
                                            ->first();
                $userData->international_palyer = $internationalPalyerInfo;                 // add internationalPalyerInfo data to userData
            }

            // create Activity log
            $activity_data = [
                'user_id'               => auth()->id(),
                'activity_type_id'      => 5,      // login
                'activity'              => 'User went to profile page',
                'ip'                    => $this->request->getIPAddress()
            ];
            createActivityLog($activity_data);

            // Return a success message as a JSON response
            $response = [
                "status"    => true,
                "message"   => lang('App.login_success'),
                "data"      => ['user_data' => $userData ]
            ];

        } else {

            // create Activity log
            $activity_data = [
                'activity_type_id'      => 6,      // login
                'activity'              => 'Unauthorized access to profile page',
                'ip'                    => $this->request->getIPAddress()
            ];
            createActivityLog($activity_data);

            // Invalid token
            $response = [
                "status"    => false,
                "message"   => lang('App.invalid_link'),
                "data"      => []
            ];
        }
            
        return $this->respondCreated($response);

    } */

    /* public function profile() {
        $response = [];
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $userMetaObject = new UserMetaDataModel();
        
        $imagePath = base_url() . 'uploads/';
        $flagPath =  base_url() . 'uploads/logos/';
        
        $id = auth()->id();

        $builder = $this->db->table('users');
        $builder->select( 'users.*, 
                            user_roles.role_name as role_name,
                            languages.language as language,
                            domains.domain_name as user_domain,
                            domains.location as user_location,
                            auth.secret as email,
                            pm.last4 as last4,
                            pm.brand as brand,
                            pm.exp_month as exp_month,
                            pm.exp_year as exp_year,
                            us.status as subscription_status,
                            us.plan_period_start as plan_period_start,
                            us.plan_period_end as plan_period_end,
                            us.plan_interval as plan_interval,
                            us.coupon_used as coupon_used,
                            JSON_ARRAYAGG(
                                JSON_OBJECT(
                                    "country_name", c.country_name, 
                                    "flag_path", CONCAT("'.$flagPath.'", c.country_flag)
                                )
                            ) AS user_nationalities
                        ');

        $builder->join('(SELECT user_id, secret FROM auth_identities WHERE type = "email_password") auth', 'auth.user_id = users.id', 'LEFT');
        $builder->join('user_roles', 'user_roles.id = users.role', 'LEFT');
        $builder->join('languages', 'languages.id = users.lang', 'LEFT');
        $builder->join('domains', 'domains.id = users.user_domain', 'LEFT');
        $builder->join('user_nationalities un', 'un.user_id = users.id', 'LEFT');
        $builder->join('countries c', 'c.id = un.country_id', 'LEFT');
        $builder->join('(SELECT user_id, brand, exp_month, exp_year, last4 FROM payment_methods WHERE is_default = 1) pm', 'pm.user_id = users.id', 'LEFT');
        $builder->join('(SELECT user_id, status, plan_period_start, plan_period_end, plan_interval, coupon_used FROM user_subscriptions WHERE id =  (SELECT MAX(id)  FROM user_subscriptions WHERE user_id = '.$id.')) us', 'us.user_id = users.id', 'LEFT');

        $builder->where('users.id', $id);
        $userData   = $builder->get()->getRow();
        //echo '>>>>>>>> getLastQuery >>>>> '. $this->db->getLastQuery(); exit;

       
        if ($userData) {

            // add user's meta data to user info
            $metaData = $userMetaObject->where('user_id', $id)->findAll();
            $meta = [];
            if($metaData && count($metaData) > 0){
                foreach($metaData as $data){
                    $meta[$data['meta_key']] = $data['meta_value'];

                    if($data['meta_key'] == 'profile_image'){
                        $meta['profile_image_path'] = $imagePath.$data['meta_value'];
                    }
                    if($data['meta_key'] == 'cover_image'){
                        $meta['cover_image_path'] = $imagePath.$data['meta_value'];
                    }
                    
                }
            }
            
            $userData->email = $userData->email;
            $userData->meta = $meta;

            // Return a success message as a JSON response
            $response = [
                "status"    => true,
                "message"   => lang('User found'),
                "data"      => ['user_data' => $userData]
            ];
        } else {
            // Invalid token
            $response = [
                "status"    => false,
                "message"   => lang('App.invalid_link'),
                "data"      => []
            ];
        }
            
        return $this->respondCreated($response);

    } */



    public function logout()
    {

        // create Activity log
        // $activity_data = [
        //     'user_id'               => auth()->id(),
        //     'activity_type_id'      => 8,      // logout
        //     'activity'              => 'User logged out',
        //     'ip'                    => $this->request->getIPAddress()
        // ];


        $activity_data = [
            'user_id'               => auth()->id(),
            'activity_type_id'      => 8,      // logout
            'activity'              => 'logged out',
            'activity_en'           => 'logged out',
            'activity_de'           => 'abgemeldet',
            'activity_it'           => 'disconnesso',
            'activity_fr'           => 'déconnecté',
            'activity_es'           => 'desconectado',
            'activity_pt'           => 'desconectado',
            'activity_da'           => 'logget ud',
            'activity_sv'           => 'utloggad',
            'ip'                    => $this->request->getIPAddress()
        ];

        createActivityLog($activity_data);

        auth()->logout();
        auth()->user()->revokeAllAccessTokens();

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $response = [
            "status"    => true,
            "message"   => lang('App.logout_success'),
            "data"      => []
        ];

        return $this->respondCreated($response);
    }


    /* public function deleteUser() {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $id = $this->request->getVar("id");

        if($id && count($id) > 0){

            // Get the User Provider (UserModel by default)
            $users = auth()->getProvider();
            $del_res = $users->delete($id, true);

            if($del_res == true){
                // Return a success message as a JSON response
                $response = [
                    "status"    => true,
                    "message"   => lang('App.delete_user_success'),
                    "data"      => ['user_data' => $del_res ]
                ];
            } else {
                $response = [
                    "status"    => false,
                    "message"   => lang('App.invalid_userID'),
                    "data"      => ['user_data' => $del_res ]
                ];
            }
            
        } else {
            $response = [
                "status"    => false,
                "message"   => lang('App.invalid_userID'),
                "data"      => []
            ];
        }
                
        return $this->respondCreated($response);
    } */


    public function accessDenied()
    {
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $response = [
            "status"    => false,
            "message"   => lang('App.access_denied'),
            "data"      => []
        ];
        return $this->respondCreated($response);
    }

    public function adminAccessDenied()
    {
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $response = [
            "status"    => false,
            "message"   => lang('App.adminAccessDenied'),
            //"message"   => 'only admin can access this page',
            "data"      => []
        ];
        return $this->respondCreated($response);
    }


    public function playerAccessDenied()
    {
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $response = [
            "status"    => false,
            "message"   => lang('App.playerAccessDenied'),
            //"message"   => 'only players can access this page',
            "data"      => []
        ];
        return $this->respondCreated($response);
    }

    public function clubAccessDenied()
    {
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $response = [
            "status"    => false,
            "message"   => lang('App.clubAccessDenied'),
            //"message"   => 'only Club can access this page',
            "data"      => []
        ];
        return $this->respondCreated($response);
    }


    public function scoutAccessDenied()
    {
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $response = [
            "status"    => false,
            "message"   => lang('App.scoutAccessDenied'),
            //"message"   => 'only Scout can access this page',
            "data"      => []
        ];
        return $this->respondCreated($response);
    }


    public function getPlayers()
    {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $url =  isset($_SERVER['HTTPS']) &&
            $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
        $url .= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $parse_url = parse_url($url);
        $uri = new \CodeIgniter\HTTP\URI($url);

        $queryString =  $uri->getQuery();
        parse_str($queryString, $searchParams);

        $whereClause = [];
        $metaQuery = [];
        $orderBy =  $order = '';
        $noLimit =  $countOnly = FALSE;

        $limit = $searchParams['limit'] ?? PER_PAGE;
        $offset = $searchParams['offset'] ?? 0;


        if (isset($searchParams['whereClause']) && count($searchParams['whereClause']) > 0) {
            $whereClause = $searchParams['whereClause'];
        }

        if (isset($searchParams['metaQuery']) && count($searchParams['metaQuery']) > 0) {
            $metaQuery = $searchParams['metaQuery'];
        }

        if (isset($searchParams['orderBy']) && !empty($searchParams['orderBy'])) {
            $orderBy = $searchParams['orderBy'];
        }

        if (isset($searchParams['order']) && !empty($searchParams['order'])) {
            $order = $searchParams['order'];
        }

        if (isset($searchParams['noLimit']) && !empty($searchParams['noLimit'])) {
            $noLimit = $searchParams['noLimit'];
        }

        if (isset($searchParams['countOnly']) && !empty($searchParams['countOnly'])) {
            $countOnly = $searchParams['countOnly'];
        }


        /* $metaQuery = [
            [   'meta_key' => 'first_name',
                'meta_value' => 'de',
                'operator' => "LIKE"
            ],
            [   'meta_key' => 'current_club',
                'meta_value' => 'club',
                'operator' => "LIKE"
            ],
            [   'meta_key' => 'height',
                'meta_value' => '180',
                'operator' => "="
            ]
        ]; */
        //$users = getPlayers($whereClause, $metaQuery, $orderBy, $order, $limit, $offset);
        $users = getPlayers($whereClause, $metaQuery, $orderBy, $order, $limit, $offset, $noLimit, $countOnly);

        if ($users) {
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => ['userData' => $users]
            ];
        } else {
            $response = [
                "status"    => false,
                "message"   => lang('App.noDataFound'),
                "data"      => []
            ];
        }

        // $activity_data = [
        //     'user_id'               => auth()->id(),
        //     'activity_type_id'      => 9,      // viewed
        //     'activity'              => 'User viewed users listing page',
        //     'ip'                    => $this->request->getIPAddress()
        // ];

        $activity_data = [
            'user_id'               => auth()->id(),
            'activity_type_id'      => 9,      // viewed
            'activity'              => 'viewed users listing page',
            'activity_en'           => 'viewed users listing page',
            'activity_de'           => 'Seite mit den Benutzerlisten angesehen',
            'activity_it'           => 'pagina di inserimento degli utenti visualizzati',
            'activity_fr'           => 'page d\'inscription des utilisateurs consultés',
            'activity_es'           => 'ver la página de usuarios',
            'activity_pt'           => 'página de listagem de utilizadores visualizados',
            'activity_da'           => 'viste side med brugerliste',
            'activity_sv'           => 'visade användares listningssida',
            'ip'                    => $this->request->getIPAddress()
        ];

        createActivityLog($activity_data);

        return $this->respondCreated($response);
    }


    public function displayFile($filename)
    {
        $path = WRITEPATH . 'uploads/' . $filename;

        if (!file_exists($path)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException("File not found: $filename");
        }

        /* $mimeType = mime_content_type($path);
        header('Content-Type: ' . $mimeType);
        readfile($path); */

        $mimeType = mime_content_type($path);
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($path));

        // Output the image
        readfile($path);
        exit;
    }
    public function displayCsv($filename)
    {
        $path = WRITEPATH . 'uploads/exports/' . $filename;
        // if (file_exists($path)) {
        //     // Set headers to display file inline instead of downloading
        //     header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        //     header('Content-Disposition: inline; filename="' . basename($path) . '"');
        //     header('Content-Length: ' . filesize($path));

        //     // Output the file
        //     readfile($path);
        //     exit;
        // } else {
        //     if (!file_exists($path)) {
        //         throw new \CodeIgniter\Exceptions\PageNotFoundException("File not found: $filename"); //die;
        //     }
        // }
        $filePath = $path;
        $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        // Check if the file exists
        if (!file_exists($filePath)) {
            die("File not found.");
        }

        // Handle different file types
        switch ($fileExtension) {
            case 'pdf':
                // PDF can be directly previewed
                header('Content-Type: application/pdf');
                readfile($filePath);
                break;

            case 'jpg':
            case 'jpeg':
            case 'png':
            case 'gif':
                // Images can be previewed directly
                $imageInfo = getimagesize($filePath);
                header('Content-Type: ' . $imageInfo['mime']);
                readfile($filePath);
                break;

            case 'txt':
                // Text files can be read and displayed
                header('Content-Type: text/plain');
                readfile($filePath);
                break;

            case 'doc':
            case 'docx':
            case 'xls':
            case 'xlsx':
            case 'ppt':
            case 'pptx':
                // For MS Office files, we'll force download as preview isn't natively supported
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
                header('Content-Transfer-Encoding: binary');
                header('Accept-Ranges: bytes');
                readfile($filePath);
                break;

            default:
                die('Unsupported file type for preview.');
        }
        exit;
    }
    public function displayTeamLogo($filename)
    {
        $path = WRITEPATH . 'uploads/teams_logos/' . $filename;

        if (!file_exists($path)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException("File not found: $filename");
        }

        /* $mimeType = mime_content_type($path);
        header('Content-Type: ' . $mimeType);
        readfile($path); */

        $mimeType = mime_content_type($path);
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($path));

        // Output the image
        readfile($path);
        exit;
    }

    public function displayDocuments($filename)
    {
        $path = WRITEPATH . 'uploads/documents/' . $filename;

        if (!file_exists($path)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException("File not found: $filename");
        }

        /* $mimeType = mime_content_type($path);
        header('Content-Type: ' . $mimeType);
        readfile($path); */

        $mimeType = mime_content_type($path);
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($path));

        // Output the image
        readfile($path);
        exit;
    }


    public function displayPDFIcons($filename)
    {
        $path = WRITEPATH . 'uploads/pdf-icons/' . $filename;

        if (!file_exists($path)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException("File not found: $filename");
        }

        /* $mimeType = mime_content_type($path);
        header('Content-Type: ' . $mimeType);
        readfile($path); */

        $mimeType = mime_content_type($path);
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($path));

        // Output the image
        readfile($path);
        exit;
    }

    public function updateUserLanguage()
    {
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        // echo $currentLang; die;
        $this->request->setLocale(getLanguageCode($currentLang));
        $language = $this->request->getVar('language');
        # echo $language;   die;
        $users = auth()->getProvider();

        $user = $users->findById(auth()->id());
        $user->fill([
            'lang' => $language
        ]);
        if (!empty($language) && $users->save($user)) {
            $languageModel = new LanguageModel();
            $languageArr = $languageModel->find($language);
            // if (isset($languageArr) && !empty($languageArr)) {
            //     $activityMsg = 'User Language ' . $languageArr['language'] . ' updated successFully';
            //     $this->request->setLocale($languageArr['slug']);
            // } else {
            //     $activityMsg = 'User Language updated successFully';
            // }
            $activityMsg = [
                'en' => 'User Language ' . $languageArr['language'] . ' updated successfully',
                'de' => 'Benutzersprache ' . $languageArr['language'] . ' erfolgreich aktualisiert',
                'fr' => 'Langue de l\'utilisateur ' . $languageArr['language'] . ' mise à jour avec succès',
                'it' => 'Lingua utente ' . $languageArr['language'] . ' aggiornata con successo',
                'es' => 'Idioma del usuario ' . $languageArr['language'] . ' actualizado con éxito',
                'pt' => 'Idioma do usuário ' . $languageArr['language'] . ' atualizado com sucesso',
                'da' => 'Brugersprog ' . $languageArr['language'] . ' opdateret med succes',
                'sv' => 'Användarspråk ' . $languageArr['language'] . ' uppdaterat framgångsrikt'
            ];
            $activity_data = array();
            $languageService = \Config\Services::language();
            $languages = ['en', 'de', 'fr', 'it', 'es', 'pt', 'da', 'sv'];
            $activity_data = [
                'user_id'               => auth()->id(),
                'activity_type_id'      => 2, // updated
                'ip'                    => $this->request->getIPAddress()
            ];
            foreach ($languages as $lang) {
                $languageService->setLocale($lang);
                // $translated_message = lang('App.new_registration', ['userEmail' => $this->request->getVar("email")]);
                $translated_message = $activityMsg[$lang];
                $activity_data['activity_' . $lang] = $translated_message;
            }
            createActivityLog($activity_data);
            $response = [
                "status"    => true,
                "message"   => lang('App.profile_updatedSuccess'),
                "data"      => [] #'userId' => $userId
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

    public function deleteCSVFile()
    {
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        // echo $currentLang; die;
        $this->request->setLocale(getLanguageCode($currentLang));
        // Get the filename from the request (assuming it's sent via POST)
        $filename = $this->request->getVar('filename');

        // Define the path to the folder where CSV files are stored
        $filePath = FCPATH . 'export_csv/' . $filename;
        $response = [
            "status"    => true,
            "message"   => lang('App.file_deleteError'),
            "data"      => [] #'userId' => $userId
        ];
        // Check if the file exists
        if (file_exists($filePath)) {
            // Attempt to delete the file
            if (unlink($filePath)) { # Error deleting the file
                $response = [
                    "status"    => true,
                    "message"   => lang('App.file_deleteSuccess'),
                    "data"      => [] #'userId' => $userId
                ];
            }
        }
        return $this->respondCreated($response);
    }
    public function frontEnd($filename)
    {
        $path = WRITEPATH . 'uploads/frontend/' . $filename;
        if (!file_exists($path)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException("File not found: $filename");
        }

        /* $mimeType = mime_content_type($path);
        header('Content-Type: ' . $mimeType);
        readfile($path); */

        $mimeType = mime_content_type($path);
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($path));

        // Output the image
        readfile($path);
        exit;
    }
}
