<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use App\Models\SightingModel;
use App\Models\SightingInviteModel;
use App\Models\SightingAttachmentModel;
use App\Models\EmailTemplateModel;
use CodeIgniter\Shield\Models\UserModel;


use CodeIgniter\Files\File;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


class SightingController extends ResourceController
{
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);
        $this->db = \Config\Database::connect();
        $this->sightingModel = new SightingModel();
        $this->sightingInviteModel = new SightingInviteModel();
        $this->sightingAttachmentModel = new SightingAttachmentModel();
        $this->emailTemplateModel = new EmailTemplateModel();
    }

    // Add sighting data 
    public function addSighting($club_id = null)
    {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        // if (auth()->user()->inGroup('superadmin', 'superadmin-representator') && (auth()->user()->can('admin.access') || auth()->user()->can('transfer.ownership'))) {

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            $userId = $club_id ?? auth()->id();
            if(auth()->user()->role == 5){
                $userId = auth()->user()->parent_id;
            }

            $rules = [
                'event_name'        => 'required|max_length[100]',
                'manager_name'      => 'required|max_length[100]',
                'event_date'        => 'required|valid_date',
                'event_time'        => 'required',
                'address'           => 'required|max_length[256]',
                'zipcode'           => 'required',
                'city'              => 'required|max_length[100]',
                'about_event'       => 'required',
                'attachments.*.title' => 'required|max_length[256]',  // Validate each title in the group
                
                'banner' => [
                    'label' => 'Upload Banner',
                    'rules' => 'uploaded[banner]|ext_in[banner,jpg,jpeg,png]|max_size[banner,5000]',
                    'errors' => [
                        'uploaded'  => lang('App.uploaded', ['file' => 'jpg, jpeg, png']),
                        'ext_in'    => lang('App.ext_in', ['file' => 'jpg, jpeg, png']),
                        'max_size'  => lang('App.max_size', ['size' => '200000']),
                    ],
                ],
            ];

            $validation = \Config\Services::validation();

            // Validate text fields
            if (!$this->validate($rules)) {
                $errors = $validation->getErrors();
            } else {
                $errors = [];
            }
            

            // Manually validate each document file
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
            if ($files = $this->request->getFiles()) {
                $attachments = $files['attachments'];
                foreach ($files['attachments'] as $file) {
                
                    $ext = $file['file']->getClientExtension();
                    $size = $file['file']->getSize();
    
                    if (!$file['file']->isValid()) {
                        $errors['attachments'][] = lang('App.file_invalid', ['file' => $file["file"]->getClientName()]);
                    }
    
                    if ($size > MAX_FILE_SIZE) {
                        $errors['attachments'][] = lang('App.file_too_large', ['file' => $file["file"]->getClientName()]);
                    }
    
                    if (!in_array($ext, $allowedExtensions)) {
                        $errors['attachments'][] = lang('App.file_type_not_allowed', ['file' => $file["file"]->getClientName(), 'ext' => implode(", ",$allowedExtensions)]);
                    }
                }
            }

            if (!empty($errors)) {
                $response = [
                    "status"    => false,
                    "message"   => lang('App.provideValidData'),
                    "data"      => ['errors' => $errors]
                ];
                return $this->respondCreated($response);
            }

            // Save the data
            $data = [
                'club_id'        => $userId,
                'event_name'     => $this->request->getVar('event_name'),
                'manager_name'   => $this->request->getVar('manager_name'),
                'event_date'     => $this->request->getVar('event_date'),
                'event_time'     => $this->request->getVar('event_time'),
                'address'        => $this->request->getVar('address'),
                'zipcode'        => $this->request->getVar('zipcode'),
                'city'           => $this->request->getVar('city'),
                'region'         => $this->request->getVar('region'),
                'about_event'    => $this->request->getVar('about_event'),
                'banner'         => basename($this->request->getFile('banner')->store('')),
            ];

            if(auth()->user()->role != 2){
                $data['added_by'] = auth()->id();
            }

            if ($this->sightingModel->save($data)) {
                $insertID = $this->sightingModel->insertID();
                // create Activity log
                $new_data = [
                    'event_id'      => $insertID,
                    'event_data'    => $data
                ];
                $activity = 'added sighting (' . $this->request->getVar("event_name") . ')';
                $activity_data = [
                    'user_id'               => auth()->id(),
                    'activity_type_id'      => 1,      // added 
                    'activity'              => $activity,
                    'new_data'              => serialize($new_data),
                    'ip'                    => $this->request->getIPAddress()
                ];
                createActivityLog($activity_data);

                // send email to club
                $emailTypeAdmin = 'New sighting created';
                $getEmailTemplateAdmin = $this->emailTemplateModel->where('type', $emailTypeAdmin)
                    ->where('language', auth()->user()->lang)              // 2 = german   
                    ->whereIn('email_for', [2])         // 2 = club role
                    ->first();
                // pr($getEmailTemplate);
                // echo '>>>>>>>>>>> forgotPassword  getLastQuery >>> ' . $this->db->getLastQuery(); exit;

                if($getEmailTemplateAdmin){

                    $event_address = $this->request->getVar('address') .' '. $this->request->getVar('city') .' '. $this->request->getVar('zipcode');

                    $replacements = [
                        '{clubName}'            => ucfirst(auth()->user()->first_name),
                        '{sightingDate}'        => formatDate($this->request->getVar('event_date')),
                        '{sightingTime}'        => $this->request->getVar('event_time'),
                        '{sightingAddress}'     => $event_address,
                    ];

                    $adminSubject = $getEmailTemplateAdmin['subject'];

                    // Replace placeholders with actual values in the email body
                    $content = strtr($getEmailTemplateAdmin['content'], $replacements);
                    $adminMessage = view('emailTemplateView' , ['message' => $content, 'disclaimerText' => getEmailDisclaimer(auth()->user()->lang), 'footer' => getEmailFooter(auth()->user()->lang) ]);
        
                    $adminEmailData = [
                        'fromEmail'     => FROM_EMAIL,
                        'fromName'      => FROM_NAME,
                        'toEmail'       => auth()->user()->email,
                        'subject'       => $adminSubject,
                        'message'       => $adminMessage,
                    ];
                    sendEmail($adminEmailData);
                }

                // Save each document file
                $attachmentTitles = $this->request->getVar('attachments');
                $attachmentData = [];
                $count = 0;
                foreach ($attachments as $attachment) {

                    $attachmentData[] = [
                        'event_id'      => $insertID,
                        'title'         => $attachmentTitles[$count]['title'],
                        'attachment'    => basename($attachment['file']->store('')),
                        'added_by'      => (auth()->user()->role != 2 ? auth()->id() : NULL ),

                    ];
                    $count++;
                }
                
                // save attachment data
                if(count($attachmentData) > 0){
                    $this->sightingAttachmentModel->insertBatch($attachmentData);
                }


                // Save invites data
                $inviteData = [];
                if ($invites = $this->request->getVar('invites')) {


                    foreach ($invites as $invite) {

                        $users = auth()->getProvider();
                        $talent = $users->findById($invite);

                        // send email to talent
                        $emailTypeTalent = 'Club invites Talent to sighting';
                        $getEmailTemplateTalent = $this->emailTemplateModel->where('type', $emailTypeTalent)
                            ->where('language', $talent->lang)              // 2 = german   
                            ->whereIn('email_for', PLAYER_ROLE)         // 2 = club role
                            ->first();

                        if($getEmailTemplateTalent){

                            $replacements = [
                                '{talentName}'      => ucfirst($talent->first_name),
                                '{clubName}'        => ucfirst(getClubName(auth()->id())),
                                '{faqLink}'         => '<a href="#">FAQ</a>',
                                '{helpEmail}'       => '<a href="mailto:help@socceryou.ch">help@socceryou.ch</a>',
                                '{loginLink}'       => '<a href="' . site_url() . '" target="_blank">login</a>',
                            ];

                            $talentSubject = $getEmailTemplateTalent['subject'];

                            // Replace placeholders with actual values in the email body
                            $content = strtr($getEmailTemplateTalent['content'], $replacements);
                            $talentMessage = view('emailTemplateView' , ['message' => $content, 'disclaimerText' => getEmailDisclaimer($talent->lang), 'footer' => getEmailFooter($talent->lang) ]);

                            $talentEmailData = [
                                'fromEmail'     => FROM_EMAIL,
                                'fromName'      => FROM_NAME,
                                'toEmail'       => $talent->email,
                                'subject'       => $talentSubject,
                                'message'       => $talentMessage,
                            ];
                            sendEmail($talentEmailData);
                        }


                        $inviteData[] = [
                            'event_id'   => $insertID,
                            'user_id'    => $invite,
                            'added_by'   => (auth()->user()->role != 2 ? auth()->id() : NULL ),
                        ];
                    }
                }

                // save invite data
                if(count($inviteData) > 0){
                    $this->sightingInviteModel->insertBatch($inviteData);
                }


                $response = [
                    "status"    => true,
                    "message"   => lang('App.sightingAdded'),
                    "data"      => []
                ];
            } else {
                $response = [
                    "status"    => false,
                    "message"   => lang('App.sightingAddFailed'),
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

    /* public function addSighting_old()
    {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $userId = auth()->id();

        $rules = [
            'event_name'        => 'required|max_length[100]',
            'manager_name'      => 'required|max_length[100]',
            'event_date'        => 'required|valid_date',
            'event_time'        => 'required',
            'address'           => 'required|max_length[256]',
            'zipcode'           => 'required',
            'city'              => 'required|max_length[100]',
            //'region'            => 'required|max_length[100]',
            // 'document_title'    => 'required|max_length[100]',
            'about_event'       => 'required',
        ];

        $fileRules = [
            'banner' => [
                'label' => 'Upload Banner',
                'rules' => 'uploaded[banner]|ext_in[banner,jpg,jpeg,png]|max_size[banner,5000]',
                'errors' => [
                    'uploaded'  => lang('App.uploaded', ['file' => 'jpg, jpeg, png, pdf']),
                    'ext_in'    => lang('App.ext_in', ['file' => 'jpg, jpeg, png, pdf']),
                    'max_size'  => lang('App.max_size', ['size' => '200000']),
                ],
            ],

            'document_file' => [
                'label' => 'Upload Banner',
                'rules' => 'uploaded[document_file]|ext_in[document_file,jpg,jpeg,png,pdf]|max_size[document_file,5000]',
                'errors' => [
                    'uploaded'  => lang('App.uploaded', ['file' => 'jpg, jpeg, png, pdf']),
                    'ext_in'    => lang('App.ext_in', ['file' => 'jpg, jpeg, png, pdf']),
                    'max_size'  => lang('App.max_size', ['size' => '200000']),
                ],
            ],
        ];

        $validation = \Config\Services::validation();

        // Validate text fields
        if (!$this->validate($rules)) {
            $errors = $validation->getErrors();
        } else {
            $errors = [];
        }

        // Validate single file upload (banner)
        if (!$this->validate($fileRules)) {
            $fileErrors = $this->validator->getErrors();
            $errors = array_merge($errors, $fileErrors);
        }

        // Validate multiple file uploads (attachments)
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
        if ($files = $this->request->getFiles()) {
            $attachments = $files['attachments'];
            foreach ($files['attachments'] as $file) {
                $ext = $file->getClientExtension();
                $size = $file->getSize();

                if (!$file->isValid()) {
                    $errors['attachments'][] = lang('App.file_invalid', ['file' => $file->getClientName()]);
                }

                if ($size > MAX_FILE_SIZE) {
                    $errors['attachments'][] = lang('App.file_too_large', ['file' => $file->getClientName()]);
                }

                if (!in_array($ext, $allowedExtensions)) {
                    $errors['attachments'][] = lang('App.file_type_not_allowed', ['file' => $file->getClientName()]);
                }
            }
        }

        if (!empty($errors)) {
            $response = [
                "status"    => false,
                "message"   => lang('App.provideValidData'),
                "data"      => ['errors' => $errors]
            ];
            return $this->respondCreated($response);
        }

        // Save the data
        $data = [
            'club_id'        => $userId,
            'event_name'     => $this->request->getVar('event_name'),
            'manager_name'   => $this->request->getVar('manager_name'),
            'event_date'     => $this->request->getVar('event_date'),
            'event_time'     => $this->request->getVar('event_time'),
            'address'        => $this->request->getVar('address'),
            'zipcode'        => $this->request->getVar('zipcode'),
            'city'           => $this->request->getVar('city'),
            'region'         => $this->request->getVar('region'),
            // 'document_title' => $this->request->getVar('document_title'),
            'about_event'    => $this->request->getVar('about_event'),
            'banner'         => basename($this->request->getFile('banner')->store('')),
            // 'document_file'  => basename($this->request->getFile('document_file')->store('')),
        ];

        if ($this->sightingModel->save($data)) {
            $insertID = $this->sightingModel->insertID();
            // create Activity log
            $new_data = [
                'event_id'      => $insertID,
                'event_data'    => $data
            ];
            $activity = 'added sighting (' . $this->request->getVar("event_name") . ')';
            $activity_data = [
                'user_id'               => auth()->id(),
                'activity_type_id'      => 1,      // added 
                'activity'              => $activity,
                'new_data'              => serialize($new_data),
                'ip'                    => $this->request->getIPAddress()
            ];
            createActivityLog($activity_data);

            // Save each document file
            foreach ($attachments as $attachment) {

                $attachmentData = [
                    'event_id'      => $insertID,
                    'attachment'    => basename($attachment->store('')),
                ];

                // save attachment data
                $this->sightingAttachmentModel->save($attachmentData);
            }

            // Save invites data
            if ($invites = $this->request->getVar('invites')) {
                foreach ($invites as $invite) {
                    $inviteData = [
                        'event_id'   => $insertID,
                        'user_id'    => $invite,
                    ];

                    // save invite data
                    $this->sightingInviteModel->save($inviteData);
                }
            }

            $response = [
                "status"    => true,
                "message"   => lang('App.sightingAdded'),
                "data"      => []
            ];
        } else {
            $response = [
                "status"    => false,
                "message"   => lang('App.sightingAddFailed'),
                "data"      => []
            ];
        }

        return $this->respondCreated($response);
    } */


    // delete sighting data 
    public function deleteSighting()
    {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        // if (auth()->user()->inGroup('superadmin', 'superadmin-representator') && (auth()->user()->can('admin.access') || auth()->user()->can('transfer.ownership'))) {

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            $id = $this->request->getVar("id");

            if (!empty($id)) {

                $sightings = $this->sightingModel->whereIn('id', $id)->findAll();

                if ($sightings && count($sightings) > 0) {

                    foreach ($sightings as $sighting) {
                        if (!empty($sighting['banner']) && file_exists(UPLOAD_DIRECTORY . $sighting['banner'])) {
                            unlink(UPLOAD_DIRECTORY . $sighting['banner']);
                        }
                        if (!empty($sighting['document_file']) && file_exists(UPLOAD_DIRECTORY . $sighting['document_file'])) {
                            unlink(UPLOAD_DIRECTORY . $sighting['document_file']);
                        }

                        // delete related attachments
                        $attachments = $this->sightingAttachmentModel->whereIn('event_id', $id)->findAll();

                        if ($attachments) {
                            foreach ($attachments as $attachment) {
                                if (!empty($attachment['attachment']) && file_exists(UPLOAD_DIRECTORY . $attachment['attachment'])) {
                                    unlink(UPLOAD_DIRECTORY . $attachment['attachment']);
                                }
                            }
                        }


                        $del_res = $this->sightingModel->delete($sighting['id']);

                        // create Activity log
                        $activity = 'removed sighting (' . $sighting["event_name"] . ')';
                        $activity_data = [
                            'user_id'               => auth()->id(),
                            'activity_type_id'      => 3,      // deleted 
                            'activity'              => $activity,
                            'old_data'              => serialize($sightings),
                            'ip'                    => $this->request->getIPAddress()
                        ];
                        createActivityLog($activity_data);
                    }

                    if ($del_res == true) {
                        $response = [
                            "status"    => true,
                            "message"   => lang('App.sightingDeleted'),
                            "data"      => []
                        ];
                    } else {
                        $response = [
                            "status"    => false,
                            "message"   => lang('App.sightingDeletedFailed'),
                            "data"      => []
                        ];
                    }
                } else {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.noDataFound'),
                        "data"      => ['report_data' => $del_res]
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

    // get list of sighting data 
    public function getSightings($club_id = null)
    {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $userId = $club_id ?? auth()->id();

        if(auth()->user()->role == 5){
            $userId = auth()->user()->parent_id;
        }

        $url  = $this->request->getServer('REQUEST_URI');
        $params = getQueryString($url);
        

        $sightings = $this->sightingModel
            ->select('id, event_name, manager_name, event_date, event_time, address, zipcode, city')
            ->where('club_id', $userId);

        if ($params && !empty($params['search'])) {
            $sightings = $sightings
                ->like('event_name', $params['search'])
                ->orLike('manager_name', $params['search'])
                ->orLike('address', $params['search']);
        }

        // Get the total number of rows (count) before applying limit and offset
        $totalCount = $sightings->countAllResults(false); // 'false' to avoid resetting the query

        $sightings->orderBy('id', 'DESC');

        if ($params && !empty($params['limit'])) {
            $sightings = $sightings->findAll($params['limit'], $params['offset']);
        } else {
            $sightings = $sightings->findAll();
        }

        // echo ' >>>>>>>>>>>>>>>>>>>> getLastQuery >>>>>> '. $this->db->getLastQuery();    

        if ($sightings) {
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => [
                    'totalCount'    => $totalCount,
                    'sightings'     => $sightings
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

    // get single sight data
    public function getSighting($id = null)
    {
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $imagePath = base_url() . 'uploads/';

        $sighting = $this->sightingModel
            ->select('*, CONCAT("' . $imagePath . '", banner ) AS banner_path, CONCAT("' . $imagePath . '", document_file ) AS document_file_path')
            ->where('id', $id)
            ->first();

        $playersBuilder = $this->db->table('sighting_invites si');
        $playersBuilder->select('si.*, 
                                u.first_name as first_name, 
                                u.last_name as last_name, 
                                um.meta_value as profile_image,
                                CONCAT("' . $imagePath . '", um.meta_value ) AS profile_image_path');
        $playersBuilder->join('users u', 'u.id = si.user_id', 'INNER');
        $playersBuilder->join('user_meta um', 'um.user_id = si.user_id AND um.meta_key LIKE "profile_image"', 'LEFT');
        $playersBuilder->where('event_id', $id);
        $playersQuery = $playersBuilder->get();
        $players_invited = $playersQuery->getResultArray();


        $attachments = $this->sightingAttachmentModel
            ->select('*, CONCAT("' . $imagePath . '", attachment ) AS attachment_path')
            ->where('event_id', $id)
            ->findAll();

        if ($sighting) {
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => [
                    'sighting'          => $sighting,
                    'players_invited'   => $players_invited,
                    'attachments'       => $attachments
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

    // EDIT APIs
    // edit sighting cover image 
    public function editSightingCover($id = null)
    {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        // if (auth()->user()->inGroup('superadmin', 'superadmin-representator') && (auth()->user()->can('admin.access') || auth()->user()->can('admin.edit') || auth()->user()->can('transfer.ownership'))) {

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP) || hasAccess(auth()->user(), EDITOR_ACCESS)) {

            $isExist = $this->sightingModel->where('id', $id)->first();

            if ($isExist) {
                $errors = [];
                $userId = auth()->id();
                $fileRules = [
                    'banner' => [
                        'label' => 'Upload Banner',
                        'rules' => 'uploaded[banner]|ext_in[banner,jpg,jpeg,png]|max_size[banner,5000]',
                        'errors' => [
                            'uploaded'  => lang('App.uploaded', ['file' => 'jpg, jpeg, png, pdf']),
                            'ext_in'    => lang('App.ext_in', ['file' => 'jpg, jpeg, png, pdf']),
                            'max_size'  => lang('App.max_size', ['size' => '200000']),
                        ],
                    ]
                ];

                $validation = \Config\Services::validation();

                // Validate single file upload (banner)
                if (!$this->validate($fileRules)) {
                    $fileErrors = $this->validator->getErrors();
                    $errors = array_merge($errors, $fileErrors);
                }

                if (!empty($errors)) {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.provideValidData'),
                        "data"      => ['errors' => $errors]
                    ];
                    return $this->respondCreated($response);
                }

                // Save the data
                $data = [
                    'id'        => $id,
                    'banner'    => basename($this->request->getFile('banner')->store('')),
                ];

                if ($this->sightingModel->save($data)) {

                    if (!empty($isExist['banner']) && file_exists(UPLOAD_DIRECTORY . $isExist['banner'])) {
                        unlink(UPLOAD_DIRECTORY . $isExist['banner']);
                    }

                    $activity = 'updated sighting cover photo of (' . $isExist["event_name"] . ')';
                    $activity_data = [
                        'user_id'               => auth()->id(),
                        'activity_type_id'      => 2,      // updated 
                        'activity'              => $activity,
                        'old_data'              => serialize($isExist),
                        'ip'                    => $this->request->getIPAddress()
                    ];
                    createActivityLog($activity_data);

                    $response = [
                        "status"    => true,
                        "message"   => lang('App.sightingCoverUpdated'),
                        "data"      => []
                    ];
                } else {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.sightingCoverUpdateFailed'),
                        "data"      => []
                    ];
                }
            } else {
                $response = [
                    "status"    => false,
                    "message"   => lang('App.recordNotExist'),
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

    // edit sighting detail
    public function editSightingDetail($id = null)
    {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        // if (auth()->user()->inGroup('superadmin', 'superadmin-representator') && (auth()->user()->can('admin.access') || auth()->user()->can('admin.edit') || auth()->user()->can('transfer.ownership'))) { 

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP) || hasAccess(auth()->user(), EDITOR_ACCESS)) {

            $isExist = $this->sightingModel->where('id', $id)->first();
            if ($isExist) {

                $rules = [
                    'event_name'        => 'required|max_length[100]',
                    'manager_name'      => 'required|max_length[100]',
                    'event_date'        => 'required|valid_date',
                    'event_time'        => 'required',
                    'address'           => 'required|max_length[256]',
                    'zipcode'           => 'required',
                    'city'              => 'required|max_length[100]',
                    //'region'            => 'required|max_length[100]',
                ];

                if($this->request->getFile('banner')){
                    $rules['banner']    = [
                        'label' => 'Upload Banner',
                        'rules' => 'uploaded[banner]|ext_in[banner,jpg,jpeg,png]|max_size[banner,5000]',
                        'errors' => [
                            'uploaded'  => lang('App.uploaded', ['file' => 'jpg, jpeg, png']),
                            'ext_in'    => lang('App.ext_in', ['file' => 'jpg, jpeg, png']),
                            'max_size'  => lang('App.max_size', ['size' => '200000']),
                        ],
                    ];
                }

                $validation = \Config\Services::validation();

                $errors = [];

                // Validate text fields
                if (!$this->validate($rules)) {
                    $errors = $validation->getErrors();
                }

                if (!empty($errors)) {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.provideValidData'),
                        "data"      => ['errors' => $errors]
                    ];
                    return $this->respondCreated($response);
                }

                // Save the data
                $data = [
                    'id'             => $id,
                    'event_name'     => $this->request->getVar('event_name'),
                    'manager_name'   => $this->request->getVar('manager_name'),
                    'event_date'     => $this->request->getVar('event_date'),
                    'event_time'     => $this->request->getVar('event_time'),
                    'address'        => $this->request->getVar('address'),
                    'zipcode'        => $this->request->getVar('zipcode'),
                    'city'           => $this->request->getVar('city'),
                    'about_event'    => $this->request->getVar('about_event'),
                ];

                // add banner if uploaded
                if($this->request->getFile('banner')){
                    $data['banner'] = basename($this->request->getFile('banner')->store(''));
                }

                if (auth()->user()->role == '1') {
                    $data['added_by'] = auth()->id();
                }

                if ($this->sightingModel->save($data)) {
                    
                    if($this->request->getVar('invites')){
                        $invites = $this->request->getVar('invites');
                    
                        $getInvites = $this->sightingInviteModel
                            ->select('user_id')
                            ->where('event_id', $id)
                            ->findall();

                        $allInvites = array_column($getInvites, 'user_id');

                        $addInvites = array_diff($invites, $allInvites);
                        $delInvites = array_diff($allInvites, $invites);

                        if ($delInvites) {
                            $this->sightingInviteModel
                                ->where('event_id', $id)
                                ->whereIn('user_id', $delInvites)
                                ->delete();
                        }

                        if ($addInvites) {
                            $batchInvite = [];
                            foreach ($addInvites as $nvite) {
                                $batchInvite[]  = [
                                    'event_id'  => $id,
                                    'user_id'   => $nvite,
                                    'added_by'  => ((auth()->user()->role == 1 || auth()->user()->role == 5) ? auth()->id() : NULL)
                                ]; 
                            }

                            if(count($batchInvite) > 0){
                                $this->sightingInviteModel->insertBatch($batchInvite);
                            }
                        }
                    }

                    // create Activity log
                    $new_data = [
                        'event_id'      => $id,
                        'event_data'    => $data
                    ];

                    $old_data = [
                        'event_id'      => $id,
                        'event_data'    => $isExist
                    ];

                    $activity = 'updated sighting detail of (' . $isExist["event_name"] . ')';
                    $activity_data = [
                        'user_id'               => auth()->id(),
                        'activity_type_id'      => 2,      // updated 
                        'activity'              => $activity,
                        'old_data'              => serialize($old_data),
                        'new_data'              => serialize($new_data),
                        'ip'                    => $this->request->getIPAddress()
                    ];
                    createActivityLog($activity_data);

                    $response = [
                        "status"    => true,
                        "message"   => lang('App.sightingDetailUpdated'),
                        "data"      => []
                    ];
                } else {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.sightingDetailUpdateFailed'),
                        "data"      => []
                    ];
                }
            } else {
                $response = [
                    "status"    => false,
                    "message"   => lang('App.recordNotExist'),
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

    // edit sighting about detail
    public function editSightingAbout($id = null)
    {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        // if (auth()->user()->inGroup('superadmin', 'superadmin-representator') && (auth()->user()->can('admin.access') || auth()->user()->can('admin.edit') || auth()->user()->can('transfer.ownership'))) { 

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP) || hasAccess(auth()->user(), EDITOR_ACCESS)) {

            $isExist = $this->sightingModel->where('id', $id)->first();
            if ($isExist) {

                $rules = [
                    'about_event'        => 'required',
                ];

                $validation = \Config\Services::validation();

                $errors = [];

                // Validate text fields
                if (!$this->validate($rules)) {
                    $errors = $validation->getErrors();
                }

                if (!empty($errors)) {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.provideValidData'),
                        "data"      => ['errors' => $errors]
                    ];
                    return $this->respondCreated($response);
                }

                // Save the data
                $data = [
                    'id'             => $id,
                    'about_event'    => $this->request->getVar('about_event'),
                ];

                if ($this->sightingModel->save($data)) {

                    // create Activity log
                    $new_data = [
                        'event_id'      => $id,
                        'event_data'    => $data
                    ];

                    $old_data = [
                        'event_id'      => $id,
                        'event_data'    => $isExist['about_event']
                    ];

                    $activity = 'updated sighting about detail of (' . $isExist["event_name"] . ')';
                    $activity_data = [
                        'user_id'               => auth()->id(),
                        'activity_type_id'      => 2,      // updated 
                        'activity'              => $activity,
                        'old_data'              => serialize($old_data),
                        'new_data'              => serialize($new_data),
                        'ip'                    => $this->request->getIPAddress()
                    ];
                    createActivityLog($activity_data);

                    $response = [
                        "status"    => true,
                        "message"   => lang('App.sightingAboutUpdated'),
                        "data"      => []
                    ];
                } else {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.sightingAboutUpdateFailed'),
                        "data"      => []
                    ];
                }
            } else {
                $response = [
                    "status"    => false,
                    "message"   => lang('App.recordNotExist'),
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


    // add sighting attachments
    public function addSightingAttachments($id = null)
    {
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            $isExist = $this->sightingModel->where('id', $id)->first();
            if ($isExist) {


                $userId = $club_id ?? auth()->id();
                if(auth()->user()->role == 5){
                    $userId = auth()->user()->parent_id;
                }

                $rules = [
                    'attachments.*.title' => 'required|max_length[256]',  // Validate each title in the group
                ];

                $validation = \Config\Services::validation();

                // Validate text fields
                $errors = [];
                if (!$this->validate($rules)) {
                    $errors = $validation->getErrors();
                }

                // Validate multiple file uploads (attachments)
                // Manually validate each document file
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
                if ($files = $this->request->getFiles()) {
                    $attachments = $files['attachments'];
                    foreach ($files['attachments'] as $file) {
                    
                        $ext = $file['file']->getClientExtension();
                        $size = $file['file']->getSize();
        
                        if (!$file['file']->isValid()) {
                            $errors['attachments'][] = lang('App.file_invalid', ['file' => $file["file"]->getClientName()]);
                        }
        
                        if ($size > MAX_FILE_SIZE) {
                            $errors['attachments'][] = lang('App.file_too_large', ['file' => $file["file"]->getClientName()]);
                        }
        
                        if (!in_array($ext, $allowedExtensions)) {
                            $errors['attachments'][] = lang('App.file_type_not_allowed', ['file' => $file["file"]->getClientName(), 'ext' => implode(", ",$allowedExtensions)]);
                        }
                    }
                } else {
                    $errors['attachments'] = lang('App.sightingUploadAttachment');
                }

                if (!empty($errors)) {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.provideValidData'),
                        "data"      => ['errors' => $errors]
                    ];
                    return $this->respondCreated($response);
                }

                // Save each attachment file
                if (isset($attachments)) {

                    // Save each document file
                    $attachmentTitles = $this->request->getVar('attachments');
                    $attachmentData = [];
                    $count = 0;
                    foreach ($attachments as $attachment) {

                        $attachmentData[] = [
                            'event_id'      => $id,
                            'title'         => $attachmentTitles[$count]['title'],
                            'attachment'    => basename($attachment['file']->store('')),
                            'added_by'      => (auth()->user()->role != 2 ? auth()->id() : NULL ),

                        ];
                        $count++;
                    }
                    
                    // save attachment data
                    if(count($attachmentData) > 0){
                        if($this->sightingAttachmentModel->insertBatch($attachmentData)){

                            $activity = 'updated attachments in  sighting (' . $isExist["event_name"] . ')';
                            $activity_data = [
                                'user_id'               => auth()->id(),
                                'activity_type_id'      => 2,      // updated 
                                'activity'              => $activity,
                                'ip'                    => $this->request->getIPAddress()
                            ];
                            createActivityLog($activity_data);

                            $response = [
                                "status"    => true,
                                "message"   => lang('App.sightingAttachmentUpdated'),
                                "data"      => []
                            ];
                        } else {
                            $response = [
                                "status"    => false,
                                "message"   => lang('App.sightingAttachmentUpdateFailed'),
                                "data"      => []
                            ];
                        }
                    }
                }
            } else {
                $response = [
                    "status"    => false,
                    "message"   => lang('App.recordNotExist'),
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

    // add sighting attachments
    public function addSightingAttachments_old($id = null)
    {
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $isExist = $this->sightingModel->where('id', $id)->first();
        if ($isExist) {

            $errors = [];
            // Validate multiple file uploads (attachments)
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
            if ($files = $this->request->getFiles()) {
                $attachments = $files['attachments'];
                foreach ($files['attachments'] as $file) {
                    $ext = $file->getClientExtension();
                    $size = $file->getSize();

                    if (!$file->isValid()) {
                        $errors['attachments'][] = lang('App.file_invalid', ['file' => $file->getClientName()]);
                    }

                    if ($size > MAX_FILE_SIZE) {
                        $errors['attachments'][] = lang('App.file_too_large', ['file' => $file->getClientName(), 'size' => '5 MB']);
                    }

                    if (!in_array($ext, $allowedExtensions)) {
                        $errors['attachments'][] = lang('App.file_type_not_allowed', ['file' => $file->getClientName(), 'ext' => 'jpg, jpeg, png, pdf']);
                    }
                }
            } else {
                $errors[] = 'Please upload atleast one attachment';
            }

            if (!empty($errors)) {
                $response = [
                    "status"    => false,
                    "message"   => lang('App.provideValidData'),
                    "data"      => ['errors' => $errors]
                ];
                return $this->respondCreated($response);
            }

            // Save each attachment file
            if (isset($attachments)) {
                foreach ($attachments as $attachment) {

                    $attachmentData = [
                        'event_id'      => $id,
                        'attachment'    => basename($attachment->store('')),
                    ];

                    if (auth()->user()->role == 1 || auth()->user()->role == 5) {
                        $attachmentData['added_by'] = auth()->id();
                    }

                    // save attachment data
                    if ($this->sightingAttachmentModel->save($attachmentData)) {
                        $activity = 'updated attachments in  sighting (' . $isExist["event_name"] . ')';
                        $activity_data = [
                            'user_id'               => auth()->id(),
                            'activity_type_id'      => 2,      // updated 
                            'activity'              => $activity,
                            'ip'                    => $this->request->getIPAddress()
                        ];
                        createActivityLog($activity_data);

                        $response = [
                            "status"    => true,
                            "message"   => 'Attachments updated successfully',
                            "data"      => []
                        ];
                    }
                }
            }
        } else {
            $response = [
                "status"    => false,
                "message"   => lang('App.recordNotExist'),
                "data"      => []
            ];
        }

        return $this->respondCreated($response);
    }

    // delete sighting Attachment
    public function deleteSightingAttachment($id = null)
    {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        // if (auth()->user()->inGroup('superadmin', 'superadmin-representator') && (auth()->user()->can('admin.access') || auth()->user()->can('transfer.ownership'))) {

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            $del_data = $this->sightingAttachmentModel->where('id', $id)->first();
            $del_res = $this->sightingAttachmentModel->delete($id);

            if ($del_res == true) {

                if (!empty($del_data['attachment']) && file_exists(UPLOAD_DIRECTORY . $del_data['attachment'])) {
                    unlink(UPLOAD_DIRECTORY . $del_data['attachment']);
                }
                // create Activity log
                $activity_data = [
                    'user_id'               => auth()->id(),
                    'activity_type_id'      => 3,      // deleted 
                    'activity'              => 'deleted sighting invite',
                    'old_data'              =>  serialize($del_data),
                    'ip'                    => $this->request->getIPAddress()
                ];
                createActivityLog($activity_data);

                // Return a success message as a JSON response
                $response = [
                    "status"    => true,
                    "message"   => lang('App.sightingAttachmentDeleted'),
                    "data"      => []
                ];
            } else {
                $response = [
                    "status"    => false,
                    "message"   => lang('App.sightingAttachmentDeleteFailed'),
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

    // add sighting invites
    public function addSightingInvites($id = null)
    {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            $isExist = $this->sightingModel->where('id', $id)->first();
            if ($isExist) {
                $errors = [];
                $rules = [
                    'invites'       => 'required',
                ];

                $validation = \Config\Services::validation();

                // Validate text fields
                if (!$this->validate($rules)) {
                    $errors = $validation->getErrors();
                }

                if (!empty($errors)) {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.provideValidData'),
                        "data"      => ['errors' => $errors]
                    ];
                    return $this->respondCreated($response);
                }

                // Save invites data
                if ($invites = $this->request->getVar('invites')) {
                    $existMessage = $inviteAdded = [];
                    foreach ($invites as $invite) {

                        $inviteExist =  $this->sightingInviteModel
                            ->select('sighting_invites.*, users.first_name, users.last_name')
                            ->where('event_id', $id)
                            ->where('user_id', $invite)
                            ->join('users', 'users.id = sighting_invites.user_id', 'INNER')
                            ->first();

                        if ($inviteExist) {
                            $message = $inviteExist["first_name"] . ' ' . $inviteExist["last_name"] . ' already exist';
                            array_push($existMessage, $message);
                        } else {

                            $inviteData = [
                                'event_id'   => $id,
                                'user_id'    => $invite,
                            ];

                            if (auth()->user()->role == '1') {
                                $inviteData['added_by'] = auth()->id();
                            }

                            // save invite data
                            if ($this->sightingInviteModel->save($inviteData)) {

                                $users = auth()->getProvider();
                                $talent = $users->findById($invite);

                                // send email to talent
                                $emailTypeTalent = 'Club invites Talent to sighting';
                                $getEmailTemplateTalent = $this->emailTemplateModel->where('type', $emailTypeTalent)
                                    ->where('language', $talent->lang)              // 2 = german   
                                    ->whereIn('email_for', PLAYER_ROLE)         // 2 = club role
                                    ->first();

                                if($getEmailTemplateTalent){

                                    $replacements = [
                                        '{talentName}'      => ucfirst($talent->first_name),
                                        '{clubName}'        => ucfirst(getClubName(auth()->id())),
                                        '{faqLink}'         => '<a href="#">FAQ</a>',
                                        '{helpEmail}'       => '<a href="mailto:help@socceryou.ch">help@socceryou.ch</a>',
                                        '{loginLink}'       => '<a href="' . site_url() . '" target="_blank">login</a>',
                                    ];

                                    $talentSubject = $getEmailTemplateTalent['subject'];

                                    // Replace placeholders with actual values in the email body
                                    $content = strtr($getEmailTemplateTalent['content'], $replacements);
                                    $talentMessage = view('emailTemplateView' , ['message' => $content, 'disclaimerText' => getEmailDisclaimer($talent->lang), 'footer' => getEmailFooter($talent->lang) ]);

                                    $talentEmailData = [
                                        'fromEmail'     => FROM_EMAIL,
                                        'fromName'      => FROM_NAME,
                                        'toEmail'       => $talent->email,
                                        'subject'       => $talentSubject,
                                        'message'       => $talentMessage,
                                    ];
                                    sendEmail($talentEmailData);
                                }

                                // $userModel = new UserModel();
                                // $userData = $userModel->where('id', $invite)->first();
                                // $addMessge = $userData->first_name . ' ' . $userData->last_name . ' added';
                                $addMessge = $talent->first_name . ' ' . $talent->last_name . ' added';
                                array_push($inviteAdded, $addMessge);


                                $activity = 'added invites in sighting (' . $isExist["event_name"] . ')';
                                $activity_data = [
                                    'user_id'               => auth()->id(),
                                    'activity_type_id'      => 2,      // updated 
                                    'activity'              => $activity,
                                    'ip'                    => $this->request->getIPAddress()
                                ];
                                createActivityLog($activity_data);
                            }
                        }

                        $response = [
                            "status"    => true,
                            "message"   => lang('App.sightingInvitesAdded'),
                            "data"      => [
                                'inviteExist' => $existMessage,
                                'inviteAdded' => $inviteAdded
                            ]
                        ];
                    }
                }
            } else {
                $response = [
                    "status"    => false,
                    "message"   => lang('App.sightingInvitesAddFailed'),
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

    // response to sighting invite
    public function updateSightingInviteResponse($inviteId = null){

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));
    
        $rules = [
            'status'    => 'required',
        ];
    
        $validation = \Config\Services::validation();
    
        // Validate text fields
        if (!$this->validate($rules)) {
            $errors = $validation->getErrors();
        }
    
        if (!empty($errors)) {
            $response = [
                "status"    => false,
                "message"   => lang('App.provideValidData'),
                "data"      => ['errors' => $errors]
            ];
    
        } else {
    
            $isExist = $this->sightingInviteModel
                        ->select('sighting_invites.*, s.club_id, s.event_name')
                        ->join('sightings s', 's.id = sighting_invites.event_id', 'LEFT')
                        ->where('sighting_invites.id', $inviteId)
                        ->first();

            if ($isExist) {
    
                $save_data = [
                    'id'        => $inviteId,
                    'status'    => $this->request->getVar("status"),
                ];
    
                if($this->sightingInviteModel->save($save_data)){

                    $users = auth()->getProvider();
                    $club = $users->findById($isExist['club_id']);

                    // send email to Club
                    if($this->request->getVar("status") == 'accepted'){
                        $emailTypeClub = 'Talent accepts sighting invite';
                    } else {
                        $emailTypeClub = 'Talent rejects sighting invite';
                    }

                    $getEmailTemplateClub = $this->emailTemplateModel->where('type', $emailTypeClub)
                        ->where('language', $club->lang)            // 2 = german   
                        // ->where('language', 2)            // 2 = german   
                        ->whereIn('email_for', CLUB_ROLE)           // 2 = club role
                        ->first();

                    if($getEmailTemplateClub){

                        $replacements = [
                            '{clubName}'        => ucfirst($club->first_name),
                            '{talentName}'      => ucfirst(auth()->user()->first_name),
                            '{faqLink}'         => '<a href="#">FAQ</a>',
                            '{helpEmail}'       => '<a href="mailto:help@socceryou.ch">help@socceryou.ch</a>',
                        ];

                        $subjectReplacements = [
                            '{talentName}'      => ucfirst(auth()->user()->first_name)
                        ];

                        // Replace placeholders with actual values in the email body
                        $clubSubject = strtr($getEmailTemplateClub['subject'], $subjectReplacements);
                        $content = strtr($getEmailTemplateClub['content'], $replacements);
                        $clubMessage = view('emailTemplateView' , ['message' => $content, 'disclaimerText' => getEmailDisclaimer($club->lang), 'footer' => getEmailFooter($club->lang) ]);

                        $clubEmailData = [
                            'fromEmail'     => FROM_EMAIL,
                            'fromName'      => FROM_NAME,
                            'toEmail'       => $club->email,
                            'subject'       => $clubSubject,
                            'message'       => $clubMessage,
                        ];
                        sendEmail($clubEmailData);
                    }


                    $activity = $this->request->getVar("status") . ' sighting invites for sighting (' . $isExist["event_name"] . ')';
                    $activity_data = [
                        'user_id'               => auth()->id(),
                        'activity_type_id'      => 2,      // updated 
                        'activity'              => $activity,
                        'ip'                    => $this->request->getIPAddress()
                    ];
                    createActivityLog($activity_data);

                    $response = [
                        "status"    => true,
                        "message"   => lang('App.inviteResponseUpdated'),
                        "data"      => []
                    ];
                } else {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.inviteResponseFailed'),
                        "data"      => []
                    ];
                }
    
            } else {
                $response = [
                    "status"    => false,
                    "message"   => lang('App.invalidInvite'),
                    "data"      => []
                ];
            }
    
        }
        return $this->respondCreated($response);
    }


    // delete sighting invites
    public function deleteSightingInvite($id = null)
    {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        // if (auth()->user()->inGroup('superadmin', 'superadmin-representator') && (auth()->user()->can('admin.access') || auth()->user()->can('transfer.ownership'))) {

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            $del_data = $this->sightingInviteModel->where('id', $id)->first();
            $del_res = $this->sightingInviteModel->delete($id);

            if ($del_res == true) {

                // create Activity log
                $activity_data = [
                    'user_id'               => auth()->id(),
                    'activity_type_id'      => 3,      // deleted 
                    'activity'              => 'deleted sighting invite',
                    'old_data'              =>  serialize($del_data),
                    'ip'                    => $this->request->getIPAddress()
                ];
                createActivityLog($activity_data);

                // Return a success message as a JSON response
                $response = [
                    "status"    => true,
                    "message"   => lang('App.sightingInvitesDeleted'),
                    "data"      => []
                ];
            } else {
                $response = [
                    "status"    => false,
                    "message"   => lang('App.sightingInvitesDeletedFailed'),
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

    // delete sighting Cover
    public function deleteSightingCover($id = null)
    {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        // if (auth()->user()->inGroup('superadmin', 'superadmin-representator') && (auth()->user()->can('admin.access') || auth()->user()->can('transfer.ownership'))) {

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            $del_data   = $this->sightingModel->where('id', $id)->first();

            if ($del_data) {

                $data = ['banner' => null];

                $this->sightingModel->update($id, $data);

                if (!empty($del_data['banner']) && file_exists(UPLOAD_DIRECTORY . $del_data['banner'])) {
                    unlink(UPLOAD_DIRECTORY . $del_data['banner']);
                }
                // create Activity log
                $activity_data = [
                    'user_id'               => auth()->id(),
                    'activity_type_id'      => 3,      // deleted 
                    'activity'              => 'deleted sighting cover Image',
                    'old_data'              =>  serialize($del_data),
                    'ip'                    => $this->request->getIPAddress()
                ];
                createActivityLog($activity_data);

                // Return a success message as a JSON response
                $response = [
                    "status"    => true,
                    "message"   => lang('App.sightingCoverDeleted'),
                    "data"      => []
                ];
            } else {
                $response = [
                    "status"    => false,
                    "message"   => lang('App.sightingCoverDeletedFailed'),
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


    // ADMIN APIs

    // Admin - Add sighting data 
    public function addSightingAdmin__($club_id = null)
    {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        //$userId = $club_id; 

        $rules = [
            'event_name'        => 'required|max_length[100]',
            'manager_name'      => 'required|max_length[100]',
            'event_date'        => 'required|valid_date',
            'event_time'        => 'required',
            'address'           => 'required|max_length[256]',
            'zipcode'           => 'required',
            'city'              => 'required|max_length[100]',
            //'region'            => 'required|max_length[100]',
            'document_title'    => 'required|max_length[100]',
            'about_event'       => 'required',
        ];

        $fileRules = [
            'banner' => [
                'label' => 'Upload Banner',
                'rules' => 'uploaded[banner]|ext_in[banner,jpg,jpeg,png]|max_size[banner,5000]',
                'errors' => [
                    'uploaded'  => lang('App.uploaded', ['file' => 'jpg, jpeg, png, pdf']),
                    'ext_in'    => lang('App.ext_in', ['file' => 'jpg, jpeg, png, pdf']),
                    'max_size'  => lang('App.max_size', ['size' => '200000']),
                ],
            ],

            'document_file' => [
                'label' => 'Upload Banner',
                'rules' => 'uploaded[document_file]|ext_in[document_file,jpg,jpeg,png,pdf]|max_size[document_file,5000]',
                'errors' => [
                    'uploaded'  => lang('App.uploaded', ['file' => 'jpg, jpeg, png, pdf']),
                    'ext_in'    => lang('App.ext_in', ['file' => 'jpg, jpeg, png, pdf']),
                    'max_size'  => lang('App.max_size', ['size' => '200000']),
                ],
            ],
        ];

        $validation = \Config\Services::validation();

        // Validate text fields
        if (!$this->validate($rules)) {
            $errors = $validation->getErrors();
        } else {
            $errors = [];
        }

        // Validate single file upload (banner)
        if (!$this->validate($fileRules)) {
            $fileErrors = $this->validator->getErrors();
            $errors = array_merge($errors, $fileErrors);
        }

        // Validate multiple file uploads (attachments)
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
        if ($files = $this->request->getFiles()) {
            $attachments = $files['attachments'];
            foreach ($files['attachments'] as $file) {
                $ext = $file->getClientExtension();
                $size = $file->getSize();

                if (!$file->isValid()) {
                    $errors['attachments'][] = lang('App.file_invalid', ['file' => $file->getClientName()]);
                }

                if ($size > MAX_FILE_SIZE) {
                    $errors['attachments'][] = lang('App.file_too_large', ['file' => $file->getClientName()]);
                }

                if (!in_array($ext, $allowedExtensions)) {
                    $errors['attachments'][] = lang('App.file_type_not_allowed', ['file' => $file->getClientName()]);
                }
            }
        }

        if (!empty($errors)) {
            $response = [
                "status"    => false,
                "message"   => lang('App.provideValidData'),
                "data"      => ['errors' => $errors]
            ];
            return $this->respondCreated($response);
        }

        // Save the data
        $data = [
            'club_id'        => $club_id,
            'event_name'     => $this->request->getVar('event_name'),
            'manager_name'   => $this->request->getVar('manager_name'),
            'event_date'     => $this->request->getVar('event_date'),
            'event_time'     => $this->request->getVar('event_time'),
            'address'        => $this->request->getVar('address'),
            'zipcode'        => $this->request->getVar('zipcode'),
            'city'           => $this->request->getVar('city'),
            'region'         => $this->request->getVar('region'),
            'document_title' => $this->request->getVar('document_title'),
            'about_event'    => $this->request->getVar('about_event'),
            'banner'         => basename($this->request->getFile('banner')->store('')),
            'document_file'  => basename($this->request->getFile('document_file')->store('')),
            'added_by'       => auth()->id(),
        ];

        if ($this->sightingModel->save($data)) {
            $insertID = $this->sightingModel->insertID();
            // create Activity log
            $new_data = [
                'event_id'      => $insertID,
                'event_data'    => $data
            ];
            $activity = 'added sighting (' . $this->request->getVar("event_name") . ')';
            $activity_data = [
                'user_id'               => auth()->id(),
                'activity_type_id'      => 1,      // added 
                'activity'              => $activity,
                'new_data'              => serialize($new_data),
                'ip'                    => $this->request->getIPAddress()
            ];
            createActivityLog($activity_data);

            // Save each document file
            foreach ($attachments as $attachment) {

                $attachmentData = [
                    'event_id'      => $insertID,
                    'attachment'    => basename($attachment->store('')),
                ];

                // save attachment data
                $this->sightingAttachmentModel->save($attachmentData);
            }

            // Save invites data
            if ($invites = $this->request->getVar('invites')) {
                foreach ($invites as $invite) {
                    $inviteData = [
                        'event_id'   => $insertID,
                        'user_id'    => $invite,
                    ];

                    // save invite data
                    $this->sightingInviteModel->save($inviteData);
                }
            }

            $response = [
                "status"    => true,
                "message"   => lang('App.sightingAdded'),
                "data"      => []
            ];
        } else {
            $response = [
                "status"    => false,
                "message"   => lang('App.sightingAddFailed'),
                "data"      => []
            ];
        }

        return $this->respondCreated($response);
    }

    //Admin - delete sighting data 
    public function deleteSightingAdmin()
    {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        // if (auth()->user()->inGroup('superadmin', 'superadmin-representator') && (auth()->user()->can('admin.access') || auth()->user()->can('transfer.ownership'))) {

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {
            
            $id = $this->request->getVar("id");

            if (!empty($id)) {

                $sightings = $this->sightingModel->whereIn('id', $id)->findAll();

                if ($sightings && count($sightings) > 0) {

                    foreach ($sightings as $sighting) {
                        if (!empty($sighting['banner']) && file_exists(UPLOAD_DIRECTORY . $sighting['banner'])) {
                            unlink(UPLOAD_DIRECTORY . $sighting['banner']);
                        }
                        if (!empty($sighting['document_file']) && file_exists(UPLOAD_DIRECTORY . $sighting['document_file'])) {
                            unlink(UPLOAD_DIRECTORY . $sighting['document_file']);
                        }

                        // delete related attachments
                        $attachments = $this->sightingAttachmentModel->whereIn('event_id', $id)->findAll();

                        if ($attachments) {
                            foreach ($attachments as $attachment) {
                                if (!empty($attachment['attachment']) && file_exists(UPLOAD_DIRECTORY . $attachment['attachment'])) {
                                    unlink(UPLOAD_DIRECTORY . $attachment['attachment']);
                                }
                            }
                        }


                        $del_res = $this->sightingModel->delete($sighting['id']);

                        // create Activity log
                        $activity = 'removed sighting (' . $sighting["event_name"] . ')';
                        $activity_data = [
                            'user_id'               => auth()->id(),
                            'activity_type_id'      => 3,      // deleted 
                            'activity'              => $activity,
                            'old_data'              => serialize($sightings),
                            'ip'                    => $this->request->getIPAddress()
                        ];
                        createActivityLog($activity_data);
                    }

                    if ($del_res == true) {
                        $response = [
                            "status"    => true,
                            "message"   => lang('App.sightingDeleted'),
                            "data"      => []
                        ];
                    } else {
                        $response = [
                            "status"    => false,
                            "message"   => lang('App.sightingDeletedFailed'),
                            "data"      => []
                        ];
                    }
                } else {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.noDataFound'),
                        "data"      => ['report_data' => $del_res]
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

    // Admin get list of sighting data 
    /* public function getSightingsAdmin_old($club_id = null)
    {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        //$userId = auth()->id(); 
        
        $url  = $this->request->getServer('REQUEST_URI');
        $params = getQueryString($url);
        // pr($params);

        $sightings = $this->sightingModel
            ->select('id, event_name, manager_name, event_date, event_time, address, zipcode, city')
            ->where('club_id', $club_id);

        if ($params && !empty($params['search'])) {
            $sightings = $sightings
                ->like('event_name', $params['search'])
                ->orLike('manager_name', $params['search'])
                ->orLike('address', $params['search']);
        }

        $sightings->orderBy('id', 'DESC');

        if ($params && !empty($params['limit'])) {
            $sightings = $sightings->findAll($params['limit'], $params['offset']);
        } else {
            $sightings = $sightings->findAll();
        }

        //  echo '<<<<<<<<<<<< Query >>>>>>>>>>>>>'.$this->db->getLastQuery(); die;
        if ($sightings) {
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => ['sightings' => $sightings]
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


    // edit sighting about detail
    /* public function editSightingAboutAdmin( $id = null )
    {
        
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $isExist = $this->sightingModel->where('id', $id )->first();
        if($isExist){

            $rules = [
                'about_event'        => 'required',
            ];

            $validation = \Config\Services::validation();

            $errors = [];

            // Validate text fields
            if(!$this->validate($rules)){    
                $errors = $validation->getErrors();
            } 
                
            if (!empty($errors)) {
                $response = [
                    "status"    => false,
                    "message"   => lang('App.provideValidData'),
                    "data"      => ['errors' => $errors]
                ];
                return $this->respondCreated($response);
            }

            // Save the data
            $data = [
                'id'             => $id,
                'about_event'    => $this->request->getVar('about_event'),
            ];

            if ($this->sightingModel->save($data)) {

                // create Activity log
                $new_data = [ 
                    'event_id'      => $id,
                    'event_data'    => $data
                ];

                $old_data = [ 
                    'event_id'      => $id,
                    'event_data'    => $isExist['about_event']
                ];

                $activity = 'updated sighting about detail of ('.$isExist["event_name"].')';
                $activity_data = [
                    'user_id'               => auth()->id(),
                    'activity_type_id'      => 2,      // updated 
                    'activity'              => $activity,
                    'old_data'              => serialize($old_data),
                    'new_data'              => serialize($new_data),
                    'ip'                    => $this->request->getIPAddress()
                ];
                createActivityLog($activity_data);

                $response = [
                    "status"    => true,
                    "message"   => 'Sighting about details updated successfully',
                    "data"      => []
                ];
            } else {
                $response = [
                    "status"    => false,
                    "message"   => 'Failed to update sighting about details',
                    "data"      => []
                ];
            }
        }   else {
            $response = [
                "status"    => false,
                "message"   => lang('App.recordNotExist'),
                "data"      => []
            ];
        }

        return $this->respondCreated($response);
    } */


    

    public function exportSightingsAdmin($club_id = null)
    {
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        //$userId = auth()->id(); 

        $url  = $this->request->getServer('REQUEST_URI');
        $params = getQueryString($url);

        // $sightings = $this->sightingModel
        //     ->select('id, event_name, manager_name, event_date, event_time, address, zipcode, city,usr.first_name,usr.last_name,usr.id as user_id')
        //     ->where('club_id', $club_id)
        //     ->join('users usr', 'user_id = sightings.club_id', 'LEFT');
        // $sightings = $this->sightingModel
        //     ->select('sightings.id, sightings.event_name, sightings.manager_name, sightings.event_date, sightings.event_time, sightings.address, sightings.zipcode, sightings.city, usr.first_name, usr.last_name, usr.id as user_id')
        //     ->where('sightings.club_id', $club_id)
        //     ->join('users usr', 'usr.id = sightings.club_id', 'LEFT');
        // if ($params && !empty($params['search'])) {
        //     $sightings = $sightings
        //         ->orLike('sightings.event_name', $params['search'])
        //         ->orLike('sightings.manager_name', $params['search'])
        //         ->orLike('sightings.address', $params['search']);
        // }
        // if (isset($params['id']) && !empty($params['id'])) {
        //     $ids = $params['id'];
        //     if (is_array($ids)) {
        //         $sightings = $sightings->whereIn('sightings.id', $ids);
        //     } else {
        //         $sightings = $sightings->where('sightings.id', $ids);
        //     }
        // }
        // $sightings = $sightings->findAll();
        /* ##### New query ##### */
        $sightings = $this->sightingModel
            ->select('sightings.id, sightings.event_name, sightings.manager_name, sightings.event_date, sightings.event_time, sightings.address, sightings.zipcode, sightings.city, usr.first_name, usr.last_name, usr.id as user_id')
            ->where('sightings.club_id', $club_id) // Filter by club_id
            ->join('users usr', 'usr.id = sightings.club_id', 'LEFT'); // Join user by user_id

        // Check if there is a search parameter and it's not empty
        if ($params && !empty($params['search'])) {
            $sightings = $sightings
                ->groupStart() // Start grouping the search conditions
                ->orLike('sightings.event_name', $params['search'])
                ->orLike('sightings.manager_name', $params['search'])
                ->orLike('sightings.address', $params['search'])
                ->groupEnd(); // End grouping
        }

        // Check if 'id' is provided and is not empty
        if (isset($params['id']) && !empty($params['id'])) {
            $ids = $params['id'];
            if (is_array($ids)) {
                $sightings = $sightings->whereIn('sightings.id', $ids); // Multiple IDs
            } else {
                $sightings = $sightings->where('sightings.id', $ids); // Single ID
            }
        }

        // Execute the query and get the results
        $sightings = $sightings->findAll();

        /* ##### New query ##### */
        // echo '<pre>';
        // print_r($sightings);
        // die;
        // echo $this->db->getLastQuery(); die;
        if (isset($sightings) && !empty($sightings)) {
            // echo '<pre>'; print_r($sightings); die;
            /* ##### excel code By Amrit ##### */
            // Create a new Spreadsheet object
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Set the headers
            $sheet->setCellValue('A1', 'First Name');
            $sheet->setCellValue('B1', 'Last Name');
            $sheet->setCellValue('C1', 'Event');
            $sheet->setCellValue('D1', 'Manager Name');
            $sheet->setCellValue('E1', 'Place');
            $sheet->setCellValue('F1', 'Date');
            $sheet->setCellValue('G1', 'Time');

            $row = 2; // Start from the second row
            foreach ($sightings as $sighting) {
                $sheet->setCellValue('A' . $row, htmlspecialchars($sighting['first_name']));
                $sheet->setCellValue('B' . $row, htmlspecialchars($sighting['last_name']));
                $sheet->setCellValue('C' . $row, htmlspecialchars($sighting['event_name']));
                $sheet->setCellValue('D' . $row, htmlspecialchars($sighting['manager_name']));
                $place = $sighting['address'] . ',' . $sighting['city'] . ',' . $sighting['zipcode'];
                $sheet->setCellValue('E' . $row, htmlspecialchars($place));
                $sheet->setCellValue('F' . $row, htmlspecialchars($sighting['event_date']));
                $sheet->setCellValue('G' . $row, htmlspecialchars($sighting['event_time']));
                $row++;
            }
            $filename = 'sightings_export_' . date('Y-m-d_H-i-s') . '.xlsx';
            $folder_nd_file = 'uploads/exports/' . $filename;
            $saveDirectory = WRITEPATH  . 'uploads/exports/';

            // Full path of the file
            $filePath = $saveDirectory . $filename;
            // $fileURL = $ base_url() . '/exports/' . $filename; 

            // Check if the directory exists, if not, create it
            if (!is_dir($saveDirectory)) {
                mkdir($saveDirectory, 0777, true);  // Create the directory with proper permissions
            }

            // Create the Excel file and save it to the specified directory
            $writer = new Xlsx($spreadsheet);
            $writer->save($filePath);  // Save the file
            $activity_data = [
                'user_id'               => auth()->id(),
                'activity_type_id'      => 1,      // viewed
                'activity'              => 'Sighting Csv Downloded by user',
                'ip'                    => $this->request->getIPAddress()
            ];
            createActivityLog($activity_data);
            // Return the file details in the response
            $response = [
                "status"    => true,
                "message"   => "File created successfully.",
                "data"      => [
                    "file_name" => $filename,
                    "file_path" => base_url() . $folder_nd_file
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
