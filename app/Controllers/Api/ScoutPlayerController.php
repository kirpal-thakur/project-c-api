<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use App\Models\ScoutPlayerModel;
use App\Models\EmailTemplateModel;
use CodeIgniter\Shield\Models\UserModel;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ScoutPlayerController extends ResourceController
{

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);
        $this->db               = \Config\Database::connect();
        $this->scoutPlayerModel = new ScoutPlayerModel();
    }

    // Players - Add players under Scout 
    public function addScoutPlayer($scout_id = null)
    {
        $response = [];
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {
            $scout_id = $scout_id ?? auth()->id();

            $rules = [
                'players.*.player_id'       => 'required|is_natural',
            ];

            if (!$this->validate($rules)) {
                $errors = $this->validator->getErrors();

                $response = [
                    "status"    => false,
                    "message"   => lang('App.provideValidData'),
                    "data"      => ['errors' => $errors]
                ];
            } else {
                $playerExist = $playerAdded = [];
                foreach ($this->request->getPost('players') as $key => $player) {
                    $isExist = $this->scoutPlayerModel
                        ->select('scout_players.*, users.first_name, users.last_name')
                        ->join('users', 'users.id = scout_players.player_id', 'INNER')
                        ->where('player_id', $player["player_id"])
                        ->where('scout_id', $scout_id)
                        ->first();
                    if ($isExist) {
                        $existMessage = $isExist["first_name"] . ' ' . $isExist["last_name"] . ' already Exists in players list';
                        array_push($playerExist, $existMessage);
                    } else {

                        // update status to inactive for previous records
                        // $this->scoutPlayerModel->where('player_id', $player["player_id"])
                        //     ->set(['status' => 2])      //2 = inactive
                        //     ->update();

                        // save new data
                        $save_data = [
                            'scout_id'      => $scout_id,
                            'player_id'     => $player["player_id"],
                            'status'        => 'inactive',
                        ];

                        // check if added by admin
                        if (auth()->user()->role == 1) {
                            $save_data['added_by'] = auth()->id();
                        }

                        // save data
                        if ($this->scoutPlayerModel->save($save_data)) {

                            $users = auth()->getProvider();
                            $playerInfo = $users->findById($player["player_id"]);

                            // Email to Talent
                            $emailTemplateModel = new EmailTemplateModel();

                            $emailType = 'Scout Invites Player';

                            // email to player
                            $getEmailTemplate = getEmailTemplate($emailType, $playerInfo->lang, $email_for = PLAYER_ROLE );

                            if($getEmailTemplate){

                                $replacements = [
                                    '{talentName}'  => $playerInfo->first_name,
                                    '{scoutName}'   => auth()->user()->first_name,
                                    '{link}'        => '<a href="#">link</a>',
                                    // '{faqLink}'     => '<a href="#">faq</a>',
                                    '{faqLink}'         => '<a href="'.getPageInUserLanguage($playerInfo->user_domain, $playerInfo->lang, "faq").'">faq</a>',
                                    '{helpEmail}'   => '<a href="mailto:help@socceryou.ch">help@socceryou.ch</a>',
                                ]; 

                                $subject = $getEmailTemplate['subject'];
                                // $subject = strtr($getEmailTemplate['subject'], $subjectReplacements);

                                // Replace placeholders with actual values in the email body
                                $content = strtr($getEmailTemplate['content'], $replacements);
                                $message = view('emailTemplateView' , ['message' => $content, 'disclaimerText' => getEmailDisclaimer($playerInfo->lang), 'footer' => getEmailFooter($playerInfo->lang)]);

                                $toEmail = $playerInfo->email;
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


                            if ($playerInfo) {
                                $addMessge = $playerInfo->first_name . ' ' . $playerInfo->last_name . ' added under scout portfolio';
                                array_push($playerAdded, $addMessge);
                            }


                            $activity = 'added player in club';
                            $activity_data = [
                                'user_id'               => auth()->id(),
                                'activity_type_id'      => 1,      // created 
                                'activity'              => $activity,
                                'ip'                    => $this->request->getIPAddress()
                            ];
                            createActivityLog($activity_data);
                        }
                    }

                    $response = [
                        "status"    => true,
                        "message"   => lang('App.playersAdded'),
                        "data"      => [
                            'playerExist' => $playerExist,
                            'playerAdded' => $playerAdded
                        ]
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


    // Scout - Get list of scout players
    public function getScoutPlayers($scout_id = null)
    {

        $scout_id = $scout_id ?? auth()->id();

        // check if representator
        if(in_array(auth()->user()->role, REPRESENTATORS_ROLES)){
            $authUser = auth()->user();
            $scout_id = $authUser->parent_id;
        }

        $imagePath = base_url() . 'uploads/';
        $logoPath = base_url() . 'uploads/logos/';

        $scoutPlayers = $this->scoutPlayerModel
            ->select('scout_players.*,
                                    u.first_name,
                                    u.last_name,
                                    u.status as user_status,
                                    l.language, 
                                    CONCAT("' . $imagePath . '", um.meta_value) AS profile_image_path,

                                    cp.team_id,
                                    cp.end_date as expiry_date,

                                    t.team_type,
                                    um2.meta_value AS club_name,
                                    CONCAT("' . $imagePath . '", um3.meta_value) AS club_logo_path,
                                    c.country_name,
                                    CONCAT("' . $logoPath . '", c.country_flag) AS country_flag_path

                                ')
            ->join('users u', 'u.id = scout_players.player_id', 'INNER')
            ->join('languages l', 'l.id = u.lang', 'INNER')
            ->join('user_meta um', 'um.user_id = scout_players.player_id AND meta_key = "profile_image"', 'LEFT')
            // ->join('(SELECT user_id, meta_value FROM user_meta WHERE meta_key = "profile_image") um', 'um.user_id = scout_players.player_id', 'LEFT')
            ->join('club_players cp', 'cp.player_id = scout_players.player_id AND status = "active"', 'LEFT')
            // ->join('(SELECT team_id, player_id, end_date FROM club_players WHERE status = "active") cp', 'cp.player_id = scout_players.player_id', 'LEFT')
            ->join('teams t', 't.id = cp.team_id', 'INNER')
            ->join('user_meta um2', 'um2.user_id = t.club_id AND um2.meta_key = "club_name"', 'LEFT')
            // ->join('(SELECT user_id, meta_value FROM user_meta WHERE meta_key = "club_name") um2', 'um2.user_id = t.club_id', 'LEFT')
            ->join('(SELECT user_id, meta_value FROM user_meta WHERE meta_key = "profile_image") user_meta um3', 'um3.user_id = t.club_id AND um3.meta_key = "profile_image"', 'LEFT')
            // ->join('(SELECT user_id, meta_value FROM user_meta WHERE meta_key = "profile_image") um3', 'um3.user_id = t.club_id', 'LEFT')
            ->join('countries c', 'c.id = t.country_id', 'INNER')
            ->where('scout_players.is_accepted', 1)
            ->where('scout_players.scout_id', $scout_id)
            ->findAll();

        if ($scoutPlayers) {
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => ['scoutPlayers' => $scoutPlayers]
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

    // Scout - Delete players under Scout 
    public function deleteScoutPlayer($id = null)
    {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            $del_data   = $this->scoutPlayerModel->where('id', $id)->first();
            $del_res    = $this->scoutPlayerModel->delete($id, true);

            if ($del_res == true) {

                // create Activity log
                $activity_data = [
                    'user_id'               => auth()->id(),
                    'activity_type_id'      => 3,      // deleted 
                    'activity'              => 'deleted Scout player',
                    'old_data'              =>  serialize($del_data),
                    'ip'                    => $this->request->getIPAddress()
                ];
                createActivityLog($activity_data);

                // Return a success message as a JSON response
                $response = [
                    "status"    => true,
                    "message"   => lang('App.playerDeleteScout'),
                    "data"      => []
                ];
            } else {
                $response = [
                    "status"    => false,
                    "message"   => lang('App.playerDeleteScoutFailed'),
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


    public function updateScoutRequest($request_id)
    {
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            if (!empty($this->request->getVar("is_accepted"))) {

                $user_id = auth()->id();

                $request_data   = $this->scoutPlayerModel->where('id', $request_id)->where('is_accepted IS NULL')->first();
                if ($request_data && $request_data['player_id'] ==  $user_id) {

                    // get currently active scout info
                    $currentScout   = $this->scoutPlayerModel->where('player_id', $user_id)->where('is_accepted', 'accepted')->where('status', 'active')->first();

                    $save_data = [
                        'id'            => $request_id,
                        'is_accepted'   => $this->request->getVar("is_accepted"),
                        'status'        => $this->request->getVar("is_accepted") == 'accepted' ? 'active' : 'inactive',
                    ];

                    if ($this->scoutPlayerModel->save($save_data)) {

                        // update old data to inactive
                        if($this->request->getVar("is_accepted") == 'accepted'){
                            $up_data = [
                                'id'        => $currentScout['id'],
                                'status'    => 'inactive',
                            ];
                            $this->scoutPlayerModel->save($up_data);
                        }

                        // Email to new scout 
                        $users = auth()->getProvider();
                        $scoutInfo = $users->findById($request_data['scout_id']);        // get scout info

                        $emailType = 'Talent respond to scout invite';
                        // $getEmailTemplate = getEmailTemplate($emailType, 2, SCOUT_ROLE );
                        $getEmailTemplate = getEmailTemplate($emailType, $scoutInfo->lang, SCOUT_ROLE );
            
                        if($getEmailTemplate){

                            $subjectReplacements = [
                                '{talentName}'  => auth()->user()->first_name,
                            ]; 

                            // '{faqLink}'         =>  getPageInUserLanguage(auth()->user()->user_domain, auth()->user()->lang, 'faq' ),

                            $replacements = [
                                '{scoutName}'       => $scoutInfo->first_name,
                                '{talentName}'      => auth()->user()->first_name,
                                '{inviteStatus}'    => $this->request->getVar("is_accepted"),
                                '{link}'            => '<a href="'.getPageInUserLanguage($scoutInfo->user_domain).'">link</a>',
                                '{faqLink}'         => '<a href="'.getPageInUserLanguage($scoutInfo->user_domain, $scoutInfo->lang, "faq").'">faq</a>',
                                '{helpEmail}'       => '<a href="mailto:help@socceryou.ch">help@socceryou.ch</a>',
                            ]; 
                            
                            $subject = strtr($getEmailTemplate['subject'], $subjectReplacements);

                            // Replace placeholders with actual values in the email body
                            $content = strtr($getEmailTemplate['content'], $replacements);
                            $message = view('emailTemplateView' , ['message' => $content, 'disclaimerText' => getEmailDisclaimer($scoutInfo->lang), 'footer' => getEmailFooter($scoutInfo->lang)]);

                            $toEmail = $scoutInfo->email;
                            $emailData = [
                                'fromEmail'     => FROM_EMAIL,
                                'fromName'      => FROM_NAME,
                                'toEmail'       => $toEmail,
                                'subject'       => $subject,
                                'message'       => $message,
                            ];
                            sendEmail($emailData);
                        }

                        if($this->request->getVar("is_accepted") == 'accepted'){

                            // send email to new scout
                            $emailType = 'When Talent changes Scout to new scout';
                            // $getNewScoutEmailTemplate = getEmailTemplate($emailType, 2, SCOUT_ROLE );
                            $getNewScoutEmailTemplate = getEmailTemplate($emailType, $scoutInfo->lang, SCOUT_ROLE );

                            if($getNewScoutEmailTemplate){
                                $replacements = [
                                    '{scoutName}'       => $scoutInfo->first_name,
                                    '{talentName}'      => auth()->user()->first_name,
                                    // '{faqLink}'         => '<a href="#">faq</a>',
                                    '{faqLink}'         => '<a href="'.getPageInUserLanguage($scoutInfo->user_domain, $scoutInfo->lang, "faq").'">faq</a>',
                                    '{helpEmail}'       => '<a href="mailto:help@socceryou.ch">help@socceryou.ch</a>',
                                ];

                                $subjectReplacements = [
                                    '{talentName}' => auth()->user()->first_name,
                                ];

                                // Replace placeholders with actual values in the email body
                                $subject = strtr($getNewScoutEmailTemplate['subject'], $subjectReplacements);
                                $content = strtr($getNewScoutEmailTemplate['content'], $replacements);
                                $message = view('emailTemplateView' , ['message' => $content, 'disclaimerText' => getEmailDisclaimer($scoutInfo->lang), 'footer' => getEmailFooter($scoutInfo->lang)]);

                                $toEmail = $scoutInfo->email;

                                $emailData = [
                                    'fromEmail'     => FROM_EMAIL,
                                    'fromName'      => FROM_NAME,
                                    'toEmail'       => $toEmail,
                                    'subject'       => $subject,
                                    'message'       => $message,
                                ];
                                sendEmail($emailData);
                            }

                            if($currentScout){

                                // send email to talent regarding scout change
                                $talentEmailType = 'When Talent changes Scout to Talent';
                                // $getTalentEmailTemplate = getEmailTemplate($talentEmailType, 2, PLAYER_ROLE );
                                $getTalentEmailTemplate = getEmailTemplate($talentEmailType, auth()->user()->lang, PLAYER_ROLE );
                    
                                if($getTalentEmailTemplate){

                                    $replacements = [
                                        '{newScoutName}'    => $scoutInfo->first_name,
                                        '{talentName}'      => auth()->user()->first_name,
                                        // '{faqLink}'         => '<a href="#">faq</a>',
                                        '{faqLink}'         => '<a href="'.getPageInUserLanguage(auth()->user()->user_domain, auth()->user()->lang, "faq").'">faq</a>',
                                        '{helpEmail}'       => '<a href="mailto:help@socceryou.ch">help@socceryou.ch</a>',
                                    ]; 
                                    
                                    $subject = $getTalentEmailTemplate['subject'];

                                    // Replace placeholders with actual values in the email body
                                    $content = strtr($getTalentEmailTemplate['content'], $replacements);
                                    $message = view('emailTemplateView' , ['message' => $content, 'disclaimerText' => getEmailDisclaimer(auth()->user()->lang), 'footer' => getEmailFooter(auth()->user()->lang)]);

                                    $toEmail = auth()->user()->email;
                                    $emailData = [
                                        'fromEmail'     => FROM_EMAIL,
                                        'fromName'      => FROM_NAME,
                                        'toEmail'       => $toEmail,
                                        'subject'       => $subject,
                                        'message'       => $message,
                                    ];
                                    sendEmail($emailData);
                                }
                                
                                // send email to current/old scout
                                $currentScoutInfo = $users->findById($currentScout['scout_id']);        // get old scout info
                                $emailType = 'When Talent changes Scout to old scout';
                                // $getEmailTemplate = getEmailTemplate($emailType, 2, SCOUT_ROLE );
                                $getEmailTemplate = getEmailTemplate($emailType, $currentScoutInfo->lang, SCOUT_ROLE );

                                if($getEmailTemplate){
                                    $replacements = [
                                        '{scoutName}'       => $currentScoutInfo->first_name,
                                        '{talentName}'      => auth()->user()->first_name,
                                        // '{faqLink}'         => '<a href="#">faq</a>',
                                        '{faqLink}'         => '<a href="'.getPageInUserLanguage($currentScoutInfo->user_domain, $currentScoutInfo->lang, "faq").'">faq</a>',
                                        '{helpEmail}'       => '<a href="mailto:help@socceryou.ch">help@socceryou.ch</a>',
                                    ];

                                    $subjectReplacements = [
                                        '{talentName}' => auth()->user()->first_name,
                                    ];
        
                                    // Replace placeholders with actual values in the email body
                                    $subject = strtr($getEmailTemplate['subject'], $subjectReplacements);
                                    $content = strtr($getEmailTemplate['content'], $replacements);
                                    $message = view('emailTemplateView' , ['message' => $content, 'disclaimerText' => getEmailDisclaimer($currentScoutInfo->lang), 'footer' => getEmailFooter($currentScoutInfo->lang)]);
        
                                    $toEmail = $currentScoutInfo->email;
                                    $emailData = [
                                        'fromEmail'     => FROM_EMAIL,
                                        'fromName'      => FROM_NAME,
                                        'toEmail'       => $toEmail,
                                        'subject'       => $subject,
                                        'message'       => $message,
                                    ];
                                    sendEmail($emailData);
                                }
                            }
                        }
                        
                        $response = [
                            "status"    => true,
                            "message"   => lang('App.statusUpdated'),
                            "data"      => []
                        ];
                    } else {
                        $response = [
                            "status"    => false,
                            "message"   => lang('App.statusUpdateFailed'),
                            "data"      => []
                        ];
                    }

                    /* //1 = 'Pending' 2 = 'Verified' 3 = 'Rejected'	
                        if($this->request->getVar("status") == 2){
                            $status = 'Verified';
                        } elseif($this->request->getVar("status") == 3){
                            $status = 'Rejected';
                        } else {
                            $status = 'Pending';
                        }
                        
                        $userMessage = '';
                        $userSubject = 'Your account status is updated on Succer You Sports AG';
                        $userMessage .= 'Dear '.$first_name.', <br>Your account status is updated to '.$status .' on Succer You Sports AG' ;

                        if($this->request->getVar("status") == 2){
                            $userMessage .= '<br> You can login and complete your profile.';
                        } else {
                            $userMessage .= '<br> Please contact admin.';
                        }

                        $userEmailData = [
                            'fromEmail'     => FROM_EMAIL,
                            'fromName'      => FROM_NAME,
                            'toEmail'       => $email,
                            'subject'       => $userSubject,
                            'message'       => $userMessage,
                        ];
                        sendEmail($userEmailData); */
                } else {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.unauthorized_access'),
                        "data"      => []
                    ];
                }
            } else {
                $response = [
                    "status"    => false,
                    "message"   => lang('App.invalid_userID'),
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

    public function exportScoutPlayers($scout_id = null)
    {
        $scout_id = $scout_id ?? auth()->id();

        $imagePath = base_url() . 'uploads/';
        $logoPath = base_url() . 'uploads/logos/';

        $scoutPlayers = $this->scoutPlayerModel
            ->select('scout_players.*,
                                    u.first_name,
                                    u.last_name,
                                    u.status as user_status, 
                                    l.language, 
                                    CONCAT("' . $imagePath . '", um.meta_value) AS profile_image_path,

                                    cp.team_id,
                                    cp.end_date as expiry_date,

                                    t.team_type,
                                    um2.meta_value AS club_name,
                                    CONCAT("' . $imagePath . '", um3.meta_value) AS club_logo_path,
                                    c.country_name,
                                    CONCAT("' . $logoPath . '", c.country_flag) AS country_flag_path

                                ')
            ->join('users u', 'u.id = scout_players.player_id', 'INNER')
            ->join('languages l', 'l.id = u.lang', 'INNER')
            ->join('user_meta um', 'um.user_id = scout_players.player_id AND um.meta_key = "profile_image"', 'LEFT')
            // ->join('(SELECT user_id, meta_value FROM user_meta WHERE meta_key = "profile_image") um', 'um.user_id = scout_players.player_id', 'LEFT')
            ->join('club_players cp', 'cp.player_id = scout_players.player_id AND cp.status = "active"', 'LEFT')
            // ->join('(SELECT team_id, player_id, end_date FROM club_players WHERE status = "active") cp', 'cp.player_id = scout_players.player_id', 'LEFT')
            ->join('teams t', 't.id = cp.team_id', 'INNER')
            ->join('user_meta um2', 'um2.user_id = t.club_id AND um2.meta_key = "club_name"', 'LEFT')
            // ->join('(SELECT user_id, meta_value FROM user_meta WHERE meta_key = "club_name") um2', 'um2.user_id = t.club_id', 'LEFT')
            ->join('user_meta um3', 'um3.user_id = t.club_id AND um3.meta_key = "profile_image"', 'LEFT')
            // ->join('(SELECT user_id, meta_value FROM user_meta WHERE meta_key = "profile_image") um3', 'um3.user_id = t.club_id', 'LEFT')
            ->join('countries c', 'c.id = t.country_id', 'INNER')
            ->where('scout_players.is_accepted', 1)
            ->where('scout_players.scout_id', $scout_id)
            ->findAll();

        if (isset($scoutPlayers) && !empty($scoutPlayers)) {
            /* ##### excel code By Amrit ##### */
            // Create a new Spreadsheet object
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Set the headers
            $sheet->setCellValue('A1', 'First Name');
            $sheet->setCellValue('B1', 'Last Name');
            $sheet->setCellValue('C1', 'Profile Image Path');
            $sheet->setCellValue('D1', 'Language');
            $sheet->setCellValue('E1', 'Club Name');
            $sheet->setCellValue('F1', 'Country Logo Path');
            // $sheet->setCellValue('G1', ' Contract Starts');
            // $sheet->setCellValue('H1', '  Contract Expires');
            $row = 2; // Start from the second row
            $fullNameUser = 'Portfolio_export_'.rand();
            foreach ($scoutPlayers as $scoutPlayer) {  
                $fullNameUser = trim($scoutPlayer['first_name']).''.trim($scoutPlayer['last_name']).'_export';
                $sheet->setCellValue('A' . $row, htmlspecialchars($scoutPlayer['first_name']));
                $sheet->setCellValue('B' . $row, htmlspecialchars($scoutPlayer['last_name']));
                $sheet->setCellValue('C' . $row, htmlspecialchars($scoutPlayer['profile_image_path']));
                $sheet->setCellValue('D' . $row, htmlspecialchars($scoutPlayer['language']));
                $sheet->setCellValue('E' . $row, htmlspecialchars($scoutPlayer['club_name']));
                $sheet->setCellValue('F' . $row, htmlspecialchars($scoutPlayer['country_flag_path']));
                // $sheet->setCellValue('G' . $row, htmlspecialchars($scoutPlayer['club_name']));
                $row++;
            }
            $filename = $fullNameUser.'_'.date('Y-m-d_H-i-s').'.xlsx';
            $folder_nd_file = 'uploads/exports/'.$filename;
            $saveDirectory = WRITEPATH  . 'uploads/exports/'; 
            
            $filePath = $saveDirectory . $filename;
            if (!is_dir($saveDirectory)) {
                mkdir($saveDirectory, 0777, true);  // Create the directory with proper permissions
            }
            $writer = new Xlsx($spreadsheet);
            $writer->save($filePath);
            $activity_data = [
                'user_id'               => auth()->id(),
                'activity_type_id'      => 1,      // Created
                'activity'              => 'Scout Players Csv Downloded by user',
                'ip'                    => $this->request->getIPAddress()
            ];
            createActivityLog($activity_data);
            // Return the file details in the response
            $response = [
                "status"    => true,
                "message"   => "File created successfully.",
                "data"      => [
                    "file_name" => $filename,
                    "file_path" => base_url().$folder_nd_file
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
}
