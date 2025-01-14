<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use App\Models\EmailTemplateModel;


class EmailTemplateController extends ResourceController
{
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);
        $this->db = \Config\Database::connect();
        $this->emailTemplateModel = new EmailTemplateModel();
    }

    // Add Email Template
    public function addEmailTemplate(){
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            $rules = [
                'title'             => 'required|max_length[256]',
                'type'              => 'required|max_length[256]',
                'subject'           => 'required',
                'content'           => 'required',
                'email_for'         => 'required',
                'language'          => 'required',
                'location'          => 'required',
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
                    'user_id'       => auth()->id(),
                    'title'         => $this->request->getVar("title"),
                    'type'          => trim($this->request->getVar("type")),
                    'subject'       => $this->request->getVar("subject"),
                    'content'       => $this->request->getVar("content"),
                    'email_for'     => $this->request->getVar("email_for"),
                    'language'      => $this->request->getVar("language"),
                    'location'      => $this->request->getVar("location"),
                    'status'        => $this->request->getVar("status")
                ];

                if($this->emailTemplateModel->save($save_data)){

                    // create Activity log
                    // $activity_data = [
                    //     'user_id'               => auth()->id(),
                    //     'activity_type_id'      => 1,      // added 
                    //     'activity'              => 'added email template',
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
                        $translated_message = lang('App.emailTemplateAdded');
                        $activity_data['activity_' . $lang] = $translated_message;
                    }
                    createActivityLog($activity_data);

                    $response = [
                        "status"    => true,
                        "message"   => lang('App.emailTemplateAdded'),
                        "data"      => []
                    ];
                } else {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.emailTemplateAddFailed'),
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

    // Edit Email Template
    public function editEmailTemplate( $id = null ){
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP) || hasAccess(auth()->user(), EDITOR_ACCESS)) {

            $rules = [
                'title'             => 'required|max_length[256]',
                'type'              => 'required|max_length[256]',
                'subject'           => 'required',
                'content'           => 'required',
                'email_for'         => 'required',
                'language'          => 'required',
                'location'          => 'required',
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
                    'type'          => trim($this->request->getVar("type")),
                    'subject'       => $this->request->getVar("subject"),
                    'content'       => $this->request->getVar("content"),
                    'email_for'     => $this->request->getVar("email_for"),
                    'language'      => $this->request->getVar("language"),
                    'location'      => $this->request->getVar("location"),
                    'status'        => $this->request->getVar("status")
                ];

                if($this->emailTemplateModel->save($save_data)){

                    // create Activity log
                    // $activity_data = [
                    //     'user_id'               => auth()->id(),
                    //     'activity_type_id'      => 5,      // added 
                    //     'activity'              => 'updated email template',
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
                        $translated_message = lang('App.emailTemplateUpdated');
                        $activity_data['activity_' . $lang] = $translated_message;
                    }
                    createActivityLog($activity_data);

                    $response = [
                        "status"    => true,
                        "message"   => lang('App.emailTemplateUpdated'),
                        "data"      => []
                    ];
                } else {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.emailTemplateUpdateFailed'),
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

    // get list of Email Templates 
    public function getEmailTemplates(){
            
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));
    
        $userId = auth()->id(); 

        $url  = $this->request->getServer('REQUEST_URI');
        $params = getQueryString($url);

        $limit  = $params['limit'] ?? PER_PAGE;
        $offset = $params['offset'] ?? 0;

        $emailTemplates = $this->emailTemplateModel
            ->select('email_templates.*, d.location, l.language')
            ->join('domains d', 'd.id = email_templates.location') 
            ->join('languages l', 'l.id = email_templates.language');

        if ($params && !empty($params['search'])) {
            $emailTemplates = $emailTemplates
                ->like('title', $params['search'])
                ->orLike('email_templates.type', $params['search'])
                ->orLike('email_templates.content', $params['search'])
                ->orLike('email_templates.subject', $params['search']);
        }
        /* ##### Code By Amrit ##### */
        if(isset($params['whereClause']) && count($params['whereClause']) > 0){
            $whereClause = $params['whereClause'];
            if (isset($whereClause) && count($whereClause) > 0) {
                
                foreach ($whereClause as $key => $value) {
                    if ($key == 'role') {
                        $emailTemplates->where("email_templates.email_for", $value);  
                    } elseif ($key == 'language') {
                        $emailTemplates->where("email_templates.language", $value);  
                    }
                }

            }
        }
        /* ##### End Code By Amrit ##### */
        // Get the total count of records that match the criteria
        $totalCount = $emailTemplates->countAllResults(false);

        $emailTemplates = $emailTemplates->orderBy('id', 'DESC')
                        ->findAll($limit,$offset);
                        
        // echo '>>>>>>>>> email_templates >>>> ' . $this->db->getLastQuery();

        if($emailTemplates){
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => [
                    'totalCount'     => $totalCount,
                    'emailTemplates' => $emailTemplates
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

    // get single Email Templates 
    public function getEmailTemplate( $id = null ){
                
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        // $url  = $this->request->getServer('REQUEST_URI');
        // $params = getQueryString($url);

        $emailTemplate = $this->emailTemplateModel
            ->select('email_templates.*, d.location as location_name, l.language as language_name')
            ->join('domains d', 'd.id = email_templates.location') 
            ->join('languages l', 'l.id = email_templates.language')
            ->where('email_templates.id', $id)
            ->first();

        // if ($params && !empty($params['search'])) {
        //     $emailTemplates = $emailTemplates
        //         ->like('title', $params['search'])
        //         ->orLike('email_templates.content', $params['search']);
        // }

        // $emailTemplates = $emailTemplates->orderBy('id', 'DESC')
        //                 ->findAll();
        //echo ' >>>>>>>>>>>>>>>>>>>> getLastQuery >>>>>> '. $this->db->getLastQuery();    

        if($emailTemplate){
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => ['emailTemplate' => $emailTemplate]
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

    // Delete Email Template
    public function deleteEmailTemplate() {
            
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            $id = $this->request->getVar("id");
            if(!empty($id) && count($id) > 0){

                if($this->emailTemplateModel->delete($id)){

                    // create Activity log
                    // $activity = 'deleted System PopUps';
                    // $activity_data = [
                    //     'user_id'               => auth()->id(),
                    //     'activity_type_id'      => 3,      // deleted 
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
                        $translated_message = lang('App.emailTemplateDeleted');
                        $activity_data['activity_' . $lang] = $translated_message;
                    }
                    createActivityLog($activity_data);
                    
                    $response = [
                        "status"    => true,
                        "message"   => lang('App.emailTemplateDeleted'),
                        "data"      => []
                    ];
                } else {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.emailTemplateDeleteFailed'),
                        "data"      => []
                    ];
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
}
