<?php

namespace App\Controllers\Frontend;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use App\Models\PageMetaModel;
use App\Models\AdvertisementModel;


class ContentPageController extends ResourceController
{
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);
        $this->db = \Config\Database::connect();
        $this->page_meta = new PageMetaModel();
        $this->frontendDir = 'uploads/frontend/';
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
        if (empty($pageId) || !is_numeric($pageId) || empty($lang_id) || !is_numeric($lang_id)) {
            return $this->respondCreated([
                "status" => false,
                "message" => lang('App.invalidParam'),
                "data" => []
            ]);
        }
        // Define validation rules (fields are optional, not required)
        $validationRules = [
            'banner_img' => [
                'label' => 'Banner Image',
                'rules' => [
                    'is_image[banner_img]',
                    'mime_in[banner_img,image/jpg,image/jpeg,image/gif,image/png,image/webp]',
                    'max_size[banner_img,1000]',
                ],
                'errors' => [
                    'is_image'  => lang('App.is_image', ['file' => 'jpg, jpeg, gif, png, webp']),
                    'mime_in'   => lang('App.mime_in', ['file' => 'jpg, jpeg, gif, png, webp']),
                    'max_size'  => lang('App.max_size', ['size' => '1000']),
                ],
            ],
            'banner_title' => [
                'label' => 'Banner Title',
                'rules' => 'min_length[5]', // Optional text
                'errors' => [
                    'max_length' => lang('App.min_length', ['length' => 5]),
                ],
            ],
            'page_content' => [
                'label' => 'Page Content',
                'rules' => 'min_length[10]', // Optional text
                'errors' => [
                    'max_length' => lang('App.min_length', ['length' => 100]),
                ],
            ]
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
        $banner_title = $this->request->getVar('banner_title');
        $page_content = $this->request->getVar('page_content');
        $savedArr = array();
        $saveData = ['page_id' => $pageId, 'lang_id' => $lang_id];
        if ($banner_img && $banner_img->isValid() && !$banner_img->hasMoved()) {
            $uniqueName = uniqid() . rand() . date('y_m_d_his') . '_' . $banner_img->getClientName();
            if ($banner_img->move(WRITEPATH . $frontendDir, $uniqueName)) {
                // Check if an existing record for this image already exists
                $ExistInfo = $pageMetaObject->where('page_id', $pageId)->where('lang_id', $lang_id)->where('meta_key', 'banner_img')->first();
                if ($ExistInfo) {
                    // If a file already exists, delete it
                    $existingFilePath = WRITEPATH . $frontendDir . $ExistInfo['meta_value'];
                    if (file_exists($existingFilePath)) {
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
            foreach ($savedArr as $key => $valueArr) {
                $returnArr[$valueArr['meta_key']] = $valueArr['meta_value'];
            }
        }
        // Prepare response
        return $this->respondCreated([
            "status" => true,
            "message" => lang('App.content_updatedSuccess'),
            "data" => $returnArr
        ]);
    }

    
}
