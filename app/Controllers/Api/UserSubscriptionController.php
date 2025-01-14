<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use App\Models\UserSubscriptionModel;
use App\Models\PackageModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class UserSubscriptionController extends ResourceController
{
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);
        $this->db                           = \Config\Database::connect();
        $this->userSubscriptionModel        = new UserSubscriptionModel();
    }

    public function getActivePackages($userId = null){

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));
        $userId = $userId ?? auth()->id();

        $premium_packages = [1, 2];
        $booster_packages = [3, 4];
        $country_packages = [5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24];
        $demo_packages = [25, 26];


        // Fetch packages in three groups
        $premium = $this->getPackages($userId, $premium_packages);
        $country = $this->getPackages($userId, $country_packages);
        $booster = $this->getPackages($userId, $booster_packages);
        $demo = $this->getPackages($userId, $demo_packages);

        // Return data or pass to a view
        $packages =  [
            'premium'   => $premium,
            'country'   => $country,
            'booster'   => $booster,
            'demo'      => $demo,
        ];

        if ($packages) {
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => ['packages' => $packages]
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

    // Helper function to reduce query duplication
    private function getPackages($userId, $packageIds) {
        $userCurrency = getUserCurrency($userId);

        $packages =  $this->userSubscriptionModel
                    ->select('user_subscriptions.*,
                        p.title as package_name, 
                        pd.interval, 
                        pp.price, 
                        pp.currency,
                        p.domain_id
                    ')
                    ->join('package_details pd', 'pd.id = user_subscriptions.package_id', 'INNER')
                    ->join('packages p', 'p.id = pd.package_id', 'INNER')
                    // ->join('package_prices pp', 'pp.package_detail_id = pd.id' , 'LFFT')
                    ->join('package_prices pp', 'pp.package_detail_id = pd.id AND pp.currency = "'. $userCurrency.'"' , 'LFFT')

                    ->where('user_subscriptions.user_id', $userId)
                    ->where('user_subscriptions.status', 'active')
                    ->whereIn('user_subscriptions.package_id', $packageIds)
                    ->orderBy('user_subscriptions.package_id', 'ASC')
                    ->findAll();
        // echo ' >>>>>>>>>>>>>>>>>> getLastQuery >>>>> '. $this->db->getLastQuery(); exit;

        return  $packages;           
    }


    /* public function getActiveDomains($userId = null){

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));
        $userId = $userId ?? auth()->id();

        $country_packages = [5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24];


        // Fetch packages in three groups
        // $country = $this->getPackages($userId, $country_packages);

        $packages =  $this->userSubscriptionModel
                        ->select('user_subscriptions.*,
                            p.title as package_name, 
                            pd.interval, 
                            pd.price, 
                            pd.currency,
                            p.domain_id
                        ')
                        ->join('package_details pd', 'pd.id = user_subscriptions.package_id', 'INNER')
                        ->join('packages p', 'p.id = pd.package_id', 'INNER')
                        ->join('domains d', 'd.id = p.domain_id', 'RIGHT')
                        ->where('user_subscriptions.user_id', $userId)
                        ->where('user_subscriptions.status', 'active')
                        ->whereIn('user_subscriptions.package_id', $country_packages)
                        ->orderBy('user_subscriptions.package_id', 'ASC')
                        ->findAll();

                        // echo '>>>>>>>>>>>>>>>>>>> getLastQuery >> ' . $this->db->getLastQuery();

        if ($packages) {
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => ['packages' => $packages]
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


    // Helper function to reduce query duplication
    public function getUserActivePackages($userId = null){

        $userSubscriptionModel = new UserSubscriptionModel();
        $userId = $userId ?? auth()->id();

        $premium_packages = [1, 2];
        $booster_packages = [3, 4];
        $country_packages = [5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24];
        $demo_packages = [25, 26];


        // Fetch packages in three groups
        $premium = $this->getUserPackages($userId, $premium_packages);
        $country = $this->getUserPackages($userId, $country_packages);
        $booster = $this->getUserPackages($userId, $booster_packages);
        $demo = $this->getUserPackages($userId, $demo_packages);

        // Return data or pass to a view
        $packages =  [
            'premium'   => $premium,
            'country'   => $country,
            'booster'   => $booster,
            'demo'      => $demo
        ];

        return $packages;
    }

    public function getUserPackages($userId, $packageIds) {
        $userSubscriptionModel = new UserSubscriptionModel();
        $userCurrency = getUserCurrency($userId);
        $packages =  $userSubscriptionModel
                    ->select('user_subscriptions.*,
                        p.title as package_name, 
                        pd.interval, 
                        pp.price, 
                        pp.currency,
                        p.domain_id
                    ')

                    // pd.price, 
                    // pd.currency,

                    ->join('package_details pd', 'pd.id = user_subscriptions.package_id', 'INNER')
                    ->join('packages p', 'p.id = pd.package_id', 'INNER')
                    ->join('package_prices pp', 'pp.package_detail_id = pd.id AND pp.currency =  "'.$userCurrency.'"', 'LEFT')
                    ->where('user_subscriptions.user_id', $userId)
                    ->where('user_subscriptions.status', 'active')
                    ->whereIn('user_subscriptions.package_id', $packageIds)
                    ->orderBy('user_subscriptions.package_id', 'ASC')
                    ->findAll();

        return  $packages;           
    }

    public function getUserPurchaseHistoryBCKp($userId = null) {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));
        $documentsPath =  base_url() . 'uploads/documents/';

        if ($userId == null) {
            $userId = auth()->id();
        }
        $userId = '58';
        $purchaseQuery = $this->userSubscriptionModel;
        $purchaseQuery->select('
            *,
            packages.title as package_name,
            CONCAT("' . $documentsPath . '", invoice_file) as invoice_file_path
        ');

        $purchaseQuery->join('packages', 'packages.id = us.package_id', 'LEFT');  // Joining with packages table
        // $builder->join('package_prices pp', 'pp.package_detail_id = pd.id AND pp.currency = (SELECT currency FROM domains WHERE id = '.$user_domain.')', 'LEFT');



        /* ### Code By Amrit ### */
        $url  = $this->request->getServer('REQUEST_URI');
        $params = getQueryString($url);
        if ($params && !empty($params['search'])) {
            $purchaseQuery->like('id', $params['search']);
        }

        /* ### End Code By Amrit ### */
        $purchaseQuery->where('user_id',  $userId);
        // $purchaseQuery->join('packages', 'packages.id = user_subscriptions.package_id', 'left');  // Joining with packages table

        
        $purchaseQuery->orderBy('id',  'DESC');

        # $purchaseHistory = $purchaseQuery->findAll();
        /* ### Paging By Amrit ### */
        // Pagination variables
        $page = $params['page'] ?? 1; // Current page, default is 1
        $perPage = $params['limit'] ?? 10; // Items per page, default is 10
        $offset = ($page - 1) * $perPage;

        // Applying limit and offset for pagination
        $purchaseQuery->limit($perPage, $offset);

        $purchaseHistory = $purchaseQuery->findAll();

        // Optionally, count total records for pagination metadata
        $totalRecords = $this->userSubscriptionModel->where('user_id', $userId)->countAllResults();

        // $response = [
        //     'status' => true,
        //     'message' => 'Purchase history retrieved successfully',
        //     'data' => $purchaseHistory,
        //     'pagination' => [
        //         'total_records' => $totalRecords,
        //         'current_page' => $page,
        //         'per_page' => $perPage,
        //         'total_pages' => ceil($totalRecords / $perPage),
        //     ],
        // ];
        /* ### Paging By Amrit ### */
        if ($purchaseHistory) {
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => ['purchaseHistory' => $purchaseHistory, 'totalCount' => $totalRecords]
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

    // get list of Transactions
    public function getTransactions() {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $url  = $this->request->getServer('REQUEST_URI');
        $params = getQueryString($url);
        $limit = $params['limit'] ?? PER_PAGE;
        $offset = $params['offset'] ?? 0;
        $no_limit = $params['no_limit'] ?? false;

        $purchaseQuery = $this->userSubscriptionModel;

        if ($params && !empty($params['search'])) {
            $purchaseQuery->like('id', $params['search'])
                ->orLike('payer_email', $params['search'])
                ->orLike('coupon_used', $params['search'])
                ->orLike('invoice_number', $params['search']);
        }

        $purchaseQuery->orderBy('id', 'DESC');
        $totalCount = $purchaseQuery->countAllResults();

        if ($no_limit != true) {
            $transactions = $purchaseQuery->findAll($limit, $offset);
        } else {
            $transactions = $purchaseQuery->findAll();
        }

        if ($transactions) {
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => [
                    'totalCount'    => $totalCount,
                    'transactions'  => $transactions,
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

    public function getSubscriptionPlans()
    {
        $this->PackageModel  = new PackageModel();
        // $purchaseQuery->orderBy('id', 'DESC');
        $packageQuery = $this->PackageModel;
        $titlesArr = ['Premium', 'Multi-Country', 'Boost Profile'];
        $packageQuery->whereIn('title', $titlesArr);

        $packageQuery->orderBy('id', 'DESC');
        $packages = $packageQuery->findAll();
        if ($packages) {
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => [
                    $packages
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

    public function getUserPurchaseHistory_01_10_24($userId = null)
    {
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));
        $documentsPath =  base_url() . 'uploads/documents/';

        if ($userId == null) {
            $userId = auth()->id();
        }
        $purchaseQuery = $this->userSubscriptionModel;
        $purchaseQuery->select('
            user_subscriptions.*,      
            packages.title as name,        
            CONCAT("' . $documentsPath . '", invoice_file) as invoice_file_path
        ');

        /* ### Code By Amrit ### */
        $url  = $this->request->getServer('REQUEST_URI');
        $params = getQueryString($url);

        if ($params && !empty($params['search'])) {
            $purchaseQuery->like('user_subscriptions.id', $params['search']);
        }
        /* ### End Code By Amrit ### */

        $purchaseQuery->where('user_subscriptions.user_id', $userId);
        $purchaseQuery->join('packages', 'packages.id = user_subscriptions.package_id', 'left');  // Joining with packages table
        $purchaseQuery->orderBy('user_subscriptions.id', 'DESC');

        // Pagination variables
        $page = isset($params['page']) ? (int) $params['page'] : 1;  // Cast to int, default is 1
        $perPage = isset($params['limit']) ? (int) $params['limit'] : 10;  // Cast to int, default is 10
        $offset = ($page - 1) * $perPage;

        // Applying limit and offset for pagination
        $purchaseQuery->limit((int) $perPage, (int) $offset);


        $purchaseHistory = $purchaseQuery->findAll();

        // Optionally, count total records for pagination metadata
        $totalRecords = $this->userSubscriptionModel->where('user_id', $userId)->countAllResults();


        if ($purchaseHistory) {
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => ['purchaseHistory' => $purchaseHistory, 'totalCount' => $totalRecords, 'currentPage' => $page, 'limit' => $perPage,]
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
    public function getUserPurchaseHistory($userId = null)
    {
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));
        $documentsPath =  base_url() . 'uploads/documents/';

        // if ($userId == null) {
        //     $userId = auth()->id();
        // }
        $userId = $userId ?? auth()->id(); 

        // check if representator
        // $user_role = getUserRoleByID($userId, 'id');
        // if($user_role == 5){
        //     $authUser = auth()->user();
        //     $userId = $authUser->parent_id;
        // }
        // check if representator
        if(in_array(auth()->user()->role, REPRESENTATORS_ROLES)){
            $authUser = auth()->user();
            $userId = $authUser->parent_id;
        }
        $userCurrency = getUserCurrency($userId);
        $purchaseQuery = $this->userSubscriptionModel;
        $purchaseQuery->select('
            user_subscriptions.*, 
            p.title as package_name, 
            pd.interval, 
            pp.price, 
            pp.currency,
            CONCAT("' . $documentsPath . '", invoice_file) as invoice_file_path,
            domains.location as user_location,
            coupons.discount_type,
            coupons.discount as coupon_discount,
            coupons.status as coupons_status
        '); //   coupons.status as coupon_status,

        $url  = $this->request->getServer('REQUEST_URI');
        $params = getQueryString($url);

        if ($params && !empty($params['search'])) {
            $purchaseQuery->like('user_subscriptions.id', $params['search']);
        }

        $purchaseQuery->where('user_subscriptions.user_id', $userId);
        $purchaseQuery->join('package_details pd', 'pd.id = user_subscriptions.package_id', 'INNER');
        $purchaseQuery->join('package_prices pp', 'pp.package_detail_id = pd.id AND pp.currency = "'.$userCurrency.'"', 'LEFT');


        $purchaseQuery->join('packages p', 'p.id = pd.package_id', 'INNER');

        // $purchaseQuery->join('packages', 'packages.id = user_subscriptions.package_id', 'LEFT');  // Joining with packages table
        // $purchaseQuery->join('domains', 'domains.id = users.user_domain', 'left');
        $purchaseQuery->join('coupons', 'coupons.coupon_code = user_subscriptions.coupon_used', 'left');
        $purchaseQuery->join('users', 'users.id = user_subscriptions.user_id', 'left');
        $purchaseQuery->join('domains', 'domains.id = users.user_domain', 'left');
        $purchaseQuery->orderBy('user_subscriptions.id', 'DESC');

        // Pagination variables
        $page = isset($params['page']) ? (int) $params['page'] : 1;
        $perPage = isset($params['limit']) ? (int) $params['limit'] : 10;
        $offset = ($page - 1) * $perPage;

        $purchaseQuery->limit((int) $perPage, (int) $offset);

        // Fetch data without any VAT logic
        $purchaseHistory = $purchaseQuery->findAll();
        // echo '<pre>'; print_r($purchaseHistory); die;
        // Initialize an array to hold processed data
        $processedPurchaseHistory = [];

        // Loop through the results and apply VAT logic
        $processedPurchaseHistory = [];

        // Loop through the results and apply VAT logic
        foreach ($purchaseHistory as $purchase) {
            // Initialize default VAT and subtotal
            $subtotal = $purchase['plan_amount'];
            // $vatAmount = 0; 
            $vat = 0.0; //8.1%
            $vat_text = 0.0;
            $subtotalPrice = '';
            $tax_amount = '';
            // Check if the location is Switzerland
            if ($purchase['user_location'] == 'Switzerland') {
                $vat = 0.081; // 8.1%
                $vat_text = 8.1;
            }
            // elseif ($purchase['location'] == 'Germany') {
            //     $vat = 0.19; // 19%
            //     $vat_text = 19;
            // } elseif ($purchase['location'] == 'Italy') {
            //     $vat = 0.22; // 22%
            //     $vat_text = 22;
            // } elseif ($purchase['location'] == 'France') {
            //     $vat = 0.20; // 20%
            //     $vat_text = 20;
            // } elseif ($purchase['location'] == 'England') {
            //     $vat = 0.20; // 20%
            //     $vat_text = 20;
            // } elseif ($purchase['location'] == 'Spain') {
            //     $vat = 0.21; // 21%
            //     $vat_text = 21;
            // } elseif ($purchase['location'] == 'Portugal') {
            //     $vat = 0.23; // 23%
            //     $vat_text = 23;
            // } elseif ($purchase['location'] == 'Belgium') {
            //     $vat = 0.21; // 21%
            //     $vat_text = 21;
            // } elseif ($purchase['location'] == 'Denmark') {
            //     $vat = 0.25; // 25%
            //     $vat_text = 25;
            // } elseif ($purchase['location'] == 'Sweden') {
            //     $vat = 0.25; // 25%
            //     $vat_text = 25;
            // }

            // VAT calculation
            $tax_amount = $vat * $subtotal;
            $subtotalPrice = $subtotal - $tax_amount;
            $vatAmount = round($purchase['plan_amount'] - $subtotalPrice, 2); // VAT amount

            // Add the processed purchase to the new array, preserving the original fields and adding new ones
            $processedPurchaseHistory[] = array_merge($purchase, [
                'subtotal' => number_format((float)$subtotalPrice, 2, '.', ''), // Subtotal (without VAT for Switzerland)
                'total' => number_format((float)$purchase['plan_amount'], 2, '.', ''), // Total is same as plan_amount (includes VAT for Switzerland)
                'tax_amount' => number_format((float)$tax_amount, 2, '.', ''),
                'tax_percentage' => $vat_text
            ]);
        }

        // Now you can return or render $processedPurchaseHistory
        // return $processedPurchaseHistory;

        $totalRecords = $this->userSubscriptionModel->where('user_id', $userId)->countAllResults();
        $purchase_history_url = base_url().'api/user/export-purchase-history';
        if ($processedPurchaseHistory) {
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => ['purchaseHistory' => $processedPurchaseHistory, 'totalCount' => $totalRecords, 'currentPage' => $page, 'limit' => $perPage,'purchaseHistoryDownload'=>$purchase_history_url]
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
    public function getUserPurchaseHistoryExport($userId = null)
    {
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));
        $documentsPath =  base_url() . 'uploads/documents/';
        $is_admin = false;

        $user = getUserByID(auth()->id()); // current user ID
        if (isset($user) && !empty($user) && $user->role == 1) {
            $is_admin = true;
        }

        if ($userId == null) {
            $userId = auth()->id();
        }

        $firstName = $user->first_name;
        $lastName = $user->last_name;
        // echo '<pre>'; print_r($userId); die;
        $purchaseQuery = $this->userSubscriptionModel;
        $purchaseQuery->select('
            user_subscriptions.*, 
            packages.title as  package_name,
            CONCAT("' . $documentsPath . '", invoice_file) as invoice_file_path,
            domains.location as user_location,
            coupons.discount_type,
            coupons.discount as coupon_discount,
            coupons.status as coupons_status
        '); //   coupons.status as coupon_status,

        $url  = $this->request->getServer('REQUEST_URI');
        $params = getQueryString($url);

        if ($params && !empty($params['search'])) {
            $purchaseQuery->like('user_subscriptions.id', $params['search']);
        }

        $purchaseQuery->where('user_subscriptions.user_id', $userId);
        $purchaseQuery->join('packages', 'packages.id = user_subscriptions.package_id', 'left'); //coupon_used
        // $purchaseQuery->join('domains', 'domains.id = users.user_domain', 'left');
        $purchaseQuery->join('coupons', 'coupons.coupon_code = user_subscriptions.coupon_used', 'left');
        $purchaseQuery->join('users', 'users.id = user_subscriptions.user_id', 'left');
        $purchaseQuery->join('domains', 'domains.id = users.user_domain', 'left');
        $purchaseQuery->orderBy('user_subscriptions.id', 'DESC');

        // Pagination variables
        $page = isset($params['page']) ? (int) $params['page'] : 1;
        $perPage = isset($params['limit']) ? (int) $params['limit'] : 10;
        $offset = ($page - 1) * $perPage;
        if (!isset($params['no_limit']) && empty($params['no_limit'])) {
            $purchaseQuery->limit((int) $perPage, (int) $offset);
        }

        // Fetch data without any VAT logic
        $purchaseHistory = $purchaseQuery->findAll();

        // Loop through the results and apply VAT logic0
        $processedPurchaseHistory = [];

        // Loop through the results and apply VAT logic
        foreach ($purchaseHistory as $purchase) {
            // Initialize default VAT and subtotal
            $subtotal = $purchase['plan_amount'];
            // $vatAmount = 0; 
            $vat = 0.0; //8.1%
            $vat_text = 0.0;
            $subtotalPrice = '';
            $tax_amount = '';
            // Check if the location is Switzerland
            if ($purchase['user_location'] == 'Switzerland') {
                $vat = 0.081; // 8.1%
                $vat_text = 8.1;
            }

            // VAT calculation
            $tax_amount = $vat * $subtotal;
            $subtotalPrice = $subtotal - $tax_amount;
            $vatAmount = round($purchase['plan_amount'] - $subtotalPrice, 2); // VAT amount

            // Add the processed purchase to the new array, preserving the original fields and adding new ones
            $processedPurchaseHistory[] = array_merge($purchase, [
                'subtotal' => number_format((float)$subtotalPrice, 2, '.', ''), // Subtotal (without VAT for Switzerland)
                'total' => number_format((float)$purchase['plan_amount'], 2, '.', ''), // Total is same as plan_amount (includes VAT for Switzerland)
                'tax_amount' => number_format((float)$tax_amount, 2, '.', ''),
                'tax_percentage' => $vat_text
            ]);
        }
        if (isset($processedPurchaseHistory) && !empty($processedPurchaseHistory)) {
            // Create a new Spreadsheet object
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            // Set the headers
            if ($is_admin) {
                $sheet->setCellValue('A1', 'ID');
                $sheet->setCellValue('B1', 'FirstName');
                $sheet->setCellValue('C1', 'LastName');
                $sheet->setCellValue('D1', 'Package Name');
                $sheet->setCellValue('E1', 'Voucher code');
                $sheet->setCellValue('F1', 'Amount');
                $sheet->setCellValue('G1', 'Date');

                $user = getUserByID($userId); // User Id by admin
                $firstName = $lastName = '';
                if (!empty($user->first_name)) {
                    $firstName = trim($user->first_name);
                }
                if (!empty($user->last_name)) {
                    $lastName = trim($user->last_name);
                }

                $row = 2; // Start from the second row
                foreach ($processedPurchaseHistory as $purchaseHistory) {
                    // echo '<pre>'; print_r($purchaseHistory); die;
                    $sheet->setCellValue('A' . $row, htmlspecialchars($purchaseHistory['id']));
                    $sheet->setCellValue('B' . $row, htmlspecialchars($firstName));
                    $sheet->setCellValue('C' . $row, htmlspecialchars($lastName));
                    $sheet->setCellValue('D' . $row, htmlspecialchars($purchaseHistory['package_name']));
                    $sheet->setCellValue('E' . $row, htmlspecialchars($purchaseHistory['coupon_used']));
                    $sheet->setCellValue('F' . $row, htmlspecialchars($purchaseHistory['amount_paid'] . ' ' . $purchaseHistory['amount_paid_currency']));
                    $sheet->setCellValue('G' . $row, htmlspecialchars(date('Y-m-d', strtotime($purchaseHistory['created_at']))));
                    $row++;
                }
            } else {
                $sheet->setCellValue('A1', 'ID');
                $sheet->setCellValue('B1', 'Date');
                $sheet->setCellValue('C1', 'Voucher code');
                $sheet->setCellValue('D1', 'Amount');
                $sheet->setCellValue('E1', 'Package Name');
                $row = 2; // Start from the second row
                foreach ($processedPurchaseHistory as $Purchase_History) {
                    $sheet->setCellValue('A' . $row, htmlspecialchars($Purchase_History['id']));
                    $sheet->setCellValue('B' . $row, htmlspecialchars(date('Y-m-d', strtotime($Purchase_History['created_at']))));
                    $sheet->setCellValue('C' . $row, htmlspecialchars($Purchase_History['coupon_used']));
                    $sheet->setCellValue('D' . $row, htmlspecialchars($Purchase_History['amount_paid'] . ' ' . $Purchase_History['amount_paid_currency']));
                    $sheet->setCellValue('E' . $row, htmlspecialchars($Purchase_History['package_name']));
                    $row++;
                }
            }
            $saveDirectory = WRITEPATH  . 'uploads/exports/';
            $filename = 'purchase_history_export_' . date('Y-m-d_H-i-s') . '.xlsx';
            $folder_nd_file = 'uploads/exports/'.$filename;
            // Full path of the file
            $filePath = $saveDirectory . $filename;
            // $fileURL = $ base_url() . '/exports/' . $filename; 

            // Check if the directory exists, if not, create it
            if (!is_dir($saveDirectory)) {
                mkdir($saveDirectory, 0777, true);  // Create the directory with proper permissions
            }

            // Create the Excel file and save it to the specified directory
            $writer = new Xlsx($spreadsheet);
            $writer->save($filePath);  // Save the file
            $activity_data = [
                'user_id'               => auth()->id(),
                'activity_type_id'      => 9,      // viewed
                'activity'              => 'Downloded Purchase history Csv.',
                'ip'                    => $this->request->getIPAddress()
            ];
            createActivityLog($activity_data);
            // Return the file details in the response
            $response = [
                "status"    => true,
                "message"   => "File created successfully.",
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
        return $this->respondCreated($response);
    }



    public function getCouponAppliedCount($coupon_code = null, $user_id = null){
        $userSubscriptionModel = new UserSubscriptionModel();

        $couponBuilder =  $userSubscriptionModel
                    ->selectCount('coupon_used')
                    ->where('coupon_used', $coupon_code);

                    if (!is_null($user_id)) {
                        $couponBuilder->where('user_id', $user_id);
                    }

        $coupon_used = $couponBuilder->first();
        return  $coupon_used['coupon_used'];  
    }
}
