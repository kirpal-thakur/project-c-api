<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Shield\Models\UserModel;

class ActivityModel extends Model
{
    protected $table            = 'activities';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',      // who made activity
        'activity_on_id',   // on which ID activity is performed
        'activity_type_id', 
        'activity',             // store breif activity message
        'activity_en',             
        'activity_de',             
        'activity_it',             
        'activity_fr',             
        'activity_es',             
        'activity_pt',             
        'activity_da',             
        'activity_sv',             
        'old_data', 
        'new_data', 
        'ip', 
        'status'
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    // public function getActivities($userId, $limit = 10, $offset = 0)
    // {
    //     return $this->where('user_id', $userId)
    //                 ->where('status', 1) // Only fetch active activities
    //                 ->limit($limit)
    //                 ->offset($offset)
    //                 ->orderBy('created_at', 'DESC')
    //                 ->findAll();
    // }
    // Function to fetch activities with pagination and search
    public function getActivitiesOld($userId, $limit = 10, $offset = 0, $search = '')
    {
        $builder = $this->table('activities');
        $builder = $this->where('user_id', $userId)
                        ->where('status', 1); // Only fetch active activities

        if (!empty($search)) {
            $builder->like('activity', $search); // Search by activity name or description
        }

        return $builder->limit($limit)
                       ->offset($offset)
                       ->orderBy('created_at', 'DESC')
                       ->findAll();
    }
    # Modified by Amrit: 
    # Updated function to properly handle user activity queries with 'You are' set for the user,
    # and the user's name is now displayed for admin to view all user activities.
    public function getActivities($userId, $limit = 100, $offset = 0, $userlang = ''){
        // $limit = 10000;
        if(empty($userlang)){
            $activity_type = 'al.activity_en';
        }else{
            $activity_type = 'al.activity_'.strtolower($userlang);
        }
        $builder = $this->db->table('activities al');
        $adminArr = array();
        if ($userId == '1') { // for admin
            $builder->select('al.id, al.user_id, al.activity_on_id, CONCAT(CONCAT(u.first_name, " ", u.last_name," "), '.$activity_type.') AS activity, al.created_at');
            $builder->join('users u', 'al.user_id = u.id');
            $builder->limit($limit)->offset($offset)->orderBy('al.created_at', 'DESC');

            $builder2 = $this->db->table('users');
            $builder2->select('first_name, last_name');
            $builder2->where('users.id', $userId);
            $adminArr   = $builder2->get()->getRow();
        } else {
            $builder->select('al.id, al.user_id, al.activity_on_id, CONCAT("You have ", '.$activity_type.') AS activity, 
                         CONCAT(u.first_name, " ", u.last_name) AS full_name, al.created_at');
            $builder->join('users u', 'al.user_id = u.id');
            $builder->where('al.user_id', $userId);
            $builder->limit($limit)->offset($offset)->orderBy('al.created_at', 'DESC');
        }
        $query = $builder->get();
        $results = $query->getResult();
        $modifiedArr = array();
        $userModel = new UserModel();
        // echo $userId; die;
        // Use the model to find the user by ID
        if(!empty($results)){

            foreach($results  as $obj_key => $result){
                $string = $result->activity;
                if (preg_match('/\[USER_NAME_(\d+)\]/', $string, $matches)) {
                    $current_user = $matches[1];  // This will hold '12345'
                    $modifiedString = preg_replace('/\[USER_NAME_\d+\]/', '[USER_FULL_NAME]', $string);
                    $user = $userModel->select('first_name, last_name')
                          ->where('id', $current_user)
                          ->first();
                    if(empty($user)){
                        $builder = $this->db->table('users');
                        $builder->select('first_name, last_name');
                        $builder->where('users.id', $current_user);
                        $user   = $builder->get()->getRow();
                    }
                    if(isset($user) && !empty($user->first_name)){ 
                        $fullName = trim(ucfirst(strtolower($user->first_name))).' '.trim(ucfirst(strtolower($user->last_name)));
                        $modifiedString = str_replace('[USER_FULL_NAME]',$fullName,$modifiedString);
                        
                        $result->activity = $modifiedString;
                    }
                }
                $modifiedString = $result->activity;
                if($userId === 1){
                    if($userlang == 'en'){
                        $word = "your";
                    }else{
                        $word = "Ihr";
                    }
                    if (preg_match('/\b' . preg_quote(strtolower($word), '/') . '\b/', strtolower($modifiedString))) {
                        $modifiedString = str_replace($word,'his',$modifiedString);
                    }
                    
                }
                if($result->user_id == 1 && !empty($adminArr)){
                    $word = trim(ucfirst(strtolower($adminArr->first_name))).' '.trim(ucfirst(strtolower($adminArr->last_name)));
                    $replace_with = $this->getYouHaveKeyWord($userlang); 
                    $modifiedString = str_replace($word, $replace_with, $modifiedString);
                }else{
                    // For User
                    $replace_with = $this->getYouHaveKeyWord($userlang); 
                    $modifiedString = str_replace('You have', $replace_with, $modifiedString);
                    $modifiedString = str_replace('{userName}','',$modifiedString); // for user remove constand {userName}
                }
                $package_id = 0;
                preg_match('/PACKAGE_ID_(\d+)/', $modifiedString, $package_id);
                if(isset($package_id[1]) && !empty($package_id[1]) && is_numeric($package_id[1])){
                    $builder1 = $this->db->table('package_details');
                    $builder1->select('package_id, description');
                    $builder->where('package_id', $package_id[1]);
                    $packageArr   = $builder1->get()->getRow();
                    // echo '<pre>'; print_r($packageArr); die;
                    if(isset($packageArr) && !empty($packageArr->description)){
                        $modifiedString = preg_replace('/\{PACKAGE_ID_\d+\}/', $packageArr->description, $modifiedString);
                    }
                }
                ### code to add base_url by amrit ###
                // if (preg_match('/\b[\w-]+\.(jpg|jpeg|png|gif|bmp|pdf)\b/', $modifiedString, $matches)) {
                //     // $imageUrl = base_url() .'uploads/'. $matches[0];
                //     if (strpos($modifiedString, '.pdf') !== false) {
                //         $imageUrl = base_url() .'uploads/exports/';
                //     }else{
                //         $imageUrl = base_url() .'uploads/';
                //     }
                //     $imageUrl = '<a href="'.$imageUrl.$matches[0].'" target="_blank">'.$matches[0].'</a>';
                //     $modifiedString = str_replace($matches[0], $imageUrl, $modifiedString);
                // }
                ### code to add base_url by amrit ###
                // $userName = 
                // $modifiedString = str_replace('[USER_NAME_]','',$modifiedString);
                // $modifiedString = str_replace('',$modifiedString);

                $result->activity = $modifiedString;
                $modifiedArr[] = $result;
            }
            
        }
        return $modifiedArr;

    }

    // Function to get total count for pagination
    public function getActivityCount($userId, $search = '')
    {
        $builder = $this->table('activities');
        if($userId == 1){
         //   $builder = $this->where('user_id', $userId)
        }else{
            $builder = $this->where('user_id', $userId)->where('status', 1); // Only count active activities
        }
        
        if (!empty($search)) {
            $builder->like('activity', $search); // Include search in count
        }

        return $builder->countAllResults();
    }

    public function getYouHaveKeyWord($userlang = ''){
        if ($userlang == 'en') {
            $replace_with = "You have"; // English
        } else if ($userlang == 'de') {
            $replace_with = "Sie haben"; // German
        } else if ($userlang == 'fr') {
            $replace_with = "Vous avez"; // French
        } else if ($userlang == 'it') {
            $replace_with = "Hai"; // Italian
        } else if ($userlang == 'es') {
            $replace_with = "Tienes"; // Spanish
        } else if ($userlang == 'pt') {
            $replace_with = "Você tem"; // Portuguese
        } else if ($userlang == 'da') {
            $replace_with = "Du har"; // Danish
        } else if ($userlang == 'sv') {
            $replace_with = "Du har"; // Swedish
        } else {
            $replace_with = "Sie haben"; // Default to German
        }
        return $replace_with;
    }

}
