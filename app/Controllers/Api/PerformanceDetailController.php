<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use App\Models\PerformanceDetailModel;
use App\Models\EmailTemplateModel;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PerformanceDetailController extends ResourceController
{
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);
        $this->db = \Config\Database::connect();

        $this->emailTemplateModel = new EmailTemplateModel();
    }


    public function addPerformanceDetail(){

        $performanceDetailModel = new PerformanceDetailModel();

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        // echo ' >>>>>>>>>>>>>>>>>>>>>>>>> ' . getTeamNameByID(1); exit;
        // pr($scoutInfo);
        // exit;


        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            $rules = [
                'coach'         => 'required|max_length[100]',
                'team_id'       => 'required',
                'matches'       => 'required|is_natural',
                'goals'         => 'required|is_natural',
                'player_age'    => 'required|is_natural',
                'session'       => 'required',
                // 'from_date'     => 'required',
                // 'to_date'       => 'required',
            ];

            if (!$this->validate($rules)) {
                $errors = $this->validator->getErrors();

                $response = [
                    "status"    => false,
                    "message"   => lang('App.provideValidData'),
                    "data"      => ['errors' => $errors]
                ];
            } else{

                $save_data = [
                    'user_id'           => auth()->id(),
                    'team_id'           => $this->request->getVar("team_id"),
                    'matches'           => $this->request->getVar("matches"),
                    'goals'             => $this->request->getVar("goals"),
                    'session'           => $this->request->getVar("session"),
                    'coach'             => $this->request->getVar("coach"),
                    'player_age'        => $this->request->getVar("player_age"),
                    'from_date'         => $this->request->getVar("from_date") ?? '',
                    'to_date'           => $this->request->getVar("to_date") ?? '',
                ];

                // save data                
                if($performanceDetailModel->save($save_data)){

                    // create Activity log
                    $activity_data = [
                        'user_id'               => auth()->id(),
                        'activity_type_id'      => 1,      // added 
                        'activity'              => 'added performance detail',
                        'new_data'              => serialize($this->request->getVar()),
                        'ip'                    => $this->request->getIPAddress()
                    ];
                    createActivityLog($activity_data);


                    // send email to Talent
                    $emailTypeTalent = 'Talent adds performance';
                    $getEmailTemplateTalent = $this->emailTemplateModel->where('type', $emailTypeTalent)
                                                            ->where('language', auth()->user()->lang)            // 2 = german   
                                                            // ->where('language', 2)            // 2 = german   
                                                            ->whereIn('email_for', PLAYER_ROLE)           // 4 = Talent role
                                                            ->first();

                    if($getEmailTemplateTalent){

                        $replacements = [
                            '{talentName}'      => ucfirst(auth()->user()->first_name),
                            '{faqLink}'         => '<a href="#">FAQ</a>',
                            '{helpEmail}'       => '<a href="mailto:help@socceryou.ch">help@socceryou.ch</a>',
                        ];

                        $talentSubject = $getEmailTemplateTalent['subject'];
                    

                        // Replace placeholders with actual values in the email body
                        $content = strtr($getEmailTemplateTalent['content'], $replacements);
                        $talentMessage = view('emailTemplateView' , ['message' => $content, 'disclaimerText' => getEmailDisclaimer(auth()->user()->lang), 'footer' => getEmailFooter(auth()->user()->lang) ]);

                        $talentEmailData = [
                            'fromEmail'     => FROM_EMAIL,
                            'fromName'      => FROM_NAME,
                            'toEmail'       => auth()->user()->email,
                            'subject'       => $talentSubject,
                            'message'       => $talentMessage,
                        ];
                        sendEmail($talentEmailData);
                    }

                    $clubInfo = getClubDetailByUserID(auth()->id()); 

                    if($clubInfo){
                        $getEmailTemplateClub = $this->emailTemplateModel->where('type', $emailTypeTalent)
                                                ->where('language', $clubInfo->lang)            // 2 = german   
                                                // ->where('language', 2)            // 2 = german   
                                                ->whereIn('email_for', CLUB_ROLE)           // 2 = Club role
                                                ->first();

                        if($getEmailTemplateClub){

                            $replacements = [
                                '{clubName}'        => $clubInfo->club_firstname,
                                '{talentName}'      => auth()->user()->first_name,
                                '{faqLink}'         => '<a href="#">FAQ</a>',
                                '{helpEmail}'       => '<a href="mailto:help@socceryou.ch">help@socceryou.ch</a>',
                                '{loginLink}'       => '<a href="#">'. site_url().'</a>',
                            ];

                            $subjectReplacements = [
                                '{talentName}'      => auth()->user()->first_name,
                            ];
                            
                            // Replace placeholders with actual values in the email body
                            //   $clubSubject = $getEmailTemplateClub['subject'];

                            $clubSubject = strtr($getEmailTemplateClub['subject'], $subjectReplacements);
                            $content = strtr($getEmailTemplateClub['content'], $replacements);
                            $clubMessage = view('emailTemplateView' , ['message' => $content, 'disclaimerText' => getEmailDisclaimer($clubInfo->lang), 'footer' => getEmailFooter($clubInfo->lang) ]);

                            $clubEmailData = [
                                'fromEmail'     => FROM_EMAIL,
                                'fromName'      => FROM_NAME,
                                'toEmail'       => $clubInfo->club_email,
                                'subject'       => $clubSubject,
                                'message'       => $clubMessage,
                            ];
                            sendEmail($clubEmailData);
                        }
                    }

                    // send emal to scout
                    $scoutInfo = getScoutDetailByUserID(auth()->id());
                    if($scoutInfo){
                        $getEmailTemplateScout = $this->emailTemplateModel->where('type', $emailTypeTalent)
                                                                        ->where('language', $scoutInfo->lang)            // 2 = german   
                                                                        // ->where('language', 2)            // 2 = german   
                                                                        ->whereIn('email_for', SCOUT_ROLE)           // 3 = Scout role
                                                                        ->first();

                        if($getEmailTemplateScout){

                            $replacements = [
                                '{scoutName}'        => $scoutInfo->scout_firstname,
                                '{talentName}'      => auth()->user()->first_name,
                                '{faqLink}'         => '<a href="#">FAQ</a>',
                                '{helpEmail}'       => '<a href="mailto:help@socceryou.ch">help@socceryou.ch</a>',
                                '{loginLink}'       => '<a href="#">'. site_url().'</a>',
                            ];

                            $subjectReplacements = [
                                '{talentName}'      => auth()->user()->first_name,
                            ];
                            
                            // Replace placeholders with actual values in the email body
                            #  $ScoutSubject = $getEmailTemplateScout['subject'];

                            $scoutSubject = strtr($getEmailTemplateScout['subject'], $subjectReplacements);
                            $content = strtr($getEmailTemplateScout['content'], $replacements);
                            $scoutMessage = view('emailTemplateView' , ['message' => $content, 'disclaimerText' => getEmailDisclaimer($scoutInfo->lang), 'footer' => getEmailFooter($scoutInfo->lang) ]);

                            $scoutEmailData = [
                                'fromEmail'     => FROM_EMAIL,
                                'fromName'      => FROM_NAME,
                                'toEmail'       => $scoutInfo->scout_email,
                                'subject'       => $scoutSubject,
                                'message'       => $scoutMessage,
                            ];
                            sendEmail($scoutEmailData);
                        }
                    }


                    $response = [
                        "status"    => true,
                        "message"   => lang('App.performanceAdded'),
                        "data"      => []
                    ];
                } else {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.performanceAddedFailed'),
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


    public function editPerformanceDetail($id = null){
        //echo '>>>>>>> test >>> '; exit;
        $performanceDetailModel = new PerformanceDetailModel();

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP) || hasAccess(auth()->user(), EDITOR_ACCESS)) {
        
            $isRecordExist = $performanceDetailModel->where('id', $id)->first();

            if(empty($isRecordExist)){
                $response = [
                            "status"    => false,
                            "message"   => lang('App.recordNotExist'),
                            "data"      => []
                        ];
            } else {

                $rules = [
                    'coach'         => 'required|max_length[100]',
                    'team_id'       => 'required',
                    'matches'       => 'required|is_natural',
                    'goals'         => 'required|is_natural',
                    'player_age'    => 'required|is_natural',
                    'session'       => 'required',
                    // 'from_date'     => 'required',
                    // 'to_date'       => 'required',
                ];

                if (!$this->validate($rules)) {
                    $errors = $this->validator->getErrors();

                    $response = [
                        "status"    => false,
                        "message"   => lang('App.provideValidData'),
                        "data"      => ['errors' => $errors]
                    ];
                } else {

                    $save_data = [
                        'id'                => $id,
                        'user_id'           => auth()->id(),
                        'team_id'           => $this->request->getVar("team_id"),
                        'matches'           => $this->request->getVar("matches"),
                        'goals'             => $this->request->getVar("goals"),
                        'coach'             => $this->request->getVar("coach"),
                        'player_age'        => $this->request->getVar("player_age"),
                        'session'           => $this->request->getVar("session"),
                        'from_date'         => $this->request->getVar("from_date"),
                        'to_date'           => $this->request->getVar("to_date"),
                    ];

                    // save data                    
                    if($performanceDetailModel->save($save_data)){ 
                        $activity_data = [
                            'user_id'               => auth()->id(),
                            'activity_type_id'      => 1,      // added 
                            'activity'              => 'updated performance detail.',
                            'new_data'              => serialize($this->request->getVar()),
                            'ip'                    => $this->request->getIPAddress()
                        ];
                        createActivityLog($activity_data);
                        $response = [
                            "status"    => true,
                            "message"   => lang('App.performanceUpdated'),
                            "data"      => []
                        ];
                    } else {
                        $response = [
                            "status"    => false,
                            "message"   => lang('App.performanceUpdateFailed'),
                            "data"      => []
                        ];
                    }
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

    public function getPerformanceDetail($userId = null){
            
        $performanceDetailModel = new PerformanceDetailModel();
    
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $logoPath = base_url() . 'uploads/logos/';
        $profilePath = base_url() . 'uploads/';
        // $userId = auth()->id();
        $userId = $userId ?? auth()->id();

        $builder = $performanceDetailModel->builder();
        $builder->select('
                    performance_details.*, 
                    cb.club_name as team_name, 
                    cb.club_logo as team_club_logo, 
                    CONCAT("'.$profilePath.'", cb.club_logo ) AS team_club_logo_path,
                    c.country_name as country_name,
                    c.country_flag as country_flag,
                    CONCAT( "'.$logoPath.'", c.country_flag) AS country_flag_path
                ');

        $builder->join('teams t', 't.id = performance_details.team_id', 'LEFT');
        $builder->join('clubs cb', 'cb.id = t.club_id', 'LEFT');
        // $builder->join('user_meta um', 'um.user_id = t.club_id AND meta_key = "um.club_name"', 'LEFT');        
        // $builder->join('user_meta pi', 'pi.user_id = t.club_id AND pi.meta_key = "profile_image"', 'LEFT');
        // $builder->join('user_nationalities un', 'un.user_id = t.club_id', 'LEFT');
        $builder->join('countries c', 'c.id = t.country_id', 'LEFT');
        $builder->where('performance_details.user_id', $userId);
        $builder->orderBy('performance_details.id', 'DESC');
        $performanceQuery = $builder->get();
        $performanceDetail = $performanceQuery->getResultArray();     

        // echo '>>>>>>>>>>> getLastQuery >> ' . $this->db->getLastQuery();

        if($performanceDetail){
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => [
                    'logo_path'             => $profilePath,
                    'flag_path'             => $logoPath,
                    'performanceDetail'     => $performanceDetail
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


    public function deletePerformanceDetail($id = null) {

        $performanceDetailModel = new PerformanceDetailModel();

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        // if (auth()->user()->inGroup('superadmin', 'superadmin-representator') && (auth()->user()->can('admin.access') || auth()->user()->can('transfer.ownership'))) {
            
        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            $del_data = $performanceDetailModel->where('id', $id)->first();
            $del_res = $performanceDetailModel->delete($id, true);

            if($del_res == true){

                // create Activity log
                $activity_data = [
                    'user_id'               => auth()->id(),
                    'activity_type_id'      => 3,      // deleted 
                    'activity'              => 'deleted performance detail',
                    'old_data'              => serialize($del_data),
                    'ip'                    => $this->request->getIPAddress()
                ];
                createActivityLog($activity_data);

                // Return a success message as a JSON response
                $response = [
                    "status"    => true,
                    "message"   => lang('App.performanceDeleted'),
                    "data"      => []
                ];
            } else {
                $response = [
                    "status"    => false,
                    "message"   => lang('App.performanceDeleteFailed'),
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


    //////////////////////////// ADMIN APIs /////////////////////////////////

    // Get player performance detail by admin
    // Get API
    /* public function getPerformanceDetailAdmin(){
            
        $performanceDetailModel = new PerformanceDetailModel();
    
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));
    
        $url  = $this->request->getServer('REQUEST_URI');
        $params = getQueryString($url);

        $logoPath = base_url() . 'uploads/logos/';
        $profilePath = base_url() . 'uploads/';

        $builder = $performanceDetailModel->builder();
        $builder->select('
                    performance_details.*, 
                    um.meta_value as team_name, 
                    pi.meta_value as team_logo,
                    CONCAT( "'.$profilePath.'", pi.meta_value) AS team_logo_path,
                    c.country_name as country_name,
                    CONCAT( "'.$logoPath.'", c.country_flag) AS country_flag_path
                ');
        //$builder->join('users u', 'u.id = performance_details.team_id', 'INNER');
        $builder->join('teams t', 't.id = performance_details.team_id', 'INNER');
        $builder->join('user_meta um', 'um.user_id = t.club_id AND um.meta_key = "club_name"', 'INNER');
        $builder->join('user_meta pi', 'pi.user_id = t.club_id AND pi.meta_key = "profile_image"', 'INNER');

        // $builder->join('(SELECT user_id, meta_key, meta_value FROM user_meta WHERE meta_key = "club_name") um', 'um.user_id = t.club_id', 'INNER');
        // $builder->join('(SELECT user_id, meta_key, meta_value FROM user_meta WHERE meta_key = "profile_image") pi', 'pi.user_id = t.club_id', 'INNER');
        $builder->join('user_nationalities un', 'un.user_id = t.club_id', 'INNER');
        $builder->join('countries c', 'c.id = un.country_id', 'INNER');

        $builder->orderBy('performance_details.id', 'DESC');
        $performanceQuery = $builder->get();
        $performanceDetail = $performanceQuery->getResultArray();     
        //    echo '>>>>>>>>>>>>>>>>>>>>>> '. $this->db->getLastQuery();

        if($performanceDetail){
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => [
                    'performanceDetail'    => $performanceDetail
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


    // Edit player performance detail by admin
    // Post API
    public function editPerformanceDetailAdmin($id = null){

        $performanceDetailModel = new PerformanceDetailModel();

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        // if (auth()->user()->inGroup('superadmin', 'superadmin-representator') && (auth()->user()->can('admin.access') || auth()->user()->can('admin.edit') || auth()->user()->can('transfer.ownership'))) {
        
        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {
            
            $isRecordExist = $performanceDetailModel->where('id', $id)->first();

            if(empty($isRecordExist)){
                $response = [
                            "status"    => false,
                            "message"   => lang('App.recordNotExist'),
                            "data"      => []
                        ];
            } else {

                $rules = [
                    'coach'         => 'required|max_length[100]',
                    'team_id'       => 'required',
                    'matches'       => 'required|is_natural',
                    'goals'         => 'required|is_natural',
                    'player_age'    => 'required|is_natural',
                    'session'       => 'required',
                    // 'from_date'     => 'required',
                    // 'to_date'       => 'required',
                ];

                if (!$this->validate($rules)) {
                    $errors = $this->validator->getErrors();

                    $response = [
                        "status"    => false,
                        "message"   => lang('App.provideValidData'),
                        "data"      => ['errors' => $errors]
                    ];
                } else {

                    $save_data = [
                        'id'                => $id,
                        'team_id'           => $this->request->getVar("team_id"),
                        'matches'           => $this->request->getVar("matches"),
                        'goals'             => $this->request->getVar("goals"),
                        'coach'             => $this->request->getVar("coach"),
                        'player_age'        => $this->request->getVar("player_age"),
                        'session'           => $this->request->getVar("session")
                        // 'from_date'         =>  ?? '',
                        // 'to_date'           => $this->request->getVar("to_date") ?? '',
                    ];
                     if(($this->request->getVar("from_date")) && !empty($this->request->getVar("from_date"))){
                        $save_data['from_date'] = $this->request->getVar("from_date");
                     }
                     if(($this->request->getVar("to_date")) && !empty($this->request->getVar("to_date"))){
                        $save_data['to_date'] = $this->request->getVar("to_date");
                     }
                    // save data
                    // $res = $performanceDetailModel->save($save_data);
                        
                    if($performanceDetailModel->save($save_data)){
                        $response = [
                            "status"    => true,
                            "message"   => lang('App.performanceUpdated'),
                            "data"      => []
                        ];
                    } else {
                        $response = [
                            "status"    => false,
                            "message"   => lang('App.performanceUpdateFailed'),
                            "data"      => []
                        ];
                    }
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
    
    public function exportPerformanceDetailAdmin(){
        $performanceDetailModel = new PerformanceDetailModel();
    
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));
    
        $url  = $this->request->getServer('REQUEST_URI');
        $params = getQueryString($url);

        $logoPath = base_url() . 'uploads/logos/';
        $profilePath = base_url() . 'uploads/';

        $builder = $performanceDetailModel->builder();
        $builder->select('
                    performance_details.*, 
                    um.meta_value as team_name, 
                    pi.meta_value as team_logo,
                    CONCAT( "'.$profilePath.'", pi.meta_value) AS team_logo_path,
                    c.country_name as country_name,
                    CONCAT( "'.$logoPath.'", c.country_flag) AS country_flag_path,
                    usr.first_name,usr.last_name
                ');
        //$builder->join('users u', 'u.id = performance_details.team_id', 'INNER');
        $builder->join('teams t', 't.id = performance_details.team_id', 'INNER');
        $builder->join('(SELECT user_id, meta_key, meta_value FROM user_meta WHERE meta_key = "club_name") um', 'um.user_id = t.club_id', 'INNER');
        $builder->join('(SELECT user_id, meta_key, meta_value FROM user_meta WHERE meta_key = "profile_image") pi', 'pi.user_id = t.club_id', 'INNER');
        $builder->join('user_nationalities un', 'un.user_id = t.club_id', 'INNER');
        $builder->join('countries c', 'c.id = un.country_id', 'INNER');
        $builder->join('users usr', 'usr.id = performance_details.user_id', 'INNER');

        $builder->orderBy('performance_details.id', 'DESC');
        $performanceQuery = $builder->get();
        $performanceDetail = $performanceQuery->getResultArray();     
        // echo $this->db->getLastQuery(); die;
        // echo '<pre>'; print_r($performanceDetail); die;
        if(isset($performanceDetail) && !empty($performanceDetail)){
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            // Set the headers
            $sheet->setCellValue('A1', 'First Name');
            $sheet->setCellValue('B1', 'Last Name');
            $sheet->setCellValue('C1', 'Team');
            $sheet->setCellValue('D1', 'Team Flag');
            $sheet->setCellValue('E1', 'Saison');
            $sheet->setCellValue('F1', 'Matches');
            $sheet->setCellValue('G1', 'Goal');
            $sheet->setCellValue('H1', 'Coach during debut');
            $sheet->setCellValue('I1', 'Age of the player');
            $row = 2; // Start from the second row
            foreach ($performanceDetail as $performance) {
                $sheet->setCellValue('A' . $row, htmlspecialchars($performance['first_name']));
                $sheet->setCellValue('B' . $row, htmlspecialchars($performance['last_name']));
                $sheet->setCellValue('C' . $row, htmlspecialchars($performance['country_name']));
                $sheet->setCellValue('D' . $row, htmlspecialchars($performance['country_flag_path']));
                $sheet->setCellValue('E' . $row, htmlspecialchars($performance['session']));
                $sheet->setCellValue('F' . $row, htmlspecialchars($performance['matches']));
                $sheet->setCellValue('G' . $row, htmlspecialchars($performance['goals']));
                $sheet->setCellValue('H' . $row, htmlspecialchars($performance['coach']));
                $sheet->setCellValue('I' . $row, htmlspecialchars($performance['player_age'].' Year'));
                $row++;
            }
            $filename = 'performance_export_' . date('Y-m-d_H-i-s') . '.xlsx';
            $saveDirectory = WRITEPATH  . 'uploads/exports/';
            $folder_nd_file = 'uploads/exports/'.$filename;
            $filePath = $saveDirectory . $filename;
            if (!is_dir($saveDirectory)) {
                mkdir($saveDirectory, 0777, true);  // Create the directory with proper permissions
            }
            $writer = new Xlsx($spreadsheet);
            $writer->save($filePath);  // Save the file
            $activity_data = [
                'user_id'               => auth()->id(),
                'activity_type_id'      => 9,      // viewed
                'activity'              => 'Performance Csv Downloded by user',
                'ip'                    => $this->request->getIPAddress()
            ];
            createActivityLog($activity_data);
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => [
                    "file_name" => $filename,
                    "file_path" => base_url() . $folder_nd_file
                ]
            ];
        }else{
            $response = [
                "status"    => false,
                "message"   => lang('App.noDataFound'),
                "data"      => []
            ];
        }
        return $this->respondCreated($response);
    }
}
