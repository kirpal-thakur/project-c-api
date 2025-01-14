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



    public function generatePDF()

    {

        // $data = ['amrit' => 'myName is Amrit Pandit'];
        // ob_start();
        // echo view('playgound_or_positions');
        // $content = view('playgound_or_positions');
        // echo ($content);
        // $content = ob_get_clean();
        // echo '<img src="' . $content . '" alt=" My Image"> ';
        // die;
        try {

            $flagPath = base_url() . 'public/assets/images/';
            // view('generatePDF');
            die;
            $defaultConfig = (new Mpdf\Config\ConfigVariables())->getDefaults();

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
            $id = 58; // default id just by test

            if (!empty(auth()->id())) {
                // $id = auth()->id();
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
                            cb.club_name as player_current_club_name');
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
                $userData['meta'] = $meta;
            }
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
            // echo '<pre>'; print_r($performanceDetail); die(' ABC');
            /* #### End Performance Detail #### */
            /* #### Gallery #### */
            $galleryModel = new GalleryModel();
            $images = $galleryModel->where('user_id', $userId)->where('is_featured', 1)->whereIn('file_type', ALLOWED_IMAGE_EXTENTION)->orderBy('id', 'DESC')->findAll();
            # $videos = $galleryModel->where('user_id', $userId)->whereIn('file_type', ALLOWED_VEDIO_EXTENTION)->orderBy('id','DESC')->findAll();
            /* #### End Gallery #### */
            $content = '<!DOCTYPE html>

                <html lang="en">

                <head>

                <title>Soccer You</title>

                <meta charset="utf-8">

                <meta name="viewport" content="width=device-width, initial-scale=1">

                <meta name="robots" content="noindex,nofollow,noarchive" />

                <link rel="preconnect" href="https://fonts.googleapis.com">

                <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

                <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">

                <style type="text/css">

                body { padding:0 !important; margin:0 !important; display:block !important; min-width:100% !important; width:100% !important; -webkit-text-size-adjust:none }

                * {box-sizing: border-box;}

                body {font-family: "roboto";font-size: 14px; line-height: 1.35;}

                table {border-collapse: collapse;border:0;font-family: "roboto";}

                table td, table th {vertical-align: top;padding: 10px; text-align: left;line-height: 1.35;}

                .current_club {

                	font-size: 20px;

                }

                .performance_data, .transfer_history { width: 100%;background: #f8f8f8;padding: 12px;margin-top: 15px; }

                .performance_data tbody tr, .transfer_history tbody tr {

                	background: #fff;

                }

                .social_links {width: 160px;float: right;font-size: 13px;}

                table.player_detail td.detail_list {

                    width: 33.33% !important;

                    border-bottom: 1px solid #fff;

                    padding: 5px;

                }

                table.player_detail {

                    border-collapse: separate;

                    border-spacing: 10px;

                }

                table.player_detail td.detail_list.no_border {

                    border: 0;

                }

                .player_detail h3 {

                	font-size: 13px;

                }

                .court_position {position: relative;}

                img.ground_fix {position: absolute;left: 0;top: 0; }

                img.dot_posg {position: absolute;left: 0;top: 0;}

                </style>

                </head>';


            $role = '';
            $sm_x = '';
            $sm_facebook = '';
            $sm_instagram = '';
            $sm_tiktok = '';
            $sm_youtube = '';
            $sm_vimeo = '';
            if (isset($userData['role_name']) && !empty($userData['role_name'])) {
                $role = strtoupper($userData['role_name']);
            }




            $content .= '<body class="body" style="padding:0 !important; margin:0 !important; display:block !important; min-width:100% !important; width:100% !important;-webkit-text-size-adjust:none">

                    <div width="100%" border="0" cellspacing="0" cellpadding="0">

                        <div style="width:100%; min-width:650px; padding:0; margin:0 auto; font-weight:normal;">

                            <table width="100%" border="0" cellspacing="0" cellpadding="0" class="header">

                                <tr>

                                    <td style="width: 50%;padding-left: 0;">';

            $content .= '<table width="100%" border="0" cellspacing="0" cellpadding="0" class="Inner_header">
                                    		<tr>
                                    			<td>
                                                    <img src="' . $flagPath . 'logo.png" style="width: 150px;vertical-align: top;margin-top: -4px;">
                                                    <span style="font-size: 20px;padding-left: 5px;">' . $role . ' PROFILE</span>
                                                </td>
                                            </tr>
                                            <tr>
                                    			<td style="line-height: 1;">
                                                    <h1 style="width: 100%;float: left;font-size: 40px;margin: 20px 0;">' . $fullName . '<h1>
                                                </td>
                                            </tr>
                                            <tr>
                                    			<td class="current_club">
                                                    <strong>Current Club</strong><br />';
            if (!empty($userData['club_logo_path'])) {
                $club_logo_path = $userData['club_logo_path'];
            } else {
                $club_logo_path = 'https://api.socceryou.ch/no-img.png';
            }
            $clubLogo = get_headers($club_logo_path, 1);
            if (strpos($clubLogo[0], '200') !== false) {
                $content .= '<img src="' . $club_logo_path . '" style="width: 32px;float:left;margin-right: 4px;vertical-align:bottom;">';
            }
            $content .= $current_club_name;
            $content .= $userData['team_type'] ? ' (' . $userData['team_type'] . ')' : '';
            // echo $content; die(' here');
            $content .= '</td>
                                            </tr>';
            $content .= '<tr>
                                            	<td>
                                                    <table width="100%" border="0" cellspacing="0" cellpadding="0" class="Inner_header1">
                                                    	<tr>
	                                                        <td>
	                                                            <strong>Height</strong><br />
	                                                            <img src="' . $flagPath . 'Icons19.png" style="width: 25px;vertical-align: middle;margin-right: 4px;margin-top: 3px;float: left;vertical-align:bottom;"> ' . $height . '
	                                                        </td>
	                                                        <td>
	                                                            <strong>Weight</strong><br />
	                                                            <img src="' . $flagPath . 'Icons18.png" style="width: 25px;vertical-align: middle;margin-right: 4px;margin-top: 3px;float: left;vertical-align:bottom;"> ' . $weight . '
	                                                        </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>';
            $content .= '</td>
                                    <td style="width: 50%;padding-right: 0;">
                                    	<table width="100%" border="0" cellspacing="0" cellpadding="0" class="social_table">
                                        	<tr>
                                            	<td class="social_links">
                                            		<table width="100%" border="0" cellspacing="0" cellpadding="0">';
            if (isset($userData['meta']['sm_x']) && !empty($userData['meta']['sm_x'])) {
                $sm_x = trim($userData['meta']['sm_x']);
                $content .= '<tr>';
                $content .= '<td style="vertical-align: middle;padding: 5px;"><img src="' . $flagPath . 'social/twitter.svg" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;vertical-align:middle;">x.com/' . $sm_x . '</td>';
                $content .= '</tr>';
            }
            if (isset($userData['meta']['sm_facebook']) && !empty($userData['meta']['sm_facebook'])) {
                $sm_facebook = trim($userData['meta']['sm_facebook']);
                $content .= '<tr>';
                $content .= '<td style="vertical-align: middle;padding: 5px;"><img src="' . $flagPath . 'social/facebook.svg" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;vertical-align:middle;">facebook.com/' . $sm_facebook . '</td>';
                $content .= '</tr>';
            }
            if (isset($userData['meta']['sm_instagram']) && !empty($userData['meta']['sm_instagram'])) {
                $sm_instagram = trim($userData['meta']['sm_instagram']);
                $content .= '<tr>';
                $content .= '<td style="vertical-align: middle;padding: 5px;"><img src="' . $flagPath . 'social/instagram.svg" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;vertical-align:middle;">instagram.com/' . $sm_instagram . '</td>';
                $content .= '</tr>';
            }
            if (isset($userData['meta']['sm_tiktok']) && !empty($userData['meta']['sm_tiktok'])) {
                $sm_tiktok = trim($userData['meta']['sm_tiktok']);
                $content .= '<tr>';
                $content .= '<td style="vertical-align: middle;padding: 5px;"><img src="' . $flagPath . 'social/tiktok.svg" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;vertical-align:middle;">tiktok.com/' . $sm_tiktok . '</td>';
                $content .= '</tr>';
            }
            if (isset($userData['meta']['sm_youtube']) && !empty($userData['meta']['sm_youtube'])) {
                $sm_youtube = trim($userData['meta']['sm_youtube']);
                $content .= '<tr>';
                $content .= '<td style="vertical-align: middle;padding: 5px;"><img src="' . $flagPath . 'social/youtube.svg" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;vertical-align:middle;">youtube.com/' . $sm_youtube . '</td>';
                $content .= '</tr>';
            }
            if (isset($userData['meta']['sm_vimeo']) && !empty($userData['meta']['sm_vimeo'])) {
                $sm_vimeo = trim($userData['meta']['sm_vimeo']);
                $content .= '<tr>';
                $content .= '<td style="vertical-align: middle;padding: 5px;"><img src="' . $flagPath . 'social/vimeo.svg" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;vertical-align:middle;">vimeo.com/' . $sm_vimeo . '</td>';
                $content .= '</tr>';
            }
            //  echo $content; die;
            $content .= '</table>
                                            	</td>
                                        	</tr>
                                        	<tr>
                                            	<td class="bnr_img" style="vertical-align:bottom;">';
            if (!empty($userData['meta']['profile_image_path'])) {
                $profile_image_path = $userData['meta']['profile_image_path'];
            } else {
                $profile_image_path = $flagPath . 'banner-pic.png';
            }
            $content .= '<img src="' . $profile_image_path . '" style="max-width: 100%;">';
            $content .= '</td>
                                        	</tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>';
            $content .= '<table width="100%" border="0" cellspacing="0" cellpadding="0" class="player_detail" style="background: #f8f8f8;">
                            	<tr>
                                    <td colspan="3" style="padding: 0;">';
            if (!empty($images)) {
                foreach ($images as $image) {
                    $imagepathFull = $imagePath . $image['file_name'];
                    $headers = get_headers($imagepathFull, 1);
                    if (strpos($headers[0], '200') !== false) {
                        $content .= '<img src="' . $imagePath . $image['file_name'] . '" style="width: 23%;margin-left: 4px;">';
                    }
                }
            }
            $dob =  '';
            if (!empty($userData['meta']['date_of_birth'])) {
                $dob = $userData['meta']['date_of_birth'];
            }
            $age = $this->calculateAge($dob);
            $content .= '</td>
                                </tr>
                                <tr>
                                    <td class="detail_list">
                                        <h3>Age</h3>
                                        <p><img src="' . $flagPath . 'Icons20.png" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;"> ' . $age . ' Years</p>
                                    </td>
                                    <td class="detail_list no_border">
                                        <h3>In the team since</h3>
                                        <p>' . $in_team_since . '</p>
                                    </td>
                                    <td class="detail_list no_border">
                                        <h3>Top Speed</h3>
                                        <p>' . $top_speed . '</p>
                                    </td>
                                </tr>
                                <tr>';
            $content .= '<td class="detail_list">
                                        <h3>Nationality</h3>';
            if (!empty($user_nationalities)) {
                foreach ($user_nationalities as $nationality) {
                    $headers = get_headers($nationality['flag_path'], 1);
                    if (strpos($headers[0], '200') !== false) {
                        $content .= '<p>
                                                        <img src="' . $nationality['flag_path'] . '" style="width: 25px; vertical-align: middle; margin-right: 4px; float: left;">
                                                        ' . $nationality['country_name'] . '
                                                    </p>';
                    } else if (!empty($nationality['country_name'])) {
                        $content .= '<p>' . $nationality['country_name'] . '</p>';
                    }
                }
            }
            $content .= '</td>';
            $content .= '<td class="detail_list no_border">
                                        <h3>Current market value</h3>
                                        <p>' . $market_value . '</p>
                                    </td>';
            if (isset($userData['meta']['international_player']) && !empty($userData['meta']['international_player'])) {
                $international = trim($userData['meta']['international_player']);
                // $international = get_headers($userData['meta']['international_player'], 1);
                // if (strpos($international[0], '200') !== false) {
                $content .= '<td class="detail_list no_border">';
                $content .= '<h3>International Player</h3>';
                $content .= '<p>' . $international . '</p>';
                $content .= '</td>';
                // }
            }
            // <td class="detail_list no_border">
            //     <h3>International Player</h3>
            //     <p><img src="' . $flagPath . 'Icons8.png" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;"> Kosovo</p>
            // </td>
            $content .= '</tr>
                                <tr>
                                    <td class="detail_list">
                                        <h3>Date of birth</h3>
                                        <p><img src="' . $flagPath . 'Icons11.png" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;">' . $date_of_birth . '</p>
                                    </td>
                                    <td colspan="2" class="detail_list">
                                        <h3>Last change</h3>';
            if (!empty($userData['meta']['last_change'])) {
                $content .= '<p>' . $userData['meta']['last_change'] . '</p>';
            } else {
                $content .= '<p>-</p>';
            }
            $content .= ' </td>
                                </tr>
                                <tr>
                                    <td class="detail_list">
                                        <h3>Place of birth</h3>
                                        <p><img src="' . $flagPath . 'flag-1.png" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;">' . $userData['meta']['place_of_birth'] . '</p>
                                    </td>
                                    <td colspan="2" rowspan="4" class="detail_list no_border">
                                    	<table width="100%" border="0" cellspacing="0" cellpadding="0">
                                        	<tr>
		                                        <td class="court_position">';

            // <img src="' . $flagPath . 'Playground-image.png" class="ground_fix">

            // <img src="' . $flagPath . 'playground-point1.png" class="pos_st dot_posg">

            // <img src="' . $flagPath . 'playground-point2.png" class="pos_cam dot_posg">

            // <img src="' . $flagPath . 'playground-point3.png" class="pos_lm dot_posg">

            // <img src="' . $flagPath . 'playground-point4.png" class="pos_cm dot_posg">

            // <img src="' . $flagPath . 'playground-point5.png" class="pos_rm dot_posg">

            // <img src="' . $flagPath . 'playground-point6.png" class="pos_cdm dot_posg">

            // <img src="' . $flagPath . 'playground-point7.png" class="pos_lwb dot_posg">

            // <img src="' . $flagPath . 'playground-point8.png" class="pos_lb dot_posg">

            // <img src="' . $flagPath . 'playground-point9.png" class="pos_rb dot_posg">

            // <img src="' . $flagPath . 'playground-point10.png" class="pos_rwb dot_posg">

            // <img src="' . $flagPath . 'playground-point11.png" class="pos_gk dot_posg">

            $content .= '</td>
		                                    </tr>
		                                    <tr>
		                                        <td class="plyr_pos" style="padding: 0;">
		                                       	 	<table width="100%" border="0" cellspacing="0" cellpadding="0">
		                                       	 		<tr>';
            if (isset($userData['positions']) && !empty($userData['positions'])) {
                $positions = json_decode($userData['positions'], true);
                usort($positions, function ($a, $b) {
                    return $b['main_position'] <=> $a['main_position'];
                });
                // echo '<pre>'; print_r($positions); die;
                $otherPositions = [];
                foreach ($positions as $position) {
                    if (!empty($position['main_position']) && !empty($position['position_name'])) {
                        $content .= '<td><h3>Main Position</h3>' . $position['position_name'] . '</td>';
                    } else if (empty($position['main_position']) && !empty($position['position_name'])) {
                        $otherPositions[] = $position['position_name'];
                    }
                }
                if (!empty($otherPositions)) {
                    $content .= '<td><h3>Other position(s)</h3>' . implode("/", $otherPositions) . '</td>';
                }
            }
            $content .= '</tr>
		                                            </table>
		                                        </td>
		                                    </tr>
		                                </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="detail_list">
                                        <h3>Contract</h3>
                                        <p><img src="' . $flagPath . 'Icons15.png" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;"> 2017 - 2025</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="detail_list">';
            if (isset($userData['league_name']) && !empty($userData['league_name'])) {
                $content .= '<p>';
                if (isset($userData['league_logo_path']) && !empty($userData['league_logo_path'])) {
                    $league_logo_path = $userData['league_logo_path'];
                    $leaguepath = get_headers($league_logo_path, 1);
                    if (strpos($leaguepath[0], '200') !== false) {
                        $content .= '<h3>League</h3>';
                        $content .= '<img src="' . $league_logo_path . '" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;">';
                    }
                }
                $content .= $userData['league_name'] . '<p>';
            }
            $content .= '</td>
                                </tr>
                                <tr>
                                    <td class="detail_list no_border">';
            $foot = '';
            if (!empty($userData['meta']['foot'])) {
                $foot = trim(strtolower($userData['meta']['foot']));
                $foot_image = get_headers($flagPath . '/foot/' . $foot . '.svg', 1);
                // echo $foot_image; die;
                // echo '<pre>'; print_r($foot_image); die;
                if (strpos($foot_image[0], '200') !== false) {
                    $foot_image = $flagPath . '/foot/' . $foot . '.svg';
                    $content .= '<h3>Foot</h3>';
                    $content .= '<p><img src="' . $foot_image . '" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;"> ' . ucfirst($foot) . '</p>';
                }
            }
            $content .= '</td>
                                </tr>
                            </table>

                            <div class="transfer_history" style="page-break-before:always;">

                                <h2 style="margin-top: 5px;padding: 0 10px;">Transfer History</h2>

                                <table width="100%" border="0" cellspacing="0" cellpadding="0" class="table_transfer">

	                                <thead>

                                        <tr>

	                                        <th style="white-space: nowrap;"><img src="' . $flagPath . 'Icons6.png" style="width: 15px;vertical-align: middle;margin-right: 4px;float: left;"><span>Saison</span></th>

	                                        <th style="white-space: nowrap;"><img src="' . $flagPath . 'Icons20.png" style="width: 15px;vertical-align: middle;margin-right: 4px;float: left;"><span>Date</span></th>

	                                        <th>Moving From</th>

	                                        <th>Moving To</th>

	                                    </tr>

                                    </thead>

	                                <tbody>';

            if (!empty($transferDetail)) {

                foreach ($transferDetail as $transfer) {

                    // $date = date_format($transfer['date_of_transfer'], "d.m.Y");

                    $date = $transfer['date_of_transfer'];



                    $session = !empty($transfer['session']) ? $transfer['session'] : ' ';

                    $formattedDate = !empty($date) ? $date : ' ';

                    $teamFrom = !empty($transfer['team_name_from']) ? $transfer['team_name_from'] : ' ';

                    $countryFrom = !empty($transfer['country_name_from']) ? $transfer['country_name_from'] : ' ';



                    $headersFrom = get_headers($transfer['team_logo_path_from'], 1);

                    $teamLogoFrom = (strpos($headersFrom[0], '200') !== false) ? $transfer['team_logo_path_from'] : '';



                    $teamTo = !empty($transfer['team_name_to']) ? $transfer['team_name_to'] : '';

                    $countryTo = !empty($transfer['country_name_to']) ? $transfer['country_name_to'] : ' ';



                    $headersTo = get_headers($transfer['team_logo_path_to'], 1);

                    $teamLogoTo = (strpos($headersTo[0], '200') !== false) ? $transfer['team_logo_path_to'] : '';





                    $content .= '<tr>

                                                                                <td>' . $session . '</td>

                                                                                <td>' . $formattedDate . '</td>

                                                                                <td style="white-space: nowrap;">

                                                                                    <img src="' . $teamLogoFrom . '" style="width: 20px; vertical-align: middle; margin-right: 5px; margin-top: -2px;">

                                                                                    ' . $teamFrom . ' ,' . $countryFrom . '

                                                                                </td>

                                                                                <td style="white-space: nowrap;">

                                                                                    <img src="' . $teamLogoTo . '" style="width: 20px; vertical-align: middle; margin-right: 5px; margin-top: -2px;">

                                                                                    ' . $teamTo . ' ,' . $countryTo . '

                                                                                </td>

                                                                            </tr>';
                }
            }

            $content .= '

	                                </tbody>

                                </table>

                            </div>

                            <div class="performance_data">

                                <h2 style="margin-top: 5px;padding: 0 10px;">Performance Data</h2>

                                <table width="100%" border="0" cellspacing="0" cellpadding="0" class="table_perform">

                                	<thead>

	                                    <tr>

	                                        <th><img src="' . $flagPath . 'flag-1.png" style="width: 15px;vertical-align: middle;margin-right: 4px;float: left;margin-top: 3px;"><span>Team</span></th>

	                                        <th><img src="' . $flagPath . 'Icons6.png" style="width: 15px;vertical-align: middle;margin-right: 4px;float: left;"><span>Saison</span></th>

	                                        <th><img src="' . $flagPath . 'Icons13.png" style="width: 15px;vertical-align: middle;margin-right: 4px;float: left;"><span>Matches</span></th>

	                                        <th><img src="' . $flagPath . 'Icons17.png" style="width: 15px;vertical-align: middle;margin-right: 4px;float: left;"><span>Goals</span></th>

	                                        <th><img src="' . $flagPath . 'Icons14.png" style="width: 15px;vertical-align: middle;margin-right: 4px;float: left;"><span>Coach<br /> during debut</span></th>

	                                        <th><img src="' . $flagPath . 'Icons7.png" style="width: 15px;vertical-align: middle;margin-right: 4px;float: left;"><span>Age of<br /> player</span></th>

	                                    </tr>

	                                </thead>

	                                <tbody>';

            if (!empty($performanceDetail)) {

                foreach ($performanceDetail as $performance) {



                    $session = !empty($performance['session']) ? $performance['session'] : ' ';



                    $teamName = !empty($performance['team_name']) ? $performance['team_name'] : ' ';

                    $matches = !empty($performance['matches']) ? $performance['matches'] : ' ';

                    $goals = !empty($performance['goals']) ? $performance['goals'] : ' ';

                    $coach = !empty($performance['coach']) ? $performance['coach'] : '';

                    $player_age = !empty($performance['player_age']) ? $performance['player_age'] : ' ';



                    $content .= '<tr>
	                                        <td>' . $teamName . '</td>
	                                        <td>' . $session . '</td>
	                                        <td>' . $matches . '</td>
	                                        <td>' . $goals . '</td>
	                                        <td>' . $coach . '</td>
	                                        <td>' . $player_age . ' Years</td>
	                                    </tr>';
                }
            }
            $content .= '</tbody>
                                </table>
                            </div>
                            <table width="100%" border="0" cellspacing="0" cellpadding="0" class="footer_table">
                                <tr>
                                    <td style="text-align: center;font-weight: bold;">Succer You Sports AG | www.socceryou.ch </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </body>
                </html>
            ';

            /* Static code */
            /*  $mpdf->SetHTMLHeader('
                <div style="text-align: right; font-weight: bold;">
                    Header document
                </div>
            ');
            $mpdf->SetHTMLFooter('
                <table width="100%">
                    <tr>
                        <td width="33%">{DATE j-m-Y}</td>
                        <td width="33%" align="center">{PAGENO}/{nbpg}</td>
                        <td width="33%" style="text-align: right;">My document</td>
                    </tr>
                </table>
            '); */
            $stylesheet = file_get_contents(FCPATH . 'public/assets/css/user-profile-pdf.css'); // external css
            // with coupon 267
            // 261
            // 264 - proration (Upgrade)
            // 263 - mastercard
            // 289 - proration (Downgrade)
            // $content = getInvoicePdfContent($subscriptionID = 261);     // for testing
            // echo $content; exit;
            $mpdf->WriteHTML($content);
            // $mpdf->WriteHTML($stylesheet,\Mpdf\HTMLParserMode::HEADER_CSS);
            // $mpdf->WriteHTML($content,\Mpdf\HTMLParserMode::HTML_BODY);
            // exit;
            $this->response->setHeader('Content-Type', 'application/pdf');
            // echo  $content; exit;
            $mpdf->Output();

            // exit;

        } catch (\Mpdf\MpdfException $e) { // Note: safer fully qualified exception name used for catch

            // Process the exception, log, print etc.

            echo $e->getMessage();
        }
    }
}
