<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use App\Models\GalleryModel;
use CodeIgniter\Files\File;

class GalleryController extends ResourceController
{

    public function uploadGalleryImage()
    {
        $galleryModel = new GalleryModel();

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale($currentLang);

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP) || hasAccess(auth()->user(), EDITOR_ACCESS)) {

            $userId = auth()->id();

            // check if representator
            if (in_array(auth()->user()->role, REPRESENTATORS_ROLES)) {
                $authUser = auth()->user();
                $userId = $authUser->parent_id;
            }

            $responses = [];
            //$maxSize = 2 * 1024 * 1024; // 2 MB in bytes 
            //$maxSize = 1024; // 2 MB in bytes

            $allowedExtensions = array_merge(ALLOWED_IMAGE_EXTENTION, ALLOWED_VEDIO_EXTENTION);

            if ($files = $this->request->getFiles()) {
                foreach ($files['gallery_images'] as $file) {
                    $ext = $file->getClientExtension();
                    $size = $file->getSize();

                    if (!$file->isValid()) {
                        $responses[] = [
                            "status" => false,
                            "message" => lang('App.file_invalid', ['file' => $file->getClientName()]),
                            "data" => ['file' => $file->getClientName()]
                        ];
                        continue;
                    }

                    if ($size > MAX_FILE_SIZE) {
                        $responses[] = [
                            "status" => false,
                            "message" => lang('App.file_too_large', ['file' => $file->getClientName()]),
                            "data" => ['file' => $file->getClientName()]
                        ];
                        continue;
                    }

                    if (!in_array($ext, $allowedExtensions)) {
                        $responses[] = [
                            "status" => false,
                            "message" => lang('App.file_type_not_allowed', ['file' => $file->getClientName()]),
                            "data" => ['file' => $file->getClientName()]
                        ];
                        continue;
                    }

                    if (! $file->hasMoved()) {
                        $newName = $file->getRandomName();

                        if ($file->move(WRITEPATH . 'uploads', $newName)) {
                            $save_data = [
                                'user_id' => $userId,
                                'file_name' => $newName,
                                'file_type' => $ext,
                            ];

                            if ($galleryModel->save($save_data)) {

                                // create Activity log
                                // $activity_data = [
                                //     'user_id'               => auth()->id(),
                                //     'activity_type_id'      => 1,      // created 
                                //     'activity'              => 'uploaded file in gallery ' . $newName,
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
                                    $translated_message = lang('App.galleryFileUpload', ['imageName' => $newName]);
                                    $activity_data['activity_' . $lang] = $translated_message;
                                }
                                createActivityLog($activity_data);

                                $responses[] = [
                                    "status" => true,
                                    "message" => lang('App.file_upload_success', ['file' => $file->getClientName()]),
                                    "data" => [
                                        'file'          => $file->getClientName(),
                                        'uploaded_file' => $newName,
                                        'id'            => $galleryModel->getInsertID(),
                                    ]
                                ];
                            } else {

                                $responses[] = [
                                    "status" => false,
                                    "message" => lang('App.database_save_failure', ['file' => $file->getClientName()]),
                                    "data" => ['file' => $file->getClientName()]
                                ];
                            }
                        } else {
                            $responses[] = [
                                "status" => false,
                                "message" => lang('App.file_move_failure', ['file' => $file->getClientName()]),
                                "data" => ['file' => $file->getClientName()]
                            ];
                        }
                    } else {
                        $responses[] = [
                            "status" => false,
                            "message" => lang('App.file_already_moved', ['file' => $file->getClientName()]),
                            "data" => ['file' => $file->getClientName()]
                        ];
                    }
                }
            } else {
                $responses = [
                    "status" => false,
                    "message" => lang('App.no_files_uploaded'),
                    "data" => []
                ];
            }
        } else {
            $response = [
                "status"    => false,
                "message"   => lang('App.permissionDenied'),
                "data"      => []
            ];
        }

        return $this->respondCreated($responses);
    }

    public function getGallery($userId = null)
    {

        $galleryModel = new GalleryModel();

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale($currentLang);

        $userId = $userId ?? auth()->id();

        // check if representator
        if (auth()->id() && in_array(auth()->user()->role, REPRESENTATORS_ROLES)) {
            $authUser = auth()->user();
            $userId = $authUser->parent_id;
        }

        $images = $galleryModel->where('user_id', $userId)->whereIn('file_type', ALLOWED_IMAGE_EXTENTION)->orderBy('id', 'DESC')->findAll();
        $videos = $galleryModel->where('user_id', $userId)->whereIn('file_type', ALLOWED_VEDIO_EXTENTION)->orderBy('id', 'DESC')->findAll();

        if ($images || $videos) {
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => [
                    'images' => $images,
                    'videos' => $videos,
                    'file_path' => base_url() . 'uploads/'
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


    public function getGalleryHighlights($userId = null)
    {

        $galleryModel = new GalleryModel();

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale($currentLang);

        $userId = $userId ?? auth()->id();

        // check if representator
        if (auth()->id() && in_array(auth()->user()->role, REPRESENTATORS_ROLES)) {
            $authUser = auth()->user();
            $userId = $authUser->parent_id;
        }


        $images = $galleryModel->where('user_id', $userId)->where('is_featured', 1)->whereIn('file_type', ALLOWED_IMAGE_EXTENTION)->orderBy('id', 'DESC')->findAll();
        $videos = $galleryModel->where('user_id', $userId)->where('is_featured', 1)->whereIn('file_type', ALLOWED_VEDIO_EXTENTION)->orderBy('id', 'DESC')->findAll();

        if ($images || $videos) {
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => [
                    'images' => $images,
                    'videos' => $videos,
                    'file_path' => base_url() . 'uploads/'
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

    public function deleteGalleryFile()
    {
        $galleryModel = new GalleryModel();

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale($currentLang);

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            $id = $this->request->getVar("id");
            if (!isset($id) || count($id) == 0) {
                $response = [
                    "status"    => false,
                    "message"   => lang('App.provideDeleteData'),
                    "data"      => []
                ];
            } else {

                $userId = auth()->id();

                // check if representator
                if (in_array(auth()->user()->role, REPRESENTATORS_ROLES)) {
                    $authUser = auth()->user();
                    $userId = $authUser->parent_id;
                }

                $files = $galleryModel->where('user_id', $userId)->whereIn('id', $id)->findAll();

                if ($files) {

                    foreach ($files as $file) {
                        if (file_exists(WRITEPATH . 'uploads/' . $file['file_name'])) {
                            unlink(WRITEPATH . 'uploads/' . $file['file_name']);
                            $res = $galleryModel->delete($file['id']);

                            // create Activity log
                            // $activity_data = [
                            //     'user_id'               => auth()->id(),
                            //     'activity_type_id'      => 3,      // deleted 
                            //     'activity'              => 'deleted file from gallery ' . $file['file_name'],
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
                                $translated_message = lang('App.selectedFilesDeleted');
                                $activity_data['activity_' . $lang] = $translated_message;
                            }
                            createActivityLog($activity_data);
                        }
                    }

                    if ($res) {
                        $response = [
                            "status"    => true,
                            "message"   => lang('App.selectedFilesDeleted'),
                            "data"      => []
                        ];
                    } else {
                        $response = [
                            "status"    => false,
                            "message"   => lang('App.selectedFilesDeleteFailed'),
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

    public function SetFeaturedFile()
    {
        $galleryModel = new GalleryModel();

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale($currentLang);

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP) || hasAccess(auth()->user(), EDITOR_ACCESS)) {

            $id = $this->request->getVar("id");
            if (!isset($id) || count($id) == 0) {
                $response = [
                    "status"    => false,
                    "message"   => lang('App.provideFeaturedFiles'),
                    "data"      => []
                ];
            } else {

                $userId = auth()->id();

                // check if representator
                if (in_array(auth()->user()->role, REPRESENTATORS_ROLES)) {
                    $authUser = auth()->user();
                    $userId = $authUser->parent_id;
                }

                $files = $galleryModel->where('user_id', $userId)->where('is_featured', 1)->findAll();
                $filesArr = $galleryModel->where('user_id', $userId)->findAll();
                // echo '<pre>'; print_r($files); die;
                $flipped_array = array_column($filesArr, 'file_name', 'id');
                $file_names = array_intersect_key($flipped_array, array_flip($id));
                $featuredFiles = array_column($files, 'id');


                $unsetFeaturedFiles = array_diff($featuredFiles, $id);
                $setFeaturedFiles   = array_diff($id, $featuredFiles);

                if ($unsetFeaturedFiles) {
                    $res = $galleryModel->update($unsetFeaturedFiles, ['is_featured' => 0]);
                    $response = [
                        "status"    => true,
                        "message"   => lang('App.featuredFilesSet'),
                        "data"      => []
                    ];
                }

                if ($setFeaturedFiles) {
                    foreach ($setFeaturedFiles as $file) {
                        $rowCount = $galleryModel->where('user_id', $userId)->where('is_featured', 1)->countAllResults();
                        if ($rowCount < FEATURED_COUNT) {
                            if ($galleryModel->update($file, ['is_featured' => 1])) {
                                // $a = ['Files'=>$file_names,'file'=>$file];
                                // echo '<pre>'; print_r($a); die;
                                if (isset($file_names[$file]) && !empty($file_names[$file])) {
                                    $file = $file_names[$file];
                                }
                                // create Activity log
                                // $activity_data = [
                                //     'user_id'               => auth()->id(),
                                //     'activity_type_id'      => 2,      // updated 
                                //     'activity'              => 'set ' . $file . ' as featured file',
                                //     'old_data'              => $file,
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
                                    $translated_message = lang('App.setFeaturedFile', ['imageName' => $file]);
                                    $activity_data['activity_' . $lang] = $translated_message;
                                }
                                createActivityLog($activity_data);

                                $response = [
                                    "status"    => true,
                                    "message"   => lang('App.featuredFilesSet'),
                                    "data"      => []
                                ];
                            } else {
                                $response = [
                                    "status"    => false,
                                    "message"   => lang('App.featuredFilesSetFailed'),
                                    "data"      => []
                                ];
                            }
                        } else {
                            // create Activity log
                            $activity_data = [
                                'user_id'               => auth()->id(),
                                'activity_type_id'      => 2,      // updated 
                                'activity'              => 'featured file limit Exceeded',
                                'old_data'              => $file,
                                'ip'                    => $this->request->getIPAddress()
                            ];
                            createActivityLog($activity_data);

                            $response = [
                                "status"    => false,
                                "message"   => lang('App.featuredLimitExceeded'),
                                "data"      => []
                            ];
                        }
                    }
                } else {
                    $response = [
                        "status"    => true,
                        "message"   => lang('App.featuredFilesSet'),
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

    public function UnSetFeaturedFile($id = null)
    {
        $galleryModel = new GalleryModel();

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale($currentLang);

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP) || hasAccess(auth()->user(), EDITOR_ACCESS)) {

            if (!$id) {
                $response = [
                    "status"    => false,
                    "message"   => lang('App.provideFeaturedFiles'),
                    "data"      => []
                ];
            } else {

                $data = ['is_featured' => 0];
                $res = $galleryModel->update($id, $data);

                if ($res) {

                    // create Activity log
                    // $activity_data = [
                    //     'user_id'               => auth()->id(),
                    //     'activity_type_id'      => 2,      // updated 
                    //     'activity'              => 'Removed  from featured file',
                    //     'old_data'              => serialize($id),
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
                        $translated_message = lang('App.removeFeaturedFile');
                        $activity_data['activity_' . $lang] = $translated_message;
                    }
                    createActivityLog($activity_data);

                    $response = [
                        "status"    => true,
                        "message"   => lang('App.featuredFilesSet'),
                        "data"      => []
                    ];
                } else {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.featuredFilesSetFailed'),
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


    //////////////////////////// ADMIN APIs /////////////////////////////////

    // Get Player's gallery by admin
    // GET API
    /* public function getGalleryAdmin($userId = null){

        $galleryModel = new GalleryModel();
    
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale($currentLang);
    
        $images = $galleryModel->where('user_id', $userId)->whereIn('file_type', ALLOWED_IMAGE_EXTENTION)->orderBy('id','DESC')->findAll();
        $videos = $galleryModel->where('user_id', $userId)->whereIn('file_type', ALLOWED_VEDIO_EXTENTION)->orderBy('id','DESC')->findAll();
    
        if($images || $videos){
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => [
                    'images'    => $images, 
                    'videos'    => $videos,
                    'file_path' => base_url() . 'uploads/' 
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

    // Add photos and Videos in Player's gallery by admin
    // Post API
    public function uploadGalleryImageAdmin($userId = null)
    {
        $galleryModel = new GalleryModel();

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale($currentLang);

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            $responses = [];
            //$maxSize = 2 * 1024 * 1024; // 2 MB in bytes 
            //$maxSize = 1024; // 2 MB in bytes

            $allowedExtensions = array_merge(ALLOWED_IMAGE_EXTENTION, ALLOWED_VEDIO_EXTENTION);

            if ($files = $this->request->getFiles()) {
                foreach ($files['gallery_images'] as $file) {
                    $ext = $file->getClientExtension();
                    $size = $file->getSize();

                    if (!$file->isValid()) {
                        $responses[] = [
                            "status" => false,
                            "message" => lang('App.file_invalid', ['file' => $file->getClientName()]),
                            "data" => ['file' => $file->getClientName()]
                        ];
                        continue;
                    }

                    // if ($size > MAX_FILE_SIZE) {
                    //     $responses[] = [
                    //         "status" => false,
                    //         "message" => lang('App.file_too_large', ['file' => $file->getClientName()]),
                    //         "data" => ['file' => $file->getClientName()]
                    //     ];
                    //     continue;
                    // }

                    if (!in_array($ext, $allowedExtensions)) {
                        $responses[] = [
                            "status" => false,
                            "message" => lang('App.file_type_not_allowed', ['file' => $file->getClientName()]),
                            "data" => ['file' => $file->getClientName()]
                        ];
                        continue;
                    }

                    if (! $file->hasMoved()) {
                        $newName = $file->getRandomName();

                        if ($file->move(WRITEPATH . 'uploads', $newName)) {

                            $save_data = [
                                'user_id' => $userId,
                                'file_name' => $newName,
                                'file_type' => $ext,
                            ];

                            if ($galleryModel->save($save_data)) {

                                // create Activity log
                                // $activity_data = [
                                //     'user_id'               => auth()->id(),
                                //     'activity_on_id'        => $userId,
                                //     'activity_type_id'      => 1,      // created 
                                //     'activity'              => 'uploaded file in gallery ' . $newName,
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
                                    $translated_message = lang('App.file_upload_success', ['fileName' => $file->getClientName()]);
                                    $activity_data['activity_' . $lang] = $translated_message;
                                }
                                createActivityLog($activity_data);
                                $responses[] = [
                                    "status" => true,
                                    "message" => lang('App.file_upload_success', ['file' => $file->getClientName()]),
                                    "data" => [
                                        'file'          => $file->getClientName(),
                                        'uploaded_file' => $newName,
                                        'id'            => $galleryModel->getInsertID(),
                                    ]
                                ];
                            } else {
                                $responses[] = [
                                    "status" => false,
                                    "message" => lang('App.database_save_failure', ['file' => $file->getClientName()]),
                                    "data" => ['file' => $file->getClientName()]
                                ];
                            }
                        } else {
                            $responses[] = [
                                "status" => false,
                                "message" => lang('App.file_move_failure', ['file' => $file->getClientName()]),
                                "data" => ['file' => $file->getClientName()]
                            ];
                        }
                    } else {
                        $responses[] = [
                            "status" => false,
                            "message" => lang('App.file_already_moved', ['file' => $file->getClientName()]),
                            "data" => ['file' => $file->getClientName()]
                        ];
                    }
                }
            } else {
                $responses[] = [
                    "status" => false,
                    "message" => lang('App.no_files_uploaded'),
                    "data" => []
                ];
            }
        } else {
            $response = [
                "status"    => false,
                "message"   => lang('App.permissionDenied'),
                "data"      => []
            ];
        }

        return $this->respondCreated($responses);
    }


    // Delete photos and Videos in Player's gallery by admin
    // Post API
    public function deleteGalleryFileAdmin()
    {
        $galleryModel = new GalleryModel();

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale($currentLang);

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            $id = $this->request->getVar("id");
            if (!isset($id) || count($id) == 0) {
                $response = [
                    "status"    => false,
                    "message"   => lang('App.provideDeleteData'),
                    "data"      => []
                ];
            } else {

                $files = $galleryModel->whereIn('id', $id)->findAll();

                if ($files) {

                    foreach ($files as $file) {
                        if (file_exists(WRITEPATH . 'uploads/' . $file['file_name'])) {
                            unlink(WRITEPATH . 'uploads/' . $file['file_name']);
                            $res = $galleryModel->delete($file['id']);
                            // create Activity log
                            // $activity_data = [
                            //     'user_id'               => auth()->id(),
                            //     'activity_type_id'      => 3,      // deleted 
                            //     'activity'              => 'deleted file from gallery ' . $file['file_name'],
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
                                $translated_message = lang('App.fileDeleteSuccess', ['fileName' => $file->getClientName()]);
                                $activity_data['activity_' . $lang] = $translated_message;
                            }
                            createActivityLog($activity_data);
                        }
                    }

                    if ($res) {
                        $response = [
                            "status"    => true,
                            "message"   => lang('App.selectedFilesDeleted'),
                            "data"      => []
                        ];
                    } else {
                        $response = [
                            "status"    => false,
                            "message"   => lang('App.selectedFilesDeleteFailed'),
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
