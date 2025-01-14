<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use App\Models\PageModel;
use App\Models\PageMetaModel;
use App\Models\AdvertisementModel;

class PageController extends ResourceController
{
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);
        $this->db = \Config\Database::connect();
        $this->pageModel = new PageModel();
        $this->frontendDir = 'uploads/frontend/';
        // Ensure the directory exists and is writable
        if (!is_dir(WRITEPATH . $this->frontendDir)) {
            mkdir(WRITEPATH . $this->frontendDir, 0777, true);
        }
    }

    // Add Page
    public function addPage()
    {
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            $rules = [
                'title'     => 'required|max_length[500]',
                'language'  => 'required'
            ];

            $validationRule = [];
            $errors = [];
            if ($this->request->getFile('featured_image')) {
                $validationRule = [
                    'featured_image' => [
                        'label' => 'Upload Featured Image',
                        'rules' => [
                            'ext_in[featured_image,jpg,jpeg,png]',
                            'max_size[featured_image,50000]',
                        ],
                        'errors' => [
                            'ext_in'   => lang('App.ext_in', ['file' => 'jpg, jpeg, png']),
                            'max_size'  => lang('App.max_size', ['size' => '50000']),
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
                $page_slug = $this->request->getVar("slug");
                $existArr = $this->pageModel->getPageIdBySlug($page_slug);
                if(isset($existArr) && !empty($existArr['slug'])){ 
                    $slugExistError = lang('App.slugExist',['slugName'=>$existArr['slug']]);
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.provideValidData'),
                        "data"      => ['errors' => $slugExistError]
                    ];
                    return $this->respondCreated($response);   
                }
                // getPageIdBySlug
                $save_data = [
                    'user_id'       => auth()->id(),
                    'title'         => $this->request->getVar("title"),
                    'content'       => $this->request->getVar("content"),
                    'language'      => $this->request->getVar("language"),
                    'status'        => $this->request->getVar("status"),
                    'slug'        => $this->request->getVar("slug"),
                    'page_type'        => $this->request->getVar("page_type"),
                ];
                $ad_types = $this->request->getVar("ad_types");
                if (isset($ad_types) && !empty($ad_types)) {
                    // $ad_types = $this->request->getVar("ad_types");
                    $json_ad_types = json_encode($ad_types);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $save_data['ad_types'] = $json_ad_types;
                    }
                }
                if ($featured_image = $this->request->getFile('featured_image')) {
                    if (! $featured_image->hasMoved()) {
                        $filepath = WRITEPATH . 'uploads/' . $featured_image->store('');
                        $ext = $featured_image->getClientExtension();
                        $save_data['featured_image'] = $featured_image->getName();
                    }
                }

                // save data
                if ($this->pageModel->save($save_data)) {
                    // create Activity log
                    $new_data = 'added page ' . $this->request->getVar("title");
                    $activity_data = [
                        'user_id'               => auth()->id(),
                        'activity_type_id'      => 1,      // added 
                        'activity'              => 'added page',
                        'new_data'              =>  $new_data,
                        'ip'                    => $this->request->getIPAddress()
                    ];
                    createActivityLog($activity_data);

                    $response = [
                        "status"    => true,
                        "message"   => lang('App.pageAdded'),
                        "data"      => []
                    ];
                } else {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.pageAddFailed'),
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
    // Edit Page
    public function editPage($id = null)
    {
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP) || hasAccess(auth()->user(), EDITOR_ACCESS)) {

            $rules = [
                'title'     => 'required|max_length[500]',
                'language'  => 'required'
            ];

            $validationRule = [];
            $errors = [];
            if ($this->request->getFile('featured_image')) {
                $validationRule = [
                    'featured_image' => [
                        'label' => 'Upload Featured Image',
                        'rules' => [
                            'ext_in[featured_image,jpg,jpeg,png]',
                            'max_size[featured_image,50000]',
                        ],
                        'errors' => [
                            'ext_in'   => lang('App.ext_in', ['file' => 'jpg, jpeg, png']),
                            'max_size'  => lang('App.max_size', ['size' => '50000']),
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
                    'language'      => $this->request->getVar("language"),
                    'status'        => $this->request->getVar("status"),
                    'slug'        => $this->request->getVar("slug"),
                ];
                $ad_types = $this->request->getVar("ad_types");
                if (isset($ad_types) && !empty($ad_types)) {
                    // $ad_types = $this->request->getVar("ad_types");
                    $json_ad_types = json_encode($ad_types);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $save_data['ad_types'] = $json_ad_types;
                    }
                }

                if ($featured_image = $this->request->getFile('featured_image')) {
                    $isExist = $this->pageModel->where('id', $id)->first();
                    if ($isExist) {
                        if (!empty($isExist['featured_image']) && file_exists(WRITEPATH . 'uploads/' . $isExist['featured_image'])) {
                            unlink(WRITEPATH . 'uploads/' . $isExist['featured_image']);
                        }
                    }

                    if (! $featured_image->hasMoved()) {
                        $filepath = WRITEPATH . 'uploads/' . $featured_image->store('');
                        $save_data['featured_image'] = $featured_image->getName();
                    }
                }
                // echo '<pre>'; print_r($save_data); die;
                // save data
                if ($this->pageModel->save($save_data)) {
                    // create Activity log
                    $new_data = 'updated page ' . $this->request->getVar("title");
                    $activity_data = [
                        'user_id'               => auth()->id(),
                        'activity_type_id'      => 2,      // updated 
                        'activity'              => 'updated page',
                        'new_data'              =>  $new_data,
                        'ip'                    => $this->request->getIPAddress()
                    ];
                    createActivityLog($activity_data);

                    $response = [
                        "status"    => true,
                        "message"   => lang('App.pageUpdated'),
                        "data"      => []
                    ];
                } else {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.pageUpdateFailed'),
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
    // get list of Pages
    public function getPages()
    {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));
        $url  = $this->request->getServer('REQUEST_URI');
        $params = getQueryString($url);
        
        $imagePath = base_url() . 'uploads/';
        
        $pages = $this->pageModel
        ->select('pages.*, l.language, CONCAT("' . $imagePath . '", pages.featured_image ) AS featured_image_path')
        ->join('languages l', 'l.id = pages.language');
        
       // $params['lang_id'] = '1'; // english
        // if ($params && !empty($params['lang_id'])) {
        //     $pages = $pages->where('pages.language', $params['lang_id']);
        // }

        // if ($params && !empty($params['status'])) {
        //     $pages = $pages->where('pages.status', $params['status']);
        // }




        // $pages = $pages->whereIn('pages.language', [1,2]);

        if ($params && !empty($params['search'])) {

            $pages->groupStart()
                    ->like('pages.title', $params['search'])
                    ->orGroupStart()
                        ->orLike('pages.content', $params['search'])
                    ->groupEnd()
                ->groupEnd();
        }

        if ($params && !empty($params['lang_id'])) {
            $pages->where('pages.language', $params['lang_id']);
        }
        if ($params && !empty($params['status'])) {
            $pages->where('pages.status', $params['status']);
        }

        $frontendDir = $this->frontendDir;
        $pageMetaObject = new PageMetaModel();

        $pages = $pages->orderBy('pages.language', 'ASC')
            ->findAll();
        if(isset($pages) && !empty($pages)){
            foreach($pages as $page_key => $page){
                if(!empty($page['id'])){
                   $allPageMeta =  $pageMetaObject->getFullPageMetaDataById($page['id']);
                   if (isset($allPageMeta) && !empty($allPageMeta)) {
                        $metaData = [];
                        $serilized_fields = array('talent_section', 'feature_sctn', 'pricing_tab', 'news_section');
                        $explode_fields = array('banner_imgs');
                        foreach ($allPageMeta as $meta) {
                            $tabsArr = array();
                            if ($meta['meta_key'] == 'tabs_data') {
                                $tabData = json_decode($meta['meta_value'], true);
                                if (isset($tabData['first_tab']) && !empty($tabData['first_tab'])) {
                                    $firstTabItems = $tabData['first_tab'];
                                    foreach ($firstTabItems as $item) { 
                                        if(!empty($item['images'])){
                                            $item['images'] = explode(',', $item['images']);
                                        }else{
                                            $item['images'] = ''; // added 1-7-2025 to avoid error  
                                        }
                                        $tabsArr["first_tab"][] = [
                                            "title" => $item['title'],
                                            "desc" => $item['desc'],
                                            "images" => $item['images'] // Split images into an array
                                        ];
                                    }
                                }
                                if (isset($tabData['second_tab']) && !empty($tabData['second_tab'])) {
                                    $secondTabItems = $tabData['second_tab'];
                                    foreach ($secondTabItems as $item) { 
                                        if(!empty($item['images'])){
                                            $item['images'] = explode(',', $item['images']);
                                        }else{
                                            $item['images'] = ''; // 1-7-25 
                                        }
                                        $tabsArr["second_tab"][] = [
                                            "title" => $item['title'],
                                            "desc" => $item['desc'],
                                            "images" => $item['images'] // Split images into an array
                                        ];
                                    }
                                }
                                if(isset($tabData['title']) && !empty($tabData['title'])){
                                    $tabsArr['title'] = $tabData['title'];
                                }
                                if(isset($tabData['first_btn_txt']) && !empty($tabData['first_btn_txt'])){
                                    $tabsArr['first_btn_txt'] = $tabData['title'];
                                }
                                if(isset($tabData['sec_btn_txt']) && !empty($tabData['sec_btn_txt'])){
                                    $tabsArr['sec_btn_txt'] = $tabData['title'];
                                }
                                $meta['meta_value'] = $tabsArr;
                            } else if (isset($meta) && !empty($meta['meta_key']) && !empty($meta['meta_value']) && in_array($meta['meta_key'], $serilized_fields)) {
                            //   echo '<pre>'; print_r($meta['meta_value']); die;
                            // $meta['meta_value'] = stripslashes($meta['meta_value']);
                                // $meta['meta_value'] = unserialize($meta['meta_value']);
                                $meta_value = $meta['meta_value'];
                                if (is_string($meta_value) && @unserialize($meta_value) === false && $meta_value !== 'b:0;') {
                                    //  die('Heere');
                                    error_log("Unserialize failed for value: " . print_r($meta_value, true));
                                } else {
                                    $meta['meta_value'] = unserialize($meta_value);
                                }
                            } else if (isset($meta) && !empty($meta['meta_key']) && !empty($meta['meta_value']) && in_array($meta['meta_key'], $explode_fields)) {
                                $meta['meta_value'] = explode(',', $meta['meta_value']);
                            }
                            $metaData['pageData'][$meta['meta_key']] = $meta['meta_value'];
                        }
                        $pages[$page_key]['meta'] = $metaData['pageData'];                                    
                    }else{
                        $pages[$page_key]['meta'] = [];     
                    }
                }
            }
        }
        //echo ' >>>>>>>>>>>>>>>>>>>> getLastQuery >>>>>> '. $this->db->getLastQuery();    
        if ($pages) {
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => ['pages' => $pages]
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

    // get single Page
    public function getPage($id = null)
    {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $imagePath = base_url() . 'uploads/';

        $page = $this->pageModel
            ->select('pages.*, l.language, CONCAT("' . $imagePath . '", pages.featured_image ) AS featured_image_path')
            ->join('languages l', 'l.id = pages.language')
            ->where('pages.id', $id)
            ->first();

        if ($page) {
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => ['page' => $page]
            ];
        } else {
            $response = [
                "status"    => false,
                "message"   => lang('App.noDataFound'),
                "data"      => []
            ];
        }
        echo ' >>>>>>>>>>>>>>>>>>>> getLastQuery >>>>>> '. $this->db->getLastQuery();  
        die;
        return $this->respondCreated($response);
    }
    // Delete Pages
    public function deletePage()
    {
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));
        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {
            $id = $this->request->getVar("id");
            if (!empty($id)) {
                $pages = $this->pageModel->whereIn('id', $id)->findAll();
                if ($pages && count($pages) > 0) {
                    $del_res = false;
                    foreach ($pages as $page) {
                        if ($page['featured_image'] && file_exists(WRITEPATH . 'uploads/' . $page['featured_image'])) {
                            unlink(WRITEPATH . 'uploads/' . $page['featured_image']);
                        }
                        $del_res = $this->pageModel->delete($page['id']);
                        $activity = lang('App.deletePage', ['pageName' => $page['title']]);
                        $old_data = 'page title ' . $page['title'] . ' deleted';
                        $activity_data = [
                            'user_id'               => auth()->id(),
                            'activity_type_id'      => 3,      // deleted 
                            'activity'              => $activity,
                            'old_data'              =>  $old_data,
                            'ip'                    => $this->request->getIPAddress()
                        ];
                        createActivityLog($activity_data);
                    }
                    if ($del_res == true) {
                        $response = [
                            "status"    => true,
                            "message"   => lang('App.pageDeleted'),
                            "data"      => []
                        ];
                    } else {
                        $response = [
                            "status"    => false,
                            "message"   => lang('App.pageDeleteFailed'),
                            "data"      => []
                        ];
                    }
                } else {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.noDataFound'),
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
    // Publish Pages
    public function publishPage()
    {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            $id = $this->request->getVar("id");
            if (!empty($id) && count($id) > 0) {

                if ($this->pageModel
                    ->whereIn('id', $id)
                    ->set(['status' => 2])   //  2 = Published
                    ->update()
                ) {

                    // create Activity log
                    $activity = 'updated pages status to published';
                    $activity_data = [
                        'user_id'               => auth()->id(),
                        'activity_type_id'      => 2,      // updated 
                        'activity'              => $activity,
                        'ip'                    => $this->request->getIPAddress()
                    ];
                    createActivityLog($activity_data);

                    $response = [
                        "status"    => true,
                        "message"   => lang('App.pagePublished'),
                        "data"      => []
                    ];
                } else {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.pagePublishFailed'),
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
    // Draft Pages
    public function draftPage()
    {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            $id = $this->request->getVar("id");
            if (!empty($id) && count($id) > 0) {

                if ($this->pageModel
                    ->whereIn('id', $id)
                    ->set(['status' => 1])   //  1 = Draft
                    ->update()
                ) {

                    // create Activity log
                    $activity = 'updated pages status to Draft';
                    $activity_data = [
                        'user_id'               => auth()->id(),
                        'activity_type_id'      => 2,      // updated 
                        'activity'              => $activity,
                        'ip'                    => $this->request->getIPAddress()
                    ];
                    createActivityLog($activity_data);

                    $response = [
                        "status"    => true,
                        "message"   => lang('App.pageDraft'),
                        "data"      => []
                    ];
                } else {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.pageDraftFailed'),
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
    public function getPageAds($pageId)
    {
        // Get the current language, defaulting to a constant if not provided
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));
        // Prepare the image path (not needed for 'ad_types' data, so this part can be ignored)
        $imagePath = base_url() . 'uploads/';
        // Query the pages table and select only 'ad_types' where page_id is 1
        $page = $this->pageModel->select('ad_types')->where('id', $pageId)->first();
        $this->advertisementModel = new AdvertisementModel();
        // Check if the page with id 1 exists
        if ($page) {
            // The ad_types column is likely a JSON field, so decode it
            $adTypes = json_decode($page['ad_types'], true); // Decode JSON to an array
            $validTypes = [];
            if(!empty($adTypes)){
                // foreach($adTypes as $exist_ad){
                //     $builder = $this->advertisementModel->builder();
                //     $builder->select('advertisements.*, p.title as page_name,p.ad_types');
                //     $builder->join('pages p', 'advertisements.page_id = '.$pageId, 'INNER');
                //     $builder->where('advertisements.type', $exist_ad);
                //     $adQuery = $builder->get();
                //     $advertisements = $adQuery->getResultArray();
                //     if(!empty($advertisements)){

                //     }else{
                //         $validTypes[] = $exist_ad;
                //     }
                // }
            }
            // Prepare the response
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                // "data"      => ['ad_types' => $validTypes]
                "data"      => ['ad_types' => $adTypes]
            ];
        } else {
            // If no data found
            $response = [
                "status"    => false,
                "message"   => lang('App.noDataFound'),
                "data"      => []
            ];
        }
        // Return the response
        return $this->respondCreated($response);
    }
}
