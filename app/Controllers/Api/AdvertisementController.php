<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use App\Models\AdvertisementModel;
use App\Models\AdvertisementLogModel;


class AdvertisementController extends ResourceController
{
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);
        $this->db = \Config\Database::connect();
        $this->advertisementModel = new AdvertisementModel();
    }

    // Add Advertisement
    public function addAdvertisement()
    {
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            $rules = [
                'title'         => 'required|max_length[500]',
                'type'          => 'required',
                'redirect_url'  => 'required',
                'page_id '      => 'required',
                'valid_from'    => 'required',
                'views'         => 'required|integer',
                'clicks'        => 'required|integer',
            ];

            $validationRule = [];
            $errors = [];
            if ($this->request->getFile('featured_image')) {
                $validationRule = [
                    'featured_image' => [
                        'label' => 'Upload Featured Image',
                        'rules' => [
                            'ext_in[featured_image,jpg,jpeg,png]',
                            'max_size[featured_image,2000]',
                        ],
                        'errors' => [
                            'ext_in'    => lang('App.ext_in', ['file' => 'jpg, jpeg, png']),
                            'max_size'  => lang('App.max_size', ['size' => '2000']),
                        ],
                    ],
                ];
            }

            if (! $this->validate($rules)) {
                $errors[] = $this->validator->getErrors();
            }
            if ($validationRule && !$this->validate($validationRule)) {
                $errors[] = $this->validator->getErrors();
            }

            if (count($errors) != 0) {
                $errors = $this->validator->getErrors();

                $response = [
                    "status"    => false,
                    "message"   => lang('App.provideValidData'),
                    "data"      => ['errors' => $errors]
                ];
            } else {

                $isAdExist = $this->advertisementModel->where('type', $this->request->getVar("type"))
                                                    ->where('page_id', $this->request->getVar("page_id"))
                                                    ->first();
                                    
                if(!$isAdExist){

                    $save_data = [
                        'user_id'       => auth()->id(),
                        'title'         => $this->request->getVar("title"),
                        'content'       => $this->request->getVar("content"),
                        'redirect_url'  => $this->request->getVar("redirect_url"),
                        'page_id'       => $this->request->getVar("page_id"),
                        'valid_from'    => $this->request->getVar("valid_from"),
                        'type'          => $this->request->getVar("type"),
                        'views'         => $this->request->getVar("views"),
                        'clicks'        => $this->request->getVar("clicks"),
                    ];

                    // if ($featured_image = $this->request->getFile('featured_image')) {
                    //     if (! $featured_image->hasMoved()) {
                    //         $filepath = WRITEPATH . 'uploads/' . $featured_image->store('');
                    //         $ext = $featured_image->getClientExtension();
                    //         $save_data['featured_image'] = $featured_image->getName();
                    //     }
                    // }
                    if ($featured_image = $this->request->getFile('featured_image')) {
                        if (! $featured_image->hasMoved()) {
                            $filepath = WRITEPATH . 'uploads/' . $featured_image->store('');
                            $ext = $featured_image->getClientExtension();
                            $save_data['featured_image'] = $featured_image->getName();
                        }
                    }

                    if ($this->request->getVar("content")) {
                        $save_data['content'] = $this->request->getVar("content");
                    }
                    if ($this->request->getVar("valid_to")) {
                        $save_data['valid_to'] = $this->request->getVar("valid_to");
                    }
                    if ($this->request->getVar("no_validity")) {
                        $save_data['no_validity'] = $this->request->getVar("no_validity");
                    }
                    if ($this->request->getVar("status")) {
                        $save_data['status'] = $this->request->getVar("status");
                    }
                    // echo '<pre>'; print_r($save_data); die;
                    // save data
                    if ($this->advertisementModel->save($save_data)) {
                        // create Activity log
                        $new_data = 'added advertisement ' . $this->request->getVar("title");
                        $activity_data = [
                            'user_id'               => auth()->id(),
                            'activity_type_id'      => 1,      // added 
                            'activity'              => 'added advertisement',
                            'new_data'              =>  $new_data,
                            'ip'                    => $this->request->getIPAddress()
                        ];
                        createActivityLog($activity_data);

                        $response = [
                            "status"    => true,
                            "message"   => lang('App.advertisementAdded'),
                            "data"      => []
                        ];
                    } else {
                        $response = [
                            "status"    => false,
                            "message"   => lang('App.advertisementAddFailed'),
                            "data"      => []
                        ];
                    }
                } else {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.advertisementExist'),
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

    // Edit Advertisement
    public function editAdvertisement($id = null)
    {
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP) || hasAccess(auth()->user(), EDITOR_ACCESS)) {

            $rules = [
                'title'         => 'required|max_length[500]',
                'type'          => 'required',
                'redirect_url'  => 'required',
                'page_id '      => 'required',
                'valid_from'    => 'required',
                'views'         => 'required|integer',
                'clicks'        => 'required|integer',
            ];

            $validationRule = [];
            $errors = [];
            if ($this->request->getFile('featured_image')) {
                $validationRule = [
                    'featured_image' => [
                        'label' => 'Upload Featured Image',
                        'rules' => [
                            'ext_in[featured_image,jpg,jpeg,png]',
                            'max_size[featured_image,2000]',
                        ],
                        'errors' => [
                            'ext_in'    => lang('App.ext_in', ['file' => 'jpg, jpeg, png']),
                            'max_size'  => lang('App.max_size', ['size' => '2000']),
                        ],
                    ],
                ];
            }

            if (! $this->validate($rules)) {
                $errors[] = $this->validator->getErrors();
            }
            if ($validationRule && !$this->validate($validationRule)) {
                $errors[] = $this->validator->getErrors();
            }

            if (count($errors) != 0) {
                $errors = $this->validator->getErrors();

                $response = [
                    "status"    => false,
                    "message"   => lang('App.provideValidData'),
                    "data"      => ['errors' => $errors]
                ];
            } else {

                $save_data = [
                    'id'            => $id,
                    'title'         => $this->request->getVar("title"),
                    'content'       => $this->request->getVar("content"),
                    'redirect_url'  => $this->request->getVar("redirect_url"),
                    'page_id'       => $this->request->getVar("page_id"),
                    'valid_from'    => $this->request->getVar("valid_from"),
                    'type'          => $this->request->getVar("type"),
                    'views'         => $this->request->getVar("views"),
                    'clicks'        => $this->request->getVar("clicks"),
                ];

                if ($featured_image = $this->request->getFile('featured_image')) {

                    $isExist = $this->advertisementModel->where('id', $id)->first();
                    if ($isExist) {
                        if (!empty($isExist['featured_image']) && file_exists(WRITEPATH . 'uploads/' . $isExist['featured_image'])) {
                            unlink(WRITEPATH . 'uploads/' . $isExist['featured_image']);
                        }
                    }

                    if (! $featured_image->hasMoved()) {
                        $filepath = WRITEPATH . 'uploads/' . $featured_image->store('');
                        $ext = $featured_image->getClientExtension();
                        $save_data['featured_image'] = $featured_image->getName();
                    }
                }

                if ($this->request->getVar("content")) {
                    $save_data['content'] = $this->request->getVar("content");
                }
                if ($this->request->getVar("valid_to")) {
                    $save_data['valid_to'] = $this->request->getVar("valid_to");
                }
                if ($this->request->getVar("no_validity")) {
                    $save_data['no_validity'] = $this->request->getVar("no_validity");
                }
                if ($this->request->getVar("status")) {
                    $save_data['status'] = $this->request->getVar("status");
                }

                // save data
                if ($this->advertisementModel->save($save_data)) {
                    // create Activity log
                    $new_data = 'updated advertisement ' . $this->request->getVar("title");
                    $activity_data = [
                        'user_id'               => auth()->id(),
                        'activity_type_id'      => 2,      // updated 
                        'activity'              => 'updated advertisement',
                        'new_data'              =>  $new_data,
                        'ip'                    => $this->request->getIPAddress()
                    ];
                    createActivityLog($activity_data);

                    $response = [
                        "status"    => true,
                        "message"   => lang('App.advertisementupdated'),
                        "data"      => []
                    ];
                } else {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.advertisementupdateFailed'),
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


    // get list of Advertisement
    public function getAdvertisements()
    {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $url  = $this->request->getServer('REQUEST_URI');
        $params = getQueryString($url);

        $builder = $this->advertisementModel->builder();
        $builder->select('advertisements.*, p.title as page_name,p.ad_types');
        $builder->join('pages p', 'advertisements.page_id = p.id', 'INNER');

        if ($params && !empty($params['search'])) {
            $builder->like('advertisements.title', $params['search']);
        }
        /* ##### Code By Amrit ##### */
        if (isset($params['whereClause']) && count($params['whereClause']) > 0) {
            $whereClause = $params['whereClause'];
            if (isset($whereClause) && count($whereClause) > 0) {

                foreach ($whereClause as $key => $value) {

                    if ($key == 'type') {
                        $builder->where("advertisements.type", $value);
                    } elseif ($key == 'page_name') {
                        $builder->where("p.title", $value);
                    } elseif ($key == 'status') {
                        $builder->where("advertisements.status", $value);
                    }
                }
            }
        }
        /* ##### End Code By Amrit ##### */
        // Clone the builder and reset the select part for the count query
        $countBuilder = clone $builder;
        $countBuilder->select('COUNT(*) as total_count', false); // Select only the count
        $countQuery = $countBuilder->get();
        $totalCount = $countQuery->getRow()->total_count;

        $builder->orderBy('id', 'DESC');
        $adQuery = $builder->get();
        $advertisements = $adQuery->getResultArray();

        // echo '>>>>>>>>>>>>>>>>>>>>>> '. $this->db->getLastQuery();
        // die;
        if ($advertisements) {
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => [
                    'totalCount'        => $totalCount,
                    'advertisements'    => $advertisements
                ]
            ];
        } else {
            $response = [
                "status"    => true,
                "message"   => lang('App.noDataFound'),
                "data"      => []
            ];
        }
        return $this->respondCreated($response);
    }

    // get single Advertisement
    public function getAdvertisement($id = null)
    {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        // $advertisement = $this->advertisementModel
        //     ->where('id', $id)
        //     ->first();

        $this->db = \Config\Database::connect();

        $sql = "SELECT advertisements.*, 
                       (SELECT COUNT(*) FROM advertisement_logs WHERE ad_id = advertisements.id AND action_type = 'view') as ad_views,
                       (SELECT COUNT(*) FROM advertisement_logs WHERE ad_id = advertisements.id AND action_type = 'click') as ad_clicks
                FROM advertisements
                WHERE advertisements.id = ?";

        $advertisement = $this->db->query($sql, [$id])->getRowArray();

        if ($advertisement) {
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => ['advertisement' => $advertisement]
            ];
        } else {
            $response = [
                "status"    => true,
                "message"   => lang('App.noDataFound'),
                "data"      => []
            ];
        }
        return $this->respondCreated($response);
    }

    // Delete Advertisement
    public function deleteAdvertisement()
    {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            $id = $this->request->getVar("id");
            if (!empty($id)) {

                $advertisements = $this->advertisementModel->whereIn('id', $id)->findAll();
                $del_res = false;
                if ($advertisements && count($advertisements) > 0) {

                    foreach ($advertisements as $advertisement) {
                        if ($advertisement['featured_image'] && file_exists(WRITEPATH . 'uploads/' . $advertisement['featured_image'])) {
                            unlink(WRITEPATH . 'uploads/' . $advertisement['featured_image']);
                        }
                        $del_res = $this->advertisementModel->delete($advertisement['id']);
                        // create Activity log
                        $old_data = 'Advertisement title ' . $advertisement['title'] . ' deleted';
                        $activity_data = [
                            'user_id'               => auth()->id(),
                            'activity_type_id'      => 3,      // added 
                            'activity'              => 'deleted advertisement',
                            'old_data'              =>  $old_data,
                            'ip'                    => $this->request->getIPAddress()
                        ];
                        createActivityLog($activity_data);
                    }

                    if ($del_res == true) {
                        $response = [
                            "status"    => true,
                            "message"   => lang('App.advertisementDeleted'),
                            "data"      => []
                        ];
                    } else {
                        $response = [
                            "status"    => false,
                            "message"   => lang('App.advertisementDeleteFailed'),
                            "data"      => []
                        ];
                    }
                } else {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.noDataFound'),
                        "data"      => ['advertisement_data' => $del_res]
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

    // Publish Advertisements
    public function publishAdvertisement()
    {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            $id = $this->request->getVar("id");
            if (!empty($id) && count($id) > 0) {

                if ($this->advertisementModel
                    ->whereIn('id', $id)
                    ->set(['status' => 2])   //  2 = Published
                    ->update()
                ) {

                    // create Activity log
                    $activity = 'updated advertisements status to published';
                    $activity_data = [
                        'user_id'               => auth()->id(),
                        'activity_type_id'      => 2,      // updated 
                        'activity'              => $activity,
                        'ip'                    => $this->request->getIPAddress()
                    ];
                    createActivityLog($activity_data);

                    $response = [
                        "status"    => true,
                        "message"   => lang('App.advertisementPublished'),
                        "data"      => []
                    ];
                } else {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.advertisementPublishFailed'),
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

    // Draft Advertisements
    public function draftAdvertisement()
    {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            $id = $this->request->getVar("id");
            if (!empty($id) && count($id) > 0) {

                if ($this->advertisementModel
                    ->whereIn('id', $id)
                    ->set(['status' => 1])   //  1 = Draft
                    ->update()
                ) {

                    // create Activity log
                    $activity = 'updated advertisements status to Draft';
                    $activity_data = [
                        'user_id'               => auth()->id(),
                        'activity_type_id'      => 2,      // updated 
                        'activity'              => $activity,
                        'ip'                    => $this->request->getIPAddress()
                    ];
                    createActivityLog($activity_data);

                    $response = [
                        "status"    => true,
                        "message"   => lang('App.advertisementDraft'),
                        "data"      => []
                    ];
                } else {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.advertisementDraftFailed'),
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


    // Expired Advertisements
    public function expireAdvertisement()
    {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            $id = $this->request->getVar("id");
            if (!empty($id) && count($id) > 0) {

                if ($this->advertisementModel
                    ->whereIn('id', $id)
                    ->set(['status' => 3])   //  3 = Expired
                    ->update()
                ) {

                    // create Activity log
                    $activity = 'updated advertisements status to Expired';
                    $activity_data = [
                        'user_id'               => auth()->id(),
                        'activity_type_id'      => 2,      // updated 
                        'activity'              => $activity,
                        'ip'                    => $this->request->getIPAddress()
                    ];
                    createActivityLog($activity_data);

                    $response = [
                        "status"    => true,
                        "message"   => lang('App.advertisementExpired'),
                        "data"      => []
                    ];
                } else {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.advertisementExpireFailed'),
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

    public function trackAdvertisement()
    {
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $url  = $this->request->getServer('REQUEST_URI');
        $params = getQueryString($url);

        $this->advertisementLogModel = new AdvertisementLogModel();
        $rules = [

            'activity_type' => 'required',
            // 'user_id'  => 'required|integer',
            'ad_id '      => 'required|integer',
        ];

        $errors = [];
        if (! $this->validate($rules)) {
            $errors[] = $this->validator->getErrors();
        }

        if (count($errors) != 0) {
            $errors = $this->validator->getErrors();

            $response = [
                "status"    => false,
                "message"   => lang('App.provideValidData'),
                "data"      => ['errors' => $errors]
            ];
            return $this->respondCreated($response);
        }

        $ad_id = $this->request->getVar('ad_id');
        $user_id = auth()->id();
        $activity_type = $this->request->getVar('activity_type'); // Expecting 'view' or 'click'

        if (!in_array($activity_type, ['view', 'click'])) {
            $msg = 'Invalid activity type';
        }

        $existingRecord = $this->advertisementLogModel->where([
            'ad_id' => $ad_id,
            'user_id' => $user_id,
            'action_type' => $activity_type
        ])->first();

        // echo '<pre>'; print_r($existingRecord); die;
        /* if (isset($existingRecord) && !empty($existingRecord)) {
            $msg = 'Activity already Exist';
            $response = [
                "status"    => false,
                "message"   => $msg,
                // "message"   => lang('App.provideValidData'),
                "data"      => []
            ];
            return $this->respondCreated($response);
        } */

        $logData = [
            'ad_id' => $ad_id,
            'user_id' => $user_id,
            'ip_address' => $this->request->getIPAddress(),
            'action_type' => $activity_type,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->advertisementLogModel->insert($logData)) {

            $this->advertisementModel = new AdvertisementModel();
            $advertisement = $this->advertisementModel->find($ad_id);
            $title = '';
            if (isset($advertisement) && !empty($advertisement['title'])) {
                $title = trim($advertisement['title']);
            }

            $msg = 'Activity ' . ucfirst($activity_type) . 'ed Added SuccessFully.';
            $activity_data = [
                'user_id'               => auth()->id(),
                'activity_type_id'      => 4,      // viewed 
                'activity'              => $title . ' Advertisement ' . ucfirst($activity_type) . ' by user',
                'old_data'              =>  '',
                'ip'                    => $this->request->getIPAddress()
            ];
            createActivityLog($activity_data);

            $adClicks = $this->getAdClicksCount($ad_id); 
            $adViews = $this->getAdViewsCount($ad_id);

            if($adClicks >= $advertisement['clicks'] || $adViews >= $advertisement['views']){

                // send email to admin when click or views limit reached
                $adminEmailType = 'Advertisement - when Max click or views limit reached';
                $getAdminEmailTemplate = getEmailTemplate($adminEmailType, 2, ADMIN_ROLES );
    
                if($getAdminEmailTemplate){

                    $replacements = [
                        '{advertisementName}' => $advertisement['title'],
                    ]; 

                    // Replace placeholders with actual values in the email body
                    $subject = $getAdminEmailTemplate['subject'];
                    $content = strtr($getAdminEmailTemplate['content'], $replacements);
                    $message = view('emailTemplateView' , ['message' => $content, 'disclaimerText' => getEmailDisclaimer(auth()->user()->lang)]);

                    $emailData = [
                        'fromEmail'     => FROM_EMAIL,
                        'fromName'      => FROM_NAME,
                        'toEmail'       => ADMIN_EMAIL,
                        'subject'       => $subject,
                        'message'       => $message,
                    ];
                    sendEmail($emailData);
                }
            }
        }

        $response = [
            "status"    => false,
            "message"   => $msg,
            // "message"   => lang('App.provideValidData'),
            "data"      => []
        ];
        return $this->respondCreated($response);
    }

    public function frontendAds()
    {
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $url  = $this->request->getServer('REQUEST_URI');
        $params = getQueryString($url);

        $builder = $this->advertisementModel->builder();
        $builder->select('advertisements.*, p.title as page_name');
        $builder->join('pages p', 'p.id = advertisements.page_id', 'INNER');

        if ($params && !empty($params['search'])) {
            $builder->like('advertisements.title', $params['search']);
        }
        if (isset($params['page_name']) && !empty($params['page_name'])) {
            $builder->like('p.title', $params['page_name']);
        }
        /* ##### Code By Amrit ##### */
        $builder->where("advertisements.status", 'published'); // only published ads
        /* ##### End Code By Amrit ##### */

        $builder->orderBy('id', 'DESC');
        $adQuery = $builder->get();
        $advertisements = $adQuery->getResultArray();
        if ($advertisements) {
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => ['advertisement' => $advertisements]
            ];
        } else {
            $response = [
                "status"    => true,
                "message"   => lang('App.noDataFound'),
                "data"      => []
            ];
        }
        return $this->respondCreated($response);
    }


    private function getAdClicksCount($ad_id = null){

        $advertisementLogModel = new AdvertisementLogModel();
        $builder = $advertisementLogModel->builder();

        $countResult = $builder->where('action_type', 'click')
                                ->where('ad_id', $ad_id)
                                ->countAllResults();
       
        return $countResult;        
    }


    private function getAdViewsCount($ad_id = null){

        $advertisementLogModel = new AdvertisementLogModel();
        $builder = $advertisementLogModel->builder();

        $countResult = $builder->where('action_type', 'view')
                                ->where('ad_id', $ad_id)
                                ->countAllResults();
       
        return $countResult;        
    }
}
