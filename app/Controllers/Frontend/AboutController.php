<?php

namespace App\Controllers\Frontend;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use App\Models\PageMetaModel;
use App\Models\AdvertisementModel;


class AboutController extends ResourceController
{
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);
        $this->db = \Config\Database::connect();
        $this->page_meta = new PageMetaModel();
        $this->frontendDir = 'uploads/frontend/';
    }
    public function aboutPageMetaData()
    {
        $frontendDir = $this->frontendDir;
        $pageMetaObject = new PageMetaModel();

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale($currentLang);
        $pageId = $this->request->getVar("page_id");
        $lang_id = $this->request->getVar("lang_id");
        if (empty($pageId) || !is_numeric($pageId) || empty($lang_id) || !is_numeric($lang_id)) {
            return $this->respondCreated([
                "status" => false,
                "message" => lang('App.invalidParam'),
                "data" => []
            ]);
        }
        $validationRules = [
            'about_banner_bg_img' => [
                'label' => 'Banner Background Image',
                'rules' => [
                    'permit_empty', // Make this optional
                    'is_image[about_banner_bg_img]',
                    'mime_in[about_banner_bg_img,image/jpg,image/jpeg,image/gif,image/png,image/webp]',
                    'max_size[about_banner_bg_img,1000]',
                ],
                'errors' => [
                    'is_image'  => lang('App.is_image', ['file' => 'jpg, jpeg, gif, png, webp']),
                    'mime_in'   => lang('App.mime_in', ['file' => 'jpg, jpeg, gif, png, webp']),
                    'max_size'  => lang('App.max_size', ['size' => '1000']),
                ],
            ],
            'about_banner_img' => [
                'label' => 'Banner Image',
                'rules' => [
                    'permit_empty', // Make this optional
                    'is_image[about_banner_img]',
                    'mime_in[about_banner_img,image/jpg,image/jpeg,image/gif,image/png,image/webp]',
                    'max_size[about_banner_img,1000]',
                ],
                'errors' => [
                    'is_image'  => lang('App.is_image', ['file' => 'jpg, jpeg, gif, png, webp']),
                    'mime_in'   => lang('App.mime_in', ['file' => 'jpg, jpeg, gif, png, webp']),
                    'max_size'  => lang('App.max_size', ['size' => '1000']),
                ],
            ],
            'country_section_banner_img' => [
                'label' => 'Banner Image',
                'rules' => [
                    'permit_empty', // Make this optional
                    'is_image[country_section_banner_img]',
                    'mime_in[country_section_banner_img,image/jpg,image/jpeg,image/gif,image/png,image/webp]',
                    'max_size[country_section_banner_img,1000]',
                ],
                'errors' => [
                    'is_image'  => lang('App.is_image', ['file' => 'jpg, jpeg, gif, png, webp']),
                    'mime_in'   => lang('App.mime_in', ['file' => 'jpg, jpeg, gif, png, webp']),
                    'max_size'  => lang('App.max_size', ['size' => '1000']),
                ],
            ],
            'about_banner_title' => [
                'label' => 'Banner Title',
                'rules' => 'permit_empty|max_length[100]', // Optional text
                'errors' => [
                    'max_length' => lang('App.max_length', ['length' => 100]),
                ],
            ],
            'country_section_title' => [
                'label' => 'Banner Title',
                'rules' => 'permit_empty|max_length[100]', // Optional text
                'errors' => [
                    'max_length' => lang('App.max_length', ['length' => 100]),
                ],
            ],
        ];

        if (!$this->validateData($this->request->getPost(), $validationRules)) {
            $errors = $this->validator->getErrors();
            return $this->respond([
                "status" => false,
                "message" => lang('App.aboutpage_updatedfailure'),
                "data" => ['errors' => $errors]
            ]);
        }
        // Check if any file or text data is being uploaded
        $about_banner_title = $this->request->getVar('about_banner_title');
        $about_banner_bg_img = $this->request->getFile('about_banner_bg_img');
        $about_banner_img = $this->request->getFile('about_banner_img');
        $about_banner_desc = $this->request->getVar('about_banner_desc');
        $country_section_title = $this->request->getVar('country_section_title');
        $about_country_names = $this->request->getVar('about_country_names');
        $country_section_banner_img = $this->request->getFile('country_section_banner_img');
        $about_hero_bg_img = $this->request->getFile('about_hero_bg_img');
        $about_hero_heading_txt = $this->request->getVar('about_hero_heading_txt');
        $about_hero_btn_txt = $this->request->getVar('about_hero_btn_txt');
        $about_hero_btn_link = $this->request->getVar('about_hero_btn_link');

        $savedArr = array();
        $saveData = ['page_id' => $pageId, 'lang_id' => $lang_id];
        if ($about_banner_title) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'about_banner_title')->first();
            $saveData['meta_key'] = 'about_banner_title';
            $saveData['meta_value'] = $about_banner_title;

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($about_banner_bg_img && $about_banner_bg_img->isValid() && !$about_banner_bg_img->hasMoved()) {
            $uniqueName = uniqid() . rand() . date('y_m_d_his') . '_' . $about_banner_bg_img->getClientName();
            if ($about_banner_bg_img->move(WRITEPATH . $frontendDir, $uniqueName)) {
                // Check if an existing record for this image already exists
                $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'about_banner_bg_img')->first();
                if ($ExistInfo) {
                    $existingFilePath = WRITEPATH . $frontendDir . $ExistInfo['meta_value'];
                    if (file_exists($existingFilePath)) {
                        unlink($existingFilePath);
                    }
                    $saveData['id'] = $ExistInfo['id']; // Update the existing record
                }
                $saveData['meta_key'] = 'about_banner_bg_img';
                $saveData['meta_value'] = $uniqueName;
                $pageMetaObject->savePageMeta($saveData);
                $savedArr[] = $saveData;
            }
        }
        if ($about_banner_img && $about_banner_img->isValid() && !$about_banner_img->hasMoved()) {
            $uniqueName = uniqid() . rand() . date('y_m_d_his') . '_' . $about_banner_img->getClientName();
            if ($about_banner_img->move(WRITEPATH . $frontendDir, $uniqueName)) {
                // Check if an existing record for this image already exists
                $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'about_banner_img')->first();
                if ($ExistInfo) {
                    $existingFilePath = WRITEPATH . $frontendDir . $ExistInfo['meta_value'];
                    if (file_exists($existingFilePath)) {
                        unlink($existingFilePath);
                    }
                    $saveData['id'] = $ExistInfo['id']; // Update the existing record
                }
                $saveData['meta_key'] = 'about_banner_img';
                $saveData['meta_value'] = $uniqueName;
                $pageMetaObject->savePageMeta($saveData);
                $savedArr[] = $saveData;
            }
        }
        if ($about_banner_desc) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'about_banner_desc')->first();
            $saveData['meta_key'] = 'about_banner_desc';
            $saveData['meta_value'] = $about_banner_desc;

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($country_section_title) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'country_section_title')->first();
            $saveData['meta_key'] = 'country_section_title';
            $saveData['meta_value'] = $country_section_title;

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($about_country_names) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'about_country_names')->first();
            $saveData['meta_key'] = 'about_country_names';
            if (is_array($about_country_names)) {
                $about_country_names = implode(',', $about_country_names);
            }
            $saveData['meta_value'] = $about_country_names;

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($country_section_banner_img && $country_section_banner_img->isValid() && !$country_section_banner_img->hasMoved()) {
            $uniqueName = uniqid() . rand() . date('y_m_d_his') . '_' . $country_section_banner_img->getClientName();
            if ($country_section_banner_img->move(WRITEPATH . $frontendDir, $uniqueName)) {
                // Check if an existing record for this image already exists
                $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'country_section_banner_img')->first();
                if ($ExistInfo) {
                    $existingFilePath = WRITEPATH . $frontendDir . $ExistInfo['meta_value'];
                    if (file_exists($existingFilePath)) {
                        unlink($existingFilePath);
                    }
                    $saveData['id'] = $ExistInfo['id']; // Update the existing record
                }
                $saveData['meta_key'] = 'country_section_banner_img';
                $saveData['meta_value'] = $uniqueName;
                $pageMetaObject->savePageMeta($saveData);
                $savedArr[] = $saveData;
            }
        }
        if ($about_hero_bg_img && $about_hero_bg_img->isValid() && !$about_hero_bg_img->hasMoved()) {
            $uniqueName = uniqid() . rand() . date('y_m_d_his') . '_' . $about_hero_bg_img->getClientName();
            if ($about_hero_bg_img->move(WRITEPATH . $frontendDir, $uniqueName)) {
                // Check if an existing record for this image already exists
                $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'about_hero_bg_img')->first();
                if ($ExistInfo) {
                    $existingFilePath = WRITEPATH . $frontendDir . $ExistInfo['meta_value'];
                    if (file_exists($existingFilePath)) {
                        unlink($existingFilePath);
                    }
                    $saveData['id'] = $ExistInfo['id']; // Update the existing record
                }
                $saveData['meta_key'] = 'about_hero_bg_img';
                $saveData['meta_value'] = $uniqueName;
                $pageMetaObject->savePageMeta($saveData);
                $savedArr[] = $saveData;
            }
        }
        if ($about_hero_heading_txt) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'about_hero_heading_txt')->first();
            $saveData['meta_key'] = 'about_hero_heading_txt';
            $saveData['meta_value'] = $about_hero_heading_txt;

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($about_hero_btn_txt) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'about_hero_btn_txt')->first();
            $saveData['meta_key'] = 'about_hero_btn_txt';
            $saveData['meta_value'] = $about_hero_btn_txt;

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($about_hero_btn_link) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'about_hero_btn_link')->first();
            $saveData['meta_key'] = 'about_hero_btn_link';
            $saveData['meta_value'] = $about_hero_btn_link;
            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        $returnArr = [];
        if (!empty($savedArr)) {
            foreach ($savedArr as $key => $valueArr) {
                $returnArr[$valueArr['meta_key']] = $valueArr['meta_value'];
            }
        }
        // Prepare response
        return $this->respondCreated([
            "status" => true,
            "message" => lang('App.aboutpage_updatedSuccess'),
            "data" => $returnArr
        ]);
    }
    public function getAboutPage()
    {
        $frontendDir = $this->frontendDir;
        $pageMetaObject = new PageMetaModel();
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $url =  isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
        $url .= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        parse_url($url);
        $uri = new \CodeIgniter\HTTP\URI($url);

        $queryString =  $uri->getQuery();
        parse_str($queryString, $searchParams);
        if (!empty($searchParams['lang']) && empty($this->request->getVar("lang"))) {
            $currentLang = $searchParams['lang'];
        }
        $this->request->setLocale($currentLang);

        $pageId = $this->request->getVar("page_id");
        $lang_id = $this->request->getVar("lang_id");
        if (isset($searchParams['lang_id']) && !empty($searchParams['lang_id'])) {
            $lang_id = $searchParams['lang_id'];
        }
        if (isset($searchParams['page_id']) && !empty($searchParams['page_id'])) {
            $pageId = $searchParams['page_id'];
        }
        $is_error = false;
        if (empty($pageId) || !is_numeric($pageId) || empty($lang_id) || !is_numeric($lang_id)) {
            $is_error = true;
        }
        if (!empty($is_error)) {
            return $this->respondCreated([
                "status" => false,
                "message" => lang('App.invalidParam'),
                "data" => []
            ]);
        }

        $allPageMeta = $pageMetaObject->getFullPageMetaData($pageId, $lang_id);
        if (isset($allPageMeta) && !empty($allPageMeta)) {
            // Initialize an empty array to hold the structured data
            $metaData = [];

            // Loop through each meta entry and map meta_key to meta_value
            foreach ($allPageMeta as $meta) {
                if ($meta['meta_key'] == 'first_tab_free_features_desc') {
                    $meta['meta_value'] = explode('@array_seprator', $meta['meta_value']);
                }
                if ($meta['meta_key'] == 'sec_tab_free_features_desc') {
                    $meta['meta_value'] = explode('@array_seprator', $meta['meta_value']);
                }
                $metaData['pageData'][$meta['meta_key']] = $meta['meta_value'];
            }

            $this->advertisementModel = new AdvertisementModel();
            $builder = $this->advertisementModel->builder();
            $builder->select('advertisements.*, p.title as page_name');
            $builder->join('pages p', 'p.id = advertisements.page_id', 'INNER');

            if ($pageId && !empty($pageId)) {
                $builder->where('advertisements.page_id', $pageId);
                $builder->where('advertisements.status', 'published');
            }

            $builder->orderBy('id', 'DESC');
            $adQuery = $builder->get();
            $advertisements = $adQuery->getResultArray();
            // echo '>>>>>>>>>>>>>>>>>>>>>> ' . $this->db->getLastQuery();
            if (isset($advertisements) && !empty($advertisements)) {
                $metaData['advertisemnetData'] = $advertisements;
            }
            // echo '<pre>'; print_r($metaData); die;
            $response = [
                'status' => true,
                'message' => lang('App.dataFound'),
                'data' => $metaData
            ];
        } else {
            $response = [
                'status' => false,
                'message' => lang('App.noDataFound'),
                'data' => []
            ];
        }
        return $this->respondCreated($response);
    }
    public function savePricePageData()
    {
        $frontendDir = $this->frontendDir;
        $pageMetaObject = new PageMetaModel();
        // $this->request = {};
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale($currentLang);
        // echo '<pre>'; print_r($this->request->getVar()); die;
        $pageId = $this->request->getVar("page_id");
        $lang_id = $this->request->getVar("lang_id");
        if (empty($pageId) || !is_numeric($pageId) || empty($lang_id) || !is_numeric($lang_id)) {
            return $this->respondCreated([
                "status" => false,
                "message" => lang('App.invalidParam'),
                "data" => []
            ]);
        }
        $validationRules = [
            'pricing_bnner_img' => [
                'label' => 'Banner Image',
                'rules' => [
                    'permit_empty', // Make this optional
                    'is_image[pricing_bnner_img]',
                    'mime_in[pricing_bnner_img,image/jpg,image/jpeg,image/gif,image/png,image/webp]',
                    'max_size[pricing_bnner_img,1000]',
                ],
                'errors' => [
                    'is_image'  => lang('App.is_image', ['file' => 'jpg, jpeg, gif, png, webp']),
                    'mime_in'   => lang('App.mime_in', ['file' => 'jpg, jpeg, gif, png, webp']),
                    'max_size'  => lang('App.max_size', ['size' => '1000']),
                ],
            ],
            'pricing_title' => [
                'label' => 'Pricing Title',
                'rules' => 'permit_empty|max_length[100]', // Optional text
                'errors' => [
                    'max_length' => lang('App.max_length', ['length' => 100]),
                ],
            ],
            'pricing_tabs_title' => [
                'label' => 'Pricing Tabs Title',
                'rules' => 'permit_empty|max_length[100]', // Optional text
                'errors' => [
                    'max_length' => lang('App.max_length', ['length' => 100]),
                ],
            ],
            'country_section_title' => [
                'label' => 'Banner Title',
                'rules' => 'permit_empty|max_length[100]', // Optional text
                'errors' => [
                    'max_length' => lang('App.max_length', ['length' => 100]),
                ],
            ],
        ];

        if (!$this->validateData($this->request->getPost(), $validationRules)) {
            $errors = $this->validator->getErrors();
            return $this->respond([
                "status" => false,
                "message" => lang('App.aboutpage_updatedfailure'),
                "data" => ['errors' => $errors]
            ]);
        }
        $pricing_bnner_img = $this->request->getFile('pricing_bnner_img');
        $pricing_title = $this->request->getVar('pricing_title');
        $pricing_tabs_title = $this->request->getVar('pricing_tabs_title');
        $first_tab_monthly_label = $this->request->getVar('first_tab[monthly_label]');
        $first_tab_yearly_label = $this->request->getVar('first_tab[yearly_label]');
        $first_tab_plan_name = $this->request->getVar('first_tab[plan_name]');
        $first_tab_monthly_price = $this->request->getVar('first_tab[monthly_price]');
        $first_tab_monthly_price_currency = $this->request->getVar('first_tab[monthly_price_currency]');
        $first_tab_yearly_price = $this->request->getVar('first_tab[yearly_price]');
        $first_tab_yearly_price_currency = $this->request->getVar('first_tab[yearly_price_currency]');
        $first_tab_free_features_title = $this->request->getVar('first_tab[free_features_title]');
        $first_tab_free_features_sub_title = $this->request->getVar('first_tab[free_features_sub_title]');
        $first_tab_free_features_desc = $this->request->getVar('first_tab[free_features_desc]');
        $first_tab_btn_txt = $this->request->getVar('first_tab[btn_txt]');
        /* ##### Premium & Sec Tab ##### */
        $sec_tab_monthly_label = $this->request->getVar('sec_tab[monthly_label]');
        $sec_tab_yearly_label = $this->request->getVar('sec_tab[yearly_label]');
        $sec_tab_plan_name = $this->request->getVar('sec_tab[plan_name]');
        $sec_tab_monthly_price = $this->request->getVar('sec_tab[monthly_price]');
        $sec_tab_monthly_price_currency = $this->request->getVar('sec_tab[monthly_price_currency]');
        $sec_tab_yearly_price = $this->request->getVar('sec_tab[yearly_price]');
        $sec_tab_yearly_price_currency = $this->request->getVar('sec_tab[yearly_price_currency]');
        $sec_tab_free_features_title = $this->request->getVar('sec_tab[free_features_title]');
        $sec_tab_free_features_sub_title = $this->request->getVar('sec_tab[free_features_sub_title]');
        $sec_tab_free_features_desc = $this->request->getVar('sec_tab[free_features_desc]');
        $sec_tab_btn_txt = $this->request->getVar('sec_tab[btn_txt]');
        $savedArr = array();
        $saveData = ['page_id' => $pageId, 'lang_id' => $lang_id];
        if ($pricing_bnner_img && $pricing_bnner_img->isValid() && !$pricing_bnner_img->hasMoved()) {
            $uniqueName = uniqid() . rand() . date('y_m_d_his') . '_' . $pricing_bnner_img->getClientName();
            if ($pricing_bnner_img->move(WRITEPATH . $frontendDir, $uniqueName)) {
                // Check if an existing record for this image already exists
                $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'pricing_bnner_img')->first();
                if ($ExistInfo) {
                    $existingFilePath = WRITEPATH . $frontendDir . $ExistInfo['meta_value'];
                    if (file_exists($existingFilePath)) {
                        unlink($existingFilePath);
                    }
                    $saveData['id'] = $ExistInfo['id']; // Update the existing record
                }
                $saveData['meta_key'] = 'pricing_bnner_img';
                $saveData['meta_value'] = $uniqueName;
                $pageMetaObject->savePageMeta($saveData);
                $savedArr[] = $saveData;
            }
        }
        if ($pricing_title) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'pricing_title')->first();
            $saveData['meta_key'] = 'pricing_title';
            $saveData['meta_value'] = $pricing_title;

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($pricing_tabs_title) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'pricing_tabs_title')->first();
            $saveData['meta_key'] = 'pricing_tabs_title';
            $saveData['meta_value'] = $pricing_tabs_title;

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($first_tab_monthly_label) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'first_tab_monthly_label')->first();
            $saveData['meta_key'] = 'first_tab_monthly_label';
            $saveData['meta_value'] = $first_tab_monthly_label;

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($first_tab_yearly_label) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'first_tab_yearly_label')->first();
            $saveData['meta_key'] = 'first_tab_yearly_label';
            $saveData['meta_value'] = $first_tab_yearly_label;

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($first_tab_plan_name) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'first_tab_plan_name')->first();
            $saveData['meta_key'] = 'first_tab_plan_name';
            $saveData['meta_value'] = $first_tab_plan_name;

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($first_tab_monthly_price) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'first_tab_monthly_price')->first();
            $saveData['meta_key'] = 'first_tab_monthly_price';
            $saveData['meta_value'] = $first_tab_monthly_price;

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($first_tab_monthly_price_currency) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'first_tab_monthly_price_currency')->first();
            $saveData['meta_key'] = 'first_tab_monthly_price_currency';
            $saveData['meta_value'] = $first_tab_monthly_price_currency;

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($first_tab_yearly_price) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'first_tab_yearly_price')->first();
            $saveData['meta_key'] = 'first_tab_yearly_price';
            $saveData['meta_value'] = $first_tab_yearly_price;

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($first_tab_yearly_price_currency) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'first_tab_yearly_price_currency')->first();
            $saveData['meta_key'] = 'first_tab_yearly_price_currency';
            $saveData['meta_value'] = $first_tab_yearly_price_currency;

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($first_tab_free_features_title) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'first_tab_free_features_title')->first();
            $saveData['meta_key'] = 'first_tab_free_features_title';
            $saveData['meta_value'] = $first_tab_free_features_title;

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($first_tab_free_features_sub_title) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'first_tab_free_features_sub_title')->first();
            $saveData['meta_key'] = 'first_tab_free_features_sub_title';
            $saveData['meta_value'] = $first_tab_free_features_sub_title;

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($first_tab_free_features_desc) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'first_tab_free_features_desc')->first();
            if (is_array($first_tab_free_features_desc)) {
                $first_tab_free_features_desc = implode('@array_seprator', $first_tab_free_features_desc);
            }
            $saveData['meta_key'] = 'first_tab_free_features_desc';
            $saveData['meta_value'] = $first_tab_free_features_desc;

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($first_tab_btn_txt) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'first_tab_btn_txt')->first();
            $saveData['meta_key'] = 'first_tab_btn_txt';
            $saveData['meta_value'] = $first_tab_btn_txt;

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        /* Second Tab Data */
        if ($sec_tab_monthly_label) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'sec_tab_monthly_label')->first();
            $saveData['meta_key'] = 'sec_tab_monthly_label';
            $saveData['meta_value'] = $sec_tab_monthly_label;

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($sec_tab_yearly_label) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'sec_tab_yearly_label')->first();
            $saveData['meta_key'] = 'sec_tab_yearly_label';
            $saveData['meta_value'] = $sec_tab_yearly_label;

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($sec_tab_plan_name) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'sec_tab_plan_name')->first();
            $saveData['meta_key'] = 'sec_tab_plan_name';
            $saveData['meta_value'] = $sec_tab_plan_name;

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($sec_tab_monthly_price) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'sec_tab_monthly_price')->first();
            $saveData['meta_key'] = 'sec_tab_monthly_price';
            $saveData['meta_value'] = $sec_tab_monthly_price;

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($sec_tab_monthly_price_currency) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'sec_tab_monthly_price_currency')->first();
            $saveData['meta_key'] = 'sec_tab_monthly_price_currency';
            $saveData['meta_value'] = $sec_tab_monthly_price_currency;

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($sec_tab_yearly_price) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'sec_tab_yearly_price')->first();
            $saveData['meta_key'] = 'sec_tab_yearly_price';
            $saveData['meta_value'] = $sec_tab_yearly_price;

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($sec_tab_yearly_price_currency) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'sec_tab_yearly_price_currency')->first();
            $saveData['meta_key'] = 'sec_tab_yearly_price_currency';
            $saveData['meta_value'] = $sec_tab_yearly_price_currency;

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($sec_tab_free_features_title) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'sec_tab_free_features_title')->first();
            $saveData['meta_key'] = 'sec_tab_free_features_title';
            $saveData['meta_value'] = $sec_tab_free_features_title;

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($sec_tab_free_features_sub_title) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'sec_tab_free_features_sub_title')->first();
            $saveData['meta_key'] = 'sec_tab_free_features_sub_title';
            $saveData['meta_value'] = $sec_tab_free_features_sub_title;

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($sec_tab_free_features_desc) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'sec_tab_free_features_desc')->first();
            if (is_array($sec_tab_free_features_desc)) {
                $sec_tab_free_features_desc = implode('@array_seprator', $sec_tab_free_features_desc);
            }
            $saveData['meta_key'] = 'sec_tab_free_features_desc';
            $saveData['meta_value'] = $sec_tab_free_features_desc;

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($sec_tab_btn_txt) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'sec_tab_btn_txt')->first();
            $saveData['meta_key'] = 'sec_tab_btn_txt';
            $saveData['meta_value'] = $sec_tab_btn_txt;

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        $returnArr = [];
        if (!empty($savedArr)) {
            foreach ($savedArr as $key => $valueArr) {
                // 
                if ($valueArr['meta_key'] == 'first_tab_free_features_desc') {
                    $valueArr['meta_value'] = explode('@array_seprator', $valueArr['meta_value']);
                }
                if ($valueArr['meta_key'] == 'sec_tab_free_features_desc') {
                    $valueArr['meta_value'] = explode('@array_seprator', $valueArr['meta_value']);
                }
                $returnArr[$valueArr['meta_key']] = $valueArr['meta_value'];
            }
        }
        // Prepare response
        return $this->respondCreated([
            "status" => true,
            "message" => lang('App.pricingpage_updatedSuccess'),
            "data" => $returnArr
        ]);
    }
    public function getPageByID()
    {
        $frontendDir = $this->frontendDir;
        $pageMetaObject = new PageMetaModel();
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $url =  isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
        $url .= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        parse_url($url);
        $uri = new \CodeIgniter\HTTP\URI($url);

        $queryString =  $uri->getQuery();
        parse_str($queryString, $searchParams);
        if (!empty($searchParams['lang']) && empty($this->request->getVar("lang"))) {
            $currentLang = $searchParams['lang'];
        }
        $this->request->setLocale($currentLang);

        $pageId = $this->request->getVar("page_id");
        $lang_id = $this->request->getVar("lang_id");
        if (isset($searchParams['lang_id']) && !empty($searchParams['lang_id'])) {
            $lang_id = $searchParams['lang_id'];
        }
        if (isset($searchParams['page_id']) && !empty($searchParams['page_id'])) {
            $pageId = $searchParams['page_id'];
        }
        $is_error = false;
        if (empty($pageId) || !is_numeric($pageId) || empty($lang_id) || !is_numeric($lang_id)) {
            $is_error = true;
        }
        if (!empty($is_error)) {
            return $this->respondCreated([
                "status" => false,
                "message" => lang('App.invalidParam'),
                "data" => []
            ]);
        }

        $allPageMeta = $pageMetaObject->getFullPageMetaData($pageId, $lang_id);
        if (isset($allPageMeta) && !empty($allPageMeta)) {
            // Initialize an empty array to hold the structured data
            $metaData = [];

            // Loop through each meta entry and map meta_key to meta_value
            foreach ($allPageMeta as $meta) {
                if ($meta['meta_key'] == 'first_tab_free_features_desc') {
                    $meta['meta_value'] = explode('@array_seprator', $meta['meta_value']);
                }
                if ($meta['meta_key'] == 'sec_tab_free_features_desc') {
                    $meta['meta_value'] = explode('@array_seprator', $meta['meta_value']);
                } //faq_first_btn_content
                if ($meta['meta_key'] == 'faq_first_btn_content') {
                    $meta['meta_value'] = unserialize($meta['meta_value']);
                }
                if ($meta['meta_key'] == 'faq_sec_btn_content') {
                    $meta['meta_value'] = unserialize($meta['meta_value']);
                }
                if ($meta['meta_key'] == 'faq_third_btn_content') {
                    $meta['meta_value'] = unserialize($meta['meta_value']);
                }
                $metaData['pageData'][$meta['meta_key']] = $meta['meta_value'];
            }

            $this->advertisementModel = new AdvertisementModel();
            $builder = $this->advertisementModel->builder();
            $builder->select('advertisements.*, p.title as page_name');
            $builder->join('pages p', 'p.id = advertisements.page_id', 'INNER');

            if ($pageId && !empty($pageId)) {
                $builder->where('advertisements.page_id', $pageId);
                $builder->where('advertisements.status', 'published');
            }

            $builder->orderBy('id', 'DESC');
            $adQuery = $builder->get();
            $advertisements = $adQuery->getResultArray();
            // echo '>>>>>>>>>>>>>>>>>>>>>> ' . $this->db->getLastQuery();
            if (isset($advertisements) && !empty($advertisements)) {
                $metaData['advertisemnetData'] = $advertisements;
            }
            $metaData['base_url'] = base_url() . $frontendDir; // return base Url 
            // echo '<pre>'; print_r($metaData); die;
            $response = [
                'status' => true,
                'message' => lang('App.dataFound'),
                'data' => $metaData
            ];
        } else {
            $response = [
                'status' => false,
                'message' => lang('App.noDataFound'),
                'data' => []
            ];
        }
        return $this->respondCreated($response);
    }

    public function saveFaqPageData()
    {
        $frontendDir = $this->frontendDir;
        $pageMetaObject = new PageMetaModel();
        // $this->request = {};
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale($currentLang);
        // echo '<pre>'; print_r($this->request->getVar()); die;
        $pageId = $this->request->getVar("page_id");
        $lang_id = $this->request->getVar("lang_id");
        if (empty($pageId) || !is_numeric($pageId) || empty($lang_id) || !is_numeric($lang_id)) {
            return $this->respondCreated([
                "status" => false,
                "message" => lang('App.invalidParam'),
                "data" => []
            ]);
        }
        $validationRules = [
            'faq_banner_img' => [
                'label' => 'FAQ Banner Image',
                'rules' => [
                    'permit_empty', // Make this optional
                    'is_image[faq_banner_img]',
                    'mime_in[faq_banner_img,image/jpg,image/jpeg,image/gif,image/png,image/webp]',
                    'max_size[faq_banner_img,1000]',
                ],
                'errors' => [
                    'is_image'  => lang('App.is_image', ['file' => 'jpg, jpeg, gif, png, webp']),
                    'mime_in'   => lang('App.mime_in', ['file' => 'jpg, jpeg, gif, png, webp']),
                    'max_size'  => lang('App.max_size', ['size' => '1000']),
                ],
            ],
            'faq_banner_title' => [
                'label' => 'FAQ Title',
                'rules' => 'permit_empty|max_length[100]', // Optional text
                'errors' => [
                    'max_length' => lang('App.max_length', ['length' => 100]),
                ],
            ],
            'faq_collapse_titile' => [
                'label' => 'FAQ collapse Title',
                'rules' => 'permit_empty|max_length[100]', // Optional text
                'errors' => [
                    'max_length' => lang('App.max_length', ['length' => 100]),
                ],
            ],
            'faq_first_btn_txt' => [
                'label' => 'First Button Text',
                'rules' => 'permit_empty|max_length[100]', // Optional text
                'errors' => [
                    'max_length' => lang('App.max_length', ['length' => 100]),
                ],
            ],
            'faq_sec_btn_txt' => [
                'label' => 'Second Button Text',
                'rules' => 'permit_empty|max_length[100]', // Optional text
                'errors' => [
                    'max_length' => lang('App.max_length', ['length' => 100]),
                ],
            ],
            'faq_third_btn_txt' => [
                'label' => 'Third Button Text',
                'rules' => 'permit_empty|max_length[100]', // Optional text
                'errors' => [
                    'max_length' => lang('App.max_length', ['length' => 100]),
                ],
            ],
        ];

        if (!$this->validateData($this->request->getPost(), $validationRules)) {
            $errors = $this->validator->getErrors();
            return $this->respond([
                "status" => false,
                "message" => lang('App.aboutpage_updatedfailure'),
                "data" => ['errors' => $errors]
            ]);
        }
        $faq_banner_img = $this->request->getFile('faq_banner_img');
        $faq_banner_title = $this->request->getVar('faq_banner_title');
        $faq_collapse_titile = $this->request->getVar('faq_collapse_titile');
        $faq_first_btn_txt = $this->request->getVar('faq_first_btn_txt');
        $faq_sec_btn_txt = $this->request->getVar('faq_sec_btn_txt');
        $faq_third_btn_txt = $this->request->getVar('faq_third_btn_txt');
        $faq_first_btn_content = $this->request->getVar('faq_first_btn_content');
        $faq_sec_btn_content = $this->request->getVar('faq_sec_btn_content');
        $faq_third_btn_content = $this->request->getVar('faq_third_btn_content');
        $savedArr = array();
        $saveData = ['page_id' => $pageId, 'lang_id' => $lang_id];
        if ($faq_banner_img && $faq_banner_img->isValid() && !$faq_banner_img->hasMoved()) {
            // Generate a unique filename for the image
            $uniqueName = uniqid() . rand() . date('y_m_d_his') . '_' . $faq_banner_img->getClientName();
            // Move the uploaded file to the specified path
            if ($faq_banner_img->move(WRITEPATH . $frontendDir, $uniqueName)) {
                // Check if an existing record for this image already exists
                $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'faq_banner_img')->first();

                if ($ExistInfo) {
                    // If a file already exists, delete it
                    $existingFilePath = WRITEPATH . $frontendDir . $ExistInfo['meta_value'];
                    if (file_exists($existingFilePath)) {
                        unlink($existingFilePath);
                    }
                    $saveData['id'] = $ExistInfo['id']; // Update the existing record
                }

                // Save the new image path in the database
                $saveData['meta_key'] = 'faq_banner_img';
                $saveData['meta_value'] = $uniqueName;
                $pageMetaObject->savePageMeta($saveData);

                $savedArr[] = $saveData;
            }
        }

        if ($faq_banner_title) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'faq_banner_title')->first();
            $saveData['meta_key'] = 'faq_banner_title';
            $saveData['meta_value'] = $faq_banner_title;

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($faq_collapse_titile) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'faq_collapse_titile')->first();
            $saveData['meta_key'] = 'faq_collapse_titile';
            $saveData['meta_value'] = $faq_collapse_titile;

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($faq_first_btn_txt) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'faq_first_btn_txt')->first();
            $saveData['meta_key'] = 'faq_first_btn_txt';
            $saveData['meta_value'] = $faq_first_btn_txt;

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($faq_sec_btn_txt) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'faq_sec_btn_txt')->first();
            $saveData['meta_key'] = 'faq_sec_btn_txt';
            $saveData['meta_value'] = $faq_sec_btn_txt;

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($faq_third_btn_txt) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'faq_third_btn_txt')->first();
            $saveData['meta_key'] = 'faq_third_btn_txt';
            $saveData['meta_value'] = $faq_third_btn_txt;

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($faq_first_btn_content) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'faq_first_btn_content')->first();
            $mineArr = array();
            if (is_array($faq_first_btn_content)) {
                $counter = count($faq_first_btn_content['title']);
                for ($i = 0; $i < $counter; $i++) {
                    if (isset($faq_first_btn_content['title'][$i]) && isset($faq_first_btn_content['desc'][$i])) {
                        $mineArr[] = array('title' => $faq_first_btn_content['title'][$i], 'desc' => $faq_first_btn_content['desc'][$i]);
                    }
                }
            }
            $saveData['meta_key'] = 'faq_first_btn_content';
            $saveData['meta_value'] = serialize($mineArr);

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($faq_sec_btn_content) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'faq_sec_btn_content')->first();
            $mineArr = array();
            if (is_array($faq_sec_btn_content)) {
                $counter = count($faq_sec_btn_content['title']);
                for ($i = 0; $i < $counter; $i++) {
                    if (isset($faq_sec_btn_content['title'][$i]) && isset($faq_first_btn_content['desc'][$i])) {
                        $mineArr[] = array('title' => $faq_sec_btn_content['title'][$i], 'desc' => $faq_sec_btn_content['desc'][$i]);
                    }
                }
            }
            $saveData['meta_key'] = 'faq_sec_btn_content';
            $saveData['meta_value'] = serialize($mineArr);

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($faq_third_btn_content) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'faq_third_btn_content')->first();
            $mineArr = array();
            if (is_array($faq_third_btn_content)) {
                $counter = count($faq_third_btn_content['title']);
                for ($i = 0; $i < $counter; $i++) {
                    if (isset($faq_third_btn_content['title'][$i]) && isset($faq_third_btn_content['desc'][$i])) {
                        $mineArr[] = array('title' => $faq_third_btn_content['title'][$i], 'desc' => $faq_third_btn_content['desc'][$i]);
                    }
                }
            }
            $saveData['meta_key'] = 'faq_third_btn_content';
            $saveData['meta_value'] = serialize($mineArr);

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        // echo $this->db->getLastQuery(); die;
        $returnArr = [];
        if (!empty($savedArr)) {
            foreach ($savedArr as $key => $valueArr) {
                if ($valueArr['meta_key'] == 'faq_first_btn_content') {
                    $valueArr['meta_value'] = unserialize($valueArr['meta_value']);
                }
                if ($valueArr['meta_key'] == 'faq_sec_btn_content') {
                    $valueArr['meta_value'] = unserialize($valueArr['meta_value']);
                }
                if ($valueArr['meta_key'] == 'faq_third_btn_content') {
                    $valueArr['meta_value'] = unserialize($valueArr['meta_value']);
                }
                $returnArr[$valueArr['meta_key']] = $valueArr['meta_value'];
            }
        }
        // Prepare response
        return $this->respondCreated([
            "status" => true,
            "message" => lang('App.pricingpage_updatedSuccess'),
            "data" => $returnArr
        ]);
    }
}
