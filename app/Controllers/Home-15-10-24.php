<?php



namespace App\Controllers;



use Mpdf;

use App\Models\UserMetaDataModel;

use App\Models\TeamTransferModel;

use App\Models\PerformanceDetailModel;

use App\Models\GalleryModel;


use DateTime;

class Home extends BaseController

{

    public function index(): string

    {

        return view('welcome_message');
    }





    public function calculateAge($dob)
    {
        // List of date formats to try
        $formats = ['Y-m-d', 'd-m-Y', 'm-d-Y', 'Y/m/d', 'd/m/Y', 'm/d/Y'];

        // Try creating DateTime object from each format
        foreach ($formats as $format) {
            $date = DateTime::createFromFormat($format, $dob);

            if ($date !== false) {
                // If a valid date is found, calculate the age
                $currentDate = new DateTime();
                $ageInterval = $currentDate->diff($date);
                return $ageInterval->y;  // return the age in years
            }
        }

        // If none of the formats worked, return an error message
        return "Invalid date format";
    }


    public function getPositionImage($action = 'genrate_img')
    {
        $userId = 58;
        // meta_value
        $meta_value = $this->request->getVar("meta_value");
        if (isset($meta_value) && !empty($meta_value)) {

            // $action = $_GET['action'];
            // $userId = $this->request->getPost('user_id');
            $metaKey = 'pdf_image'; // Fixed key for the example
            // $metaValue = $this->request->getPost('meta_value');

            // Get a reference to the database
            $db = \Config\Database::connect();

            // Check if the record exists
            $builder = $db->table('user_meta');
            $existingRecord = $builder->where('user_id', $userId)
                ->where('meta_key', $metaKey)
                ->get()
                ->getRowArray();

            if ($existingRecord) {
                // Update existing record
                $builder->where('id', $existingRecord['id'])
                    ->update(['meta_value' => $meta_value, 'user_id' => $userId]);
                $response = ['status' => 'success', 'message' => 'Record updated successfully.'];
            } else {
                // Insert new record
                $builder->insert([
                    'user_id' => $userId,
                    'meta_key' => $metaKey,
                    'meta_value' => $meta_value
                ]);
                $response = ['status' => 'success', 'message' => 'Record inserted successfully.'];
            }

            return $this->response->setJSON($response);
            die;
        } else {
            $action = 'some';
        }
        if ($action == 'pdf_image') {
            // $userId = $this->request->getPost('user_id');
            $metaKey = 'pdf_image'; // Fixed key for the example
            $metaValue = $this->request->getPost('meta_value');
            // Get a reference to the database
            $db = \Config\Database::connect();
            // Check if the record exists
            $builder = $db->table('user_meta_data');
            $existingRecord = $builder->where('user_id', $userId)
                ->where('meta_key', $metaKey)
                ->get()
                ->getRowArray();

            if ($existingRecord) {
                // Update existing record
                $builder->where('id', $existingRecord['id'])
                    ->update(['meta_value' => $metaValue]);
                $response = ['status' => 'success', 'message' => 'Record updated successfully.'];
            } else {
                // Insert new record
                $builder->insert([
                    'user_id' => $userId,
                    'meta_key' => $metaKey,
                    'meta_value' => $metaValue
                ]);
                $response = ['status' => 'success', 'message' => 'Record inserted successfully.'];
            }

            return $this->response->setJSON($response);
        } else {
            return view('position_image');
        }
    }
    public function generatePDF()

    {
        // $updated_image = $this->getPositionImage();
        // echo '<div id="mera_html_div">' . htmlspecialchars($updated_image) . '</div>';
        $base64String = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUA...'; // Truncated base64 data

        // Remove the image type and base64 prefix from the string
        $image_parts = explode(";base64,", $base64String);
        $image_type_aux = explode("image/", $image_parts[0]);
        $image_type = $image_type_aux[1];
        $image_base64 = base64_decode($image_parts[1]);

        // Specify the file name and path
        $fileName = 'generated_image.' . rand() . $image_type;
        $filePath = FCPATH . '' . $fileName;

        // Save the image to the server
        file_put_contents($filePath, $image_base64);

        // echo "Image saved at: " . $filePath;
        try {
            $defaultConfig = (new Mpdf\Config\ConfigVariables())->getDefaults();
            $flagPath = base_url() . 'public/assets/images/';
            $fontDirs = $defaultConfig['fontDir'];
            $defaultFontConfig = (new Mpdf\Config\FontVariables())->getDefaults();

            $fontData = $defaultFontConfig['fontdata'];
            $mpdf_data = [
                'debug' => true,
                'fontDir' => array_merge($fontDirs, [
                    FCPATH . '/public/assets/fonts',
                ]),
                'fontdata' => $fontData + [ // lowercase letters only in font key
                    'roboto' => [
                        'R' => 'Roboto-Regular.ttf',
                        'I' => 'Roboto-Regular.ttf',
                        'B' => 'Roboto-Bold.ttf',
                    ]
                ],
                'default_font' => 'roboto'
            ];
            $id = 58; //test_by_amrit

            if (!empty(auth()->id())) {
                #$id = auth()->id();
            }

            $imagePath = base_url() . 'uploads/';

            $mpdf = new \Mpdf\Mpdf($mpdf_data);

            $this->db = \Config\Database::connect();

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
            // get positions
            // $builder->join('player_positions pp', 'pp.user_id = users.id AND pp.user_id = '.$id, 'LEFT');
            // $builder->join('positions p', 'p.id = pp.position_id', 'LEFT');
            $builder->where('users.id', $id);
            // $builder->groupBy('users.id');
            $userData   = $builder->get()->getRowArray();
            $userMetaObject = new UserMetaDataModel();
            if ($userData) {
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
                // $userData->email = $userData->email;
                $userData['meta'] = $meta;
            }
            $data['userData'] = $userData; // Here set Variable $userData
            /* ##### Team Transfer Detail ##### */
            $teamTransferModel = new TeamTransferModel();
            $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
            $this->request->setLocale($currentLang);
            $userId = $id;
            // $userId = auth()->id();
            $logoPath = base_url() . 'uploads/logos/';
            $profilePath = base_url() . 'uploads/';
            $builder = $teamTransferModel->builder();
            $builder->select('
                    team_transfers.*, 
                    um1.meta_value as team_name_from, 
                    t1.team_type as team_type_from, 
                    pi1.meta_value as team_logo_from,
                    CONCAT( "' . $profilePath . '", pi1.meta_value) AS team_logo_path_from,
                    c1.country_name as country_name_from,
                    CONCAT( "' . $logoPath . '", c1.country_flag) AS country_flag_path_from,
                    um2.meta_value as team_name_to, 
                    t2.team_type as team_type_to, 
                    pi2.meta_value as team_logo_to,
                    CONCAT( "' . $profilePath . '", pi2.meta_value) AS team_logo_path_to,
                    c2.country_name as country_name_to,
                    CONCAT( "' . $logoPath . '", c2.country_flag) AS country_flag_path_to
                ');
            $builder->join('teams t1', 't1.id = team_transfers.team_from', 'LEFT');
            $builder->join('(SELECT user_id, meta_key, meta_value FROM user_meta WHERE meta_key = "club_name") um1', 'um1.user_id = t1.club_id', 'LEFT');
            $builder->join('(SELECT user_id, meta_key, meta_value FROM user_meta WHERE meta_key = "profile_image") pi1', 'pi1.user_id = t1.club_id', 'LEFT');
            $builder->join('user_nationalities un1', 'un1.user_id = t1.club_id', 'LEFT');
            $builder->join('countries c1', 'c1.id = un1.country_id', 'LEFT');
            $builder->join('teams t2', 't2.id = team_transfers.team_to', 'LEFT');
            $builder->join('(SELECT user_id, meta_key, meta_value FROM user_meta WHERE meta_key = "club_name") um2', 'um2.user_id = t2.club_id', 'LEFT');
            $builder->join('(SELECT user_id, meta_key, meta_value FROM user_meta WHERE meta_key = "profile_image") pi2', 'pi2.user_id = t2.club_id', 'LEFT');
            $builder->join('user_nationalities un2', 'un2.user_id = t2.club_id', 'LEFT');
            $builder->join('countries c2', 'c2.id = un2.country_id', 'LEFT');
            $builder->where('team_transfers.user_id', $userId);
            $builder->orderBy('team_transfers.id', 'DESC');
            $transferQuery = $builder->get();
            $transferDetail = $transferQuery->getResultArray();
            $data['transferDetail'] = $transferDetail; // Here is set $transferDetail
            /* ##### End Team Transfer Detail ##### */
            /* #### Performance Detail #### */
            $performanceDetailModel = new PerformanceDetailModel();
            $builder = $performanceDetailModel->builder();
            $builder->select('
                        performance_details.*, 
                        um.meta_value as team_name, 
                        pi.meta_value as team_logo,
                        CONCAT( "' . $profilePath . '", pi.meta_value) AS team_logo_path,
                        c.country_name as country_name,
                        CONCAT( "' . $logoPath . '", c.country_flag) AS country_flag_path
                    ');
            $builder->join('teams t', 't.id = performance_details.team_id', 'INNER');
            $builder->join('(SELECT user_id, meta_key, meta_value FROM user_meta WHERE meta_key = "club_name") um', 'um.user_id = t.club_id', 'INNER');
            $builder->join('(SELECT user_id, meta_key, meta_value FROM user_meta WHERE meta_key = "profile_image") pi', 'pi.user_id = t.club_id', 'INNER');
            $builder->join('user_nationalities un', 'un.user_id = t.club_id', 'INNER');
            $builder->join('countries c', 'c.id = un.country_id', 'INNER');
            $builder->where('performance_details.user_id', $userId);
            $builder->orderBy('performance_details.id', 'DESC');
            $performanceQuery = $builder->get();
            $performanceDetail = $performanceQuery->getResultArray();
            $data['performanceDetail'] = $performanceDetail; // Here is set $transferDetail
            /* #### End Performance Detail #### */
            /* #### Gallery #### */
            $galleryModel = new GalleryModel();
            $images = $galleryModel->where('user_id', $userId)->where('is_featured', 1)->whereIn('file_type', ALLOWED_IMAGE_EXTENTION)->orderBy('id', 'DESC')->findAll();
            $data['images'] = $images; // Here is set $transferDetail
            # $videos = $galleryModel->where('user_id', $userId)->whereIn('file_type', ALLOWED_VEDIO_EXTENTION)->orderBy('id','DESC')->findAll();
            /* #### End Gallery #### */
            $stylesheet = file_get_contents(FCPATH . 'public/assets/css/user-profile-pdf.css'); // external css
            // echo $content; exit;
            $age = '';
            if (!empty($userData['meta']['date_of_birth'])) {
                $age = $this->calculateAge($userData['meta']['date_of_birth']);
            }

            // echo '<pre>';
            // print_r($commonArr);
            // die;
            // $data = [
            //     'title' => 'Page with JavaScript',
            //     'message' => 'This is dynamic content inside the view.',
            // ];
            $positions = json_decode($userData['positions'], true);
            $content2 = view('position_image.php', $positions);
            $content2 = [];
            $commonArr = ['fileName' => $fileName, 'mpdf' => $mpdf, 'age' => $age, 'imagePath' => $imagePath, 'flagPath' => $flagPath, 'performanceDetail' => $performanceDetail, 'transferDetail' => $transferDetail, 'userData' => $userData, 'images' => $images, 'positionsTxt' => ''];
            // echo $content2;
            // die;
            // echo $content;
            $content = view('new_html.php', $commonArr);
            // $content = view('generate_pdf.php', $commonArr);
            echo ($content);
            die;
            $mpdf->WriteHTML($content);
            $this->response->setHeader('Content-Type', 'application/pdf');
            $mpdf->Output();
        } catch (\Mpdf\MpdfException $e) { // Note: safer fully qualified exception name used for catch
            // Process the exception, log, print etc.
            echo $e->getMessage();
        }
    }
    public function downloadUserPdf()
    {
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale($currentLang);
        $userId = auth()->id();
        if (!empty($userId)) {
            $pdfDir = FCPATH . '/export_csv/'; // Change this to your PDF directory
            $staticPdfPath = $pdfDir . 'football_tutorial.pdf'; // Static PDF to use if user's PDF does not exist
            // echo $staticPdfPath; die;
            $timestamp = date('y_m_d_h_i_s');
            // Construct the user's PDF file name
            $pdfFileName = "pdf_{$userId}_{$timestamp}.pdf";
            $userPdfPath = $pdfDir . $pdfFileName;
            $pdfFileName = "pdf_{$userId}_{$timestamp}.pdf";
            $pdf_folder = 'export_csv/'.$pdfFileName;
            // Check if the PDF exists
            if (file_exists($userPdfPath)) {
                // PDF exists, return the file path for download
                // return $userPdfPath;
                $response = [
                    "status"    => true,
                    //"message"   => '',//lang('App.pdfDownloadFailed'), // 
                    "data"      => ['userPdfPath'=>base_url().$pdf_folder]
                ];
            } else {
                // PDF does not exist, create a new one based on the static template
                 
            
                if (file_exists($staticPdfPath)) {
                    // Copy the static PDF and rename it for the user
                    if (copy($staticPdfPath, $userPdfPath)) {
                        // Return the new file path
                        // return $userPdfPath;
                        $response = [
                            "status"    => true,
                            //"message"   => '',//lang('App.pdfDownloadFailed'), // 
                            "data"      => ['userPdfPath'=>base_url().$pdf_folder]
                        ];
                    } else {
                        $response = [
                            "status"    => false,
                            "message"   => 'Unable to copy static PDF to user-specific file.',//lang('App.pdfDownloadFailed'), // 
                            "data"      => []
                        ];
                        // Error copying the file
                        // return "Error: Unable to copy static PDF to user-specific file.";
                    }
                } else {
                    // Static template does not exist
                    // return "Error: Static PDF template not found.";
                    $response = [
                        "status"    => false,
                        "message"   => 'PDF template not found.',//lang('App.pdfDownloadFailed'), // 
                        "data"      => []
                    ];
                }
            }
        }else{
            $response = [
                "status"    => false,
                "message"   => 'User not Found.',//lang('App.pdfDownloadFailed'), // 
                "data"      => []
            ];
        }
        // echo '<pre>'; print_r($response); die;
        return $this->respondCreated($response);
    }
}
