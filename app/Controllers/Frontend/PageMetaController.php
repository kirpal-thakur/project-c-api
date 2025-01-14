<?php

namespace App\Controllers\Frontend;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use App\Models\PageMetaModel;
use App\Models\PageModel;
use App\Models\AdvertisementModel;
use App\Models\ContactModel;
use App\Models\BlogModel;


class PageMetaController extends ResourceController
{
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);
        $this->db = \Config\Database::connect();
        $this->page_meta = new PageMetaModel();
        $this->pageModel = new PageModel();
        $this->frontendDir = 'uploads/frontend/';
        // Ensure the directory exists and is writable
        if (!is_dir(WRITEPATH . $this->frontendDir)) {
            mkdir(WRITEPATH . $this->frontendDir, 0777, true);
        }
    }
    public function homePageMetaData()
    {
        $frontendDir = $this->frontendDir;
        $pageMetaObject = new PageMetaModel();

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale($currentLang);
        $savedArr = array();
        // echo '<pre>'; print_r($this->request->getVar()); die;
        $pageId = $this->request->getVar("page_id");
        $lang_id = $this->request->getVar("lang_id");
        if (isset($pageId) && !empty($pageId)) {
            //  updation
        } else {
            $save_data = [
                'user_id'       => auth()->id(),
                'title'         => $this->request->getVar("title"),
                'content'       => $this->request->getVar("content"),
                'language'      => $this->request->getVar("language"),
                'status'        => $this->request->getVar("status"),
                'slug'        => $this->request->getVar("slug"),
                'page_type'        => $this->request->getVar("page_type"),
            ];
            $errors = [];
            foreach ($save_data as $field => $value) {
                if (empty($this->request->getVar($field))) {
                    $errors[] = lang('App.page_required', ['field' => $field . '*']);
                }
            }
            $ad_types = $this->request->getVar("ad_types");
            if (isset($ad_types) && !empty($ad_types)) {
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
            $pageId = $this->pageModel->save($save_data);
            if ($pageId) {
                $pageId = $this->pageModel->db->insertID();
                $savedArr[] = $save_data;
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
            }
        }

        // Define validation rules (fields are optional, not required)
        $validationRules = [
            // 'banner_img' => [
            //     'label' => 'Banner Image',
            //     'rules' => [
            //         'permit_empty', // Make this optional
            //         'is_image[banner_img]',
            //         'mime_in[banner_img,image/jpg,image/jpeg,image/gif,image/png,image/webp]',
            //         'max_size[banner_img,1000]',
            //     ],
            //     'errors' => [
            //         'is_image'  => lang('App.is_image', ['file' => 'jpg, jpeg, gif, png, webp']),
            //         'mime_in'   => lang('App.mime_in', ['file' => 'jpg, jpeg, gif, png, webp']),
            //         'max_size'  => lang('App.max_size', ['size' => '1000']),
            //     ],
            // ],
            // 'banner_bg_img' => [
            //     'label' => 'Banner Background Image',
            //     'rules' => [
            //         'permit_empty', // Make this optional
            //         'is_image[banner_bg_img]',
            //         'mime_in[banner_bg_img,image/jpg,image/jpeg,image/gif,image/png,image/webp]',
            //         'max_size[banner_bg_img,1000]',
            //     ],
            //     'errors' => [
            //         'is_image'  => lang('App.is_image', ['file' => 'jpg, jpeg, gif, png, webp']),
            //         'mime_in'   => lang('App.mime_in', ['file' => 'jpg, jpeg, gif, png, webp']),
            //         'max_size'  => lang('App.max_size', ['size' => '1000']),
            //     ],
            // ],
            'banner_btn_txt' => [
                'label' => 'Button Text',
                'rules' => 'permit_empty|max_length[100]', // Optional text
                'errors' => [
                    'max_length' => lang('App.max_length', ['length' => 100]),
                ],
            ],
            'banner_btn_link' => [
                'label' => 'Button Link',
                'rules' => 'permit_empty|max_length[100]', // Optional text
                'errors' => [
                    'max_length' => lang('App.max_length', ['length' => 100]),
                ],
            ],
        ];
        // Validate input
        if (!$this->validateData($this->request->getPost(), $validationRules)) {
            $errors = $this->validator->getErrors();
            return $this->respond([
                "status" => false,
                "message" => lang('App.homepage_updatedfailure'),
                "data" => ['errors' => $errors]
            ]);
        }
        // Check if any file or text data is being uploaded
        // echo $pageId; die(' $pageId');
        $banner_img = $this->request->getFile('banner_img');
        $banner_img_txt = $this->request->getVar('banner_img');
        $banner_bg_img = $this->request->getFile('banner_bg_img');
        $banner_bg_img_txt = $this->request->getVar('banner_bg_img');
        // $banner_img = $banner_bg_img = '';
        $hero_bg_img = $this->request->getFile('hero_bg_img');
        $hero_bg_img_txt = $this->request->getVar('hero_bg_img');
        $banner_btn_txt = $this->request->getVar('banner_btn_txt');
        $banner_btn_link = $this->request->getVar('banner_btn_link');
        $slider_heading = $this->request->getVar('slider_heading');
        $slider_btn_txt = $this->request->getVar('slider_btn_txt');
        $slider_btn_link = $this->request->getVar('slider_btn_link');
        $hero_heading_txt = $this->request->getVar('hero_heading_txt');
        $hero_btn_txt = $this->request->getVar('hero_btn_txt');
        $hero_btn_link = $this->request->getVar('hero_btn_link');
        $saveData = ['page_id' => $pageId, 'lang_id' => $lang_id];
        if (empty($lang_id) && !empty($pageId)) {
            $pageArr1 = $this->pageModel->where('id', $pageId)->first();
            if (isset($pageArr1) && !empty($pageArr1['language'])) {
                $lang_id = $pageArr1['language'];
            }
            // echo '<pre>'; print_r($pageArr1); die;
        }
        // Process banner_img
        if ($banner_img && $banner_img->isValid() && !$banner_img->hasMoved()) {
            $uniqueName = uniqid() . rand() . date('y_m_d_his') . '_' . $banner_img->getClientName();
            // Remove spaces from the generated file name
            $uniqueName = str_replace(' ', '_', $uniqueName);
            // Optionally, remove special characters (if necessary)
            $uniqueName = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $uniqueName);
            if ($banner_img->move(WRITEPATH . $frontendDir, $uniqueName)) {
                // Check if an existing record for this image already exists
                $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'banner_img')->first();
                if ($ExistInfo) {
                    // If a file already exists, delete it
                    $existingFilePath = WRITEPATH . $frontendDir . $ExistInfo['meta_value'];
                    if (file_exists($existingFilePath) && !empty($ExistInfo['meta_value'])) {
                        unlink($existingFilePath);
                    }
                    $saveData['id'] = $ExistInfo['id']; // Update the existing record
                }
                // Save the new image path in the database
                $saveData['meta_key'] = 'banner_img';
                $saveData['meta_value'] = $uniqueName;
                $pageMetaObject->savePageMeta($saveData);
                $savedArr[] = $saveData;
            }
        }
        // Process banner_bg_img
        if ($banner_bg_img && $banner_bg_img->isValid() && !$banner_bg_img->hasMoved()) {
            // $filepathBg = WRITEPATH . $frontendDir . $banner_bg_img->store('');
            $uniqueName = uniqid() . rand() . date('y_m_d_his') . '_' . $banner_bg_img->getClientName();
            // Remove spaces from the generated file name
            $uniqueName = str_replace(' ', '_', $uniqueName);
            // Optionally, remove special characters (if necessary)
            $uniqueName = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $uniqueName);
            if ($banner_bg_img->move(WRITEPATH . $frontendDir, $uniqueName)) {
                // Check if an existing record for this image already exists
                $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'banner_bg_img')->first();

                if ($ExistInfo) {
                    // If a file already exists, delete it
                    $existingFilePath = WRITEPATH . $frontendDir . $ExistInfo['meta_value'];
                    if (file_exists($existingFilePath) && !empty($ExistInfo['meta_value'])) {
                        unlink($existingFilePath);
                    }
                    $saveData['id'] = $ExistInfo['id']; // Update the existing record
                }

                // Save the new image path in the database
                $saveData['meta_key'] = 'banner_bg_img';
                $saveData['meta_value'] = $uniqueName;
                $pageMetaObject->savePageMeta($saveData);

                $savedArr[] = $saveData;
            }
        }
        if (!empty($banner_img_txt) && $banner_img_txt == 'remove_image') {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'banner_img')->first();
            $updateData = ['meta_value' => ''];  // Replace '' with the value you want to set
            // echo '<pre>'; print_r($ExistInfo); die;
            if ($ExistInfo) {
                // If a file already exists, delete it
                $existingFilePath = WRITEPATH . $frontendDir . $ExistInfo['meta_value'];
                if (file_exists($existingFilePath) && !empty($ExistInfo['meta_value'])) {
                    unlink($existingFilePath);
                }
                $saveData['id'] = $ExistInfo['id']; // Update the existing record
            }
            $pageMetaObject->set($updateData)->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'banner_img')->update();
        }
        if (!empty($banner_bg_img_txt) && $banner_bg_img_txt == 'remove_image') {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'banner_bg_img')->first();
            $updateData = ['meta_value' => ''];  // Replace '' with the value you want to set
            if ($ExistInfo) {
                // If a file already exists, delete it
                $existingFilePath = WRITEPATH . $frontendDir . $ExistInfo['meta_value'];
                if (file_exists($existingFilePath) && !empty($ExistInfo['meta_value'])) {
                    unlink($existingFilePath);
                }
                $saveData['id'] = $ExistInfo['id']; // Update the existing record
            }
            $pageMetaObject->set($updateData)->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'banner_bg_img')->update();
        }
        // Process banner_btn_txt
        if ($banner_btn_txt) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'banner_btn_txt')->first();
            $saveData['meta_key'] = 'banner_btn_txt';
            $saveData['meta_value'] = $banner_btn_txt;

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }

        if ($banner_btn_link) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'banner_btn_link')->first();
            $saveData['meta_key'] = 'banner_btn_link';
            $saveData['meta_value'] = $banner_btn_link;

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }

        if ($slider_heading) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'slider_heading')->first();
            $saveData['meta_key'] = 'slider_heading';
            $saveData['meta_value'] = $slider_heading;

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }

        if ($slider_btn_txt) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'slider_btn_txt')->first();
            $saveData['meta_key'] = 'slider_btn_txt';
            $saveData['meta_value'] = $slider_btn_txt;

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }

        if ($slider_btn_link) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'slider_btn_link')->first();
            $saveData['meta_key'] = 'slider_btn_link';
            $saveData['meta_value'] = $slider_btn_link;

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($hero_bg_img && $hero_bg_img->isValid() && !$hero_bg_img->hasMoved()) {
            // $filepathBg = WRITEPATH . $frontendDir . $banner_bg_img->store('');
            $uniqueName = uniqid() . rand() . date('y_m_d_his') . '_' . $hero_bg_img->getClientName();
            // Remove spaces from the generated file name
            $uniqueName = str_replace(' ', '_', $uniqueName);
            // Optionally, remove special characters (if necessary)
            $uniqueName = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $uniqueName);
            if ($hero_bg_img->move(WRITEPATH . $frontendDir, $uniqueName)) {
                // Check if an existing record for this image already exists
                $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'hero_bg_img')->first();

                if ($ExistInfo) {
                    // If a file already exists, delete it
                    $existingFilePath = WRITEPATH . $frontendDir . $ExistInfo['meta_value'];
                    if (file_exists($existingFilePath) && !empty($ExistInfo['meta_value'])) {
                        unlink($existingFilePath);
                    }
                    $saveData['id'] = $ExistInfo['id']; // Update the existing record
                }

                // Save the new image path in the database
                $saveData['meta_key'] = 'hero_bg_img';
                $saveData['meta_value'] = $uniqueName;
                $pageMetaObject->savePageMeta($saveData);

                $savedArr[] = $saveData;
            }
        }
        // hero_bg_img_txt
        if (!empty($hero_bg_img_txt) && $hero_bg_img_txt == 'remove_image') {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'hero_bg_img')->first();
            $updateData = ['meta_value' => ''];  // Replace '' with the value you want to set
            if ($ExistInfo) {
                // If a file already exists, delete it
                $existingFilePath = WRITEPATH . $frontendDir . $ExistInfo['meta_value'];
                if (file_exists($existingFilePath) && !empty($ExistInfo['meta_value'])) {
                    unlink($existingFilePath);
                }
                $saveData['id'] = $ExistInfo['id']; // Update the existing record
            }
            $pageMetaObject->set($updateData)->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'hero_bg_img')->update();
        }
        if ($hero_heading_txt) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'hero_heading_txt')->first();
            $saveData['meta_key'] = 'hero_heading_txt';
            $saveData['meta_value'] = $hero_heading_txt;

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }

        if ($hero_btn_txt) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'hero_btn_txt')->first();
            $saveData['meta_key'] = 'hero_btn_txt';
            $saveData['meta_value'] = $hero_btn_txt;

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }

        if ($hero_btn_link) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'hero_btn_link')->first();
            $saveData['meta_key'] = 'hero_btn_link';
            $saveData['meta_value'] = $hero_btn_link;

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        // /* ###### Dark Mode Images ###### */
        $banner_img_dark_mode = $this->request->getFile('banner_img_dark_mode');
        $banner_img_dark_mode_txt = $this->request->getVar('banner_img_dark_mode');
        $banner_bg_img_dark_mode = $this->request->getFile('banner_bg_img_dark_mode');
        $banner_bg_dark_mode_img_txt = $this->request->getVar('banner_bg_img_dark_mode');
        $hero_bg_img_dark_mode = $this->request->getFile('hero_bg_img_dark_mode');
        $hero_bg_img_dark_mode_txt = $this->request->getVar('hero_bg_img_dark_mode');
        if ($banner_img_dark_mode && $banner_img_dark_mode->isValid() && !$banner_img_dark_mode->hasMoved()) {
            $uniqueName = uniqid() . rand() . date('y_m_d_his') . '_' . $banner_img_dark_mode->getClientName();
            $uniqueName = str_replace(' ', '_', $uniqueName);
            $uniqueName = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $uniqueName);
            if ($banner_img_dark_mode->move(WRITEPATH . $frontendDir, $uniqueName)) {
                $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'banner_img_dark_mode')->first();

                if ($ExistInfo) {
                    $existingFilePath = WRITEPATH . $frontendDir . $ExistInfo['meta_value'];
                    if (file_exists($existingFilePath) && !empty($ExistInfo['meta_value'])) {
                        unlink($existingFilePath);
                    }
                    $saveData['id'] = $ExistInfo['id']; // Update the existing record
                }
                // Save the new image path in the database
                $saveData['meta_key'] = 'banner_img_dark_mode';
                $saveData['meta_value'] = $uniqueName;
                $pageMetaObject->savePageMeta($saveData);

                $savedArr[] = $saveData;
            }
        }
        if (!empty($banner_img_dark_mode_txt) && $banner_img_dark_mode_txt == 'remove_image') {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'banner_img_dark_mode')->first();
            $updateData = ['meta_value' => ''];  // Replace '' with the value you want to set
            if ($ExistInfo) {
                // If a file already exists, delete it
                $existingFilePath = WRITEPATH . $frontendDir . $ExistInfo['meta_value'];
                if (file_exists($existingFilePath) && !empty($ExistInfo['meta_value'])) {
                    unlink($existingFilePath);
                }
                $saveData['id'] = $ExistInfo['id']; // Update the existing record
            }
            $pageMetaObject->set($updateData)->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'banner_img_dark_mode')->update();
        }

        if ($banner_bg_img_dark_mode && $banner_bg_img_dark_mode->isValid() && !$banner_bg_img_dark_mode->hasMoved()) {
            $uniqueName = uniqid() . rand() . date('y_m_d_his') . '_' . $banner_bg_img_dark_mode->getClientName();
            $uniqueName = str_replace(' ', '_', $uniqueName);
            $uniqueName = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $uniqueName);
            if ($banner_bg_img_dark_mode->move(WRITEPATH . $frontendDir, $uniqueName)) {
                $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'banner_bg_img_dark_mode')->first();

                if ($ExistInfo) {
                    $existingFilePath = WRITEPATH . $frontendDir . $ExistInfo['meta_value'];
                    if (file_exists($existingFilePath) && !empty($ExistInfo['meta_value'])) {
                        unlink($existingFilePath);
                    }
                    $saveData['id'] = $ExistInfo['id']; // Update the existing record
                }
                $saveData['meta_key'] = 'banner_bg_img_dark_mode';
                $saveData['meta_value'] = $uniqueName;
                $pageMetaObject->savePageMeta($saveData);

                $savedArr[] = $saveData;
            }
        }
        if (!empty($banner_bg_dark_mode_img_txt) && $banner_bg_dark_mode_img_txt == 'remove_image') {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'banner_bg_img_dark_mode')->first();
            $updateData = ['meta_value' => ''];  // Replace '' with the value you want to set
            if ($ExistInfo) {
                $existingFilePath = WRITEPATH . $frontendDir . $ExistInfo['meta_value'];
                if (file_exists($existingFilePath) && !empty($ExistInfo['meta_value'])) {
                    unlink($existingFilePath);
                }
                $saveData['id'] = $ExistInfo['id']; // Update the existing record
            }
            $pageMetaObject->set($updateData)->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'banner_bg_img_dark_mode')->update();
        }
        if ($hero_bg_img_dark_mode && $hero_bg_img_dark_mode->isValid() && !$hero_bg_img_dark_mode->hasMoved()) {
            $uniqueName = uniqid() . rand() . date('y_m_d_his') . '_' . $hero_bg_img_dark_mode->getClientName();
            $uniqueName = str_replace(' ', '_', $uniqueName);
            $uniqueName = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $uniqueName);
            if ($hero_bg_img_dark_mode->move(WRITEPATH . $frontendDir, $uniqueName)) {
                $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'hero_bg_img_dark_mode')->first();

                if ($ExistInfo) {
                    $existingFilePath = WRITEPATH . $frontendDir . $ExistInfo['meta_value'];
                    if (file_exists($existingFilePath) && !empty($ExistInfo['meta_value'])) {
                        unlink($existingFilePath);
                    }
                    $saveData['id'] = $ExistInfo['id']; // Update the existing record
                }
                $saveData['meta_key'] = 'hero_bg_img_dark_mode';
                $saveData['meta_value'] = $uniqueName;
                $pageMetaObject->savePageMeta($saveData);

                $savedArr[] = $saveData;
            }
        }
        if (!empty($hero_bg_img_dark_mode_txt) && $hero_bg_img_dark_mode_txt == 'remove_image') {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'hero_bg_img_dark_mode')->first();
            $updateData = ['meta_value' => ''];  // Replace '' with the value you want to set
            if ($ExistInfo) {
                $existingFilePath = WRITEPATH . $frontendDir . $ExistInfo['meta_value'];
                if (file_exists($existingFilePath) && !empty($ExistInfo['meta_value'])) {
                    unlink($existingFilePath);
                }
                $saveData['id'] = $ExistInfo['id']; // Update the existing record
            }
            $pageMetaObject->set($updateData)->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'hero_bg_img_dark_mode')->update();
        }
        $returnArr = [];
        if (!empty($savedArr)) {
            foreach ($savedArr as $key => $valueArr) {
                if (isset($valueArr['meta_key']) && !empty($valueArr['meta_key'])) {
                    $returnArr[$valueArr['meta_key']] = $valueArr['meta_value'];
                } else {
                    if (isset($valueArr) && is_array($valueArr)) {
                        foreach ($valueArr as $field_key => $field_value) {
                            $returnArr[$field_key] = $field_value;
                        }
                    }
                }
            }
        }
        $returnArr['base_url'] = base_url() . $frontendDir;
        // Prepare response
        return $this->respondCreated([
            "status" => true,
            "message" => lang('App.homepage_updatedSuccess'),
            "data" => $returnArr
        ]);
    }
    public function getPageMetaData()
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
        // echo '<pre>'; print_r($allPageMeta); die;
        if (isset($allPageMeta) && !empty($allPageMeta)) {
            // Initialize an empty array to hold the structured data
            $metaData = [];
            $serilized_fields = array('talent_section', 'feature_sctn', 'pricing_tab', 'news_section');
            $explode_fields = array('banner_imgs');
            // Loop through each meta entry and map meta_key to meta_value
            foreach ($allPageMeta as $meta) {
                $tabsArr = array();
                if ($meta['meta_key'] == 'tabs_data') {
                    $tabData = json_decode($meta['meta_value'], true);
                    if (isset($tabData['first_tab']) && !empty($tabData['first_tab'])) {
                        $firstTabItems = $tabData['first_tab'];
                        foreach ($firstTabItems as $item) {
                            if (!empty($item['images'])) {
                                $item['images'] = explode(',', $item['images']);
                            } else {
                                $item['images'] = ''; // 1-7-25 
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
                            if (!empty($item['images'])) {
                                $item['images'] = explode(',', $item['images']);
                            } else {
                                $item['images'] = ''; // 1-7-25 
                            }
                            $tabsArr["second_tab"][] = [
                                "title" => $item['title'],
                                "desc" => $item['desc'],
                                "images" => $item['images'] // Split images into an array
                            ];
                        }
                    }
                    if (isset($tabData['title']) && !empty($tabData['title'])) {
                        $tabsArr['title'] = $tabData['title'];
                    }
                    if (isset($tabData['first_btn_txt']) && !empty($tabData['first_btn_txt'])) {
                        $tabsArr['first_btn_txt'] = $tabData['title'];
                    }
                    if (isset($tabData['sec_btn_txt']) && !empty($tabData['sec_btn_txt'])) {
                        $tabsArr['sec_btn_txt'] = $tabData['title'];
                    }
                    $meta['meta_value'] = $tabsArr;
                } else if (isset($meta) && !empty($meta['meta_key']) && !empty($meta['meta_value']) && in_array($meta['meta_key'], $serilized_fields)) {
                    $meta['meta_value'] = unserialize($meta['meta_value']);
                } else if (isset($meta) && !empty($meta['meta_key']) && !empty($meta['meta_value']) && in_array($meta['meta_key'], $explode_fields)) {
                    $meta['meta_value'] = explode(',', $meta['meta_value']);
                }
                $metaData['pageData'][$meta['meta_key']] = $meta['meta_value'];
            }
            $frontEndUsers = $this->getUserForFronend();
            if (isset($frontEndUsers) && !empty($frontEndUsers)) {
                $metaData['sliderData'] = $frontEndUsers;
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
                $metaData['advertisemnet_base_url'] = base_url() . 'uploads/';
            }
            $metaData['base_url'] = base_url() . $frontendDir;
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
    public function getUserForFronend($type = '')
    {
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale($currentLang);

        $url =  isset($_SERVER['HTTPS']) &&
            $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
        $url .= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $parse_url = parse_url($url);
        $uri = new \CodeIgniter\HTTP\URI($url);

        $queryString =  $uri->getQuery();
        parse_str($queryString, $searchParams);

        $whereClause = [];
        $metaQuery = [];
        $orderBy =  $order = $search = '';
        $orderBy = 'id';
        $order = 'DESC';
        // $noLimit =  $countOnly = FALSE;
        $noLimit = FALSE;
        // $limit = $searchParams['limit'] ?? PER_PAGE;
        $limit = 15;
        if ($type == 'home_page') {
            $orderBy = 'created_at';
            $limit = 10;
            $noLimit = false;
        }
        $offset = $searchParams['offset'] ?? 0;
        $users = getPlayersOnFrontend($whereClause, $metaQuery, $search, $orderBy, $order, $limit, $offset, $noLimit);
        if (isset($users) && !empty($users)) {
            return $users;
            // $featured_images = array_map(function($item) {
            //     return $item['first_name'];
            // }, $users);
        } else {
            return [];
        }
    }
    public function homePageTabsMetaDataBckp08012025()
    {
        $frontendDir = $this->frontendDir;
        $pageMetaObject = new PageMetaModel();

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale($currentLang);

        $pageId = $this->request->getVar("page_id");
        $lang_id = $this->request->getVar("lang_id");
        if (isset($pageId) && !empty($pageId)) {
            //  updation
        } else {
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
            $pageId = $this->pageModel->save($save_data);
            if ($pageId) {
                $pageId = $this->pageModel->db->insertID();
                $savedArr[] = $save_data;
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
            }
        }
        // Initialize data structure to store tab data
        $tabsData = [];
        $title = $this->request->getVar('title');
        if ($title && !empty($title)) {
            $tabsData['title'] = $title;
        }
        $first_btn_txt = $this->request->getVar('first_btn_txt');
        if ($first_btn_txt && !empty($first_btn_txt)) {
            $tabsData['first_btn_txt'] = $first_btn_txt;
        }
        $sec_btn_txt = $this->request->getVar('sec_btn_txt');
        if ($sec_btn_txt && !empty($sec_btn_txt)) {
            $tabsData['sec_btn_txt'] = $sec_btn_txt;
        }
        $Test['textData'] = $this->request->getVar();
        $Test['imagesData'] = $this->request->getFiles();

        // Function to handle tab processing
        $logger = \Config\Services::logger();
        foreach (['first_tab', 'second_tab'] as $tabName) {
            $tabData = $this->request->getVar($tabName);
            // $commonArr['tab_txt_data'][$tabName] = $tabData;
            // Get uploaded files for the current tab
            if ($tabData) {
                foreach ($tabData as $key => $data) {
                    $tabItem = [
                        'title' => $data['title'] ?? null,
                        'desc' => $data['desc'] ?? null,
                        //'images' => [] // Initialize images array
                    ];
                    $imageNames = []; // Array to store image names
                    // echo '<pre>'; print_r($data); die;
                    $uploadedFiles = $this->request->getFiles($tabName[$key]);
                    // $commonArr['tab_img_data'][$tabName] = $uploadedFiles;
                    if (isset($uploadedFiles[$tabName][$key]['images'])) {
                        $files = $uploadedFiles[$tabName][$key]['images'];
                        if (is_array($files)) {
                            foreach ($files as $fileData) {
                                if ($fileData instanceof \CodeIgniter\HTTP\Files\UploadedFile) {
                                    if ($fileData->isValid() && $fileData->getError() === 0) {
                                        $uniqueName = uniqid() . rand() . date('y_m_d_his') . '_' . $fileData->getClientName();
                                        $uniqueName = str_replace(' ', '_', $uniqueName);

                                        // Optionally, remove special characters (if necessary)
                                        $uniqueName = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $uniqueName);
                                        // Move the file to the desired directory and save only the unique name
                                        if ($fileData->move(WRITEPATH . $frontendDir, $uniqueName)) {
                                            $imageNames[] = $uniqueName; // Store the image name
                                        }
                                    }
                                }
                            }
                            // Join image names with commas if there are multiple
                            $tabItem['images'] = implode(',', $imageNames);
                        } elseif ($files instanceof \CodeIgniter\HTTP\Files\UploadedFile) { // Handle single file upload case
                            if ($files->isValid() && $files->getError() === 0) {
                                $uniqueName = uniqid() . rand() . date('y_m_d_his') . '_' . $files->getClientName();
                                // Remove spaces from the generated file name
                                $uniqueName = str_replace(' ', '_', $uniqueName);
                                // Optionally, remove special characters (if necessary)
                                $uniqueName = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $uniqueName);
                                // Move the file to the desired directory and save only the unique name
                                if ($files->move(WRITEPATH . $frontendDir, $uniqueName)) {
                                    $tabItem['images'] = $uniqueName; // Store the single image name
                                }
                            }
                        }
                    }

                    if (!isset($tabItem[$tabName][$key]['images']) && empty($tabItem[$tabName][$key]['images']) && empty($tabItem['images'])) {
                        $tabs_data = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'tabs_data')->first();
                        if (isset($tabs_data['meta_value']) && !empty($tabs_data['meta_value'])) {
                            $tab_images = json_decode($tabs_data['meta_value'], true);
                            if (isset($tab_images[$tabName][$key]['images'])) {
                                $tabItem['images'] = $tab_images[$tabName][$key]['images'];
                            }
                        }
                    }
                    $tabsData[$tabName][] = $tabItem; // Add the tab item to the corresponding tab
                }
            }
            // echo '<pre>'; print_r($tabsData); die(' ~vtabsData');
        }
        // if(isset($data['images']) && !empty($data['images'])){
        //     $tab_images = $data['images'];
        //     echo '<pre>'; print_r($tab_images); die;
        //     foreach($tab_images as $key => $image){
        //         if($image == 'remove_image'){
        //             $imageNames[] = '';
        //         }
        //     }
        // }
        //  echo '<pre>'; print_r($tabsData); die;
        // Prepare data for saving
        $commonArr1['tabsData'] = $tabsData;
        $commonArr1['requestedData'] = $this->request->getVar();
        foreach ($commonArr1['requestedData'] as $tabKey => $tabData) {
            if ($tabKey !== 'frontend/save-tabs-homepage') {

                if (is_array($tabData)) {
                    foreach ($tabData as $index => $item) {
                        if (isset($item['images']) && in_array('remove_image', $item['images'])) {
                            // Set the corresponding images field in tabsData to an empty string
                            $commonArr1['tabsData'][$tabKey][$index]['images'] = '';
                        }
                    }
                }
            }
        }
        if (isset($commonArr1['tabsData']) && !empty($commonArr1['tabsData'])) {
            $tabsData = $commonArr1['tabsData'];
        }
        $logger->info('savePageMeta Recived Data ' . date('D-M-Y H:i:s') . ' data from frontend ' . json_encode($Test));
        // echo '<pre>'; print_r($tabsData); die(' ~vtabsData');
        $saveData = [
            'page_id' => $pageId,
            'lang_id' => $lang_id,
            'meta_key' => 'tabs_data',
            'meta_value' => json_encode($tabsData)  // Save all data as a single JSON string
        ];
        // Save to the database
        $pageMetaObject->savePageMeta($saveData);
        $tabsData['base_url'] = base_url() . $frontendDir;
        return $this->respondCreated([
            "status" => true,
            "message" => lang('App.tabSectionSaved'),
            "data" => $tabsData
        ]);
    }
    public function homePageTabsMetaData()
    {
        $frontendDir = $this->frontendDir;
        $pageMetaObject = new PageMetaModel();

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale($currentLang);

        $pageId = $this->request->getVar("page_id");
        $lang_id = $this->request->getVar("lang_id");
        if (isset($pageId) && !empty($pageId)) {
            //  updation
        } else {
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
            $pageId = $this->pageModel->save($save_data);
            if ($pageId) {
                $pageId = $this->pageModel->db->insertID();
                $savedArr[] = $save_data;
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
            }
        }

        $tabsData = [];
        $title = $this->request->getVar('title');
        if ($title && !empty($title)) {
            $tabsData['title'] = $title;
        }
        $first_btn_txt = $this->request->getVar('first_btn_txt');
        if ($first_btn_txt && !empty($first_btn_txt)) {
            $tabsData['first_btn_txt'] = $first_btn_txt;
        }
        $sec_btn_txt = $this->request->getVar('sec_btn_txt');
        if ($sec_btn_txt && !empty($sec_btn_txt)) {
            $tabsData['sec_btn_txt'] = $sec_btn_txt;
        }
        $first_tab = $this->request->getVar('first_tab');
        $first_tab_files = $this->request->getFiles('first_tab');
        // echo '<pre>'; print_r($first_tab_files['first_tab']); die;
        $sec_tab = $this->request->getVar('second_tab');
        $firstTab = array();
        $secTab = array();
        if (isset($first_tab) && !empty($first_tab)) {
            $imgName = '';
            $title = '';
            $desc = '';
            $darkImg = '';
            foreach ($first_tab as $key => $value) {
                if (is_array($value)) {
                    if (isset($value['title'])) {
                        $title = $value['title'];
                    }
                    if (isset($value['desc'])) {
                        $desc = $value['desc'];
                    }
                    // echo '<pre>'; print_r($value); die('dsf');
                    if (isset($value['images']['0']) && $value['images']['0'] == 'remove_image') {
                        $info = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'tabs_data')->first();
                        if (isset($info['meta_value']) && !empty($info['meta_value'])) {
                            $jsonOldTab = json_decode($info['meta_value'], true);
                            if (isset($jsonOldTab['first_tab'][$key]['images']) && !empty($jsonOldTab['first_tab'][$key]['images'])) {
                                $imgName = $jsonOldTab['first_tab'][$key]['images'];
                                if(isset($imgName) && !empty($imgName)){
                                    $existingFilePath = WRITEPATH . $frontendDir . $imgName;
                                    if (file_exists($existingFilePath) && !empty($imgName)) {
                                        unlink($existingFilePath);
                                        $imgName = '';
                                    }
                                }
                            }
                        }
                        $imgName = '';
                    } else {
                        $fileData = $this->request->getFiles('first_tab[' . $key . '][images]');
                        
                        if (isset($fileData['first_tab'][$key]['images']) && !empty($fileData['first_tab'][$key]['images'])) {
                            $file = $fileData['first_tab'][$key]['images'];
                            if (is_array($file) && isset($file[0]) && !empty($file[0])) {
                                $fileData = $file[0];
                                // echo '<pre>'; print_r($fileData); die;
                                if ($fileData) {
                                    if ($fileData->isValid() && $fileData->getError() === 0) {
                                        $uniqueName = uniqid() . rand() . date('y_m_d_his') . '_' . $fileData->getClientName();
                                        $uniqueName = str_replace(' ', '_', $uniqueName);
                                        // Optionally, remove special characters (if necessary)
                                        $uniqueName = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $uniqueName);
                                        // Move the file to the desired directory and save only the unique name
                                        if ($fileData->move(WRITEPATH . $frontendDir, $uniqueName)) {
                                            $imgName = $uniqueName; // Store the image name
                                        }
                                    }
                                }
                            }
                        } else {
                            $info = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'tabs_data')->first();
                            // echo '<pre>'; print_r($ExistInfo); die;
                            if (isset($info['meta_value']) && !empty($info['meta_value'])) {
                                $jsonOldTab = json_decode($info['meta_value'], true);
                                // echo '<pre>'; print_r($jsonOldTab); die;
                                if (isset($jsonOldTab['first_tab'][$key]['images']) && !empty($jsonOldTab['first_tab'][$key]['images'])) {
                                    $imgName = $jsonOldTab['first_tab'][$key]['images'];
                                }
                            }
                        }
                    }
                    $darkFileData = $this->request->getFiles('first_tab[' . $key . '][images_dark_mode]');
                    if (isset($value['images_dark_mode']['0']) && $value['images_dark_mode']['0'] == 'remove_image') {
                        $darkImg = '';
                        $oldImage = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'tabs_data')->first();
                        if (isset($oldImage['meta_value']) && !empty($oldImage['meta_value'])) {
                            $jsonOldTab = json_decode($oldImage['meta_value'], true);
                            if (isset($jsonOldTab['first_tab'][$key]['images']) && !empty($jsonOldTab['first_tab'][$key]['images'])) {
                                $darkImg = $jsonOldTab['first_tab'][$key]['images'];
                                if(isset($darkImg) && !empty($darkImg)){
                                    $existingFilePath = WRITEPATH . $frontendDir . $darkImg;
                                    if (file_exists($existingFilePath) && !empty($darkImg)) {
                                        unlink($existingFilePath);
                                        $darkImg = '';
                                    }
                                }
                            }
                        }
                        $darkImg = '';
                    } else {
                        if (isset($darkFileData['first_tab'][$key]['images_dark_mode']) && !empty($darkFileData['first_tab'][$key]['images_dark_mode'])) {
                            $dakrFile = $darkFileData['first_tab'][$key]['images_dark_mode'];
                            if (isset($dakrFile[0]) && !empty($dakrFile[0])) {
                                $darkfileData = $dakrFile[0];
                                if ($darkfileData) {
                                    if ($darkfileData->isValid() && $darkfileData->getError() === 0) {
                                        $uniqueName = uniqid() . rand() . date('y_m_d_his') . '_' . $darkfileData->getClientName();
                                        $uniqueName = str_replace(' ', '_', $uniqueName);
                                        $uniqueName = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $uniqueName);
                                        if ($darkfileData->move(WRITEPATH . $frontendDir, $uniqueName)) { 
                                            // $oldImage = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'tabs_data')->first();
                                            // if (isset($oldImage['meta_value']) && !empty($oldImage['meta_value'])) {
                                            //     $jsonOldTab = json_decode($oldImage['meta_value'], true);
                                            //     if (isset($jsonOldTab['first_tab'][$key]['images']) && !empty($jsonOldTab['first_tab'][$key]['images'])) {
                                            //         $darkImg = $jsonOldTab['first_tab'][$key]['images'];
                                            //         if(isset($darkImg) && !empty($darkImg)){
                                            //             $existingFilePath = WRITEPATH . $frontendDir . $darkImg;
                                            //             if (file_exists($existingFilePath) && !empty($darkImg)) {
                                            //                 unlink($existingFilePath);
                                            //                 $darkImg = '';
                                            //             }
                                            //         }
                                            //     }
                                            // }
                                            $darkImg = $uniqueName; // Store the image name
                                        }
                                    }
                                }
                            }
                        } else {
                            $info = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'tabs_data')->first();
                            if (isset($info['meta_value']) && !empty($info['meta_value'])) {
                                $jsonOldTab = json_decode($info['meta_value'], true);
                                if (isset($jsonOldTab['first_tab'][$key]['images_dark_mode']) && !empty($jsonOldTab['first_tab'][$key]['images_dark_mode'])) {
                                    $darkImg = $jsonOldTab['first_tab'][$key]['images_dark_mode'];
                                }
                            }
                        }
                    }
                    // $imgName = '';
                    $firstTab[$key] = ['title' => $title, 'desc' => $desc, 'images' => $imgName, 'images_dark_mode' => $darkImg];
                    // echo '<pre>'; print_r($firstTab); die;
                    $imgName = '';
                    $darkImg = '';
                }
            }
        }
        if (isset($sec_tab) && !empty($sec_tab)) {
            $imgName2 = '';
            $title2 = '';
            $desc2 = '';
            foreach ($sec_tab as $key => $value) {
                if (is_array($value)) {
                    if (isset($value['title'])) {
                        $title2 = $value['title'];
                    }
                    if (isset($value['desc'])) {
                        $desc2 = $value['desc'];
                    }
                    if (isset($value['images']['0']) && $value['images']['0'] == 'remove_image') { 
                        $info2 = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'tabs_data')->first();
                        // echo '<pre>'; print_r($info2); die;
                        if (isset($info2['meta_value']) && !empty($info2['meta_value'])) {
                            $jsonOldTab = json_decode($info2['meta_value'], true);
                            // echo '<pre>'; print_r($jsonOldTab); die;
                            if (isset($jsonOldTab['second_tab'][$key]['images']) && !empty($jsonOldTab['second_tab'][$key]['images'])) {
                                $imgName2 = $jsonOldTab['second_tab'][$key]['images'];
                            }
                        }
                        $imgName2 = '';
                    } else {
                        $fileData2 = $this->request->getFiles('second_tab[' . $key . '][images]');
                        $imgName2 = '';
                        if (isset($fileData2['second_tab'][$key]['images']) && !empty($fileData2['second_tab'][$key]['images'])) {
                            $file = $fileData2['second_tab'][$key]['images'];
                            if (isset($file[0]) && !empty($file[0])) {
                                $fileData2 = $file[0];
                                if ($fileData2) {
                                    if ($fileData2->isValid() && $fileData2->getError() === 0) {
                                        $uniqueName = uniqid() . rand() . date('y_m_d_his') . '_' . $fileData2->getClientName();
                                        $uniqueName = str_replace(' ', '_', $uniqueName);
                                        // Optionally, remove special characters (if necessary)
                                        $uniqueName = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $uniqueName);
                                        // Move the file to the desired directory and save only the unique name
                                        if ($fileData2->move(WRITEPATH . $frontendDir, $uniqueName)) {
                                            $imgName2 = $uniqueName; // Store the image name
                                        }
                                    }
                                }
                            }
                        } else {
                            $info2 = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'tabs_data')->first();
                            if (isset($info2['meta_value']) && !empty($info2['meta_value'])) {
                                $jsonOldTab = json_decode($info2['meta_value'], true);
                                if (isset($jsonOldTab['second_tab'][$key]['images']) && !empty($jsonOldTab['second_tab'][$key]['images'])) {
                                    $imgName2 = $jsonOldTab['second_tab'][$key]['images'];
                                }
                            }
                        }
                        $darkImg2 = '';
                        $darkFileData = $this->request->getFiles('second_tab[' . $key . '][images_dark_mode]');
                        if (isset($darkFileData['second_tab'][$key]['images_dark_mode']) && !empty($darkFileData['second_tab'][$key]['images_dark_mode'])) {
                            $dakrFile = $darkFileData['second_tab'][$key]['images_dark_mode'];
                            if (isset($dakrFile[0]) && !empty($dakrFile[0])) {
                                $darkfileData = $dakrFile[0];
                                if ($darkfileData) {
                                    if ($darkfileData->isValid() && $darkfileData->getError() === 0) { 
                                        // $oldImage = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'tabs_data')->first();
                                        // if (isset($oldImage['meta_value']) && !empty($oldImage['meta_value'])) {
                                        //     $jsonOldTab = json_decode($oldImage['meta_value'], true);
                                        //     if (isset($jsonOldTab['first_tab'][$key]['images']) && !empty($jsonOldTab['first_tab'][$key]['images'])) {
                                        //         $darkImg = $jsonOldTab['first_tab'][$key]['images'];
                                        //         if(isset($darkImg) && !empty($darkImg)){
                                        //             $existingFilePath = WRITEPATH . $frontendDir . $darkImg;
                                        //             if (file_exists($existingFilePath) && !empty($darkImg)) {
                                        //                 unlink($existingFilePath);
                                        //                 $darkImg = '';
                                        //             }
                                        //         }
                                        //     }
                                        // }
                                        $uniqueName = uniqid() . rand() . date('y_m_d_his') . '_' . $darkfileData->getClientName();
                                        $uniqueName = str_replace(' ', '_', $uniqueName);
                                        // Optionally, remove special characters (if necessary)
                                        $uniqueName = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $uniqueName);
                                        // Move the file to the desired directory and save only the unique name
                                        if ($darkfileData->move(WRITEPATH . $frontendDir, $uniqueName)) {
                                            $darkImg2 = $uniqueName; // Store the image name
                                        }
                                    }
                                }
                            }
                        } else {
                            $info = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'tabs_data')->first();
                            // echo '<pre>'; print_r($ExistInfo); die;
                            if (isset($info['meta_value']) && !empty($info['meta_value'])) {
                                $jsonOldTab = json_decode($info['meta_value'], true);
                                // echo '<pre>'; print_r($jsonOldTab); die;
                                if (isset($jsonOldTab['second_tab'][$key]['images_dark_mode']) && !empty($jsonOldTab['second_tab'][$key]['images_dark_mode'])) {
                                    $darkImg2 = $jsonOldTab['second_tab'][$key]['images_dark_mode'];
                                }
                            }
                        }
                    }
                    $secTab[$key] = ['title' => $title2, 'desc' => $desc2, 'images' => $imgName2, 'images_dark_mode' => $darkImg2];
                }
            }
        }
        if (isset($first_tab) && !empty($first_tab)) {
            $tabsData['first_tab'] = $firstTab;
        }
        if (isset($sec_tab) && !empty($sec_tab)) {
            $tabsData['second_tab'] = $secTab;
        }
        // echo '<pre>'; print_r($tabsData); die;
        if (isset($tabsData) && !empty($tabsData)) {
            $saveData = [
                'page_id' => $pageId,
                'lang_id' => $lang_id,
                'meta_key' => 'tabs_data',
                'meta_value' => json_encode($tabsData)  // Save all data as a single JSON string
            ];
            $pageMetaObject->savePageMeta($saveData);
        } else {
            $tabsData['validated'] = 'no input to save tabs data';
        }

        // Save to the database

        $tabsData['base_url'] = base_url() . $frontendDir;
        return $this->respondCreated([
            "status" => true,
            "message" => lang('App.tabSectionSaved'),
            "data" => $tabsData
        ]);
    }
    public function talentPageMetaData()
    {
        $frontendDir = $this->frontendDir;
        $pageMetaObject = new PageMetaModel();

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale($currentLang);
        // echo '<pre>'; print_r($this->request->getVar()); die;
        $pageId = $this->request->getVar("page_id");
        $lang_id = $this->request->getVar("lang_id");
        if (isset($pageId) && !empty($pageId)) {
            //  updation
        } else {
            $save_data = [
                'user_id'       => auth()->id(),
                'title'         => $this->request->getVar("title"),
                'content'       => $this->request->getVar("content"),
                'language'      => $this->request->getVar("language"),
                'status'        => $this->request->getVar("status"),
                'slug'        => $this->request->getVar("slug"),
                'page_type'        => $this->request->getVar("page_type"),
            ];
            $errors = [];
            foreach ($save_data as $field => $value) {
                if (empty($this->request->getVar($field))) {
                    $errors[] = lang('App.page_required', ['field' => $field . '*']);
                }
            }
            $ad_types = $this->request->getVar("ad_types");
            if (isset($ad_types) && !empty($ad_types)) {
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
            $pageId = $this->pageModel->save($save_data);
            if ($pageId) {
                $pageId = $this->pageModel->db->insertID();
                $savedArr[] = $save_data;
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
            }
        }
        // echo '<pre>'; print_r($this->request->getVar()); die;
        $banner_bg_img = $this->request->getFile('banner_bg_img');
        $banner_bg_img_txt = $this->request->getVar('banner_bg_img');
        $talent_banner_bg_img_dark_mode = $this->request->getFile('talent_banner_bg_img_dark_mode');
        $talent_banner_bg_img_dark_txt = $this->request->getVar('talent_banner_bg_img_dark_mode');
        $banner_title = $this->request->getVar('banner_title');
        $banner_desc = $this->request->getVar('banner_desc');
        $banner_btn_txt = $this->request->getVar('banner_btn_txt');
        $banner_imgs = $this->request->getFiles('banner_imgs');
        $talent_section_title = $this->request->getVar('talent_section_title');
        $talent_section = $this->request->getVar('talent_section');
        // $talent_section_icons = $this->request->getFiles('talent_section');
        $feature_sctn_title = $this->request->getVar('feature_sctn_title');
        $feature_sctn = $this->request->getVar('feature_sctn');
        $pricing_sctn_title = $this->request->getVar('pricing_sctn_title');
        $pricing_tab = $this->request->getVar('pricing_tab');
        $savedArr = array();
        $saveData = ['page_id' => $pageId, 'lang_id' => $lang_id];
        if ($banner_bg_img && $banner_bg_img->isValid() && !$banner_bg_img->hasMoved()) {
            $uniqueName = uniqid() . rand() . date('y_m_d_his') . '_' . $banner_bg_img->getClientName();
            // Remove spaces from the generated file name
            $uniqueName = str_replace(' ', '_', $uniqueName);
            // Optionally, remove special characters (if necessary)
            $uniqueName = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $uniqueName);
            if ($banner_bg_img->move(WRITEPATH . $frontendDir, $uniqueName)) {
                // Check if an existing record for this image already exists
                $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'banner_bg_img')->first();
                if ($ExistInfo) {
                    // If a file already exists, delete it
                    $existingFilePath = WRITEPATH . $frontendDir . $ExistInfo['meta_value'];
                    if (file_exists($existingFilePath) && !empty($ExistInfo['meta_value'])) {
                        unlink($existingFilePath);
                    }
                    $saveData['id'] = $ExistInfo['id']; // Update the existing record
                }
                // Save the new image path in the database
                $saveData['meta_key'] = 'banner_bg_img';
                $saveData['meta_value'] = $uniqueName;
                $pageMetaObject->savePageMeta($saveData);

                $savedArr[] = $saveData;
            }
        }
        if (isset($banner_bg_img_txt) && !empty($banner_bg_img_txt) && $banner_bg_img_txt == 'remove_image') {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'banner_bg_img')->first();
            if ($ExistInfo) {
                // If a file already exists, delete it
                $existingFilePath = WRITEPATH . $frontendDir . $ExistInfo['meta_value'];
                if (file_exists($existingFilePath) && !empty($ExistInfo['meta_value'])) {
                    unlink($existingFilePath);
                }
                $saveData['id'] = $ExistInfo['id']; // Update the existing record
            }
            $saveData['meta_key'] = 'banner_bg_img';
            $saveData['meta_value'] = '';
            $pageMetaObject->savePageMeta($saveData);

            $savedArr[] = $saveData;
        }
        /* ##### DarkMode ##### */
        # Banner BackGround Image
        if ($talent_banner_bg_img_dark_mode && $talent_banner_bg_img_dark_mode->isValid() && !$talent_banner_bg_img_dark_mode->hasMoved()) {
            $uniqueName = uniqid() . rand() . date('y_m_d_his') . '_' . $talent_banner_bg_img_dark_mode->getClientName();
            // Remove spaces from the generated file name
            $uniqueName = str_replace(' ', '_', $uniqueName);
            // Optionally, remove special characters (if necessary)
            $uniqueName = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $uniqueName);
            if ($talent_banner_bg_img_dark_mode->move(WRITEPATH . $frontendDir, $uniqueName)) {
                // Check if an existing record for this image already exists
                $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'talent_banner_bg_img_dark_mode')->first();
                if ($ExistInfo) {
                    // If a file already exists, delete it
                    $existingFilePath = WRITEPATH . $frontendDir . $ExistInfo['meta_value'];
                    if (file_exists($existingFilePath) && !empty($ExistInfo['meta_value'])) {
                        unlink($existingFilePath);
                    }
                    $saveData['id'] = $ExistInfo['id']; // Update the existing record
                }
                // Save the new image path in the database
                $saveData['meta_key'] = 'talent_banner_bg_img_dark_mode';
                $saveData['meta_value'] = $uniqueName;
                $pageMetaObject->savePageMeta($saveData);

                $savedArr[] = $saveData;
            }
        }
        if (isset($talent_banner_bg_img_dark_txt) && !empty($talent_banner_bg_img_dark_txt) && $talent_banner_bg_img_dark_txt == 'remove_image') {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'talent_banner_bg_img_dark_mode')->first();
            if ($ExistInfo) {
                // If a file already exists, delete it
                $existingFilePath = WRITEPATH . $frontendDir . $ExistInfo['meta_value'];
                if (file_exists($existingFilePath) && !empty($ExistInfo['meta_value'])) {
                    unlink($existingFilePath);
                }
                $saveData['id'] = $ExistInfo['id']; // Update the existing record
            }
            $saveData['meta_key'] = 'talent_banner_bg_img_dark_mode';
            $saveData['meta_value'] = '';
            $pageMetaObject->savePageMeta($saveData);

            $savedArr[] = $saveData;
        }
        # End Banner BackGround Image
        $uploadedFileNames = [];

        if (isset($banner_imgs) && !empty($banner_imgs)) {
            // echo '<pre>'; print_r($banner_imgs); die;
            if (isset($banner_imgs['banner_imgs']) && !empty($banner_imgs['banner_imgs'])) {
                $banner_imgs = $banner_imgs['banner_imgs'];
                foreach ($banner_imgs as $file) {
                    // echo '<pre>'; print_r($file); die('files');
                    if (is_array($file)) {
                        $files = $file;
                        foreach ($files as $file1) {
                            if (isset($file1['img']) && !empty($file1['img'])) {
                                $file1 = $file1['img'];
                            }
                            if ($file1->isValid() && !$file1->hasMoved()) {
                                $uniqueName = uniqid() . '_' . date('YmdHis') . '_' . $file1->getClientName();
                                // Remove spaces from the generated file name
                                $uniqueName = str_replace(' ', '_', $uniqueName);
                                // Optionally, remove special characters (if necessary)
                                $uniqueName = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $uniqueName);
                                if ($file1->move(WRITEPATH . $frontendDir, $uniqueName)) {
                                    $uploadedFileNames[] = $uniqueName;
                                }
                            }
                        }
                    } else {
                        if ($file->isValid() && !$file->hasMoved()) {
                            $uniqueName = uniqid() . '_' . date('YmdHis') . '_' . $file->getClientName();
                            $uniqueName = str_replace(' ', '_', $uniqueName);
                            // Optionally, remove special characters (if necessary)
                            $uniqueName = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $uniqueName);
                            if ($file->move(WRITEPATH . $frontendDir, $uniqueName)) {
                                $uploadedFileNames[] = $uniqueName;
                            }
                        }
                    }
                }
            } else {
                if (is_array($banner_imgs) && !empty($banner_imgs->banner_imgs)) {
                    $banner_imgs = $banner_imgs->banner_imgs;
                    foreach ($banner_imgs as $bnner_img) {
                        if ($bnner_img->isValid() && !$bnner_img->hasMoved()) {
                            $uniqueName = uniqid() . '_' . date('YmdHis') . '_' . $bnner_img->getClientName();
                            $uniqueName = str_replace(' ', '_', $uniqueName);
                            // Optionally, remove special characters (if necessary)
                            $uniqueName = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $uniqueName);
                            if ($bnner_img->move(WRITEPATH . $frontendDir, $uniqueName)) {
                                $uploadedFileNames[] = $uniqueName;
                            }
                        }
                    }
                } else if (!empty($banner_imgs->banner_imgs) && $banner_imgs->isValid() && !$banner_imgs->hasMoved()) {
                    $uniqueName = uniqid() . '_' . date('YmdHis') . '_' . $banner_imgs->getClientName();
                    $uniqueName = str_replace(' ', '_', $uniqueName);
                    // Optionally, remove special characters (if necessary)
                    $uniqueName = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $uniqueName);
                    if ($banner_imgs->move(WRITEPATH . $frontendDir, $uniqueName)) {
                        $uploadedFileNames[] = $uniqueName;
                    }
                }
            }
            $fileNamesString = implode(',', $uploadedFileNames);
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'banner_imgs')->first();
            if ($ExistInfo) {
                $existingFilePaths = explode(',', $ExistInfo['meta_value']);
                foreach ($existingFilePaths as $filePath) {
                    $fullPath = WRITEPATH . $frontendDir . $filePath;
                    if (file_exists($fullPath) && !empty($filePath)) {
                        unlink($fullPath);
                    }
                }
                $saveData['id'] = $ExistInfo['id'];
            }
            $saveData['meta_key'] = 'banner_imgs';
            $saveData['meta_value'] = $fileNamesString;
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($banner_title) {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'banner_title')->first();
            $saveData['meta_key'] = 'banner_title';
            $saveData['meta_value'] = $banner_title;
            if ($ExistInfo) {
                $saveData['id'] = $ExistInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($banner_desc) {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'banner_desc')->first();
            $saveData['meta_key'] = 'banner_desc';
            $saveData['meta_value'] = $banner_desc;
            if ($ExistInfo) {
                $saveData['id'] = $ExistInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($banner_btn_txt) {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'banner_btn_txt')->first();
            $saveData['meta_key'] = 'banner_btn_txt';
            $saveData['meta_value'] = $banner_btn_txt;
            if ($ExistInfo) {
                $saveData['id'] = $ExistInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        /* ##### Banner Images Are Pending ##### */
        if ($talent_section_title) {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'talent_section_title')->first();
            $saveData['meta_key'] = 'talent_section_title';
            $saveData['meta_value'] = $talent_section_title;
            if ($ExistInfo) {
                $saveData['id'] = $ExistInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($talent_section) {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'talent_section')->first();
            ##### Icon  #####
            $tabName = 'talent_section';
            $tabs = ['first_tab', 'sec_tab', 'third_tab'];
            foreach ($tabs as $tab) {
                // $iconFile = $this->request->getFiles("$tabName[$tab]");
                $iconFile = $this->request->getFiles("talent_section[$tab]");
                // echo '<pre>'; print_r($iconFile); die;
                if (isset($iconFile[$tabName][$tab]['icon']) && !empty($iconFile[$tabName][$tab]['icon'])) {
                    $iconFile = $iconFile[$tabName][$tab]['icon'];
                    // Validate file
                    if ($iconFile && $iconFile->isValid() && !$iconFile->hasMoved()) {
                        $uniqueName = uniqid() . rand() . date('y_m_d_his') . '_' . $iconFile->getClientName();
                        $uniqueName = str_replace(' ', '_', $uniqueName);
                        // Optionally, remove special characters (if necessary)
                        $uniqueName = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $uniqueName);
                        if ($iconFile->move(WRITEPATH . $frontendDir, $uniqueName)) {
                            // Check if an existing record for this image already exists
                            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'banner_bg_img')->first();
                            if ($ExistInfo) {
                                // If a file already exists, delete it
                                $existingFilePath = WRITEPATH . $frontendDir . $ExistInfo['meta_value'];
                                if (file_exists($existingFilePath) && !empty($ExistInfo['meta_value'])) {
                                    unlink($existingFilePath);
                                }
                                $saveData['id'] = $ExistInfo['id']; // Update the existing record
                            }
                            $talent_section[$tab]['icon'] = $uniqueName;
                        }
                    }
                } else if (!empty($iconFile) && $iconFile == 'remove_image') {
                    $talent_section[$tab]['icon'] = '';
                }
                // Save any additional text data in each tab, if needed
            }
            ##### End Icon  #####
            // details
            if (is_array($talent_section)) {
                // $talent_section = serialize($talent_section);
                // $talent_section = json_encode(array($talent_section)); 1-8-24 update
                $talent_section = json_encode($talent_section);
                // echo '<pre>'; print_r($talent_section); die;
            }
            $saveData['meta_key'] = 'talent_section';
            $saveData['meta_value'] = $talent_section;
            if ($ExistInfo) {
                $saveData['id'] = $ExistInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($feature_sctn_title) {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'feature_sctn_title')->first();
            $saveData['meta_key'] = 'feature_sctn_title';
            $saveData['meta_value'] = $feature_sctn_title;
            if ($ExistInfo) {
                $saveData['id'] = $ExistInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($feature_sctn) {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'feature_sctn')->first();
            // feature_sctn
            $featureSections = $this->request->getFiles('feature_sctn');
            if (isset($featureSections['feature_sctn']) && !empty($featureSections['feature_sctn'])) {
                foreach ($featureSections['feature_sctn'] as $index => $sectionFiles) {
                    if (isset($sectionFiles['icon']) && !empty($sectionFiles['icon'])) {

                        if ($sectionFiles['icon'] instanceof \CodeIgniter\HTTP\Files\UploadedFile) {
                            // Handle single file upload
                            $iconFile = $sectionFiles['icon'];
                            if ($iconFile->isValid() && !$iconFile->hasMoved()) {
                                // Move the file to the defined directory with a unique name
                                $newFileName = $iconFile->getRandomName();
                                $newFileName = str_replace(' ', '_', $newFileName);
                                // Optionally, remove special characters (if necessary)
                                $newFileName = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $newFileName);
                                $iconFile->move($frontendDir, $newFileName);
                                // Save the path in the section data
                                $feature_sctn[$index]['icon'] = $newFileName;
                            }
                        } elseif (is_array($sectionFiles['icon'])) {
                            // Handle multiple files
                            foreach ($sectionFiles['icon'] as $iconFile) {
                                if ($iconFile->isValid() && !$iconFile->hasMoved()) {
                                    // Move the file to the defined directory with a unique name
                                    $newFileName = $iconFile->getRandomName();
                                    // Remove spaces from the generated file name
                                    $newFileName = str_replace(' ', '_', $newFileName);
                                    // Optionally, remove special characters (if necessary)
                                    $newFileName = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $newFileName);
                                    $iconFile->move($frontendDir, $newFileName);
                                    // Save the path in the section data
                                    $feature_sctn[$index]['icon'] = $newFileName;
                                }
                            }
                        } else {
                            $feature_sctn[$index]['icon'] = '';
                        }
                    }
                }
            }
            // echo '<pre>'; print_r($feature_sctn); die;
            if (is_array($feature_sctn) && !empty($feature_sctn)) {
                // $feature_sctn = serialize($feature_sctn);
                $feature_sctn = json_encode($feature_sctn);
            }
            $saveData['meta_key'] = 'feature_sctn';
            $saveData['meta_value'] = $feature_sctn;
            if ($ExistInfo) {
                $saveData['id'] = $ExistInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($pricing_sctn_title) {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'pricing_sctn_title')->first();
            $saveData['meta_key'] = 'pricing_sctn_title';
            $saveData['meta_value'] = $pricing_sctn_title;
            if ($ExistInfo) {
                $saveData['id'] = $ExistInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($pricing_tab) {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'pricing_tab')->first();
            if (is_array($pricing_tab)) {
                // $pricing_tab = serialize($pricing_tab);
                $pricing_tab = json_encode($pricing_tab);
            }
            $saveData['meta_key'] = 'pricing_tab';
            $saveData['meta_value'] = $pricing_tab;
            if ($ExistInfo) {
                $saveData['id'] = $ExistInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        $returnArr = [];
        // $serilized_fields = array('talent_section', 'feature_sctn', 'pricing_tab');
        $json_fields = array('feature_sctn', 'pricing_tab', 'talent_section');
        if (!empty($savedArr)) {
            foreach ($savedArr as $key => $valueArr) {
                if (isset($valueArr) && !empty($valueArr['meta_key']) && !empty($valueArr['meta_value']) && in_array($valueArr['meta_key'], $json_fields)) {
                    $returnArr[$valueArr['meta_key']] = json_decode($valueArr['meta_value']);
                } else if (isset($valueArr['meta_key']) && !empty($valueArr['meta_key'])) {
                    $returnArr[$valueArr['meta_key']] = $valueArr['meta_value'];
                } else {
                    if (isset($valueArr) && is_array($valueArr)) {
                        foreach ($valueArr as $field_key => $field_value) {
                            $returnArr[$field_key] = $field_value;
                        }
                    }
                }
            }
        }
        // Prepare response
        return $this->respondCreated([
            "status" => true,
            "message" => lang('App.talentpage_updatedSuccess'),
            "data" => $returnArr
        ]);
    }
    public function clubsAndScoutsMetaData()
    {
        $frontendDir = $this->frontendDir;
        $pageMetaObject = new PageMetaModel();
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale($currentLang);
        $pageId = $this->request->getVar("page_id");
        $lang_id = $this->request->getVar("lang_id");
        if (isset($pageId) && !empty($pageId)) {
            //  updation
        } else {
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
            $pageId = $this->pageModel->save($save_data);
            if ($pageId) {
                $pageId = $this->pageModel->db->insertID();
                $savedArr[] = $save_data;
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
            }
        }
        $banner_bg_img = $this->request->getFile('banner_bg_img');
        $banner_bg_img_txt = $this->request->getVar('banner_bg_img');
        $banner_title = $this->request->getVar('banner_title');
        $banner_desc = $this->request->getVar('banner_desc');
        $banner_btn_txt = $this->request->getVar('banner_btn_txt');
        $banner_imgs = $this->request->getFiles('banner_imgs');
        $club_nd_scout_section_title = $this->request->getVar('club_nd_scout_section_title');
        $club_nd_scout_section = $this->request->getVar('club_nd_scout_section');
        // $club_nd_scout_section_icons = $this->request->getFiles('club_nd_scout_section');
        $feature_sctn_title = $this->request->getVar('feature_sctn_title');
        $feature_sctn = $this->request->getVar('feature_sctn');
        $pricing_sctn_title = $this->request->getVar('pricing_sctn_title');
        $pricing_tab = $this->request->getVar('pricing_tab');
        $savedArr = array();
        $saveData = ['page_id' => $pageId, 'lang_id' => $lang_id];

        if ($banner_bg_img && $banner_bg_img->isValid() && !$banner_bg_img->hasMoved()) {
            $uniqueName = uniqid() . rand() . date('y_m_d_his') . '_' . $banner_bg_img->getClientName();
            // Remove spaces from the generated file name
            $uniqueName = str_replace(' ', '_', $uniqueName);
            // Optionally, remove special characters (if necessary)
            $uniqueName = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $uniqueName);
            if ($banner_bg_img->move(WRITEPATH . $frontendDir, $uniqueName)) {
                // Check if an existing record for this image already exists
                $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'banner_bg_img')->first();
                if ($ExistInfo) {
                    // If a file already exists, delete it
                    $existingFilePath = WRITEPATH . $frontendDir . $ExistInfo['meta_value'];
                    if (file_exists($existingFilePath) && !empty($ExistInfo['meta_value'])) {
                        unlink($existingFilePath);
                    }
                    $saveData['id'] = $ExistInfo['id']; // Update the existing record
                }
                // Save the new image path in the database
                $saveData['meta_key'] = 'banner_bg_img';
                $saveData['meta_value'] = $uniqueName;
                $pageMetaObject->savePageMeta($saveData);

                $savedArr[] = $saveData;
            }
        }
        if (isset($banner_bg_img_txt) && !empty($banner_bg_img_txt) && $banner_bg_img_txt == 'remove_image') {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'banner_bg_img')->first();
            if ($ExistInfo) {
                // If a file already exists, delete it
                $existingFilePath = WRITEPATH . $frontendDir . $ExistInfo['meta_value'];
                if (file_exists($existingFilePath) && !empty($ExistInfo['meta_value'])) {
                    unlink($existingFilePath);
                }
                $saveData['id'] = $ExistInfo['id']; // Update the existing record
            }
            $saveData['meta_key'] = 'banner_bg_img';
            $saveData['meta_value'] = '';
            $pageMetaObject->savePageMeta($saveData);

            $savedArr[] = $saveData;
        }

        $uploadedFileNames = [];
        if (isset($banner_imgs) && !empty($banner_imgs)) {
            if (isset($banner_imgs) && !empty($banner_imgs)) {
                if (is_array($banner_imgs) && !empty($banner_imgs['banner_imgs'])) {
                    $banner_imgs = $banner_imgs['banner_imgs'];
                    foreach ($banner_imgs as $file) {
                        if (is_object($file) && $file->isValid() && !$file->hasMoved()) {
                            $uniqueName = uniqid() . '_' . date('YmdHis') . '_' . $file->getClientName();
                            // Remove spaces from the generated file name
                            $uniqueName = str_replace(' ', '_', $uniqueName);
                            // Optionally, remove special characters (if necessary)
                            $uniqueName = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $uniqueName);
                            if ($file->move(WRITEPATH . $frontendDir, $uniqueName)) {
                                $uploadedFileNames[] = $uniqueName;
                            }
                        }
                    }
                } elseif (is_object($banner_imgs)) {
                    if ($banner_imgs->isValid() && !$banner_imgs->hasMoved()) {
                        $uniqueName = uniqid() . '_' . date('YmdHis') . '_' . $banner_imgs->getClientName();
                        // Remove spaces from the generated file name
                        $uniqueName = str_replace(' ', '_', $uniqueName);
                        // Optionally, remove special characters (if necessary)
                        $uniqueName = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $uniqueName);
                        if ($banner_imgs->move(WRITEPATH . $frontendDir, $uniqueName)) {
                            $uploadedFileNames[] = $uniqueName;
                        }
                    }
                }
                if (isset($uploadedFileNames) && !empty($uploadedFileNames)) {
                    $fileNamesString = implode(',', $uploadedFileNames);
                    $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'banner_imgs')->first();
                    if ($ExistInfo) {
                        $existingFilePaths = explode(',', $ExistInfo['meta_value']);
                        foreach ($existingFilePaths as $filePath) {
                            $fullPath = WRITEPATH . $frontendDir . $filePath;
                            if (file_exists($fullPath) && !empty($filePath)) {
                                unlink($fullPath);
                            }
                        }
                        $saveData['id'] = $ExistInfo['id'];
                    }
                    $saveData['meta_key'] = 'banner_imgs';
                    $saveData['meta_value'] = $fileNamesString;
                    $pageMetaObject->savePageMeta($saveData);
                    $savedArr[] = $saveData;
                }
            }
        }

        if ($banner_title) {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'banner_title')->first();
            $saveData['meta_key'] = 'banner_title';
            $saveData['meta_value'] = $banner_title;
            if ($ExistInfo) {
                $saveData['id'] = $ExistInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($banner_desc) {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'banner_desc')->first();
            $saveData['meta_key'] = 'banner_desc';
            $saveData['meta_value'] = $banner_desc;
            if ($ExistInfo) {
                $saveData['id'] = $ExistInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($banner_btn_txt) {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'banner_btn_txt')->first();
            $saveData['meta_key'] = 'banner_btn_txt';
            $saveData['meta_value'] = $banner_btn_txt;
            if ($ExistInfo) {
                $saveData['id'] = $ExistInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        /* ##### Banner Images Are Pending ##### */
        if ($club_nd_scout_section_title) {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'club_nd_scout_section_title')->first();
            $saveData['meta_key'] = 'club_nd_scout_section_title';
            $saveData['meta_value'] = $club_nd_scout_section_title;
            if ($ExistInfo) {
                $saveData['id'] = $ExistInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($club_nd_scout_section) {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'club_nd_scout_section')->first();
            ##### Icon  #####
            $tabName = 'club_nd_scout_section';
            $tabs = ['first_tab', 'sec_tab', 'third_tab'];
            foreach ($tabs as $tab) {
                // $iconFile = $this->request->getFiles("$tabName[$tab]");
                $iconFile = $this->request->getFiles("club_nd_scout_section[$tab]");
                if (isset($iconFile[$tabName][$tab]['icon']) && !empty($iconFile[$tabName][$tab]['icon'])) {
                    $iconFile = $iconFile[$tabName][$tab]['icon'];
                    // Validate file
                    if ($iconFile && $iconFile->isValid() && !$iconFile->hasMoved()) {
                        $uniqueName = uniqid() . rand() . date('y_m_d_his') . '_' . $iconFile->getClientName();
                        if ($iconFile->move(WRITEPATH . $frontendDir, $uniqueName)) {
                            // Check if an existing record for this image already exists
                            // $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'banner_bg_img')->first();
                            $ExistInfo = array();
                            if ($ExistInfo) {
                                // If a file already exists, delete it
                                $existingFilePath = WRITEPATH . $frontendDir . $ExistInfo['meta_value'];
                                if (file_exists($existingFilePath) && !empty($ExistInfo['meta_value'])) {
                                    unlink($existingFilePath);
                                }
                                $saveData['id'] = $ExistInfo['id']; // Update the existing record
                            }
                            $club_nd_scout_section[$tab]['icon'] = $uniqueName;
                        }
                    }
                }
                $iconFileTxt = $this->request->getVar("club_nd_scout_section[$tab][icon]");
                if (!empty($iconFileTxt) && $iconFileTxt == 'remove_image') {
                    $club_nd_scout_section[$tab]['icon'] = '';
                }
                // echo '<pre>'; print_r($iconFileTxt); die;
                // Save any additional text data in each tab, if needed
            }
            ##### End Icon  #####
            // details
            if (is_array($club_nd_scout_section)) {
                // $myArr['my_code_array'] = $club_nd_scout_section;
                // $myArr['my_post_request'] = $this->request->getVar('club_nd_scout_section');
                $club_nd_scout_section = json_encode($club_nd_scout_section);
                // echo '<pre>'; print_r($club_nd_scout_section); die;
            }
            // echo '<pre>'; print_r($club_nd_scout_section); die;
            $saveData['meta_key'] = 'club_nd_scout_section';
            $saveData['meta_value'] = $club_nd_scout_section;
            if ($ExistInfo) {
                $saveData['id'] = $ExistInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($feature_sctn_title) {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'feature_sctn_title')->first();
            $saveData['meta_key'] = 'feature_sctn_title';
            $saveData['meta_value'] = $feature_sctn_title;
            if ($ExistInfo) {
                $saveData['id'] = $ExistInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($feature_sctn) {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'feature_sctn')->first();
            // feature_sctn
            $featureSections = $this->request->getFiles('feature_sctn');
            if (isset($featureSections['feature_sctn']) && !empty($featureSections['feature_sctn'])) {
                foreach ($featureSections['feature_sctn'] as $index => $sectionFiles) {
                    if (isset($sectionFiles['icon']) && !empty($sectionFiles['icon'])) {
                        $iconFileTxt = $this->request->getVar("feature_sctn[" . $index . "][icon]");
                        if (!empty($iconFileTxt) && $iconFileTxt == 'remove_image') {
                            $feature_sctn[$index]['icon'] = '';
                            $sectionFiles['icon'] = [];
                        }
                        if ($sectionFiles['icon'] instanceof \CodeIgniter\HTTP\Files\UploadedFile) {
                            // Handle single file upload
                            $iconFile = $sectionFiles['icon'];
                            if ($iconFile->isValid() && !$iconFile->hasMoved()) {
                                // Move the file to the defined directory with a unique name
                                $newFileName = $iconFile->getRandomName();
                                $iconFile->move($frontendDir, $newFileName);
                                // Save the path in the section data
                                $feature_sctn[$index]['icon'] = $newFileName;
                            }
                        } elseif (is_array($sectionFiles['icon'])) {
                            // Handle multiple files
                            foreach ($sectionFiles['icon'] as $iconFile) {
                                if ($iconFile->isValid() && !$iconFile->hasMoved()) {
                                    // Move the file to the defined directory with a unique name
                                    $newFileName = $iconFile->getRandomName();
                                    $iconFile->move($frontendDir, $newFileName);
                                    // Save the path in the section data
                                    $feature_sctn[$index]['icon'] = $newFileName;
                                }
                            }
                        }
                    }
                }
            }

            if (is_array($feature_sctn) && !empty($feature_sctn)) {
                // $feature_sctn = serialize($feature_sctn);
                $feature_sctn = json_encode($feature_sctn, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            }
            $saveData['meta_key'] = 'feature_sctn';
            $saveData['meta_value'] = $feature_sctn;
            if ($ExistInfo) {
                $saveData['id'] = $ExistInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($pricing_sctn_title) {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'pricing_sctn_title')->first();
            $saveData['meta_key'] = 'pricing_sctn_title';
            $saveData['meta_value'] = $pricing_sctn_title;
            if ($ExistInfo) {
                $saveData['id'] = $ExistInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($pricing_tab) {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'pricing_tab')->first();
            if (is_array($pricing_tab)) {
                // $pricing_tab = serialize($pricing_tab);
                $pricing_tab = json_encode($pricing_tab);
            }
            // echo '<pre>'; print_r($pricing_tab); die;

            $saveData['meta_key'] = 'pricing_tab';
            $saveData['meta_value'] = $pricing_tab;
            if ($ExistInfo) {
                $saveData['id'] = $ExistInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        $returnArr = [];
        $json_fields = array('club_nd_scout_section', 'feature_sctn', 'pricing_tab');
        if (!empty($savedArr)) {
            foreach ($savedArr as $key => $valueArr) {
                if (isset($valueArr) && !empty($valueArr['meta_key']) && !empty($valueArr['meta_value']) && in_array($valueArr['meta_key'], $json_fields)) {
                    $returnArr[$valueArr['meta_key']] = json_decode($valueArr['meta_value']);
                } else if (isset($valueArr) && !empty($valueArr['meta_key']) && !empty($valueArr['meta_value']) && $valueArr['meta_key'] == 'banner_imgs') {
                    $returnArr[$valueArr['meta_key']] = explode(',', $valueArr['meta_value']);
                } else {
                    $returnArr[$valueArr['meta_key']] = $valueArr['meta_value'];
                }
            }
        }
        // Prepare response
        return $this->respondCreated([
            "status" => true,
            "message" => lang('App.club_scout_page_updatedSuccess'),
            "data" => $returnArr
        ]);
    }
    public function contactPageMetaData()
    {
        // pr($this->request->getVar()); exit;
        $frontendDir = $this->frontendDir;
        $pageMetaObject = new PageMetaModel();
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale($currentLang);
        $pageId = $this->request->getVar("page_id");
        $lang_id = $this->request->getVar("lang_id");
        $savedArr = array();
        // if (empty($pageId) || !is_numeric($pageId) || empty($lang_id) || !is_numeric($lang_id)) {
        //     return $this->respondCreated([
        //         "status" => false,
        //         "message" => lang('App.invalidParam'),
        //         "data" => []
        //     ]);
        // }
        // die('here '.$pageId);

        if (isset($pageId) && !empty($pageId)) {
            //  updation
        } else {
            $save_data = [
                // 'user_id'       => auth()->id(),
                'title'         => $this->request->getVar("title"),
                'content'       => $this->request->getVar("content"),
                'language'      => $this->request->getVar("lang_id"),
                'status'        => $this->request->getVar("status"),
                'slug'        => $this->request->getVar("slug"),
                'page_type'        => $this->request->getVar("page_type"),
            ];
            $ad_types = $this->request->getVar("ad_types");
            if (isset($ad_types) && !empty($ad_types)) {
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
            $pageId = $this->pageModel->save($save_data);
            // echo '<pre>'; print_r($save_data); die;
            if ($pageId) {
                $pageId = $this->pageModel->db->insertID();
                // echo $pageId; die(' pageId');
                $savedArr[] = $save_data;
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
            }
        }



        // echo $pageId; die;
        $banner_bg_img = $this->request->getFile('banner_bg_img');
        $banner_title = $this->request->getVar('banner_title');
        $form_title = $this->request->getVar('form_title');
        $txt_before_radio_btn = $this->request->getVar('txt_before_radio_btn');
        $talent_label_txt = $this->request->getVar('talent_label_txt');
        $club_label_txt = $this->request->getVar('club_label_txt');
        $scout_label_txt = $this->request->getVar('scout_label_txt');
        $submit_btn_txt = $this->request->getVar('submit_btn_txt');
        $address = $this->request->getVar('address');
        $email = $this->request->getVar('email');
        $saveData = ['page_id' => $pageId, 'lang_id' => $lang_id];
        // echo '<pre>'; print_r($saveData); die;
        if ($banner_bg_img && $banner_bg_img->isValid() && !$banner_bg_img->hasMoved()) {
            $uniqueName = uniqid() . rand() . date('y_m_d_his') . '_' . $banner_bg_img->getClientName();
            // Remove spaces from the generated file name
            $uniqueName = str_replace(' ', '_', $uniqueName);
            // Optionally, remove special characters (if necessary)
            $uniqueName = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $uniqueName);
            if ($banner_bg_img->move(WRITEPATH . $frontendDir, $uniqueName)) {
                // Check if an existing record for this image already exists
                $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'banner_bg_img')->first();
                if ($ExistInfo) {
                    // If a file already exists, delete it
                    $existingFilePath = WRITEPATH . $frontendDir . $ExistInfo['meta_value'];
                    if (file_exists($existingFilePath) && !empty($ExistInfo['meta_value'])) {
                        unlink($existingFilePath);
                    }
                    $saveData['id'] = $ExistInfo['id']; // Update the existing record
                }
                // Save the new image path in the database
                $saveData['meta_key'] = 'banner_bg_img';
                $saveData['meta_value'] = $uniqueName;
                // echo '<pre>'; print_r($saveData); die;
                $pageMetaObject->savePageMeta($saveData);

                $savedArr[] = $saveData;
            }
        }

        // to remove bg image
        if ($this->request->getVar('banner_bg_img') && $this->request->getVar('banner_bg_img') == 'remove_image') {
            // echo '>>>>>>>>>> remove_image >>> '; exit;
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'banner_bg_img')->first();

            $saveData['meta_key'] = 'banner_bg_img';
            $saveData['meta_value'] = "";

            if ($ExistInfo) {
                $saveData['id'] = $ExistInfo['id'];
            }

            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }

        if ($banner_title) {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'banner_title')->first();
            // echo '<pre>'; print_r($banner_titlex); die;
            // echo '<pre>'; print_r($ExistInfo); die;
            $saveData['meta_key'] = 'banner_title';
            $saveData['meta_value'] = $banner_title;
            if ($ExistInfo) {
                $saveData['id'] = $ExistInfo['id'];
            }
            // echo '<pre>'; print_r($saveData); die;
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($form_title) {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'form_title')->first();
            $saveData['meta_key'] = 'form_title';
            $saveData['meta_value'] = $form_title;
            if ($ExistInfo) {
                $saveData['id'] = $ExistInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($txt_before_radio_btn) {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'txt_before_radio_btn')->first();
            $saveData['meta_key'] = 'txt_before_radio_btn';
            $saveData['meta_value'] = $txt_before_radio_btn;
            if ($ExistInfo) {
                $saveData['id'] = $ExistInfo['id'];
            }
            // $pageMetaObject->delete($ExistInfo['id']);
            // $pageMetaObject->savePageMeta($saveData);
            // $savedArr[] = $saveData;
        }
        if ($talent_label_txt) {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'talent_label_txt')->first();
            $saveData['meta_key'] = 'talent_label_txt';
            $saveData['meta_value'] = $talent_label_txt;
            if ($ExistInfo) {
                $saveData['id'] = $ExistInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($club_label_txt) {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'club_label_txt')->first();
            $saveData['meta_key'] = 'club_label_txt';
            $saveData['meta_value'] = $club_label_txt;
            if ($ExistInfo) {
                $saveData['id'] = $ExistInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($scout_label_txt) {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'scout_label_txt')->first();
            $saveData['meta_key'] = 'scout_label_txt';
            $saveData['meta_value'] = $scout_label_txt;
            if ($ExistInfo) {
                $saveData['id'] = $ExistInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($submit_btn_txt) {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'submit_btn_txt')->first();
            $saveData['meta_key'] = 'submit_btn_txt';
            $saveData['meta_value'] = $submit_btn_txt;
            if ($ExistInfo) {
                $saveData['id'] = $ExistInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($address) {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'address')->first();
            $saveData['meta_key'] = 'address';
            $saveData['meta_value'] = $address;
            if ($ExistInfo) {
                $saveData['id'] = $ExistInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($email) {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'email')->first();
            $saveData['meta_key'] = 'email';
            $saveData['meta_value'] = $email;
            if ($ExistInfo) {
                $saveData['id'] = $ExistInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }

        $returnArr = array();
        if (!empty($savedArr)) {
            foreach ($savedArr as $key => $valueArr) {
                if (isset($valueArr['meta_key']) && !empty($valueArr['meta_key'])) {
                    $returnArr[$valueArr['meta_key']] = $valueArr['meta_value'];
                } else {
                    if (isset($valueArr) && is_array($valueArr)) {
                        foreach ($valueArr as $field_key => $field_value) {
                            $returnArr[$field_key] = $field_value;
                        }
                    }
                }
            }
        }
        // Prepare response
        return $this->respondCreated([
            "status" => true,
            "message" => lang('App.contact_page_updatedSuccess'),
            "data" => $returnArr
        ]);
    }
    public function newsAndMediaMetaData()
    {
        $frontendDir = $this->frontendDir;
        $pageMetaObject = new PageMetaModel();
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale($currentLang);
        $pageId = $this->request->getVar("page_id");
        $lang_id = $this->request->getVar("lang_id");
        if (isset($pageId) && !empty($pageId)) {
            //  updation
        } else {
            $save_data = [
                // 'user_id'       => auth()->id(),
                'title'         => $this->request->getVar("title"),
                'content'       => $this->request->getVar("content"),
                'language'      => $this->request->getVar("lang_id"),
                'status'        => $this->request->getVar("status"),
                'slug'        => $this->request->getVar("slug"),
                'page_type'        => $this->request->getVar("page_type"),
            ];
            $title = $this->request->getVar("title");
            $content = $this->request->getVar("content");
            $language = $this->request->getVar("language");
            $content = $this->request->getVar("content");
            $status = $this->request->getVar("status");
            $page_type = $this->request->getVar("page_type");
            $meta_title = $this->request->getVar("meta_title");
            $meta_description = $this->request->getVar("meta_description");
            if (isset($title) && !empty($title)) {
                $save_data['title'] = $title;
            }
            if (isset($content) && !empty($content)) {
                $save_data['content'] = $content;
            }
            if (isset($language) && !empty($language)) {
                $save_data['language'] = $language;
            }
            if (isset($status) && !empty($status)) {
                $save_data['status'] = $status;
            } else {
                $save_data['status'] = 'draft';
            }
            if (isset($page_type) && !empty($page_type)) {
                $save_data['page_type'] = $page_type;
            }
            if (isset($meta_title) && !empty($meta_title)) {
                $save_data['meta_title'] = $meta_title;
            }
            if (isset($meta_description) && !empty($meta_description)) {
                $save_data['meta_description'] = $meta_description;
            }
            $ad_types = $this->request->getVar("ad_types");
            if (isset($ad_types) && !empty($ad_types)) {
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
            $pageId = $this->pageModel->save($save_data);
            // echo '<pre>'; print_r($save_data); die;
            if ($pageId) {
                $pageId = $this->pageModel->db->insertID();
                // echo $pageId; die(' pageId');
                $savedArr[] = $save_data;
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
            }
        }
        if (empty($pageId) || !is_numeric($pageId) || empty($lang_id) || !is_numeric($lang_id)) {
            return $this->respondCreated([
                "status" => false,
                "message" => lang('App.invalidParam'),
                "data" => []
            ]);
        }
        $banner_bg_img = $this->request->getFile('banner_bg_img');
        $slider_imgs = array();
        // if (isset($this->request->getFiles('slider_imgs')) && !empty($this->request->getFiles('slider_imgs'))) {
        // $slider_imgs = $this->request->getFiles('slider_imgs');
        // }

        $banner_bg_img_txt = $this->request->getVar('banner_bg_img');
        $banner_title = $this->request->getVar('banner_title');
        $slider_date = $this->request->getVar('slider_date');
        $slider_title = $this->request->getVar('slider_title');
        $slider_btn_txt = $this->request->getVar('slider_btn_txt');
        $slider_btn_link = $this->request->getVar('slider_btn_link');
        $news_title = $this->request->getVar('news_title');
        // $news_desc = $this->request->getVar('news_section')['desc'];
        // $news_date = $this->request->getVar('news_section')['date'];
        // $imagesArr = $this->request->getFiles('news_section[image]');
        $imagesArr = array();
        $savedArr = array();
        $saveData = ['page_id' => $pageId, 'lang_id' => $lang_id];

        if ($banner_bg_img && $banner_bg_img->isValid() && !$banner_bg_img->hasMoved()) {
            $uniqueName = uniqid() . rand() . date('y_m_d_his') . '_' . $banner_bg_img->getClientName();
            // Remove spaces from the generated file name
            $uniqueName = str_replace(' ', '_', $uniqueName);
            // Optionally, remove special characters (if necessary)
            $uniqueName = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $uniqueName);
            if ($banner_bg_img->move(WRITEPATH . $frontendDir, $uniqueName)) {
                // Check if an existing record for this image already exists
                $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'banner_bg_img')->first();
                if ($ExistInfo) {
                    // If a file already exists, delete it
                    $existingFilePath = WRITEPATH . $frontendDir . $ExistInfo['meta_value'];
                    if (file_exists($existingFilePath) && !empty($ExistInfo['meta_value'])) {
                        unlink($existingFilePath);
                    }
                    $saveData['id'] = $ExistInfo['id']; // Update the existing record
                }
                // Save the new image path in the database
                $saveData['meta_key'] = 'banner_bg_img';
                $saveData['meta_value'] = $uniqueName;
                $pageMetaObject->savePageMeta($saveData);

                $savedArr[] = $saveData;
            }
        }
        if (!empty($banner_bg_img_txt) && $banner_bg_img_txt == 'remove_image') {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'banner_bg_img')->first();
            $updateData = ['meta_value' => ''];  // Replace '' with the value you want to set
            $pageMetaObject->set($updateData)->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'banner_bg_img')->update();
        }
        if ($banner_title) {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'banner_title')->first();
            $saveData['meta_key'] = 'banner_title';
            $saveData['meta_value'] = $banner_title;
            if ($ExistInfo) {
                $saveData['id'] = $ExistInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($slider_date) {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'slider_date')->first();
            $saveData['meta_key'] = 'slider_date';
            $saveData['meta_value'] = $slider_date;
            if ($ExistInfo) {
                $saveData['id'] = $ExistInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($slider_title) {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'slider_title')->first();
            $saveData['meta_key'] = 'slider_title';
            $saveData['meta_value'] = $slider_title;
            if ($ExistInfo) {
                $saveData['id'] = $ExistInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($slider_btn_txt) {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'slider_btn_txt')->first();
            $saveData['meta_key'] = 'slider_btn_txt';
            $saveData['meta_value'] = $slider_btn_txt;
            if ($ExistInfo) {
                $saveData['id'] = $ExistInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($slider_btn_link) {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'slider_btn_link')->first();
            $saveData['meta_key'] = 'slider_btn_link';
            $saveData['meta_value'] = $slider_btn_link;
            if ($ExistInfo) {
                $saveData['id'] = $ExistInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        $uploadedFileNames = [];
        if (isset($slider_imgs) && !empty($slider_imgs)) {
            if (isset($slider_imgs) && !empty($slider_imgs)) {
                if (is_array($slider_imgs)) {
                    $slider_imgs = $slider_imgs['slider_imgs'];
                    foreach ($slider_imgs as $file) {
                        if (is_object($file) && $file->isValid() && !$file->hasMoved()) {
                            $uniqueName = uniqid() . '_' . date('YmdHis') . '_' . $file->getClientName();
                            // Remove spaces from the generated file name
                            $uniqueName = str_replace(' ', '_', $uniqueName);
                            // Optionally, remove special characters (if necessary)
                            $uniqueName = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $uniqueName);
                            if ($file->move(WRITEPATH . $frontendDir, $uniqueName)) {
                                $uploadedFileNames[] = $uniqueName;
                            }
                        }
                    }
                } elseif (is_object($slider_imgs)) {
                    if ($slider_imgs->isValid() && !$slider_imgs->hasMoved()) {
                        $uniqueName = uniqid() . '_' . date('YmdHis') . '_' . $slider_imgs->getClientName();
                        // Remove spaces from the generated file name
                        $uniqueName = str_replace(' ', '_', $uniqueName);
                        // Optionally, remove special characters (if necessary)
                        $uniqueName = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $uniqueName);
                        if ($slider_imgs->move(WRITEPATH . $frontendDir, $uniqueName)) {
                            $uploadedFileNames[] = $uniqueName;
                        }
                    }
                }
                if (isset($uploadedFileNames) && !empty($uploadedFileNames)) {
                    $fileNamesString = implode(',', $uploadedFileNames);
                    $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'slider_imgs')->first();
                    if ($ExistInfo) {
                        $existingFilePaths = explode(',', $ExistInfo['meta_value']);
                        foreach ($existingFilePaths as $filePath) {
                            $fullPath = WRITEPATH . $frontendDir . $filePath;
                            if (file_exists($fullPath) && !empty($filePath)) {
                                unlink($fullPath);
                            }
                        }
                        $saveData['id'] = $ExistInfo['id'];
                    }
                    $saveData['meta_key'] = 'slider_imgs';
                    $saveData['meta_value'] = $fileNamesString;
                    $pageMetaObject->savePageMeta($saveData);
                    $savedArr[] = $saveData;
                }
            }
        }
        if ($news_title) {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'news_title')->first();
            $saveData['meta_key'] = 'news_title';
            $saveData['meta_value'] = $news_title;
            if ($ExistInfo) {
                $saveData['id'] = $ExistInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        // $newsSectionData = [
        //     'desc' => $news_desc,
        //     'date' => $news_date,
        //     'image' => []
        // ];
        $NewsimagesArr = array();
        // Handle image uploads
        if (isset($imagesArr['news_section']['image']) && !empty($imagesArr['news_section']['image'])) {
            $NewsimagesArr = $imagesArr['news_section']['image'];
        }
        if (isset($NewsimagesArr) && !empty($NewsimagesArr)) {
            foreach ($NewsimagesArr as $image) {
                if ($image->isValid() && !$image->hasMoved()) {
                    // Define upload path in the frontend directory
                    $uploadPath = WRITEPATH . $frontendDir;

                    // Create the directory if it doesn't exist
                    if (!is_dir($uploadPath)) {
                        mkdir($uploadPath, 0777, true);
                    }

                    // Generate a unique name for the image and move it
                    $newName = $image->getRandomName();
                    // Remove spaces from the generated file name
                    $newName = str_replace(' ', '_', $newName);
                    // Optionally, remove special characters (if necessary)
                    $newName = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $newName);
                    $image->move($uploadPath, $newName);

                    // Save the uploaded image name to the array
                    $newsSectionData['image'][] = $newName;
                } else {
                    // Add empty string if no valid image is uploaded
                    $newsSectionData['image'][] = '';
                }
            }
        }


        // Serialize the data array
        // $serializedData = serialize($newsSectionData);
        $serializedData = '';
        if (isset($serializedData) && !empty($serializedData)) {
            // $serializedData
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'news_section')->first();
            $saveData['meta_key'] = 'news_section';
            $saveData['meta_value'] = $serializedData;
            if ($ExistInfo) {
                $saveData['id'] = $ExistInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        $returnArr = array();
        if (!empty($savedArr)) {
            foreach ($savedArr as $key => $valueArr) {
                if ($valueArr['meta_key'] == 'news_section') {
                    $valueArr['meta_value'] = unserialize($valueArr['meta_value']);
                }
                $returnArr[$valueArr['meta_key']] = $valueArr['meta_value'];
            }
        }
        // Prepare response
        return $this->respondCreated([
            "status" => true,
            "message" => lang('App.news_page_updatedSuccess'),
            "data" => $returnArr
        ]);
    }
    public function adsTypes($pageID)
    {
        // $pageID = rand(1, 10);
        $allAdsTypes = array('250 x 250 - Square', '200 x 200 - Small Square', '468 x 60 - Banner', '728 x 90 - Leaderboard', '300 x 250 - Inline Rectangle', '336 x 280 - Large Rectangle', '120 x 600 - Skyscraper', '160 x 600 - Wide Skyscraper');
        $returnArr = [];
        if (in_array($pageID, [1, 2, 3, 4, 5, 6, 7, 8])) {
            # Home Page
            $returnArr = array('skyscraper', 'wide_skyscraper', 'leaderboard', 'small_square', 'large_leaderboard');
        } else if (in_array($pageID, [9, 10, 11, 12, 13, 14, 15, 16])) {
            # Talents Page 
            # Clubs & Scouts Page 
            // $returnArr = array('120 x 600 - Skyscraper', '728 x 90 - Leaderboard', '200 x 200 - Small Square', '468 x 60 - Banner');
            $returnArr = array('skyscraper', 'leaderboard', 'small_square', 'banner', 'large_rectangel', 'inline_rectangel', 'square');
        } else if (in_array($pageID, [17, 18, 19, 20, 21, 22, 23, 24])) {
            $returnArr = array('skyscraper', 'leaderboard', 'small_square', 'banner', 'large_rectangel', 'inline_rectangel', 'square');
            # club & scout 
        } else if (in_array($pageID, [25, 26, 27, 28, 29, 30, 31, 32])) {
            # About Page 
            // $returnArr = array('120 x 600 - Skyscraper', '728 x 90 - Leaderboard', '200 x 200 - Small Square', '336 x 280 - Large Rectangle', '300 x 250 - Inline Rectangle', '250 x 250 - Square');
            $returnArr = array('skyscraper', 'skyscraper',  'banner', 'leaderboard', 'small_square',  'large_rectangel', 'inline_rectangel', 'square');
        } else if (in_array($pageID, [33, 34, 35, 36, 37, 38, 39, 40])) {
            # Pricing Page 
            // $returnArr = array('120 x 600 - Skyscraper', '728 x 90 - Leaderboard', '200 x 200 - Small Square', '336 x 280 - Large Rectangle', '300 x 250 - Inline Rectangle', '250 x 250 - Square');
            $returnArr = array('skyscraper', 'skyscraper',  'large_rectangel', 'inline_rectangel',   'square');
        } else if (in_array($pageID, [41, 42, 43, 44, 45, 46, 47, 48])) {
            # News Page 
            // $returnArr = array('120 x 600 - Skyscraper', '728 x 90 - Leaderboard', '200 x 200 - Small Square', '336 x 280 - Large Rectangle', '300 x 250 - Inline Rectangle', '250 x 250 - Square');
            $returnArr = array('skyscraper', 'skyscraper_large',   'leaderboard', 'large_rectangel', 'inline_rectangel',   'square');
        } else if (in_array($pageID, [49, 50, 51, 52, 53, 54, 55, 56])) {
            # Contact Page 
            // $returnArr = array('120 x 600 - Skyscraper', '728 x 90 - Leaderboard', '200 x 200 - Small Square', '336 x 280 - Large Rectangle', '300 x 250 - Inline Rectangle', '250 x 250 - Square');
            $returnArr = array('skyscraper', 'skyscraper_large',   'leaderboard',  'small_square');
        } else if (in_array($pageID, [57, 58, 59, 60, 61, 62, 63, 64])) {
            # Imprint Page 
            // $returnArr = array('120 x 600 - Skyscraper', '336 x 280 - Large Rectangle', '300 x 250 - Inline Rectangle', '250 x 250 - Square');
            $returnArr = array('skyscraper', 'skyscraper_large',   'leaderboard',  'small_square');
        } else if (in_array($pageID, [65, 66, 67, 68, 69, 70, 71, 72])) {
            // Privacy Policy
            // $returnArr = array('120 x 600 - Skyscraper', '336 x 280 - Large Rectangle', '300 x 250 - Inline Rectangle', '250 x 250 - Square');
            $returnArr = array('skyscraper', 'skyscraper',  'leaderboard');
        } else if (in_array($pageID, [73, 74, 75, 76, 77, 78, 79, 80])) {
            # Terms and Conditions
            // $returnArr = array('120 x 600 - Skyscraper', '336 x 280 - Large Rectangle', '300 x 250 - Inline Rectangle', '250 x 250 - Square');
            $returnArr = array('skyscraper', 'skyscraper',  'leaderboard');
        } else if ($pageID == '6' || $pageID == 'content_pages') {
            # -- Contact Page 
            # -- FAQ Page 
            # -- Imprint Page 
            # -- Privacy Page 
            # -- Terms Page 
            # -- Cookie Policy Page 
            $returnArr = array('120 x 600 - Skyscraper', '728 x 90 - Leaderboard');
        }
        return $returnArr;
    }
    /* ##### About Controller Functions ##### */
    public function aboutPageMetaData()
    {
        $frontendDir = $this->frontendDir;
        $pageMetaObject = new PageMetaModel();
        // die('here');
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
            // 'about_banner_bg_img' => [
            //     'label' => 'Banner Background Image',
            //     'rules' => [
            //         'permit_empty', // Make this optional
            //         'is_image[about_banner_bg_img]',
            //         'mime_in[about_banner_bg_img,image/jpg,image/jpeg,image/gif,image/png,image/webp]',
            //         'max_size[about_banner_bg_img,1000]',
            //     ],
            //     'errors' => [
            //         'is_image'  => lang('App.is_image', ['file' => 'jpg, jpeg, gif, png, webp']),
            //         'mime_in'   => lang('App.mime_in', ['file' => 'jpg, jpeg, gif, png, webp']),
            //         'max_size'  => lang('App.max_size', ['size' => '1000']),
            //     ],
            // ],
            // 'about_banner_img' => [
            //     'label' => 'Banner Image',
            //     'rules' => [
            //         'permit_empty', // Make this optional
            //         'is_image[about_banner_img]',
            //         'mime_in[about_banner_img,image/jpg,image/jpeg,image/gif,image/png,image/webp]',
            //         'max_size[about_banner_img,1000]',
            //     ],
            //     'errors' => [
            //         'is_image'  => lang('App.is_image', ['file' => 'jpg, jpeg, gif, png, webp']),
            //         'mime_in'   => lang('App.mime_in', ['file' => 'jpg, jpeg, gif, png, webp']),
            //         'max_size'  => lang('App.max_size', ['size' => '1000']),
            //     ],
            // ],
            // 'country_section_banner_img' => [
            //     'label' => 'Banner Image',
            //     'rules' => [
            //         'permit_empty', // Make this optional
            //         'is_image[country_section_banner_img]',
            //         'mime_in[country_section_banner_img,image/jpg,image/jpeg,image/gif,image/png,image/webp]',
            //         'max_size[country_section_banner_img,1000]',
            //     ],
            //     'errors' => [
            //         'is_image'  => lang('App.is_image', ['file' => 'jpg, jpeg, gif, png, webp']),
            //         'mime_in'   => lang('App.mime_in', ['file' => 'jpg, jpeg, gif, png, webp']),
            //         'max_size'  => lang('App.max_size', ['size' => '1000']),
            //     ],
            // ],
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
        // die('here');
        // Check if any file or text data is being uploaded
        $about_banner_title = $this->request->getVar('about_banner_title');
        $about_banner_bg_img = $this->request->getFile('about_banner_bg_img');
        $about_banner_bg_img_txt = $this->request->getVar('about_banner_bg_img');
        $about_banner_img = $this->request->getFile('about_banner_img');
        $about_banner_img_txt = $this->request->getVar('about_banner_img');
        $about_banner_desc = $this->request->getVar('about_banner_desc');
        $country_section_title = $this->request->getVar('country_section_title');
        $about_country_names = $this->request->getVar('about_country_names');
        $country_section_banner_img = $this->request->getFile('country_section_banner_img');
        $country_section_banner_img_txt = $this->request->getVar('country_section_banner_img');
        $about_hero_bg_img = $this->request->getFile('about_hero_bg_img');
        $about_hero_bg_img_txt = $this->request->getVar('about_hero_bg_img');
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
            // Remove spaces from the generated file name
            $uniqueName = str_replace(' ', '_', $uniqueName);
            // Optionally, remove special characters (if necessary)
            $uniqueName = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $uniqueName);
            if ($about_banner_bg_img->move(WRITEPATH . $frontendDir, $uniqueName)) {
                // Check if an existing record for this image already exists
                $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'about_banner_bg_img')->first();
                if ($ExistInfo) {
                    $existingFilePath = WRITEPATH . $frontendDir . $ExistInfo['meta_value'];
                    if (file_exists($existingFilePath) && !empty($ExistInfo['meta_value'])) {
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

        if (!empty($about_banner_bg_img_txt) && $about_banner_bg_img_txt == 'remove_image') {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'about_banner_bg_img')->first();
            $updateData = ['meta_value' => ''];  // Replace '' with the value you want to set
            $pageMetaObject->set($updateData)->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'about_banner_bg_img')->update();
        }
        if ($about_banner_img && $about_banner_img->isValid() && !$about_banner_img->hasMoved()) {
            $uniqueName = uniqid() . rand() . date('y_m_d_his') . '_' . $about_banner_img->getClientName();
            // Remove spaces from the generated file name
            $uniqueName = str_replace(' ', '_', $uniqueName);
            // Optionally, remove special characters (if necessary)
            $uniqueName = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $uniqueName);
            if ($about_banner_img->move(WRITEPATH . $frontendDir, $uniqueName)) {
                // Check if an existing record for this image already exists
                $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'about_banner_img')->first();
                if ($ExistInfo) {
                    $existingFilePath = WRITEPATH . $frontendDir . $ExistInfo['meta_value'];
                    if (file_exists($existingFilePath) && !empty($ExistInfo['meta_value'])) {
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
        if (!empty($about_banner_img_txt) && $about_banner_bg_img_txt == 'remove_image') {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'about_banner_img')->first();
            $updateData = ['meta_value' => ''];  // Replace '' with the value you want to set
            $pageMetaObject->set($updateData)->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'about_banner_img')->update();
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
            // Remove spaces from the generated file name
            $uniqueName = str_replace(' ', '_', $uniqueName);
            // Optionally, remove special characters (if necessary)
            $uniqueName = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $uniqueName);
            if ($country_section_banner_img->move(WRITEPATH . $frontendDir, $uniqueName)) {
                // Check if an existing record for this image already exists
                $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'country_section_banner_img')->first();
                if ($ExistInfo) {
                    $existingFilePath = WRITEPATH . $frontendDir . $ExistInfo['meta_value'];
                    if (file_exists($existingFilePath) && !empty($ExistInfo['meta_value'])) {
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
        if (!empty($country_section_banner_img_txt) && $country_section_banner_img_txt == 'remove_image') {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'country_section_banner_img')->first();
            $updateData = ['meta_value' => ''];  // Replace '' with the value you want to set
            $pageMetaObject->set($updateData)->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'country_section_banner_img')->update();
        }
        if ($about_hero_bg_img && $about_hero_bg_img->isValid() && !$about_hero_bg_img->hasMoved()) {
            $uniqueName = uniqid() . rand() . date('y_m_d_his') . '_' . $about_hero_bg_img->getClientName();
            // Remove spaces from the generated file name
            $uniqueName = str_replace(' ', '_', $uniqueName);
            // Optionally, remove special characters (if necessary)
            $uniqueName = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $uniqueName);
            if ($about_hero_bg_img->move(WRITEPATH . $frontendDir, $uniqueName)) {
                // Check if an existing record for this image already exists
                $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'about_hero_bg_img')->first();
                if ($ExistInfo) {
                    $existingFilePath = WRITEPATH . $frontendDir . $ExistInfo['meta_value'];
                    if (file_exists($existingFilePath) && !empty($ExistInfo['meta_value'])) {
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
        if (!empty($about_hero_bg_img_txt) && $about_hero_bg_img_txt == 'remove_image') {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'about_hero_bg_img')->first();
            $updateData = ['meta_value' => ''];  // Replace '' with the value you want to set
            $pageMetaObject->set($updateData)->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'about_hero_bg_img')->update();
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
                if ($meta['meta_key'] == 'third_tab_free_features_desc') {
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
                $metaData['advertisemnet_base_url'] = base_url() . 'uploads/';
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
        if (isset($pageId) && !empty($pageId)) {
            //  updation
        } else {
            $save_data = [
                // 'user_id'       => auth()->id(),
                'title'         => $this->request->getVar("title"),
                'content'       => $this->request->getVar("content"),
                'language'      => $this->request->getVar("lang_id"),
                'status'        => $this->request->getVar("status"),
                'slug'        => $this->request->getVar("slug"),
                'page_type'        => $this->request->getVar("page_type"),
            ];
            $errors = [];
            foreach ($save_data as $field => $value) {
                if (empty($this->request->getVar($field))) {
                    $errors[] = lang('App.page_required', ['field' => $field . '*']);
                }
            }
            if (isset($errors) && !empty($errors)) {
                return $this->respondCreated([
                    "status" => false,
                    "message" => lang('App.invalidParam'),
                    "data" => ['errors' => $errors]
                ]);
            }
            $ad_types = $this->request->getVar("ad_types");
            if (isset($ad_types) && !empty($ad_types)) {
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
            $pageId = $this->pageModel->save($save_data);
            // echo '<pre>'; print_r($save_data); die;
            if ($pageId) {
                $pageId = $this->pageModel->db->insertID();
                // echo $pageId; die(' pageId');
                $savedArr[] = $save_data;
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
            }
        }
        // echo $pageId; die(' $pageId'); 
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
        /* ##### Third Tab ##### */
        $third_tab_monthly_label = $this->request->getVar('third_tab[monthly_label]');
        $third_tab_yearly_label = $this->request->getVar('third_tab[yearly_label]');
        $third_tab_plan_name = $this->request->getVar('third_tab[plan_name]');
        $third_tab_monthly_price = $this->request->getVar('third_tab[monthly_price]');
        $third_tab_monthly_price_currency = $this->request->getVar('third_tab[monthly_price_currency]');
        $third_tab_yearly_price = $this->request->getVar('third_tab[yearly_price]');
        $third_tab_yearly_price_currency = $this->request->getVar('third_tab[yearly_price_currency]');
        $third_tab_free_features_title = $this->request->getVar('third_tab[free_features_title]');
        $third_tab_free_features_sub_title = $this->request->getVar('third_tab[free_features_sub_title]');
        $third_tab_free_features_desc = $this->request->getVar('third_tab[free_features_desc]');
        $third_tab_btn_txt = $this->request->getVar('third_tab[btn_txt]');
        $savedArr = array();
        $saveData = ['page_id' => $pageId, 'lang_id' => $lang_id];
        if ($pricing_bnner_img && $pricing_bnner_img->isValid() && !$pricing_bnner_img->hasMoved()) {
            $uniqueName = uniqid() . rand() . date('y_m_d_his') . '_' . $pricing_bnner_img->getClientName();
            if ($pricing_bnner_img->move(WRITEPATH . $frontendDir, $uniqueName)) {
                // Check if an existing record for this image already exists
                $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'pricing_bnner_img')->first();
                if ($ExistInfo) {
                    $existingFilePath = WRITEPATH . $frontendDir . $ExistInfo['meta_value'];
                    if (file_exists($existingFilePath) && !empty($ExistInfo['meta_value'])) {
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
        // Third #######
        if ($third_tab_monthly_label) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'third_tab_monthly_label')->first();
            $saveData['meta_key'] = 'third_tab_monthly_label';
            $saveData['meta_value'] = $third_tab_monthly_label;
            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($third_tab_yearly_label) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'third_tab_yearly_label')->first();
            $saveData['meta_key'] = 'third_tab_yearly_label';
            $saveData['meta_value'] = $third_tab_yearly_label;
            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($third_tab_plan_name) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'third_tab_plan_name')->first();
            $saveData['meta_key'] = 'third_tab_plan_name';
            $saveData['meta_value'] = $third_tab_plan_name;

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($third_tab_monthly_price) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'third_tab_monthly_price')->first();
            $saveData['meta_key'] = 'third_tab_monthly_price';
            $saveData['meta_value'] = $third_tab_monthly_price;

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($third_tab_monthly_price_currency) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'third_tab_monthly_price_currency')->first();
            $saveData['meta_key'] = 'third_tab_monthly_price_currency';
            $saveData['meta_value'] = $third_tab_monthly_price_currency;

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($third_tab_yearly_price) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'third_tab_yearly_price')->first();
            $saveData['meta_key'] = 'third_tab_yearly_price';
            $saveData['meta_value'] = $third_tab_yearly_price;

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($third_tab_yearly_price_currency) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'third_tab_yearly_price_currency')->first();
            $saveData['meta_key'] = 'third_tab_yearly_price_currency';
            $saveData['meta_value'] = $third_tab_yearly_price_currency;

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($third_tab_free_features_title) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'third_tab_free_features_title')->first();
            $saveData['meta_key'] = 'third_tab_free_features_title';
            $saveData['meta_value'] = $third_tab_free_features_title;

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($third_tab_free_features_sub_title) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'third_tab_free_features_sub_title')->first();
            $saveData['meta_key'] = 'third_tab_free_features_sub_title';
            $saveData['meta_value'] = $third_tab_free_features_sub_title;

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($third_tab_free_features_desc) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'third_tab_free_features_desc')->first();
            if (is_array($third_tab_free_features_desc)) {
                $third_tab_free_features_desc = implode('@array_seprator', $third_tab_free_features_desc);
            }
            $saveData['meta_key'] = 'third_tab_free_features_desc';
            $saveData['meta_value'] = $third_tab_free_features_desc;

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($third_tab_btn_txt) {
            $btnUserInfo = $pageMetaObject->where('page_id', $pageId)->where('meta_key', 'third_tab_btn_txt')->first();
            $saveData['meta_key'] = 'third_tab_btn_txt';
            $saveData['meta_value'] = $third_tab_btn_txt;

            if ($btnUserInfo) {
                $saveData['id'] = $btnUserInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }

        $returnArr = [];
        if (!empty($savedArr)) {
            foreach ($savedArr as $key => $valueArr) {
                if ($valueArr['meta_key'] == 'first_tab_free_features_desc') {
                    $valueArr['meta_value'] = explode('@array_seprator', $valueArr['meta_value']);
                } else if ($valueArr['meta_key'] == 'sec_tab_free_features_desc') {
                    $valueArr['meta_value'] = explode('@array_seprator', $valueArr['meta_value']);
                } else if ($valueArr['meta_key'] == 'third_tab_free_features_desc') {
                    $valueArr['meta_value'] = explode('@array_seprator', $valueArr['meta_value']);
                } else {
                    if (isset($valueArr) && is_array($valueArr)) {
                        foreach ($valueArr as $field_key => $field_value) {
                            $returnArr[$field_key] = $field_value;
                        }
                    }
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
                if ($meta['meta_key'] == 'third_tab_free_features_desc') {
                    $meta['meta_value'] = explode('@array_seprator', $meta['meta_value']);
                }
                if ($meta['meta_key'] == 'faq_first_btn_content') {
                    $meta['meta_value'] = unserialize($meta['meta_value']);
                }
                if ($meta['meta_key'] == 'faq_sec_btn_content') {
                    $meta['meta_value'] = unserialize($meta['meta_value']);
                }
                if ($meta['meta_key'] == 'faq_third_btn_content') {
                    $meta['meta_value'] = unserialize($meta['meta_value']);
                }
                // die('Here');
                if ($meta['meta_key'] == 'feature_sctn') {
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
                $metaData['advertisemnet_base_url'] = base_url() . 'uploads/';
            }
            $metaData['base_url'] = base_url() . $frontendDir; // return base Url 
            if (isset($searchParams['testmode']) && !empty($searchParams['testmode'])) {
                $tabs = $metaData['tabs_data'];
                $metaData = [];
                $metaData['tabs_data'] = $tabs;
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
        // echo $pageId; die;
        // if (empty($pageId) || !is_numeric($pageId) || empty($lang_id) || !is_numeric($lang_id)) {
        //     return $this->respondCreated([
        //         "status" => false,
        //         "message" => lang('App.invalidParam'),
        //         "data" => []
        //     ]);
        // }
        if (isset($pageId) && !empty($pageId)) {
            // do update  
        } else {
            $save_data = [
                // 'user_id'       => auth()->id(),
                'title'         => $this->request->getVar("title"),
                'content'       => $this->request->getVar("content"),
                'language'      => $this->request->getVar("lang_id"),
                'status'        => $this->request->getVar("status"),
                'slug'        => $this->request->getVar("slug"),
                'page_type'        => $this->request->getVar("page_type"),
            ];
            $errors = [];
            foreach ($save_data as $field => $value) {
                if (empty($this->request->getVar($field))) {
                    $errors[] = lang('App.page_required', ['field' => $field . '*']);
                }
            }
            if (isset($errors) && !empty($errors)) {
                return $this->respondCreated([
                    "status" => false,
                    "message" => lang('App.invalidParam'),
                    "data" => ['errors' => $errors]
                ]);
            }
            $ad_types = $this->request->getVar("ad_types");
            if (isset($ad_types) && !empty($ad_types)) {
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
            $pageId = $this->pageModel->save($save_data);
            // echo '<pre>'; print_r($save_data); die;
            if ($pageId) {
                $pageId = $this->pageModel->db->insertID();
                // echo $pageId; die(' pageId');
                $savedArr[] = $save_data;
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
            }
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
        $faq_banner_img_txt = $this->request->getVar('faq_banner_img');
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

        if (empty($faq_banner_img) && !empty($faq_banner_img_txt) && $faq_banner_img_txt == 'remove_image') {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'faq_banner_img')->first();
            $updateData = ['meta_value' => ''];  // Replace '' with the value you want to set
            $pageMetaObject->set($updateData)->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'faq_banner_img')->update();
        }
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
                    if (file_exists($existingFilePath) && !empty($ExistInfo['meta_value'])) {
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
            $saveData['meta_value'] = json_encode($mineArr);

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
            $saveData['meta_value'] = json_encode($mineArr);

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
            $saveData['meta_value'] = json_encode($mineArr);

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
                    $valueArr['meta_value'] = json_decode($valueArr['meta_value']);
                } else if ($valueArr['meta_key'] == 'faq_sec_btn_content') {
                    $valueArr['meta_value'] = json_decode($valueArr['meta_value']);
                } else if ($valueArr['meta_key'] == 'faq_third_btn_content') {
                    $valueArr['meta_value'] = json_decode($valueArr['meta_value']);
                }
                if (!isset($valueArr['meta_key']) && is_array($valueArr)) {
                    foreach ($valueArr as $field_key => $field_value) {
                        $returnArr[$field_key] = $field_value;
                    }
                }
                $returnArr[$valueArr['meta_key']] = $valueArr['meta_value'];
            }
        }
        // Prepare response
        return $this->respondCreated([
            "status" => true,
            "message" => lang('App.faq_page_updatedSuccess'),
            "data" => $returnArr
        ]);
    }
    public function addContentPage()
    {
        $frontendDir = $this->frontendDir;
        $pageMetaObject = new PageMetaModel();

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale($currentLang);
        // echo '<pre>'; print_r($this->request->getVar()); die;
        $pageId = $this->request->getVar("page_id");
        $lang_id = $this->request->getVar("lang_id");
        $savedArr = array();

        if (isset($pageId) && !empty($pageId)) {
            //  updation
        } else {
            $save_data = [
                'user_id'       => auth()->id(),
                'title'         => $this->request->getVar("title"),
                'content'       => $this->request->getVar("content"),
                'language'      => $this->request->getVar("language"),
                // 'status'        => $this->request->getVar("status"),
                'status'        => 'draft',
                'slug'        => $this->request->getVar("slug"),
                'page_type'        => $this->request->getVar("page_type"),
            ];
            $ad_types = $this->request->getVar("ad_types");
            if (isset($ad_types) && !empty($ad_types)) {
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
            $pageId = $this->pageModel->save($save_data);
            if ($pageId) {
                $pageId = $this->pageModel->db->insertID();
                $savedArr[] = $save_data;
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
            }
        }
        // Define validation rules (fields are optional, not required)
        $validationRules = [
            // 'banner_img' => [
            //     'label' => 'Banner Image',
            //     'rules' => [
            //         'is_image[banner_img]',
            //         'mime_in[banner_img,image/jpg,image/jpeg,image/gif,image/png,image/webp]',
            //         'max_size[banner_img,1000]',
            //     ],
            //     'errors' => [
            //         'is_image'  => lang('App.is_image', ['file' => 'jpg, jpeg, gif, png, webp']),
            //         'mime_in'   => lang('App.mime_in', ['file' => 'jpg, jpeg, gif, png, webp']),
            //         'max_size'  => lang('App.max_size', ['size' => '1000']),
            //     ],
            // ],
            'banner_title' => [
                'label' => 'Banner Title',
                'rules' => 'min_length[5]', // Optional text
                'errors' => [
                    'max_length' => lang('App.min_length', ['length' => 5]),
                ],
            ],
            // 'page_content' => [
            //     'label' => 'Page Content',
            //     'rules' => 'min_length[10]', // Optional text
            //     'errors' => [
            //         'max_length' => lang('App.min_length', ['length' => 100]),
            //     ],
            // ]
        ];
        // Validate input
        if (!$this->validateData($this->request->getPost(), $validationRules)) {
            $errors = $this->validator->getErrors();
            return $this->respond([
                "status" => false,
                "message" => lang('App.contentpage_updatedfailure'),
                "data" => ['errors' => $errors]
            ]);
        }
        // Check if any file or text data is being uploaded
        $banner_img = $this->request->getFile('banner_img');
        $banner_img_txt = $this->request->getVar('banner_img');

        $banner_title = $this->request->getVar('banner_title');
        $page_content = $this->request->getVar('page_content');
        if (isset($banner_title) && trim($banner_title) == 'Test_Cookie') {

            echo '<pre>';
            print_r($page_content);
            die;
        }

        $saveData = ['page_id' => $pageId, 'lang_id' => $lang_id];
        if (!empty($banner_img_txt) && $banner_img_txt == 'remove_image') {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'banner_img')->first();
            $updateData = ['meta_value' => ''];  // Replace '' with the value you want to set
            $pageMetaObject->set($updateData)->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'banner_img')->update();
        }
        if ($banner_img && $banner_img->isValid() && !$banner_img->hasMoved()) {
            $uniqueName = uniqid() . rand() . date('y_m_d_his') . '_' . $banner_img->getClientName();
            // Remove spaces from the generated file name
            $uniqueName = str_replace(' ', '_', $uniqueName);
            // Optionally, remove special characters (if necessary)
            $uniqueName = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $uniqueName);
            if ($banner_img->move(WRITEPATH . $frontendDir, $uniqueName)) {
                // Check if an existing record for this image already exists
                $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'banner_img')->first();
                if ($ExistInfo) {
                    // If a file already exists, delete it
                    $existingFilePath = WRITEPATH . $frontendDir . $ExistInfo['meta_value'];
                    if (file_exists($existingFilePath) && !empty($ExistInfo['meta_value'])) {
                        // echo $existingFilePath; die;
                        unlink($existingFilePath);
                    }
                    $saveData['id'] = $ExistInfo['id']; // Update the existing record
                }
                // Save the new image path in the database
                $saveData['meta_key'] = 'banner_img';
                $saveData['meta_value'] = $uniqueName;
                $pageMetaObject->savePageMeta($saveData);

                $savedArr[] = $saveData;
            }
        }
        if ($banner_title) {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'banner_title')->first();
            $saveData['meta_key'] = 'banner_title';
            $saveData['meta_value'] = $banner_title;
            if ($ExistInfo) {
                $saveData['id'] = $ExistInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($page_content) {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'page_content')->first();
            $saveData['meta_key'] = 'page_content';
            $saveData['meta_value'] = $page_content;
            if ($ExistInfo) {
                $saveData['id'] = $ExistInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        $returnArr = [];
        if (!empty($savedArr)) {
            $serilized_fields = array();
            // $serilized_fields = array();
            // echo '<pre>'; print_r($savedArr); die;
            foreach ($savedArr as $key => $valueArr) {
                // $returnArr[$valueArr['meta_key']] = $valueArr['meta_value'];
                if (isset($valueArr) && !empty($valueArr['meta_key']) && !empty($valueArr['meta_value']) && in_array($valueArr['meta_key'], $serilized_fields)) {
                    $returnArr[$valueArr['meta_key']] = unserialize($valueArr['meta_value']);
                } else if (isset($valueArr) && !empty($valueArr['meta_key']) && !empty($valueArr['meta_value']) && $valueArr['meta_key'] == 'banner_imgs') {
                    $returnArr[$valueArr['meta_key']] = explode(',', $valueArr['meta_value']);
                } else {
                    // $returnArr[$valueArr['meta_key']] = $valueArr['meta_value']; 
                }
            }
        }
        // Prepare response
        return $this->respondCreated([
            "status" => true,
            "message" => lang('App.content_updatedSuccess'),
            "data" => $returnArr
        ]);
    }


    public function getFrontendData($lang_id = null)
    {
        $frontendDir = $this->frontendDir;
        $pageMetaObject = new PageMetaModel();
        $pageModel = new PageModel();
        $selectedFields = ['id', 'title',  'slug', 'language'];
        $pages = $pageModel->select($selectedFields)->where('language', $lang_id)->findAll();
        if (isset($pages) && !empty($pages)) {
            $response = [
                'status' => true,
                'message' => lang('App.dataFound'),
                'data' => ['pages' => $pages]
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

    public function getFrontendPages()
    {
        // die('~~~~~~~~~~~~~');
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));
        $this->pageModel = new PageModel();
        $url  = $this->request->getServer('REQUEST_URI');
        $params = getQueryString($url);

        $imagePath = base_url() . 'uploads/';
        $pages = $this->pageModel
            ->select('id, title, slug , language, page_type');
        // echo '<pre>'; print_r($params); die;
        if ($params && !empty($params['lang_id'])) {
            // Use 'where' instead of 'like' for exact matching
            $pages->where('language', $params['lang_id']);
        }

        $pages = $pages->orderBy('id', 'DESC')->findAll();

        // echo ' >>>>>>>>>>>>>>>>>>>> getLastQuery >>>>>> '. $this->db->getLastQuery();     die;

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

    public function getFullPageById($pageId = null)
    {
        // die('here');
        $frontendDir = $this->frontendDir;
        $pageMetaObject = new PageMetaModel();
        $pageModel = new PageModel();
        $pageArr = $pageModel->getPageIdByID($pageId);
        // echo '<pre>'; print_r($pageArr); die;
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
        $lang_id = '';
        if (isset($searchParams['lang_id']) && !empty($searchParams['lang_id'])) {
            $lang_id = $searchParams['lang_id'];
        }
        $slug = '';
        if (isset($searchParams['slug']) && !empty($searchParams['slug'])) {
            $slug = $searchParams['slug'];
        }
        $is_error = false;
        if (empty($slug) || empty($lang_id) || !is_numeric($lang_id)) {
            $is_error = true;
        }

        $allPageMeta = $pageMetaObject->getFullPageMetaDataById($pageId);
        if (isset($searchParams['testmode']) && !empty($searchParams['testmode'])) {
            // $lang_id = $searchParams['lang_id'];
            // echo '>>>>>>>>>>>>>>>>>> ' . $this->db->getLastQuery(); exit();
            // echo '<pre>'; print_r($pageArr); die;
            echo '<pre>';
            print_r(json_decode($allPageMeta[11]['meta_value']));
            // print_r(json_decode($allPageMeta[12]['meta_value']));
            die;
            // echo json_encode($allPageMeta); die;
        }
        if (isset($allPageMeta) && !empty($allPageMeta)) {
            // Initialize an empty array to hold the structured data
            $metaData = [];
            $explode_fields_special = array('first_tab_free_features_desc', 'sec_tab_free_features_desc', 'third_tab_free_features_desc');
            $serilized_fields = array('news_section', 'feature_sctn');
            $json_fields = array('pricing_tab', 'club_nd_scout_section', 'talent_section', 'faq_first_btn_content', 'faq_sec_btn_content', 'faq_third_btn_content');
            // Loop through each meta entry and map meta_key to meta_value
            foreach ($allPageMeta as $meta) {
                $tabsArr = array();
                if (isset($pageArr['language']) && $pageArr['language'] == $meta['lang_id']) {
                    if ($meta['meta_key'] == 'tabs_data') {
                        $tabData = json_decode($meta['meta_value'], true);
                        if (isset($tabData['first_tab']) && !empty($tabData['first_tab'])) {
                            $firstTabItems = $tabData['first_tab'];
                            foreach ($firstTabItems as $item) {
                                if (!empty($item['images'])) {
                                    $item['images'] = explode(',', $item['images']);
                                } else {
                                    $item['images'] = '';
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
                                if (!empty($item['images'])) {
                                    $item['images'] = explode(',', $item['images']);
                                } else {
                                    $item['images'] = '';
                                }
                                $tabsArr["second_tab"][] = [
                                    "title" => $item['title'],
                                    "desc" => $item['desc'],
                                    "images" => $item['images'] // Split images into an array
                                ];
                            }
                        }
                        if (isset($tabData['title']) && !empty($tabData['title'])) {
                            $tabsArr['title'] = $tabData['title'];
                        }
                        if (isset($tabData['first_btn_txt']) && !empty($tabData['first_btn_txt'])) {
                            $tabsArr['first_btn_txt'] = $tabData['first_btn_txt'];
                        }
                        if (isset($tabData['sec_btn_txt']) && !empty($tabData['sec_btn_txt'])) {
                            $tabsArr['sec_btn_txt'] = $tabData['sec_btn_txt'];
                        }
                        $meta['meta_value'] = $tabsArr;
                    }
                    if (in_array($meta['meta_key'], $explode_fields_special)) {
                        $meta['meta_value'] = explode('@array_seprator', $meta['meta_value']);
                    }
                    if (in_array($meta['meta_key'], $serilized_fields)) {
                        // $meta['meta_value'] = unserialize($meta['meta_value']);
                        if ($meta['meta_key'] == 'club_nd_scout_section') {
                            // echo '<pre>'; print_r($meta); die;
                            // $meta_meta_value = unserialize($meta['meta_value']);
                            $meta['meta_value'] = unserialize($meta['meta_value']);
                            // echo '<pre>'; print_r($meta_meta_value); die;
                            if (isset($meta['meta_value']['first_tab']['0'])) {
                                unset($meta['meta_value']['first_tab']['0']);
                            }
                            if (isset($meta['meta_value']['sec_tab']['0'])) {
                                unset($meta['meta_value']['sec_tab']['0']);
                            }
                            if (isset($meta['meta_value']['third_tab']['0'])) {
                                unset($meta['meta_value']['third_tab']['0']);
                            }
                        }
                        // else if ($meta['meta_key'] == 'talent_section') {
                        //     $meta['meta_value'] = json_decode($meta['meta_value']);
                        // } 
                        else if ($meta['meta_key'] == 'feature_sctn') {
                            $meta['meta_value'] = json_decode($meta['meta_value']);
                        } else {

                            // 
                            // $meta['meta_value'] = unserialize($meta['meta_value']);
                            $meta_value = $meta['meta_value'];
                            if (is_string($meta_value) && @unserialize($meta_value) === false && $meta_value !== 'b:0;') {
                                //  die('Heere');
                                // error_log("Unserialize failed for value: " . print_r($meta_value, true));
                                // $meta['meta_value'] = '';
                            } else {
                                $meta['meta_value'] = unserialize($meta_value);
                            }
                            if (isset($meta['meta_key']) && $meta['meta_key'] == 'feature_sctn') {
                                $returnArr = [];
                                $array = [];
                                $array = $meta['meta_value'];
                                if (!empty($meta['meta_value']) && !is_array($array)) {
                                    // echo '<pre>'; print_r($array); die;
                                    $array = @unserialize($array);
                                }
                                //   echo '<pre>'; print_r($array); die;
                                if (!empty($array)) {
                                    foreach ($array as $item) {
                                        // Check if 'icon' exists and is equal to 'remove_image'
                                        if (isset($item['icon']) && $item['icon'] === 'remove_image') {
                                            // Set the 'icon' value to null
                                            $item['icon'] = null;
                                        }
                                        $returnArr[] = $item;
                                    }
                                    $meta['meta_value'] = $returnArr;
                                }
                            }
                        }
                    }
                    if (in_array($meta['meta_key'], $json_fields)) {
                        // die('Here');
                        if ($meta['meta_key'] == 'club_nd_scout_section' || $meta['meta_key'] == 'talent_section') {
                            $arr = $meta['meta_value'];
                            $array = [];
                            // echo '<pre>'; print_r($arr); die;
                            $arr = json_decode($arr);

                            foreach ($arr as $key => $obj) {
                                $array[$key] = (array) $obj;
                            }
                            $meta['meta_value'] = json_encode($array);
                        }
                        $meta['meta_value'] = json_decode($meta['meta_value'], true);
                    }
                    if ($meta['meta_key'] == 'talent_section') {
                        // echo '<pre>'; print_r($arr); die;
                    }
                    if (isset($meta['meta_key']) && $meta['meta_key'] == 'banner_imgs' && !empty($meta['meta_value'])) {
                        $meta['meta_value'] = explode(',', $meta['meta_value']);
                    }
                    $metaData['pageData'][$meta['meta_key']] = $meta['meta_value'];
                }
            }

            $metaData['base_url'] = base_url() . $frontendDir; // return base Url 
            if (isset($pageArr) && !empty($pageArr['page_type'])) {
                $metaData['page_type'] = $pageArr['page_type'];
            }
            if (isset($pageArr) && !empty($pageArr['page_type'])) {
                $metaData['page_type'] = $pageArr['page_type'];
            }
            if (isset($pageArr) && !empty($pageArr['meta_title'])) {
                $metaData['meta_title'] = $pageArr['meta_title'];
            } else {
                $metaData['meta_title'] = '';
            }
            if (isset($pageArr) && !empty($pageArr['meta_description'])) {
                $metaData['meta_description'] = $pageArr['meta_description'];
            } else {
                $metaData['meta_description'] = '';
            }
            if (isset($pageArr) && !empty($pageArr['slug'])) {
                $metaData['slug'] = $pageArr['slug'];
            }
            if (isset($pageArr['page_type']) && strtolower($pageArr['page_type']) == 'news') {
                $blogModel = new BlogModel();

                $sliderNews = $blogModel->where('status', 'published')
                    ->where('language', $pageArr['language'])
                    ->orderBy('id', 'DESC')
                    ->findAll(3, 0);

                //$lang_id
                // if($sliderNews){
                //     $featured_images = array_map(function($item) {
                //         return $item['featured_image'];
                //     }, $sliderNews);
                //     $metaData['pageData']['slider_imgs'] = $featured_images;
                // }  
                if ($sliderNews) {
                    $metaData['newsSliderData'] = $sliderNews;
                }

                $latestNews = $blogModel->where('status', 'published')
                    ->where('language', $lang_id)
                    ->orderBy('id', 'DESC')
                    ->findAll(6, 3);

                if ($latestNews) {
                    $metaData['latestNewsData'] = $latestNews;
                }
                $metaData['news_img_path'] = base_url() . 'uploads/';
            }
            if (isset($metaData['pageData']['news_section'])) {
                unset($metaData['pageData']['news_section']);
            }
            // echo '>>>>>>>>>>>>>>>>>>>>>> ' . $this->db->getLastQuery();
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
                'data' => [$allPageMeta]
            ];
        }
        return $this->respondCreated($response);
    }
    /* ##### End About Controller Functions ##### */

    public function contactFormSubmit()
    {
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        if (is_numeric($currentLang)) {
            $this->request->setLocale(getLanguageCode($currentLang));
        } else {
            if (in_array($currentLang, array('it', 'fr', 'es', 'pt'))) {
                $currentLang = 'en';
            }
            $this->request->setLocale($currentLang);
        }
        $contactModel = new ContactModel();
        $data = [
            'name' => $this->request->getVar('name'),
            'email' => $this->request->getVar('email'),
            'phone' => $this->request->getVar('phone'),
            'message' => $this->request->getVar('message'),
            'domain' => $this->request->getVar('domain'),
        ];

        // Save data to the database
        // if (1 == 1) {
        if ($contactModel->insert($data)) {
            $response = [
                'status' => true,
                'message' => lang('App.contactFormSuccess'),
                'data' => ['class' => 'contact-success', 'redirect_url' => 'thank-you']
            ];
            // return $this->response->setJSON(['status' => 'success', 'message' => 'Contact saved successfully']);
        } else {
            $response = [
                'status' => false,
                'message' => lang('App.contactFormError'),
                'data' => ['class' => 'contact-error', 'redirect_url' => 'error']
            ];
            // return $this->response->setJSON(['status' => 'error', 'message' => 'Failed to save contact']);
        }
        return $this->respondCreated($response);
    }
    public function playerList()
    {
        $frontendDir = $this->frontendDir;
        $pageMetaObject = new PageMetaModel();
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale($currentLang);
        $pageId = $this->request->getVar("page_id");
        $lang_id = $this->request->getVar("lang_id");
        $savedArr = array();
        $msg = 'Player List Page has been added successfully';
        if (isset($pageId) && !empty($pageId)) {
            $msg = 'Player List Page has been updated successfully';
            //  updation
        } else {
            $save_data = [
                'user_id'       => auth()->id(),
                'title'         => $this->request->getVar("title"),
                'content'       => $this->request->getVar("content"),
                'language'      => $this->request->getVar("language"),
                // 'status'        => $this->request->getVar("status"),
                'status'        => 'draft',
                'slug'        => $this->request->getVar("slug"),
                'page_type'        => $this->request->getVar("page_type"),
            ];
            $ad_types = $this->request->getVar("ad_types");
            if (isset($ad_types) && !empty($ad_types)) {
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
            $pageId = $this->pageModel->save($save_data);
            if ($pageId) {
                $pageId = $this->pageModel->db->insertID();
                $savedArr[] = $save_data;
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
            }
        }
        $banner_title = $this->request->getVar('banner_title');
        $talent_btn_txt = $this->request->getVar('talent_btn_txt');
        $club_btn_txt = $this->request->getVar('club_btn_txt');
        $scout_btn_txt = $this->request->getVar('scout_btn_txt');
        if ($banner_title) {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'banner_title')->first();
            $saveData['meta_key'] = 'banner_title';
            $saveData['meta_value'] = $banner_title;
            if ($ExistInfo) {
                $saveData['id'] = $ExistInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($talent_btn_txt) {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'talent_btn_txt')->first();
            $saveData['meta_key'] = 'talent_btn_txt';
            $saveData['meta_value'] = $talent_btn_txt;
            if ($ExistInfo) {
                $saveData['id'] = $ExistInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($club_btn_txt) {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'club_btn_txt')->first();
            $saveData['meta_key'] = 'club_btn_txt';
            $saveData['meta_value'] = $club_btn_txt;
            if ($ExistInfo) {
                $saveData['id'] = $ExistInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }
        if ($scout_btn_txt) {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'scout_btn_txt')->first();
            $saveData['meta_key'] = 'scout_btn_txt';
            $saveData['meta_value'] = $scout_btn_txt;
            if ($ExistInfo) {
                $saveData['id'] = $ExistInfo['id'];
            }
            $pageMetaObject->savePageMeta($saveData);
            $savedArr[] = $saveData;
        }

        $returnArr = [];
        if (!empty($savedArr)) {
            foreach ($savedArr as $key => $valueArr) {
                if (isset($valueArr['meta_key']) && !empty($valueArr['meta_value'])) {
                    $returnArr[$valueArr['meta_key']] = $valueArr['meta_value'];
                }
                if (!isset($valueArr['meta_key']) && is_array($valueArr)) {
                    foreach ($valueArr as $field_key => $field_value) {
                        $returnArr[$field_key] = $field_value;
                    }
                }
            }
        }
        // Prepare response
        return $this->respondCreated([
            "status" => true,
            "message" => $msg,
            // "message" => lang('App.faq_page_updatedSuccess'),
            "data" => $returnArr
        ]);
    }

    public function getPageByType()
    {
        $frontendDir = $this->frontendDir;
        $pageMetaObject = new PageMetaModel();
        $pageModel = new PageModel();
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
        $lang_id = '';
        if (isset($searchParams['lang_id']) && !empty($searchParams['lang_id'])) {
            $lang_id = $searchParams['lang_id'];
        }
        $page_type = '';
        if (isset($searchParams['page_type']) && !empty($searchParams['page_type'])) {
            $page_type = $searchParams['page_type'];
        }
        // echo $page_type; die;
        $is_error = false;
        if (empty($page_type) || empty($lang_id) || !is_numeric($lang_id)) {
            $is_error = true;
        }
        if (!empty($is_error)) {
            return $this->respondCreated([
                "status" => false,
                "message" => lang('App.invalidParam'),
                "data" => []
            ]);
        }
        $slug = '';

        // $pageArr =  $pageModel->getPageIdBySlug($slug);
        $pageArr = $pageModel->getPageIdByPageType($page_type, $lang_id);
        // pr($pageArr);

        // echo '<'
        if (isset($pageArr['slug']) && !empty($pageArr['slug'])) {
            $slug = $pageArr['slug'];
        }
        $page_type = '';
        if (isset($pageArr['page_type']) && !empty($pageArr['page_type'])) {
            $page_type = $pageArr['page_type'];
        }
        // $pageArr['id'] = rand(1, 10);
        // echo '<pre>'; print_r($pageArr); die;
        if (isset($pageArr['id']) && !empty($pageArr['id']) && is_numeric($pageArr['id'])) {
            $pageId = $pageArr['id'];
        } else {
            return $this->respondCreated([
                "status" => false,
                "message" => lang('App.invalidParam'),
                "data" => []
            ]);
        }

        // echo $pageId; die;
        $allPageMeta = $pageMetaObject->getFullPageMetaData($pageId, $lang_id);

        // echo '>>>>>>>>>>>>>>>>>> ' . $this->db->getLastQuery(); //exit();
        // pr($allPageMeta);
        if (isset($allPageMeta) && !empty($allPageMeta)) {
            // Initialize an empty array to hold the structured data
            $metaData = [];
            $explode_fields_special = array('first_tab_free_features_desc', 'sec_tab_free_features_desc', 'third_tab_free_features_desc');
            $serilized_fields = array('news_section');
            // $serilized_fields = array('faq_first_btn_content', 'faq_sec_btn_content', 'faq_third_btn_content', 'news_section', 'talent_section');
            // $json_fields = array('tabs_data', 'pricing_tab', 'feature_sctn','club_nd_scout_section');
            $json_fields = array('tabs_data', 'pricing_tab', 'feature_sctn', 'club_nd_scout_section', 'talent_section', 'faq_first_btn_content', 'faq_sec_btn_content', 'faq_third_btn_content');
            // Loop through each meta entry and map meta_key to meta_value
            foreach ($allPageMeta as $meta) {
                if (isset($pageArr['language']) && $pageArr['language'] == $meta['lang_id']) {
                    if (in_array($meta['meta_key'], $explode_fields_special)) {
                        $meta['meta_value'] = explode('@array_seprator', $meta['meta_value']);
                    }
                    if (in_array($meta['meta_key'], $serilized_fields)) {
                        if ($meta['meta_key'] == 'club_nd_scout_section') {
                            // echo '<pre>'; print_r($meta); die;
                            // $meta_meta_value = unserialize($meta['meta_value']);
                            $meta['meta_value'] = unserialize($meta['meta_value']);
                            // echo '<pre>'; print_r($meta_meta_value); die;
                            if (isset($meta['meta_value']['first_tab']['0'])) {
                                unset($meta['meta_value']['first_tab']['0']);
                            }
                            if (isset($meta['meta_value']['sec_tab']['0'])) {
                                unset($meta['meta_value']['sec_tab']['0']);
                            }
                            if (isset($meta['meta_value']['third_tab']['0'])) {
                                unset($meta['meta_value']['third_tab']['0']);
                            }
                        } else if ($meta['meta_key'] == 'talent_section') {
                            // $meta['meta_value'] = json_decode($meta['meta_value'], true);
                            $meta['meta_value'] = json_decode($meta['meta_value'], JSON_FORCE_OBJECT);
                            // echo '<pre>'; print_r($meta); die;
                        } else {
                            $meta_value = $meta['meta_value'];
                            if (is_string($meta_value) && @unserialize($meta_value) === false && $meta_value !== 'b:0;') {
                                //  die('Heere');
                                error_log("Unserialize failed for value: " . print_r($meta_value, true));
                                $meta['meta_value'] = '';
                            } else {
                                $meta['meta_value'] = unserialize($meta_value);
                            }
                        }
                    }
                    // tabs_data
                    if (in_array($meta['meta_key'], $json_fields)) {

                        if ($meta['meta_key'] == 'pricing_tab') {
                            // $meta['meta_value'] = mb_convert_encoding($meta['meta_value'], 'UTF-8', 'auto');
                            // $meta['meta_value'] = json_decode($meta['meta_value'],JSON_PRETTY_PRINT);
                            // echo json_encode($formatted_plans, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);  
                            // echo json_encode($meta['meta_value'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                            // die;
                            // echo '<pre>'; print_r(html_entity_decode(utf8_encode($meta['meta_value']))); die;
                        } else if ($meta['meta_key'] == 'talent_section' && strtolower($page_type) == 'talent') {
                            // $meta['meta_value'] = json_decode($meta['meta_value'], true);
                            // $meta['meta_value'] = $meta['meta_value'];

                            // echo '<pre>'; print_r($meta); die;
                            // $meta['meta_value'] = json_encode($meta['meta_value']);
                            if (!empty($meta['meta_value'])) {
                                // $meta['meta_value'] = json_decode($meta['meta_value']);
                                // $arr = (array) $meta['meta_value'];
                                $arr =  $meta['meta_value'];
                                // $json_string = json_encode($arr);
                                $arr = json_decode($arr, true);
                                // echo '<pre>'; print_r($arr); die('dsff');
                                $array = [];

                                foreach ($arr as $key => $obj) {
                                    $array[$key] = (array) $obj;
                                }
                                $meta['meta_value'] = json_encode($array);
                                // $array = json_decode(json_encode($arr), true);
                                // echo '<pre>'; print_r($array); die('dsff');
                                // $meta['meta_value'] = json_encode($arr);
                                // echo '<pre>'; print_r(var_dump($array)); die;
                            }
                        }
                        $meta['meta_value'] = json_decode($meta['meta_value'], true);
                        // echo '<pre>'; print_r($meta['meta_value']); die;
                    }
                    if (isset($meta['meta_key']) && $meta['meta_key'] == 'about_country_names' && !empty($meta['meta_value'])) {
                        $meta['meta_value'] = explode(',', $meta['meta_value']);
                    }
                    $metaData['pageData'][$meta['meta_key']] = $meta['meta_value'];
                }
            }
            if (isset($metaData['pageData']['talent_section']) && !empty($metaData['pageData']['talent_section'])) {
                // $metaData['pageData']['talent_section'] =  $metaData['pageData']['talent_section'];
                // echo '<pre>'; print_r($metaData['pageData']); die;
            }
            // echo '<<<<<<<<<<<< '.$is_show_slider.' >>>>>>>>>>>>>>>>>>'; die;
            if (strtolower($page_type) == 'home') {
                $frontEndUsers = $this->getUserForFronend('home_page');
                if (isset($frontEndUsers) && !empty($frontEndUsers)) {
                    $metaData['sliderData'] = $frontEndUsers;
                }
            }

            if (strtolower($page_type) == 'news') {
                $blogModel = new BlogModel();

                $sliderNews = $blogModel->where('status', 'published')
                    ->where('language', $lang_id)
                    ->orderBy('id', 'DESC')
                    ->findAll(3, 0);
                //$lang_id

                if ($sliderNews) {
                    $metaData['newsSliderData'] = $sliderNews;
                }

                $latestNews = $blogModel->where('status', 'published')
                    ->where('language', $lang_id)
                    ->orderBy('id', 'DESC')
                    ->findAll(6, 3);

                if ($latestNews) {
                    $metaData['latestNewsData'] = $latestNews;
                }
                $metaData['news_img_path'] = base_url() . 'uploads/';
            }

            // START: CODE ADDED by PRATIBHA on jan 06, 2025
            $advertisemnetData = [];
            $this->advertisementModel = new AdvertisementModel();
            $clean_ad_types = str_replace(["[", "]"], "", $pageArr['ad_types']);
            $adTypeAr =  explode(",", $clean_ad_types);
            // $pageTypeAllAds = [];
            $pageTypeAllAds = $this->adsTypes($pageId);
            if ($adTypeAr) {
                foreach ($adTypeAr as $key => $adType) {

                    $adType = trim($adType, '"');
                    $builder = $this->advertisementModel->builder();
                    $builder->select('advertisements.*, p.title as page_name');
                    $builder->join('pages p', 'p.id = advertisements.page_id', 'INNER');
                    $builder->where('advertisements.page_id', $pageId);
                    $builder->where('advertisements.status', 'published');
                    $builder->where('advertisements.type', $adType);
                    $builder->orderBy('id', 'ASC');
                    $adQuery = $builder->get();
                    $advertisements = $adQuery->getResultArray();
                    // echo ' >>>>>>>>>>>>> ad data >>>>>>>>>>>>>  ' . $this->db->getLastQuery();
                    if ($advertisements) {
                        foreach ($advertisements as $ad) {
                            $adKey = array_search($ad['type'], ADVERTISEMENT_TYPES);
                            if (isset($searchParams['testmode']) && !empty($searchParams['testmode'])) {
                                $adsKeys = array_keys(ADVERTISEMENT_TYPES);
                                // die('Here');
                                if (in_array($adKey, $adsKeys)) {

                                    $pageTypeAllAds = $this->adsTypes($pageId);
                                    // echo $adKey. ' Add Key'; die;
                                }
                                // else{
                                //     echo $adKey.' not exist'; die;
                                // }
                                // echo '<pre>';
                                // if (isset($ad['valid_to']) && !empty($ad['valid_to'])) {
                                //     $valid_to = $ad['valid_to'];
                                //     $currentDate = new \DateTime();
                                //     $expiryDate = new \DateTime($valid_to);

                                //     // Compare the current date with the expiry date
                                //     if ($currentDate > $expiryDate) {
                                //        // echo "The date has expired."; die;
                                //        $ad = array();
                                //     }
                                // }
                                // print_r($ad);
                                // die;
                            }



                            // Convert the expiry date into a DateTime object

                            $advertisemnetData[$adKey] = $ad;
                        }
                    } else {
                        $adKey = array_search($adType, ADVERTISEMENT_TYPES);
                        $advertisemnetData[$adKey] = [];
                    }
                }
                // $advertisemnetData[] = $ad;
            }
            // Set Advertisements For Pages according types
            $advertisemnetData = array_filter($advertisemnetData, function ($key) use ($pageTypeAllAds) {
                return in_array($key, $pageTypeAllAds);
            }, ARRAY_FILTER_USE_KEY);

            // Add missing keys from PageType with empty array if they don't exist in advertisement
            foreach ($pageTypeAllAds as $type) {
                if (!array_key_exists($type, $advertisemnetData)) {
                    $advertisemnetData[$type] = []; // Add the missing key with empty array
                }
            }
            // if (isset($searchParams['testmode']) && !empty($searchParams['testmode'])) { 
            //     $abc['Advertisement'] = $advertisemnetData;
            //     // adsTypes
            //     $abc['AdvertisementConstant'] = ADVERTISEMENT_TYPES;
            //     echo '<pre>'; print_r($abc); die;
            //     die;
            //     // ADVERTISEMENT_TYPES
            // }
            if (isset($searchParams['testmode']) && !empty($searchParams['testmode'])) {
                // // echo $adKey.'<br>';
                // $abc['AdvertisementConstant'] = ADVERTISEMENT_TYPES;
                // $abc['Advertisement'] = $advertisemnetData;
                // $abc['PageType'] = $pageTypeAllAds;
                // echo '<pre>'; print_r($abc); die;
                // Remove keys from advertisement that are not in PageType

                // echo '<pre>'; print_r($advertisemnetData); die;
            }

            // END: CODE ADDED by PRATIBHA on jan 06, 2025



            /* $this->advertisementModel = new AdvertisementModel();
            $builder = $this->advertisementModel->builder();
            $builder->select('advertisements.*, p.title as page_name');
            $builder->join('pages p', 'p.id = advertisements.page_id', 'INNER');

            if ($pageId && !empty($pageId)) {
                $builder->where('advertisements.page_id', $pageId);
                // $builder->where('advertisements.page_id', $pageId);
                $builder->where('advertisements.status', 'published');
            }
            //  $builder->where('advertisements.valid_to <=', date('Y-m-d'));  // Assuming you're using PHP's date function for current date

            $builder->orderBy('id', 'ASC');
            // $builder->orderBy('id', 'DESC');
            $adQuery = $builder->get();
            $advertisements = $adQuery->getResultArray();


            // pr($advertisements);
            if (isset($searchParams['testmode']) && !empty($searchParams['testmode'])) {
                // echo '>>>>>>>>>>>>>>>>>>>>>> ' . $this->db->getLastQuery();
                // die;
            }
            if (isset($advertisements) && !empty($advertisements)) {

                // $allAdsTypes = [
                //     '250 x 250 - Square',
                //     '200 x 200 - Small Square',
                //     '468 x 60 - Banner',
                //     '728 x 90 - Leaderboard',
                //     '300 x 250 - Inline Rectangle',
                //     '336 x 280 - Large Rectangle',
                //     '120 x 600 - Skyscraper',
                //     '160 x 600 - Wide Skyscraper'
                // ];
                // $adsByType = [];
                // $ads = $advertisements;
                // foreach ($ads as $ad) {
                //     $typeKey = str_replace([' - ', 'x'], ['', ''], $ad['type']); // Replace ' - ' with '_' and remove 'x'
                //     $typeKey = preg_replace('/\d+/', '', $typeKey);
                //     $typeKey = ltrim($typeKey, '_');
                //     $typeKey = strtolower($typeKey);
                //     $typeKey = trim($typeKey, ' ');
                //     $typeKey = str_replace(' ', '_', $typeKey);
                //     if (!isset($adsByType[$typeKey])) {
                //         $adsByType[$typeKey] = [];
                //     }
                //     if (isset($ad['featured_image']) && !empty($ad['featured_image'])) {

                //         // if(isset($ad['valid_to']) && !empty($ad['valid_to']) && $ad['valid_to'] < date('Y-m-d')){

                //         // }else{
                //         // }
                //     }
                //     $adsByType[$typeKey][] = $ad;
                // }
                $typeMapping = [
                    'Square' => 'Small_Square',
                    'Banner' => 'Banner',
                    'Leaderboard' => 'Leaderboard',
                    'Large Leaderboard' => 'Large_Leaderboard',
                    'Inline Rectangle' => 'Inline_Rectangle',
                    'Large Rectangle' => 'Large_Rectangle',
                    'Skyscraper' => 'Skyscraper',
                    'Wide Skyscraper' => 'Wide_Skyscraper'
                ];
                
                $categorizedAds = [];
                
                foreach ($advertisements as $ad) {
                    // Extract type
                    $type = $ad['type'];
                
                    foreach ($typeMapping as $key => $slug) {

                        // echo '>>>>> type >>> ' . $type . ' >>>>>>>> key >>>> ' . $key .' >>>>>>>>>>>>>> '. strpos($type, $key);
                        // echo '<br>';
                        if (strpos($type, $key) !== false) {
                            $categorizedAds[strtolower($slug)] = $ad;
                        } 
                        // else {
                        //     $categorizedAds[strtolower($slug)] = [];
                        // }
                    }
                }

                if(isset($searchParams['testmode']) && !empty($searchParams['testmode'])){
                    $categorizedAds['large_leaderboard'] = array();
                    $metaData['advertisemnetData'] = $categorizedAds;
                 }else {
                    $metaData['advertisemnetData'] = $advertisements;
                }

                // $metaData['advertisemnetData'] = $categorizedAds;


                $metaData['advertisemnet_base_url'] = base_url() . 'uploads/';
            } else {
                $metaData['advertisemnetData'] = [];
            } */

            $metaData['advertisemnetData'] = $advertisemnetData;
            $metaData['advertisemnet_base_url'] = base_url() . 'uploads/';



            $metaData['base_url'] = base_url() . $frontendDir; // return base Url 
            if (isset($pageArr['meta_description']) && !empty($pageArr['meta_description'])) {
                $metaData['pageData']['meta_description'] = $pageArr['meta_description'];
            }
            if (isset($pageArr['meta_title']) && !empty($pageArr['meta_title'])) {
                $metaData['pageData']['meta_title'] = $pageArr['meta_title'];
            }

            $response = [
                'status' => true,
                'message' => lang('App.dataFound'),
                'data' => $metaData
            ];
        } else {
            $response = [
                'status' => false,
                'message' => lang('App.noDataFound'),
                'data' => [$allPageMeta]
            ];
        }
        return $this->respondCreated($response);
    }


    public function getSingleNews($news_id = null)
    {
        $blogModel = new BlogModel();

        $news = $blogModel->where('status', 'published')
            ->where('id', $news_id)
            ->first();

        // $blogModel = new BlogModel();
        // echo '<pre>'; print_r($news); die;
        if ($news) {
            if (isset($news) && !empty($news['attachments'])) {
                $news['attachments'] = unserialize($news['attachments']);
            }
            $sliderNews = $blogModel->where('status', 'published')
                ->where('language', $news['language'])
                ->whereNotIn('id', [$news_id])
                ->orderBy('id', 'DESC')
                ->findAll(3, 0);
        } else {
            $sliderNews = array();
        }

        //$lang_id


        if ($news) {
            $response = [
                'status' => true,
                'message' => lang('App.dataFound'),
                'data' => ['news' => $news, 'news_img_path' => base_url() . 'uploads/', 'moreNews' => $sliderNews]
            ];
        } else {
            $response = [
                'status' => false,
                'message' => lang('App.noDataFound'),
                'data' => ''
            ];
        }

        return $this->respondCreated($response);
    }

    public function testSaveData()
    {
        $frontendDir = $this->frontendDir;
        $pageMetaObject = new PageMetaModel();

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale($currentLang);
        $saveData = array();
        // echo '<pre>'; print_r($this->request->getVar()); die;
        $pageId = $this->request->getVar("page_id");
        $lang_id = $this->request->getVar("lang_id");
        if (empty($lang_id) && !empty($pageId)) {
            $pageArr1 = $this->pageModel->where('id', $pageId)->first();
            if (isset($pageArr1) && !empty($pageArr1['language'])) {
                $lang_id = $pageArr1['language'];
            }
        }
        $saveData = ['page_id' => $pageId, 'lang_id' => $lang_id];
        $hero_bg_img_dark_mode = $this->request->getFile('hero_bg_img_dark_mode');
        $hero_bg_img_dark_mode_txt = $this->request->getVar('hero_bg_img_dark_mode');
        if ($hero_bg_img_dark_mode && $hero_bg_img_dark_mode->isValid() && !$hero_bg_img_dark_mode->hasMoved()) {
            $uniqueName = uniqid() . rand() . date('y_m_d_his') . '_' . $hero_bg_img_dark_mode->getClientName();
            $uniqueName = str_replace(' ', '_', $uniqueName);
            $uniqueName = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $uniqueName);
            if ($hero_bg_img_dark_mode->move(WRITEPATH . $frontendDir, $uniqueName)) {
                $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'hero_bg_img_dark_mode')->first();

                if ($ExistInfo) {
                    $existingFilePath = WRITEPATH . $frontendDir . $ExistInfo['meta_value'];
                    if (file_exists($existingFilePath) && !empty($ExistInfo['meta_value'])) {
                        unlink($existingFilePath);
                    }
                    $saveData['id'] = $ExistInfo['id']; // Update the existing record
                }
                $saveData['meta_key'] = 'hero_bg_img_dark_mode';
                $saveData['meta_value'] = $uniqueName;
                $pageMetaObject->savePageMeta($saveData);

                $savedArr[] = $saveData;
            }
        }
        if (!empty($hero_bg_img_dark_mode_txt) && $hero_bg_img_dark_mode_txt == 'remove_image') {
            $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'hero_bg_img_dark_mode')->first();
            $updateData = ['meta_value' => ''];  // Replace '' with the value you want to set
            if ($ExistInfo) {
                $existingFilePath = WRITEPATH . $frontendDir . $ExistInfo['meta_value'];
                if (file_exists($existingFilePath) && !empty($ExistInfo['meta_value'])) {
                    unlink($existingFilePath);
                }
                $saveData['id'] = $ExistInfo['id']; // Update the existing record
            }
            $pageMetaObject->set($updateData)->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'hero_bg_img_dark_mode')->update();
        }

        if ($saveData) {
            $response = [
                'status' => true,
                'message' => lang('App.dataFound'),
                'data' => ['saveData' => $saveData]
            ];
        } else {
            $response = [
                'status' => false,
                'message' => lang('App.noDataFound'),
                'data' => ''
            ];
        }

        return $this->respondCreated($response);
    }
    public function testGetData()
    {
        echo "here";
    }
}
