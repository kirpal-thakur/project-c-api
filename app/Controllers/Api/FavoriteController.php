<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use App\Models\FavoriteModel;
use App\Models\EmailTemplateModel;
use App\Models\UserMetaDataModel;
use CodeIgniter\Shield\Models\UserModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use DateTime;

class FavoriteController extends ResourceController
{
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);

        $this->db               = \Config\Database::connect();
        $this->session          = \Config\Services::session();
        $this->favoriteModel    = new FavoriteModel();
    }

    // Add Favorite 
    public function addFavorite()
    {
        $currentLang = $this->request->getPost("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            $rules = [
                "favorite_id" => "required",
            ];

            if (!$this->validate($rules)) {
                $response = [
                    "status"    => false,
                    "message"   => lang('App.error'),
                    "data"      => ['error' => $this->validator->getErrors()]
                ];
            } else {

                $isExist = $this->favoriteModel->where('user_id', auth()->id())->where('favorite_id', $this->request->getPost("favorite_id"))->first();

                if ($isExist) {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.favoriteAlreadyAdded'),
                        "data"      => []
                    ];
                } else {
                    $save_data = [
                        'user_id'           => auth()->id(),
                        'favorite_id'       => $this->request->getPost("favorite_id")
                    ];

                    if ($this->favoriteModel->save($save_data)) {

                        if (in_array(auth()->user()->role, [2, 3])) {

                            $emailTemplateModel = new EmailTemplateModel();
                            $emailType = '';

                            if (auth()->user()->role == 2) {
                                $emailType = 'Club added Talent to favourites';
                            }

                            if (auth()->user()->role == 3) {
                                $emailType = 'Scout added Talent to favourites';
                            }

                            $users = auth()->getProvider();
                            $user = $users->findById($this->request->getPost("favorite_id"));

                            $getEmailTemplate = $emailTemplateModel->where('type', $emailType)
                                // ->where('language', $user->lang)           
                                ->where('language', 2)
                                ->whereIn('email_for', [4])     // 4 = talent
                                ->first();

                            if ($getEmailTemplate) {

                                $replacements = [
                                    '{firstName}'   => $user->first_name,
                                    '{scoutName}'   => auth()->user()->first_name,
                                    '{clubName}'    => auth()->user()->first_name,
                                    '{link}'        => '<a href="#" >link </a>',
                                ];

                                $subject = $getEmailTemplate['subject'];

                                // Replace placeholders with actual values in the email body
                                $content = strtr($getEmailTemplate['content'], $replacements);
                                $message = view('emailTemplateView', ['message' => $content, 'disclaimerText' => getEmailDisclaimer($user->lang), 'footer' => getEmailFooter($user->lang)]);

                                $toEmail = $user->email;
                                // $toEmail = 'pratibhaprajapati.cts@gmail.com';
                                $emailData = [
                                    'fromEmail'     => FROM_EMAIL,
                                    'fromName'      => FROM_NAME,
                                    'toEmail'       => $toEmail,
                                    'subject'       => $subject,
                                    'message'       => $message,
                                ];
                                sendEmail($emailData);
                            }
                        }
                        if (isset($id) && is_array($id) && !empty($id)) {
                            $id = $id['0'];
                        } else {
                            $id = $this->request->getPost("favorite_id");
                        }

                        // $constant = 'added [USER_NAME_'.$id.'] in Favorites';
                        // // create Activity log
                        // $activity_data = [
                        //     'user_id'               => auth()->id(),
                        //     'activity_type_id'      => 1,      // added 
                        //     'activity'              => $constant,
                        //     'old_data'              => serialize($this->request->getPost()),
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
                            $translated_message = lang('App.favoriteAdded', ['userID' => $id]);
                            $activity_data['activity_' . $lang] = $translated_message;
                        }
                        createActivityLog($activity_data);

                        $response = [
                            "status"    => true,
                            "message"   => lang('App.favoriteAdded'),
                            "data"      => []
                        ];
                    } else {
                        $response = [
                            "status"    => false,
                            "message"   => lang('App.favoriteAddedFailed'),
                            "data"      => []
                        ];
                    }
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

    // Get Favorite 
    public function getFavorites($id = null)
    {
        $currentLang = $this->request->getPost("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $userId = $id ?? auth()->id();

        // check if representator
        if (in_array(auth()->user()->role, REPRESENTATORS_ROLES)) {
            $authUser = auth()->user();
            $userId = $authUser->parent_id;
        }

        $url  = $this->request->getServer('REQUEST_URI');
        $params = getQueryString($url);

        $limit = $params['limit'] ?? 0;
        $offset = $params['offset'] ?? 0;
        $search = $params['search'] ?? '';
        $countOnly = $params['count_only'] ?? false;

        $result = getFavorites($userId, $search, $limit, $offset, $countOnly);

        if ($result) {
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => [$result]
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

    // delete Favorite 
    public function deleteFavorites()
    {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        // if (auth()->user()->inGroup('superadmin', 'superadmin-representator') && (auth()->user()->can('admin.access') || auth()->user()->can('transfer.ownership'))) {

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            $id = $this->request->getVar("id");

            // echo ">>>>>>>>>>> auth >>>>.. " . auth()->id();

            if (!empty($id)) {
                // if(isset($id) && is_array($id) && !empty($id['0'])){ // correction by amrit
                //     $id = $id['0'];
                // }
                foreach ($id as $fav_id) {
                    // 
                    $isExist = $this->favoriteModel->where('user_id', auth()->id())->where('id', $fav_id)->first();

                    // if ($this->favoriteModel->where('user_id',auth()->id())->where('favorite_id', $fav_id)->delete()) { 
                    if ($this->favoriteModel->delete($fav_id)) {
                        // $constant = 'removed [USER_NAME_' . $isExist["favorite_id"] . '] from favorites';
                        // $activity_data = [
                        //     'user_id'               => auth()->id(),
                        //     'activity_type_id'      => 3,      // added 
                        //     'activity'              => $constant,
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
                            $translated_message = lang('App.favoriteRemoved', ['userID' => $fav_id]);
                            $activity_data['activity_' . $lang] = $translated_message;
                        }
                        createActivityLog($activity_data);

                        $response = [
                            "status"    => true,
                            "message"   => lang('App.userDeletedFavorites'),
                            "data"      => []
                        ];
                    } else {
                        $response = [
                            "status"    => false,
                            "message"   => lang('App.userDeletedFavoritesFailed'),
                            "data"      => []
                        ];
                    }
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
    // test_by_amrit
    public function getFavoritesWithProfile()
    {
        $returnArr = [];
        $currentLang = $this->request->getPost("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $userId = $id ?? auth()->id();
        // echo $userId; die;
        // $userId = 59;
        $url  = $this->request->getServer('REQUEST_URI');
        $params = getQueryString($url);

        $limit = $params['limit'] ?? 0;
        $offset = $params['offset'] ?? 0;
        $search = $params['search'] ?? '';
        $countOnly = $params['count_only'] ?? false;

        $result = getFavorites($userId, $search, $limit, $offset, $countOnly);
        if (isset($result['favorites']) && $result['totalCount'] > 0) {
            foreach ($result['favorites'] as $key => $favorites) {
                if (!empty($favorites['id']) && is_numeric($favorites['id'])) {
                    $returnArr[] = ['favorites' => $favorites, 'favoriteProfile' => $this->genrateFavoriteProfile($favorites['id'])];
                }
            }
        }
        if ($returnArr) {
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => [$returnArr]
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

    public function genrateFavoriteProfile($id)
    {
        $response = [];
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $userMetaObject = new UserMetaDataModel();

        // $id = $id ?? auth()->id();
        // $userId = '58';
        $imagePath = base_url() . 'uploads/';
        $flagPath =  base_url() . 'uploads/logos/';

        $builder = $this->db->table('users');
        $builder->select('users.*, 
                            user_roles.role_name as role_name,
                            languages.language as language,
                            domains.domain_name as user_domain,
                            domains.location as user_location,
                            auth.secret as email,
                            pm.last4 as last4,
                            pm.brand as brand,
                            pm.exp_month as exp_month,
                            pm.exp_year as exp_year,
                            us.status as subscription_status,
                            us.plan_period_start as plan_period_start,
                            us.plan_period_end as plan_period_end,
                            us.plan_interval as plan_interval,
                            us.coupon_used as coupon_used,
                            
                            (SELECT JSON_ARRAYAGG(
                                JSON_OBJECT(
                                    "country_name", c.country_name, 
                                    "flag_path", CONCAT("' . $flagPath . '", c.country_flag)
                                )
                            )
                            FROM user_nationalities un 
                            LEFT JOIN countries c ON c.id = un.country_id 
                            WHERE un.user_id = users.id) AS user_nationalities,
                            
                            t.team_type as team_type,
                            um2.meta_value as current_club_name,
                            CONCAT("' . $imagePath . '", um3.meta_value) as club_logo_path,

                            l.league_name as league_name,
                            l.league_logo as league_logo,
                            CONCAT("' . $flagPath . '", l.league_logo) as league_logo_path,
                            
                            c1.country_name as int_player_country,
                            CONCAT("' . $flagPath . '", c1.country_flag) as int_player_country_logo_path,
                           
                            (SELECT JSON_ARRAYAGG(
                                    JSON_OBJECT(
                                        "position_id", pp.position_id ,
                                        "main_position", pp.is_main, 
                                        "position_name", p.position
                                    )
                                )
                                FROM player_positions pp
                                LEFT JOIN positions p ON p.id = pp.position_id
                                WHERE pp.user_id = users.id
                            ) As positions,

                            cb.club_name as player_current_club_name
                        ');


        $builder->join('(SELECT user_id, secret FROM auth_identities WHERE type = "email_password") auth', 'auth.user_id = users.id', 'LEFT');
        $builder->join('user_roles', 'user_roles.id = users.role', 'LEFT');
        $builder->join('languages', 'languages.id = users.lang', 'LEFT');
        $builder->join('domains', 'domains.id = users.user_domain', 'LEFT');
        // $builder->join('user_nationalities un', 'un.user_id = users.id', 'LEFT');
        // $builder->join('countries c', 'c.id = un.country_id', 'LEFT');
        $builder->join('(SELECT user_id, brand, exp_month, exp_year, last4 FROM payment_methods WHERE is_default = 1) pm', 'pm.user_id = users.id', 'LEFT');
        $builder->join('(SELECT user_id, status, plan_period_start, plan_period_end, plan_interval, coupon_used FROM user_subscriptions WHERE id =  (SELECT MAX(id)  FROM user_subscriptions WHERE user_id = ' . $id . ')) us', 'us.user_id = users.id', 'LEFT');

        // to get club details
        $builder->join('club_players cp', 'cp.player_id = users.id AND cp.status = "active"', 'LEFT');
        $builder->join('teams t', 't.id = cp.team_id', 'LEFT');
        $builder->join('(SELECT user_id, meta_value FROM user_meta WHERE meta_key = "club_name" ) um2', 'um2.user_id = t.club_id', 'LEFT');
        $builder->join('(SELECT user_id, meta_value FROM user_meta WHERE meta_key = "profile_image" ) um3', 'um3.user_id = t.club_id', 'LEFT');

        // to get club's league
        $builder->join('league_clubs lc', 'lc.club_id = t.club_id', 'LEFT');
        $builder->join('leagues l', 'l.id = lc.league_id', 'LEFT');

        $builder->join('(SELECT user_id, meta_value FROM user_meta WHERE meta_key = "international_player" ) um4', 'um4.user_id = users.id', 'LEFT');
        $builder->join('countries c1', 'c1.id = um4.meta_value', 'LEFT');

        // additoinal current club of player/talent
        $builder->join('(SELECT user_id, meta_value FROM user_meta WHERE meta_key = "player_current_club" ) um5', 'um5.user_id = users.id', 'LEFT');
        $builder->join('clubs cb', 'cb.id = um5.meta_value', 'LEFT');


        $builder->where('users.id', $id);
        // $builder->groupBy('users.id');
        $userData   = $builder->get()->getRow();
        // echo '>>>>>>>> getLastQuery >>>>> '. $this->db->getLastQuery();die; //exit;


        if ($userData) {

            // add user's meta data to user info
            $metaData = $userMetaObject->where('user_id', $id)->findAll();
            $meta = [];
            if ($metaData && count($metaData) > 0) {
                foreach ($metaData as $data) {
                    $meta[$data['meta_key']] = $data['meta_value'];

                    if ($data['meta_key'] == 'profile_image') {
                        $meta['profile_image_path'] = $imagePath . $data['meta_value'];
                    }
                    if ($data['meta_key'] == 'cover_image') {
                        $meta['cover_image_path'] = $imagePath . $data['meta_value'];
                    }
                }
            }

            $userData->email = $userData->email;
            $userData->meta = $meta;

            // Return a success message as a JSON response
            $response = [
                "status"    => true,
                "message"   => lang('User found'),
                "data"      => ['user_data' => $userData]
            ];
        }
        return $userData;
    }

    public function exportFavorites($id = null)
    {
        $currentLang = $this->request->getPost("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $userId = $id ?? auth()->id();

        $url  = $this->request->getServer('REQUEST_URI');
        $params = getQueryString($url);

        $limit = $params['limit'] ?? 0;
        $offset = $params['offset'] ?? 0;
        $search = $params['search'] ?? '';
        $countOnly = $params['count_only'] ?? false;
        $selectedIds = $params['id'] ?? false;
        // $selectedIds = $this->request->getVar("id");
        $result = getFavorites($userId, $search, $limit, $offset, $countOnly);

        // echo '<pre>'; print_r($params); die(' ~~~ID');
        if ($result['favorites']) {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            // Set the headers
            $sheet->setCellValue('A1', 'First Name');
            $sheet->setCellValue('B1', 'Last Name');
            $sheet->setCellValue('C1', 'User Type');
            $sheet->setCellValue('D1', 'Location');
            $sheet->setCellValue('E1', 'Joined Date - Time');
            $sheet->setCellValue('F1', 'Profile');
            $row = 2; // Start from the second row
            $exportRows = 0;
            foreach ($result['favorites'] as $favorite) {
                if (isset($selectedIds) && !empty($selectedIds)) {
                    if (is_array($selectedIds) && in_array($favorite['id'], $selectedIds)) {
                        $sheet->setCellValue('A' . $row, htmlspecialchars($favorite['first_name']));
                        $sheet->setCellValue('B' . $row, htmlspecialchars($favorite['last_name']));
                        $sheet->setCellValue('C' . $row, htmlspecialchars($favorite['role_name']));
                        $sheet->setCellValue('D' . $row, htmlspecialchars($favorite['location']));
                        // Convert the date using DateTime
                        $date = new DateTime($favorite['user_created_at']);
                        // Format it to 'm.d.Y - h.i A'
                        $formattedDate = $date->format('m.d.Y - h.i A');
                        $sheet->setCellValue('E' . $row, htmlspecialchars($formattedDate));
                        $sheet->setCellValue('F' . $row, htmlspecialchars($favorite['profile_image_path']));
                        $exportRows++;
                    }
                } else {
                    $sheet->setCellValue('A' . $row, htmlspecialchars($favorite['first_name']));
                    $sheet->setCellValue('B' . $row, htmlspecialchars($favorite['last_name']));
                    $sheet->setCellValue('C' . $row, htmlspecialchars($favorite['role_name']));
                    $sheet->setCellValue('D' . $row, htmlspecialchars($favorite['location']));
                    // Convert the date using DateTime
                    $date = new DateTime($favorite['user_created_at']);
                    // Format it to 'm.d.Y - h.i A'
                    $formattedDate = $date->format('m.d.Y - h.i A');
                    $sheet->setCellValue('E' . $row, htmlspecialchars($formattedDate));
                    $sheet->setCellValue('F' . $row, htmlspecialchars($favorite['profile_image_path']));
                    $exportRows++;
                }
                $row++;
            }
            if ($exportRows) {
                $filename = 'favorites_export_' . date('Y-m-d_H-i-s') . '.xlsx';
                $saveDirectory = WRITEPATH  . 'uploads/exports/';
                $folder_nd_file = 'uploads/exports/' . $filename;
                $filePath = $saveDirectory . $filename;
                if (!is_dir($saveDirectory)) {
                    mkdir($saveDirectory, 0777, true);  // Create the directory with proper permissions
                }
                $writer = new Xlsx($spreadsheet);
                $writer->save($filePath);  // Save the file
                // $activity_data = [
                //     'user_id'               => auth()->id(),
                //     'activity_type_id'      => 9,      // viewed
                //     'activity'              => 'Favorites Csv Downloded by user',
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
                    $translated_message = lang('App.favoriteCsvDownload', ['userID' => $id]);
                    $activity_data['activity_' . $lang] = $translated_message;
                }
                createActivityLog($activity_data);
                $response = [
                    "status"    => true,
                    "message"   => lang('App.dataFound'),
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
        } else {
            $response = [
                "status"    => false,
                "message"   => lang('App.noDataFound'),
                "data"      => []
            ];
        }

        return $this->respondCreated($response);
    }

    public function fetchUsers(array $fields, array $where = [])
    {
        // Check if fields array is empty
        if (empty($fields)) {
            return [
                'status' => 'error',
                'message' => 'Fields array cannot be empty.',
            ];
        }

        // Initialize the UserModel
        $userModel = new UserModel();

        // Build the query dynamically
        $query = $userModel
            ->select(implode(',', $fields)); // Select fields dynamically

        // Apply dynamic WHERE conditions, if provided
        if (!empty($where)) {
            $query->where($where);
        }

        // Fetch data as an array
        $result = $query->get()->getResultArray(); // Converts result to an array

        // Return the result
        if ($result) {
            return $result;
        } else {
            return [];
        }
    }
}
