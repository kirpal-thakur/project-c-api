<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use App\Models\BlogModel;

class BlogController extends ResourceController
{
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);
        $this->db = \Config\Database::connect();
        $this->blogModel = new BlogModel();
    }

    // Add Blog
    public function addBlog() {
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
                            'max_size[featured_image,2000]',
                        ],
                        'errors' => [
                            'ext_in'   => lang('App.ext_in', ['file' => 'jpg, jpeg, png']),
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

            if (count($errors) != 0 ) {
                $errors = $this->validator->getErrors();
        
                $response = [
                    "status"    => false,
                    "message"   => lang('App.provideValidData'),
                    "data"      => ['errors' => $errors]
                ];
            } else {
                $save_data = [
                    'user_id' => auth()->id(),
                    'title'  => $this->request->getVar("title"),
                    'meta_title'        => $this->request->getVar("meta_title"),
                    'meta_description'  => $this->request->getVar("meta_description"),
                    'content'       => $this->request->getVar("content"),
                    'language'      => $this->request->getVar("language"),
                    'status'        => $this->request->getVar("status"),
                    'slug'        => $this->request->getVar("slug"),
                ];
                if(isset($save_data['slug']) && !empty($save_data['slug'])){ 
                    $existArr = $this->blogModel
                    ->select('blogs.*, l.language')
                    ->join('languages l', 'l.id = blogs.language')
                    ->where('blogs.slug', $save_data['slug'])
                    // ->where('blogs.id', $slug)
                    ->first();  
                    if(isset($existArr) && !empty($existArr['slug'])){ 
                        $slugExistError = lang('App.slugExist',['slugName'=>$existArr['slug']]);
                        $response = [
                            "status"    => false,
                            "message"   => lang('App.provideValidData'),
                            "data"      => ['errors' => $slugExistError]
                        ];
                        return $this->respondCreated($response);   
                    }
                }
                if ($featured_image = $this->request->getFile('featured_image')) {
                    if (! $featured_image->hasMoved()) {
                        $filepath = WRITEPATH . 'uploads/' . $featured_image->store('');
                        $ext = $featured_image->getClientExtension();
                        $save_data['featured_image'] = $featured_image->getName();
                    }
                }
                $uploadedFiles = $this->request->getFiles('attachments');
                if(!empty($uploadedFiles['attachments'])){ 
                    $attachments = $uploadedFiles['attachments'];
                    $attchments_arr = [];
                    foreach($attachments as $attachment){
                        if (! $attachment->hasMoved()) {
                            $filepath = WRITEPATH . 'uploads/' . $attachment->store('');
                            $ext = $attachment->getClientExtension();
                            $attchment_name = $attachment->getName();
                            $attchments_arr[] = ['file_name'=>$attchment_name,'file_type'=>$ext];
                        }
                    }
                    if(!empty($attchments_arr)){
                        $save_data['attachments'] = serialize($attchments_arr);
                    }
                }
                // save data
                if($this->blogModel->save($save_data)){
                    // create Activity log
                    $new_data = 'added blog ' . $this->request->getVar("title");
                    $activity_data = [
                        'user_id'               => auth()->id(),
                        'activity_type_id'      => 1,      // added 
                        'activity'              => 'added blog',
                        'new_data'              =>  $new_data,
                        'ip'                    => $this->request->getIPAddress()
                    ];
                    createActivityLog($activity_data);
        
                    $response = [
                        "status"    => true,
                        "message"   => lang('App.blogAdded'),
                        "data"      => []
                    ];
                } else {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.blogAddFailed'),
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
    
    // Edit Blog
    public function editBlog($id = null) {
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
                            'max_size[featured_image,2000]',
                        ],
                        'errors' => [
                            'ext_in'   => lang('App.ext_in', ['file' => 'jpg, jpeg, png']),
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

            if (count($errors) != 0 ) {
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
        
                if ($featured_image = $this->request->getFile('featured_image')) {
                    $isExist = $this->blogModel->where('id', $id)->first();
                    if($isExist){
                        if(!empty($isExist['featured_image']) && file_exists( WRITEPATH . 'uploads/' . $isExist['featured_image']) ){
                            unlink(WRITEPATH . 'uploads/' . $isExist['featured_image']);
                        }
                    }

                    if (! $featured_image->hasMoved()) {
                        $filepath = WRITEPATH . 'uploads/' . $featured_image->store('');
                        $save_data['featured_image'] = $featured_image->getName();
                    }
                }
                $uploadedFiles = $this->request->getFiles('attachments');
                if(!empty($uploadedFiles['attachments'])){ 
                    $attachments = $uploadedFiles['attachments'];
                    $attchments_arr = [];
                    foreach($attachments as $attachment){
                        if (! $attachment->hasMoved()) {
                            $filepath = WRITEPATH . 'uploads/' . $attachment->store('');
                            $ext = $attachment->getClientExtension();
                            $attchment_name = $attachment->getName();
                            $attchments_arr[] = ['file_name'=>$attchment_name,'file_type'=>$ext];
                        }
                    }
                    if(!empty($attchments_arr)){
                        $save_data['attachments'] = serialize($attchments_arr);
                    }
                }
                // save data
                if($this->blogModel->save($save_data)){
                    // create Activity log
                    $new_data = 'updated blog ' . $this->request->getVar("title");
                    $activity_data = [
                        'user_id'               => auth()->id(),
                        'activity_type_id'      => 2,      // added 
                        'activity'              => 'updated blog',
                        'new_data'              =>  $new_data,
                        'ip'                    => $this->request->getIPAddress()
                    ];
                    createActivityLog($activity_data);
        
                    $response = [
                        "status"    => true,
                        "message"   => lang('App.blogUpdated'),
                        "data"      => []
                    ];
                } else {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.blogUpdateFailed'),
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

    // get list of blogs
    public function getBlogs(){
            
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $url  = $this->request->getServer('REQUEST_URI');
        $params = getQueryString($url);
        $imagePath = base_url() . 'uploads/';

        $blogs = $this->blogModel
                    ->select('blogs.*, l.language, CONCAT("'.$imagePath.'", blogs.featured_image ) AS featured_image_path')
                    ->join('languages l', 'l.id = blogs.language');

        if ($params && !empty($params['search'])) {

            $blogs->groupStart()
                    ->like('blogs.title', $params['search'])
                    ->orGroupStart()
                        ->orLike('blogs.content', $params['search'])
                    ->groupEnd()
                ->groupEnd();

            // $blogs->like('title', $params['search']);
            // $blogs->orLike('blogs.content', $params['search']);
        }

        if ($params && !empty($params['lang_id'])) {
            $blogs->where('blogs.language', $params['lang_id']);
        }
        if ($params && !empty($params['status'])) {
            $blogs->where('blogs.status', $params['status']);
        }

        $blogs = $blogs->orderBy('id', 'DESC')
                        ->findAll();
        // echo ' >>>>>>>>>>>>>>>>>>>> getLastQuery >>>>>> '. $this->db->getLastQuery();    

        if($blogs){
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => ['blogs' => $blogs]
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

    // get single blog
    // public function getBlog($slug = null){
            
    //     $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
    //     $this->request->setLocale(getLanguageCode($currentLang));
    //     $imagePath = base_url() . 'uploads/';

    //     $blog = $this->blogModel
    //                 ->select('blogs.*, l.language, CONCAT("'.$imagePath.'", blogs.featured_image ) AS featured_image_path')
    //                 ->join('languages l', 'l.id = blogs.language')
    //                 ->where('blogs.slug', $slug)
    //                 // ->where('blogs.id', $slug)
    //                 ->first();  

    //     if($blog){
    //         $response = [
    //             "status"    => true,
    //             "message"   => lang('App.dataFound'),
    //             "data"      => ['blog' => $blog]
    //         ];
    //     } else {
    //         $response = [
    //             "status"    => false,
    //             "message"   => lang('App.noDataFound'),
    //             "data"      => []
    //         ];
    //     }
    //     return $this->respondCreated($response);
    // }


    public function getBlog()
    {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $url  = $this->request->getServer('REQUEST_URI');
        $params = getQueryString($url);
        $imagePath = base_url() . 'uploads/';
        $id = '';
        $slug = '';
        if ($params && !empty($params['id'])) {
            $id = $params['id'];
        }
        if ($params && !empty($params['slug'])) {
            $slug = $params['slug'];
        }
        
        $currentLang = $this->request->getVar('lang') ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        // Ensure at least one parameter (id or slug) is provided
        if (empty($id) && empty($slug)) {
            return $this->respondCreated([
                "status" => false,
                "message" => "Either 'id' or 'slug' must be provided.",
                "data" => []
            ]);
        }

        $imagePath = base_url() . 'uploads/';

        // Build the query dynamically based on provided parameters
        $this->blogModel
            ->select('blogs.*, l.language,l.id as lang_id, CONCAT("' . $imagePath . '", blogs.featured_image) AS featured_image_path')
            ->join('languages l', 'l.id = blogs.language');

        if (!empty($id) && !empty($slug)) {
            // If both id and slug are provided, match both
            $this->blogModel->where('blogs.id', $id)->where('blogs.slug', $slug);
        } elseif (!empty($id)) {
            // If only id is provided
            $this->blogModel->where('blogs.id', $id);
        } elseif (!empty($slug)) {
            // If only slug is provided
            $this->blogModel->where('blogs.slug', $slug);
        }

        $blog = $this->blogModel->first();
        if(isset($blog) && !empty($blog['attachments'])){
            $blog['attachments'] = unserialize($blog['attachments']);
        }
        // Prepare the response
        if ($blog) {
            $response = [
                "status" => true,
                "message" => lang('App.dataFound'),
                "data" => ['blog' => $blog]
            ];
        } else {
            $response = [
                "status" => false,
                "message" => lang('App.noDataFound'),
                "data" => []
            ];
        }

        return $this->respondCreated($response);
    }


    // Delete blogs
    public function deleteBlog() {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            $id = $this->request->getVar("id");
            if(!empty($id)){
        
                $blogs = $this->blogModel->whereIn('id', $id)->findAll();
        
                if($blogs && count($blogs) > 0){
        
                    foreach($blogs as $blog){
                        if($blog['featured_image'] && file_exists( WRITEPATH . 'uploads/' . $blog['featured_image'])){
                            unlink(WRITEPATH . 'uploads/' . $blog['featured_image']);
                            $del_res = $this->blogModel->delete($blog['id']);
                        }
        
                        // create Activity log
                        $old_data = 'blog title ' . $blog['title'] . ' deleted';
                        $activity_data = [
                            'user_id'               => auth()->id(),
                            'activity_type_id'      => 3,      // added 
                            'activity'              => 'deleted blog',
                            'old_data'              =>  $old_data,
                            'ip'                    => $this->request->getIPAddress()
                        ];
                        createActivityLog($activity_data);
                    }
        
                    if($del_res == true){
                        $response = [
                            "status"    => true,
                            "message"   => lang('App.blogDeleted'),
                            "data"      => []
                        ];
                    } else {
                        $response = [
                            "status"    => false,
                            "message"   => lang('App.blogDeleteFailed'),
                            "data"      => []
                        ];
                    }
        
                } else {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.noDataFound'),
                        "data"      => ['blog_data' => $del_res ]
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

    // Publish blogs
    public function publishBlog() {
                
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            $id = $this->request->getVar("id");
            if(!empty($id) && count($id) > 0){

                if($this->blogModel
                        ->whereIn('id', $id)
                        ->set(['status' => 2])   //  2 = Published
                        ->update()){

                    // create Activity log
                    $activity = 'updated blogs status to published';
                    $activity_data = [
                        'user_id'               => auth()->id(),
                        'activity_type_id'      => 2,      // updated 
                        'activity'              => $activity,
                        'ip'                    => $this->request->getIPAddress()
                    ];
                    createActivityLog($activity_data);
                    
                    $response = [
                        "status"    => true,
                        "message"   => lang('App.blogPublished'),
                        "data"      => []
                    ];
                } else {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.blogPublishFailed'),
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

    // Draft blogs
    public function draftBlog() {
            
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            $id = $this->request->getVar("id");
            if(!empty($id) && count($id) > 0){

                if($this->blogModel
                        ->whereIn('id', $id)
                        ->set(['status' => 1])   //  1 = Draft
                        ->update()){

                    // create Activity log
                    $activity = 'updated blogs status to Draft';
                    $activity_data = [
                        'user_id'               => auth()->id(),
                        'activity_type_id'      => 2,      // updated 
                        'activity'              => $activity,
                        'ip'                    => $this->request->getIPAddress()
                    ];
                    createActivityLog($activity_data);
                    
                    $response = [
                        "status"    => true,
                        "message"   => lang('App.blogDraft'),
                        "data"      => []
                    ];
                } else {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.blogDraftFailed'),
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
}
