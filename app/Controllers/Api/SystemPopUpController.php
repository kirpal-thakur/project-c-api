<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use App\Models\SystemPopUpModel;
use App\Models\SystemPopUpUserRoleModel;

class SystemPopUpController extends ResourceController
{
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);
        $this->db = \Config\Database::connect();
        $this->systemPopUpModel = new SystemPopUpModel();
        $this->systemPopUpUserRoleModel = new SystemPopUpUserRoleModel();
        
    }

    // Add System PopUp 
    public function addSystemPopUp(){
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            $rules = [
                'title'             => 'required|max_length[256]',
                'description'       => 'required',
                'popup_for.*'       => 'required|is_natural_no_zero',
                'language'          => 'required',
                'location'          => 'required',
                'frequency'         => 'required',
                'start_date'        => 'required|valid_date',
                'end_date'          => 'required|valid_date',
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
                    'description'   => $this->request->getVar("description"),
                    //'popup_for'     => $this->request->getVar("popup_for"),
                    'language'      => $this->request->getVar("language"),
                    'location'      => $this->request->getVar("location"),
                    'frequency'     => $this->request->getVar("frequency"),
                    'start_date'    => $this->request->getVar("start_date"),
                    'end_date'      => $this->request->getVar("end_date"),
                    'status'        => $this->request->getVar("status")
                ];

                if($this->systemPopUpModel->save($save_data)){

                    //$this->systemPopUpUserRoleModel
                    $insertID = $this->systemPopUpModel->getInsertID();

                    if($insertID){
                        foreach($this->request->getVar("popup_for") as $popup_for){
                            $roleData = [
                                'popup_id' => $insertID,
                                'user_role' => $popup_for
                            ];
                            $this->systemPopUpUserRoleModel->save($roleData);
                        }
                    }

                    // create Activity log
                    $activity_data = [
                        'user_id'               => auth()->id(),
                        'activity_type_id'      => 1,      // added 
                        'activity'              => 'added system popup',
                        'old_data'              => serialize($this->request->getVar()),
                        'ip'                    => $this->request->getIPAddress()
                    ];
                    createActivityLog($activity_data);

                    $response = [
                        "status"    => true,
                        "message"   => lang('App.popupAdded'),
                        "data"      => []
                    ];
                } else {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.popupAddedFailed'),
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

    // get list of System PopUps
    public function getSystemPopUps(){
        
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $userId = auth()->id(); 

        $url  = $this->request->getServer('REQUEST_URI');
        $params = getQueryString($url);

        $limit  = $params['limit'] ?? PER_PAGE;
        $offset = $params['offset'] ?? 0;
        $no_limit = $params['no_limit'] ?? false;
          
        $builder = $this->db->table('system_popups');
        $builder->select('system_popups.*,');
        $builder->select('d.location as popup_location,');
        $builder->select('l.language as popup_language,');

        // Using raw SQL for GROUP_CONCAT and CASE
       /*  $builder->select("GROUP_CONCAT(DISTINCT CONCAT(ur.role_name, 
            '(', 
            CASE 
                WHEN rpt.payment_type = 'paid' THEN 'P' 
                WHEN rpt.payment_type = 'free' THEN 'F' 
                ELSE rpt.payment_type 
            END, 
            ')'
        ) SEPARATOR ', ') as popupfor"); */

        $builder->select('
            JSON_ARRAYAGG(
                JSON_OBJECT(
                    "id", rpt.id,
                    "user_role", ur.role_name, 
                    "payment_type", rpt.payment_type
                )
            ) AS popupfor,
        ');

        // Join with other tables
        $builder->join('domains d', 'd.id = system_popups.location', 'inner');
        $builder->join('languages l', 'l.id = system_popups.language', 'inner');
        $builder->join('system_popup_user_roles sur', 'sur.popup_id = system_popups.id', 'left');
        $builder->join('role_payment_types rpt', 'rpt.id = sur.user_role', 'left');
        $builder->join('user_roles ur', 'ur.id = rpt.user_role_id', 'left');

        if ($params && !empty($params['search'])) {
            $builder->like('system_popups.title', $params['search']);
            // $builder->orLike('system_popups.description', $params['search']);
        }
        /* ##### Code By Amrit ##### */
        if(isset($params['whereClause']) && count($params['whereClause']) > 0){
            $whereClause = $params['whereClause'];
            if (isset($whereClause) && count($whereClause) > 0) {
                
                foreach ($whereClause as $key => $value) {

                    if ($key == 'role') {
                        $builder->where("ur.id", $value);  
                    } elseif ($key == 'language') {
                        $builder->where("l.id", $value);  
                    } elseif ($key == 'frequency') {
                        $builder->where("system_popups.frequency", $value);
                    } elseif ($key == 'popup_location') {
                        $builder->where("d.location", $value);
                    }elseif ($key == 'status') {
                        $builder->where("system_popups.status", $value);
                    }elseif ($key == 'payment_type') {
                        $builder->where("rpt.payment_type", $value);
                    }
                }

            }
        }
        /* ##### End Code By Amrit ##### */
        // Group by and order by
        $builder->groupBy('system_popups.id');
        $builder->orderBy('system_popups.id', 'DESC');

        // Count the total number of results
        $totalCount = $builder->countAllResults(false); 

        // Limit the number of results
        if ($no_limit == false) {
            $builder->limit($limit, $offset);
        }

        // Execute the query
        $query = $builder->get();

        // Fetch results
        $results = $query->getResultArray();
        // echo ' >>>>>>>>>>>>>>>>>>>> getLastQuery >>>>>> '. $this->db->getLastQuery();   //exit;  

        if($results){

            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => [
                    'totalCount'    => $totalCount,
                    'popups'        => $results,
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

    // get single  System PopUp
    public function getSystemPopUp( $id = null ){
            
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $userId = auth()->id(); 

        // $url  = $this->request->getServer('REQUEST_URI');
        // $params = getQueryString($url);
        /* ##### code By Amrit ##### */
        $url =  isset($_SERVER['HTTPS']) && 
        $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";   
        $url .= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];     
        $parse_url = parse_url($url);
        $uri = new \CodeIgniter\HTTP\URI($url);

        $queryString =  $uri->getQuery();
        parse_str($queryString, $searchParams);
        /* ##### End code By Amrit ##### */

        $builder = $this->db->table('system_popups');
        $builder->select('system_popups.*,');
        $builder->select('d.location as popup_location,');
        $builder->select('l.language as popup_language,');

        // Using raw SQL for GROUP_CONCAT and CASE
        /* $builder->select("GROUP_CONCAT(DISTINCT CONCAT(ur.role_name, 
            '(', 
            CASE 
                WHEN rpt.payment_type = 'paid' THEN 'P' 
                WHEN rpt.payment_type = 'free' THEN 'F' 
                ELSE rpt.payment_type 
            END, 
            ')'
        ) SEPARATOR ', ') as popupfor"); */


        $builder->select('
            JSON_ARRAYAGG(
                JSON_OBJECT(
                    "id", rpt.id, 
                    "user_role", ur.role_name, 
                    "payment_type", rpt.payment_type
                )
            ) AS popupfor,
        ');

        // Join with other tables
        $builder->join('domains d', 'd.id = system_popups.location', 'inner');
        $builder->join('languages l', 'l.id = system_popups.language', 'inner');
        $builder->join('system_popup_user_roles sur', 'sur.popup_id = system_popups.id', 'left');
        $builder->join('role_payment_types rpt', 'rpt.id = sur.user_role', 'left');
        $builder->join('user_roles ur', 'ur.id = rpt.user_role_id', 'left');
        /* ##### Code By Amrit ##### */
        $builder->where('system_popups.id', $id );
        

        // Execute the query
        $query = $builder->get();
        //echo ' >>>>>>>>>>>>>>>>>>>> getLastQuery >>>>>> '. $this->db->getLastQuery();   //exit;  

        // Fetch results
        $popUp = $query->getRow();

        if($popUp){
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => ['popUp' => $popUp]
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


    // update System PopUp 
    public function editSystemPopUp( $id = null ){
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP) || hasAccess(auth()->user(), EDITOR_ACCESS)) {

            $rules = [
                'title'             => 'required|max_length[256]',
                'description'       => 'required',
                'popup_for'         => 'required',
                'language'          => 'required',
                'location'          => 'required',
                'frequency'         => 'required',
                'start_date'        => 'required|valid_date',
                'end_date'          => 'required|valid_date',
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
                    'description'   => $this->request->getVar("description"),
                    //'popup_for'     => $this->request->getVar("popup_for"),
                    'language'      => $this->request->getVar("language"),
                    'location'      => $this->request->getVar("location"),
                    'frequency'     => $this->request->getVar("frequency"),
                    'start_date'    => $this->request->getVar("start_date"),
                    'end_date'      => $this->request->getVar("end_date"),
                    'status'        => $this->request->getVar("status")
                ];

                if($this->systemPopUpModel->save($save_data)){

                    $getPopUpUserRole = $this->systemPopUpUserRoleModel
                                        //->select('position_id')
                                        ->where('popup_id', $id)
                                        ->findall();

                    $user_roles = array_column($getPopUpUserRole, 'user_role');

                    $addRoles = array_diff($this->request->getVar("popup_for"), $user_roles);
                    $delRoles = array_diff($user_roles, $this->request->getVar("popup_for"));

                    if($delRoles){
                        $this->systemPopUpUserRoleModel
                            ->where('popup_id', $id)
                            ->whereIn('user_role', $delRoles)
                            ->delete();
                    }

                    if($addRoles){
                        foreach($addRoles as $role){
                            // insert new other_position data
                            $in_data = [
                                'popup_id'      => $id,
                                'user_role'     => $role,
                            ];
                            $this->systemPopUpUserRoleModel->save($in_data);
                        }
                    }

                    // create Activity log
                    $activity_data = [
                        'user_id'               => auth()->id(),
                        'activity_type_id'      => 2,      // updted 
                        'activity'              => 'added system popup',
                        'old_data'              => serialize($this->request->getVar()),
                        'ip'                    => $this->request->getIPAddress()
                    ];
                    createActivityLog($activity_data);

                    $response = [
                        "status"    => true,
                        "message"   => lang('App.popupUpdated'),
                        "data"      => []
                    ];
                } else {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.popupUpdatedFailed'),
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

    // Delete System PopUp
    public function deleteSystemPopUp() {
        
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            $id = $this->request->getVar("id");

            if(!empty($id) && count($id) > 0){

                if($this->systemPopUpModel->delete($id)){

                    // create Activity log
                    $activity = 'deleted System PopUps';
                    $activity_data = [
                        'user_id'               => auth()->id(),
                        'activity_type_id'      => 3,      // deleted 
                        'activity'              => $activity,
                        'ip'                    => $this->request->getIPAddress()
                    ];
                    createActivityLog($activity_data);
                    
                    $response = [
                        "status"    => true,
                        "message"   => lang('App.popupUpDeleted'),
                        "data"      => []
                    ];
                } else {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.popupUpDeletedFailed'),
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
