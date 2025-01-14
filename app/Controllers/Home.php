<?php

namespace App\Controllers;

use Mpdf;
use App\Models\UserMetaDataModel;
use App\Models\TeamTransferModel;
use App\Models\PerformanceDetailModel;
use App\Models\GalleryModel;
use App\Models\BlogModel;
use \Convertio\Convertio;

use DateTime;

class Home extends BaseController
{
    public function index(): string
    {
        return view('welcome_message');
    }

    public function getPositionImage($userId = null)
    {
        // $id = '58';
        if (!empty(auth()->id())) {
            $id = auth()->id();
        }
        $url =  isset($_SERVER['HTTPS']) &&
            $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
        $url .= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $parse_url = parse_url($url);
        $uri = new \CodeIgniter\HTTP\URI($url);

        $queryString =  $uri->getQuery();
        parse_str($queryString, $searchParams);
        if (empty($id) && isset($searchParams['user_id']) && !empty(is_numeric($searchParams['user_id']))) {
            $id = trim($searchParams['user_id']);
        }
        $userId = $id;
        $this->db = \Config\Database::connect();
        $builder = $this->db->table('users');
        $builder->select('
        users.id,
        user_roles.role_name as role_name,
        (SELECT JSON_ARRAYAGG(
            JSON_OBJECT(
                "position_id", pp.position_id,
                "main_position", pp.is_main,
                "position_name", p.position
            )
        )
        FROM player_positions pp
        LEFT JOIN positions p ON p.id = pp.position_id
        WHERE pp.user_id = users.id) AS positions
        ');

        $builder->join('(SELECT user_id, secret FROM auth_identities WHERE type = "email_password") auth', 'auth.user_id = users.id', 'LEFT');
        $builder->join('user_roles', 'user_roles.id = users.role', 'LEFT');
        $builder->where('users.id', $userId);


        // Execute query with user ID
        // $query = $db->query($sql, [$userId]);
        // Get the result
        $query = $builder->get();
        $userData = $query->getRow();
        // echo $this->db->getLastQuery();
        // echo '<pre>';
        // print_r();
        // die;
        if (!empty($userData)) {
            $userData = json_encode($userData, true);
            $userData = json_decode($userData, true);
        }
        if ($userData instanceof stdClass) {
            // Convert stdClass object to array
            $userData = (array) $userData;
        }
        $_SERVER['CI_ENVIRONMENT'] = 'development';
        if (isset($userData['positions']) && !empty($userData['positions'])) {
            error_reporting(0);
            return view('new_html', ['userData' => $userData]);
        } else {
            die; // return nothing
        }
    }


    public function generateInvoicePDF()
    {

        try {
            $flagPath = base_url() . 'public/assets/images/';


            $defaultConfig = (new Mpdf\Config\ConfigVariables())->getDefaults();
            $fontDirs = $defaultConfig['fontDir'];

            $defaultFontConfig = (new Mpdf\Config\FontVariables())->getDefaults();
            $fontData = $defaultFontConfig['fontdata'];

            $mpdf_data = [
                'debug' => true,
                // 'allow_output_buffering' => true,
                // 'showImageErrors'  => true,
                // 'text_input_as_HTML' => true,

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
            $mpdf = new \Mpdf\Mpdf($mpdf_data);

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

            $content = getInvoicePdfContent($subscriptionID = 86);     // for testing
            // echo $content; exit;
            $mpdf->WriteHTML($content);

            // $mpdf->WriteHTML($stylesheet,\Mpdf\HTMLParserMode::HEADER_CSS);
            // $mpdf->WriteHTML($content,\Mpdf\HTMLParserMode::HTML_BODY);

            $this->response->setHeader('Content-Type', 'application/pdf');

            // echo  $content; exit;

            $mpdf->Output();
            // exit;
        } catch (\Mpdf\MpdfException $e) { // Note: safer fully qualified exception name used for catch
            // Process the exception, log, print etc.
            echo $e->getMessage();
        }
    }



    public function staticPdf()
    {
        $flagPath = base_url() . 'public/assets/images/';


        $defaultConfig = (new Mpdf\Config\ConfigVariables())->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];

        $defaultFontConfig = (new Mpdf\Config\FontVariables())->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];

        $mpdf_data = [
            'debug' => true,
            // 'allow_output_buffering' => true,
            // 'showImageErrors'  => true,
            // 'text_input_as_HTML' => true,

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
        $mpdf = new \Mpdf\Mpdf($mpdf_data);

        $content = '
            <!DOCTYPE html>
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
            .ground_fix11 {
                width: 100%;
                max-width: 400px;
            }
            .court_position {position: relative;}
            img.ground_fix {position: absolute;left: 0;top: 0; }
            img.dot_posg {position: absolute;left: 0;top: 0;}
            </style>
            </head>
            <body class="body" style="padding:0 !important; margin:0 !important; display:block !important; min-width:100% !important; width:100% !important;-webkit-text-size-adjust:none">
                <div width="100%" border="0" cellspacing="0" cellpadding="0">
                    <div style="width:100%; min-width:650px; padding:0; margin:0 auto; font-weight:normal;">
                        <table width="100%" border="0" cellspacing="0" cellpadding="0" class="header">
                            <tr>
                                <td style="width: 50%;padding-left: 0;">
                                    <table width="100%" border="0" cellspacing="0" cellpadding="0" class="Inner_header">
                                        <tr>
                                            <td>
                                                <img src="' . $flagPath . 'logo.png" style="width: 150px;vertical-align: top;margin-top: -4px;">
                                                <span style="font-size: 20px;padding-left: 5px;">PLAYER PROFILE</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="line-height: 1;">
                                                <h1 style="width: 100%;float: left;font-size: 40px;margin: 20px 0;">Elton Prince Morina</h1>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="current_club">
                                                <strong>Current Club</strong><br />
                                                <img src="' . $flagPath . 'fc-logo.png" style="width: 32px;float:left;margin-right: 4px;vertical-align:bottom;"> FC Thun
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <table width="100%" border="0" cellspacing="0" cellpadding="0" class="Inner_header1">
                                                    <tr>
                                                        <td>
                                                            <strong>Height</strong><br />
                                                            <img src="' . $flagPath . 'Icons19.png" style="width: 25px;vertical-align: middle;margin-right: 4px;margin-top: 3px;float: left;vertical-align:bottom;"> 175cm
                                                        </td>
                                                        <td>
                                                            <strong>Weight</strong><br />
                                                            <img src="' . $flagPath . 'Icons18.png" style="width: 25px;vertical-align: middle;margin-right: 4px;margin-top: 3px;float: left;vertical-align:bottom;"> 72kg
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                                <td style="width: 50%;padding-right: 0;">
                                    <table width="100%" border="0" cellspacing="0" cellpadding="0" class="social_table">
                                        <tr>
                                            <td class="social_links">
                                                <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                                    <tr>
                                                        <td style="vertical-align: middle;padding: 5px;"><img src="' . $flagPath . 'Icons3.png" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;vertical-align:middle;">facebook.com/elton</td>
                                                    </tr>
                                                       <tr>
                                                           <td style="vertical-align: middle;padding: 5px;"><img src="' . $flagPath . 'Icons2.png" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;vertical-align:middle;">instagram.com/elton</td>
                                                       </tr>
                                                   </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="bnr_img" style="vertical-align:bottom;">
                                                <img src="' . $flagPath . 'banner-pic.png" style="max-width: 100%;">
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                        <table width="100%" border="0" cellspacing="0" cellpadding="0" class="player_detail" style="background: #f8f8f8;">
                            <tr>
                                <td colspan="3" style="padding: 0;">
                                    <img src="' . $flagPath . 'players-1.png" style="width: 23%;margin-left: 4px;">
                                    <img src="' . $flagPath . 'players-2.png" style="width: 23%;margin-left: 6px;">
                                    <img src="' . $flagPath . 'players-3.png" style="width: 23%;margin-left: 6px;">
                                    <img src="' . $flagPath . 'players-4.png" style="width: 23%;margin-left: 6px;">
                                </td>
                            </tr>
                            <tr>
                                <td class="detail_list">
                                    <h3>Age</h3>
                                    <p><img src="' . $flagPath . 'Icons20.png" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;"> 20 Years</p>
                                </td>
                                <td class="detail_list no_border">
                                    <h3>In the team since</h3>
                                    <p>Apr 07, 2023</p>
                                </td>
                                <td class="detail_list no_border">
                                    <h3>Top Speed</h3>
                                    <p>33.7 km/h</p>
                                </td>
                            </tr>
                            <tr>
                                <td class="detail_list">
                                    <h3>Nationality</h3>
                                    <p><img src="' . $flagPath . 'flag-1.png" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;"> Switzerland</p>
                                    <p><img src="' . $flagPath . 'Icons8.png" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;"> Kosovo</p>
                                </td>
                                <td class="detail_list no_border">
                                    <h3>Current market value</h3>
                                    <p>€54.5M</p>
                                </td>
                                <td class="detail_list no_border">
                                    <h3>International Player</h3>
                                    <p><img src="' . $flagPath . 'Icons8.png" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;"> Kosovo</p>
                                </td>
                            </tr>
                            <tr>
                                <td class="detail_list">
                                    <h3>Date of birth</h3>
                                    <p><img src="' . $flagPath . 'Icons11.png" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;"> Apr 21, 2024</p>
                                </td>
                                <td colspan="2" class="detail_list">
                                    <h3>Last change</h3>
                                    <p>Apr 07, 2023</p>
                                </td>
                            </tr>
                            <tr>
                                <td class="detail_list">
                                    <h3>Place of birth</h3>
                                    <p><img src="' . $flagPath . 'flag-1.png" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;"> Zurich, Switzerland</p>
                                </td>
                                <td colspan="2" rowspan="4" class="detail_list no_border">
                                    <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                        <tr>
                                            <td class="court_position">
                                                <img src="' . $flagPath . 'Playground-image.png" class="ground_fix">
                                                <img src="' . $flagPath . 'playground-point1.png" class="pos_st dot_posg">
                                                <img src="' . $flagPath . 'playground-point2.png" class="pos_cam dot_posg">
                                                <img src="' . $flagPath . 'playground-point3.png" class="pos_lm dot_posg">
                                                <img src="' . $flagPath . 'playground-point4.png" class="pos_cm dot_posg">
                                                <img src="' . $flagPath . 'playground-point5.png" class="pos_rm dot_posg">
                                                <img src="' . $flagPath . 'playground-point6.png" class="pos_cdm dot_posg">
                                                <img src="' . $flagPath . 'playground-point7.png" class="pos_lwb dot_posg">
                                                <img src="' . $flagPath . 'playground-point8.png" class="pos_lb dot_posg">
                                                <img src="' . $flagPath . 'playground-point9.png" class="pos_rb dot_posg">
                                                <img src="' . $flagPath . 'playground-point10.png" class="pos_rwb dot_posg">
                                                <img src="' . $flagPath . 'playground-point11.png" class="pos_gk dot_posg">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="plyr_pos" style=""> 
                                                    <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                                        <tr>
                                                        <td><h3>Main Position</h3>Forward</td>
                                                        <td><h3>Other position(s)</h3>Forward/Left/Right</td>
                                                    </tr>
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
                                <td class="detail_list">
                                    <h3>League</h3>
                                    <p><img src="' . $flagPath . 'Icons16.png" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;"> Challenge League</p>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="detail_list no_border">
                                    <h3>Foot</h3>
                                    <p><img src="' . $flagPath . 'Icons21.png" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;"> Right</p>
                                </td>
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
                                <tbody>
                                    <tr>
                                        <td>2023</td>
                                        <td>19.07.2022</td>
                                        <td style="white-space: nowrap;"><img src="' . $flagPath . 'fcsicon-icon.png" style="width: 20px;vertical-align: middle;margin-right: 5px;margin-top: -2px;"> YF Juventus Zürich II</td>
                                        <td style="white-space: nowrap;"><img src="' . $flagPath . 'fcsicon-icon.png" style="width: 20px;vertical-align: middle;margin-right: 5px;margin-top: -2px;"> FC Thun, Switzerland</td>
                                    </tr>
                                    <tr>
                                        <td>2023</td>
                                        <td>19.07.2022</td>
                                        <td style="white-space: nowrap;"><img src="' . $flagPath . 'fcsicon-icon.png" style="width: 20px;vertical-align: middle;margin-right: 5px;margin-top: -2px;"> YF Juventus Zürich II</td>
                                        <td style="white-space: nowrap;"><img src="' . $flagPath . 'fcsicon-icon.png" style="width: 20px;vertical-align: middle;margin-right: 5px;margin-top: -2px;"> FC Thun, Switzerland</td>
                                    </tr>
                                    <tr>
                                        <td>2023</td>
                                        <td>19.07.2022</td>
                                        <td style="white-space: nowrap;"><img src="' . $flagPath . 'fcsicon-icon.png" style="width: 20px;vertical-align: middle;margin-right: 5px;margin-top: -2px;"> YF Juventus Zürich II</td>
                                        <td style="white-space: nowrap;"><img src="' . $flagPath . 'fcsicon-icon.png" style="width: 20px;vertical-align: middle;margin-right: 5px;margin-top: -2px;"> FC Thun, Switzerland</td>
                                    </tr>
                                    <tr>
                                        <td>2023</td>
                                        <td>19.07.2022</td>
                                        <td style="white-space: nowrap;"><img src="' . $flagPath . 'fcsicon-icon.png" style="width: 20px;vertical-align: middle;margin-right: 5px;margin-top: -2px;"> YF Juventus Zürich II</td>
                                        <td style="white-space: nowrap;"><img src="' . $flagPath . 'fcsicon-icon.png" style="width: 20px;vertical-align: middle;margin-right: 5px;margin-top: -2px;"> FC Thun, Switzerland</td>
                                    </tr>
                                    <tr>
                                        <td>2023</td>
                                        <td>19.07.2022</td>
                                        <td style="white-space: nowrap;"><img src="' . $flagPath . 'fcsicon-icon.png" style="width: 20px;vertical-align: middle;margin-right: 5px;margin-top: -2px;"> YF Juventus Zürich II</td>
                                        <td style="white-space: nowrap;"><img src="' . $flagPath . 'fcsicon-icon.png" style="width: 20px;vertical-align: middle;margin-right: 5px;margin-top: -2px;"> FC Thun, Switzerland</td>
                                    </tr>
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
                                <tbody>
                                    <tr>
                                        <td>FC Thun u 21</td>
                                        <td>23/24</td>
                                        <td>5</td>
                                        <td>3</td>
                                        <td>Stipe Matic</td>
                                        <td>19 Years</td>
                                    </tr>
                                    <tr>
                                        <td>YF Juventus Zürich II</td>
                                        <td>23/24</td>
                                        <td>5</td>
                                        <td>3</td>
                                        <td>Piero Bauer</td>
                                        <td>18 Years</td>
                                    </tr>
                                    <tr>
                                        <td>GC U18</td>
                                        <td>23/24</td>
                                        <td>5</td>
                                        <td>3</td>
                                        <td>Injured 8 Month</td>
                                        <td>18 Years</td>
                                    </tr>
                                    <tr>
                                        <td>GC U18</td>
                                        <td>23/24</td>
                                        <td>5</td>
                                        <td>3</td>
                                        <td>Heris Stefanachi</td>
                                        <td>18 Years</td>
                                    </tr>
                                    <tr>
                                        <td>FC Rapperswil-Jona</td>
                                        <td>23/24</td>
                                        <td>5</td>
                                        <td>3</td>
                                        <td>Urs Wolfensberger</td>
                                        <td>17 Years</td>
                                    </tr>
                                </tbody>
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
        echo $content;
        die;
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
        try {
            $flagPath = base_url() . 'public/assets/images/';
            // return view('new_html', ['flagPath' => $flagPath]);
            // $flagPath = base_url() . 'public/assets/images/';
            $defaultConfig = (new Mpdf\Config\ConfigVariables())->getDefaults();
            $fontDirs = $defaultConfig['fontDir'];
            $defaultFontConfig = (new Mpdf\Config\FontVariables())->getDefaults();
            $fontData = $defaultFontConfig['fontdata'];
            $mpdf_data = [
                'debug' => true,
                'fontDir' => array_merge($fontDirs, [FCPATH . '/public/assets/fonts',]),
                'fontdata' => $fontData + ['roboto' => ['R' => 'Roboto-Regular.ttf', 'I' => 'Roboto-Regular.ttf', 'B' => 'Roboto-Bold.ttf',]],
                'default_font' => 'roboto',
                'mode' => 'utf-8',
                'format' => 'A4', // Check the format
                'margin_top' => 10,
                'margin_bottom' => 10,
                'margin_left' => 10,
                'margin_right' => 10,
            ];
            $mpdf = new \Mpdf\Mpdf($mpdf_data);
            /* Logic */
            $id = '590';
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
            // echo '<pre>'; print_r($userData); die;
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
            $images = $galleryModel->where('user_id', $userId)->whereIn('file_type', ALLOWED_IMAGE_EXTENTION)->orderBy('id','DESC')->findAll();
            // $videos = $galleryModel->where('user_id', $userId)->whereIn('file_type', ALLOWED_VEDIO_EXTENTION)->orderBy('id','DESC')->findAll();
            // $images = $galleryModel->where('user_id', $userId)->where('is_featured', 1)->whereIn('file_type', ALLOWED_IMAGE_EXTENTION)->orderBy('id', 'DESC')->findAll();
            //echo '<pre>'; print_r($images); die;
            /* Logic */
            /* ##### screenshot from image ##### */
            // ApiFlash API key
            $apiKey = 'df05353ad1824362a90d9105eafec3bc'; // THis is Paid API key of timo account
            # $apiKey = 'dae8abc5f2ef456499246022fce40215'; // THis is Free API key of testmails account
            // Directory to save screenshots
            $imageDir = 'public/screenshots';
            $imageName = 'temp_sc_' . rand(1500, 9999); // Random base name
            $imagePath = $imageDir . '/' . $imageName; // Path without extension
            if (isset($userData['role_name']) && strtoupper($userData['role_name']) == 'PLAYER') {
                // Create directory if it doesn't exist
                if (!file_exists($imageDir)) {
                    if (!mkdir($imageDir, 0777, true)) {
                        die("Error creating directory: $imageDir");
                    }
                }

                // URL to capture
                $url = base_url() . '/get-positions-image?' . rand() . '&user_id=' . $userId; // Adjust this URL as needed

                // Prepare API request parameters
                $params = http_build_query(array(
                    "access_key" => $apiKey,
                    "url" => $url,
                    "height" => 230, // Adjust height as needed
                    'format' => 'png',
                    // 'transparent' => true
                ));
                $apiUrl = "https://api.apiflash.com/v1/urltoimage?" . $params;

                // Create a stream context to fetch the image
                $options = [
                    "http" => [
                        "method" => "GET",
                        "header" => "Content-Type: application/json\r\n"
                    ]
                ];
                $context = stream_context_create($options);

                // Fetch the image data
                $imageData = file_get_contents($apiUrl, false, $context);
                if ($imageData === FALSE) {
                    die("Error fetching image data from ApiFlash API.");
                }

                // Save the image data to a file
                if (file_exists($imagePath)) {
                    unlink($imagePath); // Remove the old file if it exists
                    // echo 'exist $imagePath : '.$imagePath; die;
                }
                // echo '$imagePath : '.$imagePath.'<br>'; # die;
                // Open the file for writing
                $file = fopen($imagePath . '.png', 'wb'); // Add .png extension here
                if ($file === false) {
                    die("Error opening file for writing: $imagePath");
                }

                // Write the image data to the file
                if (fwrite($file, $imageData) === false) {
                    fclose($file);
                    die("Error writing to file: $imagePath");
                }

                // Close the file
                fclose($file);

                // Validate the saved image
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $contentType = finfo_file($finfo, $imagePath . '.png'); // Check with .png extension
                finfo_close($finfo);

                if (strpos($contentType, 'image/') === false) {
                    unlink($imagePath); // Delete invalid file
                    // echo 'unlink = '.$imagePath;
                    // unlink($imagePath . '.png'); // Delete invalid file
                    // die("Error: The saved file is not a valid image.");
                }
                // die;
                // Return the image URL for viewing
                $imageURL = base_url() . 'public/screenshots/' . $imageName . '.png'; // Adjust the URL based on your server setup
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $contentType = finfo_buffer($finfo, $imageData);
                finfo_close($finfo);
                // $imageName = rand(1500, 9999) . '.' . str_replace('image/', '', $contentType); // Random image name
                $imageDetails['contentType'] = $contentType;
                // echo '<pre>'; print_r($imageDetails); die;
                // Check if the response is valid
                if ($imageData === FALSE) {
                    // echo "Error occurred while fetching the screenshot.";
                } else {
                    // Save the image to the specified path
                    file_put_contents($imagePath, $imageData);
                    // Return the image URL for viewing
                    $imageURL = base_url() . 'public/screenshots/' . $imageName . '.png';  // Adjust the URL based on your server setup
                }

                // $imageDetails['remove_bg1'] = 'remove_background';
                // $croped_image = cropImage($imageDetails);
                $croped_image = $imageURL;
                /* ##### screenshot from image ##### */
                /* ##### Crop Image ##### */
                $croped = get_headers($croped_image, 1);
                if (strpos($croped[0], '200') !== false) {
                    if (file_exists($imagePath)) {
                        unlink($imagePath);
                        // unlink($imagePath . '.png');
                    }
                    $imagePath = $croped_image;
                    if (isset($imageDetails['contentType']) && $imageDetails['contentType'] == 'image/jpeg') {
                        $image = imagecreatefromjpeg($imagePath);
                    } else if (isset($imageDetails['contentType']) && $imageDetails['contentType'] == 'image/png') {
                        $image = imagecreatefrompng($imagePath);
                    }
                    if (!$image) {
                        die("Failed to load image.");
                    }
                    // Define the crop dimensions (x, y, width, height)
                    $x = 5;
                    $y = 5;
                    $width = 400;
                    $height = 300;
                    // Specify how much to crop from the top
                    $cropFromTop = -5; // Change this value to crop more or less from the top
                    // Adjust the starting y-coordinate
                    $y += $cropFromTop;
                    // Get original image dimensions
                    $imageWidth = imagesx($image);
                    $imageHeight = imagesy($image);
                    // Adjust the crop dimensions if they exceed the original image size
                    if ($x + $width > $imageWidth) {
                        $width = $imageWidth - $x; // Adjust width to fit
                    }
                    if ($y + $height > $imageHeight) {
                        $height = $imageHeight - $y; // Adjust height to fit
                    }
                    // echo $width; die;
                    $height = 150;
                    $croppedImage = imagecreatetruecolor($width, $height);
                    imagecopy($croppedImage, $image, 0, 0, $x, $y, $width, $height);
                    // $croppedImagePath = $imagePath;
                    $croppedImagePath = 'public/screenshots/cropped_image_' . date('y_m_d_h_i_s') . '.png';
                    // $croppedImagePath = 'public/cropped_image_' . date('y_m_d_h_i_s') . '.png';
                    // imagepng($croppedImage, $croppedImagePath); // Use imagejpeg() for JPEG format/
                    if (isset($imageDetails['contentType']) && $imageDetails['contentType'] == 'image/jpeg') {
                        imagejpeg($croppedImage, $croppedImagePath); // Use imagepng() for PNG format
                    } else if (isset($imageDetails['contentType']) && $imageDetails['contentType'] == 'image/png') {
                        imagejpeg($croppedImage, $croppedImagePath); // Use imagejpeg() for JPEG format
                    }
                    imagedestroy($image);
                    imagedestroy($croppedImage);
                    // echo "Image cropped successfully!"; 
                    $imagePath = str_replace(base_url(), '', $imagePath);
                    if (file_exists($imagePath)) {
                        if (unlink($imagePath)) {
                            // echo "Image deleted successfully.";
                        } else {
                            // echo "Error deleting the image.";
                        }
                    }
                    if (file_exists($imagePath . '.png')) {
                        unlink($imagePath . '.png');
                    }
                    if (!empty($croppedImagePath)) {
                        $imagePath = base_url() . $croppedImagePath;
                    }
                }
            }
            /* ##### End Crop Image ##### */
            $content = '
                <!DOCTYPE html>
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
                img.ground_fix {max-width: 400px;}
                 .ground_fix11 {
                width: 100%;
                max-width: 400px; /* mix-blend-mode: darken; To remove white space */
            }
                </style>
                </head>';
            $role_name = '';
            $fullName = '';
            $current_club_name = '';
            $height = '';
            $weight = '';
            $sm_x = '';
            $sm_facebook = '';
            $sm_instagram = '';
            $sm_tiktok = '';
            $sm_youtube = '';
            $sm_vimeo = '';
            if (isset($userData['role_name']) && !empty($userData['role_name'])) {
                $role_name = strtoupper($userData['role_name']);
            }
            if (!empty($userData['first_name']) && !empty($userData['last_name'])) {
                $fullName = $userData['first_name'] . ' ' . $userData['last_name'];
            }
            if (isset($userData['current_club_name']) && !empty($userData['current_club_name'])) {
                $current_club_name = $userData['current_club_name'];
            }
            if (isset($userData['meta']['height']) && !empty($userData['meta']['height'])) {
                $height = $userData['meta']['height'];
                if (!empty($userData['meta']['height_unit'])) {
                    $height .= ' ' . $userData['meta']['height_unit'];
                }
                // $height = $userData['meta']['height'] . ' ' . $userData['meta']['height_unit'] ? $userData['meta']['height_unit'] : '';
            }
            // echo '__________'.$height.'______________'; die;
            if (isset($userData['meta']['weight']) && !empty($userData['meta']['weight'])) {
                $weight = $userData['meta']['weight'] . ' ' . $userData['meta']['weight_unit'];
            }
            $content .= '<body class="body" style="padding:0 !important; margin:0 !important; display:block !important; min-width:100% !important; width:100% !important;-webkit-text-size-adjust:none">';
            $content .= '<div width="100%" border="0" cellspacing="0" cellpadding="0">';
            $content .= '<div style="width:100%; min-width:650px; padding:0; margin:0 auto; font-weight:normal;">';
            $content .= '<table width="100%" border="0" cellspacing="0" cellpadding="0" class="header">';
            $content .= '<tr>';
            $content .= '<td style="width: 50%;padding-left: 0;">';
            $content .= '<table width="100%" border="0" cellspacing="0" cellpadding="0" class="Inner_header">';
            $content .= '<tr>';
            $content .= '<td>';
            $content .= '<img src="' . $flagPath . 'logo.png" style="width: 150px;vertical-align: top;margin-top: -4px;">';
            $content .= '<span style="font-size: 20px;padding-left: 5px;">' . strtoupper($role_name) . ' PROFILE</span>';
            $content .= '</td>';
            $content .= '</tr>';
            $content .= '<tr>';
            $content .= '<td style="line-height: 1;">';
            $content .= '<h1 style="width: 100%;float: left;font-size: 40px;margin: 20px 0;">' . $fullName . '</h1>';
            $content .= '</td>';
            $content .= '</tr>';
            $content .= '<tr>';
            $content .= '<td class="current_club">';
            $content .= '<strong>Current Club</strong><br />';
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
            // $content .= '<img src="' . $flagPath . 'fc-logo.png" style="width: 32px;float:left;margin-right: 4px;vertical-align:bottom;"> FC Thun';
            $content .= '</td>';
            $content .= '</tr>';
            $content .= '<tr>';
            $content .= '<td>';
            $content .= '<table width="100%" border="0" cellspacing="0" cellpadding="0" class="Inner_header1">';
            $content .= '<tr>';
            $content .= '<td>';
            $content .= '<strong>Height</strong><br />';
            $content .= '<img src="' . $flagPath . 'Icons19.png" style="width: 25px;vertical-align: middle;margin-right: 4px;margin-top: 3px;float: left;vertical-align:bottom;">' . $height;
            $content .= '</td>';
            $content .= '<td>';
            $content .= '<strong>Weight</strong><br />';
            $content .= '<img src="' . $flagPath . 'Icons18.png" style="width: 25px;vertical-align: middle;margin-right: 4px;margin-top: 3px;float: left;vertical-align:bottom;">' . $weight;
            $content .= '</td>';
            $content .= '</tr>';
            $content .= '</table>';
            $content .= '</td>';
            $content .= '</tr>';
            $content .= '</table>';
            $content .= '</td>';
            $content .= '<td style="width: 50%;padding-right: 0;">';
            $content .= '<table width="100%" border="0" cellspacing="0" cellpadding="0" class="social_table">';
            $content .= '<tr>';
            $content .= '<td class="social_links">';
            $content .= '<table width="100%" border="0" cellspacing="0" cellpadding="0">';
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
                $content .= '<td style="vertical-align: middle;padding: 5px;"><img src="' . $flagPath . 'social/instagram-lg.png" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;vertical-align:middle;">instagram.com/' . $sm_instagram . '</td>';
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
            $content .= '	</table>';
            $content .= '</td>';
            $content .= '</tr>';
            $content .= '<tr>';
            if (!empty($userData['meta']['profile_image_path'])) {
                $profile_image_path = $userData['meta']['profile_image_path'];
            } else {
                $profile_image_path = $flagPath . 'banner-pic.png';
            }
            $content .= '<td class="bnr_img" style="vertical-align:bottom;">';
            $content .= ' <img src="' . $profile_image_path . '" style="width:270px">';
            $content .= '</td>';
            $content .= '</tr>';
            $content .= '  </table>';
            $content .= ' </td>';
            $content .= '  </tr>';
            $content .= ' </table>';

            $content .= '<table width="100%" border="0" cellspacing="0" cellpadding="0" class="player_detail" style="background: #fff;">';
            // $content .= '<table width="100%" border="0" cellspacing="0" cellpadding="0" class="player_detail" style="background: #f8f8f8;">';
            $content .= '<tr>';
            $content .= '  <td colspan="3" style="padding: 0;">';
            if (!empty($images)) {
                foreach ($images as $image) {
                    $imagepathFull = $imagePath . $image['file_name'];
                    // Check if it's a URL or local file
                    if (filter_var($imagepathFull, FILTER_VALIDATE_URL)) {
                        // It's a URL, so get headers
                        $headers = get_headers($imagepathFull, 1);
                        if (!empty($headers[0]) && strpos($headers[0], '200') !== false) {
                            $content .= '<img src="' . $imagepathFull . '" style="width: 23%;margin-left: 4px;">';
                        }
                    } elseif (file_exists($imagepathFull)) {
                        // It's a local file, check if it exists
                        $content .= '<img src="' . $imagepathFull . '" style="width: 23%;margin-left: 4px;">';
                    }
                }
            }

            $dob =  '';
            if (!empty($userData['meta']['date_of_birth'])) {
                $dob = $userData['meta']['date_of_birth'];
            }
            $age = calculateAge($dob);
            $in_team_since = '';
            if (!empty($userData['meta']['in_team_since'])) {
                // $in_team_since = $userData['meta']['in_team_since'];
                $dateTime = new DateTime($userData['meta']['in_team_since']);
                $in_team_since = $dateTime->format('M.d.Y');
            }
            $top_speed = '';
            if (!empty($userData['meta']['top_speed'])) {
                $top_speed = $userData['meta']['top_speed'] . ' ' . $userData['meta']['top_speed_unit'];
            }
            $market_value = '';
            if (!empty($userData['meta']['market_value'])) {
                $market_value = $userData['meta']['market_value'] . ' ' . $userData['meta']['market_value_unit'];
            }
            $international_player = '';
            if (isset($userData['meta']['international_player']) && !empty($userData['meta']['international_player'])) {
                $international_player = trim($userData['meta']['international_player']);
            }
            $date_of_birth = '';
            if (isset($userData['meta']['date_of_birth']) && !empty($userData['meta']['date_of_birth'])) {
                $date_of_birth1 = trim($userData['meta']['date_of_birth']);
                $dateTime = new DateTime($date_of_birth1);
                // $date_of_birth = $dateTime->format('M.d.Y');
                $date_of_birth = $dateTime->format('d.M.Y');
            }
            $content .= ' </td>';
            $content .= '</tr>';
            $content .= ' <tr>';
            $content .= '  <td class="detail_list">';
            $content .= '    <h3>Age</h3>';
            $content .= '   <p><img src="' . $flagPath . 'Icons20.png" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;"> ' . $age . ' Years</p>';
            $content .= '     </td>';
            if (!empty($in_team_since)) {
                $content .= '    <td class="detail_list no_border">';
                $content .= '       <h3>In the team since</h3>';
                $content .= '          <p>' . $in_team_since . '</p>';
                $content .= '     </td>';
            }
            if (!empty($top_speed)) {
                $content .= '      <td class="detail_list no_border">';
                $content .= '          <h3>Top Speed</h3>';
                $content .= '          <p>' . $top_speed . '</p>';
                $content .= '      </td>';
                $content .= '  </tr>';
            }

            $content .= '  <tr>';
            $content .= '      <td class="detail_list">';
            $user_nationalities = $userData['user_nationalities'];
            // echo '<pre>'; print_r($user_nationalities); die;
            if (!empty($user_nationalities)) {
                $user_nationalities = json_decode($user_nationalities, true);
                $content .= '<h3>Nationality</h3>';
                foreach ($user_nationalities as $nationality) {
                    $nationality['flag_path'] = str_replace('public/assets/images/', 'uploads/logos/', $nationality['flag_path']);
                    $nationality_flag = get_headers($nationality['flag_path'], 1);
                    // echo '<pre>'; print_r($nationality_flag); die;
                    if (strpos($nationality_flag[0], '200') !== false) {
                        $content .= '<p>
                        <img src="' . $nationality['flag_path'] . '" style="width: 25px; vertical-align: middle; margin-right: 4px; float: left;">
                        ' . $nationality['country_name'] . '
                        </p>';
                    } else if (!empty($nationality['country_name'])) {
                        $content .= '<p>' . $nationality['country_name'] . '</p>';
                    }
                }
            }
            $content .= '      </td>';
            if (!empty($market_value)) {
                $content .= '      <td class="detail_list no_border">';
                $content .= '          <h3>Current market value</h3>';
                $content .= '          <p>' . $market_value . '</p>';
                $content .= '      </td>';
            }
            $content .= '      <td class="detail_list no_border">';
            $internationl_logo = $flagPath . 'Icons8.png';
            if (!empty($international_player)) {
                $internationl_logo = base_url() . 'public/assets/images/' . $international_player . '.svg';
                $internationl_flag = get_headers($internationl_logo, 1);
                if (strpos($internationl_flag[0], '200') !== false) {
                    $content .= '<h3>International Player</h3>';
                    $content .= '<p>
                <img src="' . $internationl_logo . '" style="width: 25px; vertical-align: middle; margin-right: 4px; float: left;">
                ' . $international_player . '
                </p>';
                }
            }
            // $content .= '          <p><img src="' . $flagPath . 'Icons8.png" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;"> ' . $international_player . '</p>';
            $content .= '      </td>';
            $content .= '  </tr>';
            $content .= '  <tr>';
            $content .= '      <td class="detail_list">';
            $content .= '          <h3>Date of birth</h3>';
            $content .= '          <p><img src="' . $flagPath . 'Icons11.png" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;"> ' . $date_of_birth . '</p>';
            $content .= '      </td>';

            if (!empty($userData['meta']['last_change'])) {
                $content .= '      <td colspan="2" class="detail_list">';
                $content .= '          <h3>Last change</h3>';
                $dateTime = new DateTime($userData['meta']['last_change']);
                $last_change = $dateTime->format('d.M.Y');
                $content .= '<p>' . $last_change . '</p>';
                $content .= '</td>';
            }
            $content .= '</tr>';
            $content .= '<tr>';
            $content .= '<td class="detail_list">';
            $content .= '<h3>Place of birth</h3>';
            if (!empty($userData['meta']['place_of_birth'])) {
                $place_of_birth_logo = base_url() . 'uploads/logos/' . trim(ucfirst(strtolower($userData['meta']['place_of_birth']))) . '.svg';
                $place_of_birth_logo = get_headers($place_of_birth_logo, 1);
                // echo '<pre>'; print_r($place_of_birth_logo); die;
                //$place_of_birth_logo['0'] = '500'; // need to check svg size
                // if ($place_of_birth_logo == 200) {
                if (strpos($place_of_birth_logo[0], '200') !== false) {
                    $place_of_birth_logo = base_url() . 'uploads/logos/' . trim(ucfirst(strtolower($userData['meta']['place_of_birth']))) . '.svg';
                    // $place_of_birth_logo = base_url() . 'public/assets/images/' . trim(ucfirst($userData['meta']['place_of_birth'])) . '.svg';
                } else {
                    $place_of_birth_logo =  $flagPath . 'flag-1.png';
                }
                $content .= '<p><img src="' . $place_of_birth_logo . '" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;"> ' . $userData['meta']['place_of_birth'] . '</p>';
            } else {
                $content .= '<p>--</p>';
            }
            $content .= '</td>';
            $content .= '<td colspan="2" rowspan="4" class="detail_list no_border">';
            $content .= '<table width="100%" border="0" cellspacing="0" cellpadding="0">';
            if (isset($userData['role_name']) && strtoupper($userData['role_name']) == 'PLAYER') {
                $content .= '<tr>';
                $content .= '<td class="court_position">';
                $content .= '<img src="' . $imagePath . '?' . rand() . '" class="ground_fix11">';
                $content .= '</td>';
                $content .= '</tr>';
            }
            $content .= '<tr>';
            $content .= '<td class="plyr_pos" style="">';
            $content .= '<table width="100%" border="0" cellspacing="0" cellpadding="0">';
            $content .= '<tr>';
            // $content .= '<td><h3>Main Position</h3>Forward</td>';
            if (isset($userData['positions']) && !empty($userData['positions'])) {
                $positions = json_decode($userData['positions'], true);
                usort($positions, function ($a, $b) {
                    return $b['main_position'] <=> $a['main_position'];
                });
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
            $contract = '';
            if (!empty($userData['meta']['contract_start']) && !empty($userData['meta']['contract_end'])) {
                $contract_start = new DateTime($userData['meta']['contract_start']);
                $contract_start_date = $contract_start->format('d.m.y');
                $contract_end = new DateTime($userData['meta']['contract_end']);
                $contract_end_date = $contract_end->format('d.m.y');
                $contract = $contract_start_date . ' - ' . $contract_end_date;
            }
            $content .= '</tr>';
            $content .= '</table>';
            $content .= '</td>';
            $content .= '</tr>';
            $content .= '</table>';
            $content .= '</td>';
            $content .= '</tr>';
            $content .= '<tr>';
            $content .= '<td class="detail_list">';
            $content .= '<h3>Contract</h3>';
            $content .= '<p><img src="' . $flagPath . 'Icons15.png" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;">' . $contract . '</p>';
            $content .= '</td>';
            $content .= '</tr>';
            $content .= '<tr>';
            $content .= '<td class="detail_list">';
            if (isset($userData['league_name']) && !empty($userData['league_name'])) {
                if (isset($userData['league_logo_path']) && !empty($userData['league_logo_path'])) {
                    $league_logo_path = $userData['league_logo_path'];
                    $content .= '<h3>League</h3>';
                    $leaguepath = get_headers($league_logo_path, 1);
                    if (strpos($leaguepath[0], '200') !== false) {
                        $content .= '<p>';
                        $content .= '<img src="' . $league_logo_path . '?' . rand() . '" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;">';
                    }
                }
                $content .= $userData['league_name'] . '<p>';
            }
            $content .= '</div>';
            $content .= '</td>';
            $content .= '</tr>';
            $content .= '<tr>';
            $content .= '<td class="detail_list no_border">';
            $foot = '';
            if (!empty($userData['meta']['foot'])) {
                $foot = trim(strtolower($userData['meta']['foot']));
                $foot_image = get_headers($flagPath . '/foot/' . $foot . '.svg', 1);
                if (strpos($foot_image[0], '200') !== false) {
                    $foot_image = $flagPath . '/foot/' . $foot . '.svg' . '?' . rand();
                    $content .= '<h3>Foot</h3>';
                    $content .= '<p><img src="' . $foot_image . '" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;"> ' . ucfirst(strtolower($foot)) . '</p>';
                }
            }
            $content .= '</td>';
            $content .= '</tr>';
            $content .= '</table>';
            if (!empty($transferDetail)) {
                $content .= '<div class="transfer_history" style="page-break-before:always;">';
                $content .= '   <h2 style="margin-top: 5px;padding: 0 10px;">Transfer History</h2>';
                $content .= '  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="table_transfer">';
                $content .= '     <thead>';
                $content .= '         <tr>';
                $content .= '         <th style="white-space: nowrap;"><img src="' . $flagPath . 'Icons6.png" style="width: 15px;vertical-align: middle;margin-right: 4px;float: left;"><span>Saison</span></th>';
                $content .= '         <th style="white-space: nowrap;"><img src="' . $flagPath . 'Icons20.png" style="width: 15px;vertical-align: middle;margin-right: 4px;float: left;"><span>Date</span></th>';
                $content .= '         <th>Moving From</th>';
                $content .= '         <th>Moving To</th>';
                $content .= '     </tr>';
                $content .= '  </thead>';
                $content .= '  <tbody>';
                foreach ($transferDetail as $transfer) {
                    // echo '<pre>'; print_r($transfer); die;
                    $date = $transfer['date_of_transfer'];
                    $session = !empty($transfer['session']) ? $transfer['session'] : ' ';
                    // $formattedDate = !empty($date) ? $date : ' ';
                    $dateTime = new DateTime($date);
                    $formattedDate = $dateTime->format('d.m.y');
                    /* Old Working code  */
                    /*
                    $teamFrom = !empty($transfer['team_name_from']) ? $transfer['team_name_from'] : ' ';
                    $countryFrom = !empty($transfer['country_name_from']) ? $transfer['country_name_from'] : ' ';
                    $headersFrom = get_headers($transfer['team_logo_path_from'], 1);
                    $teamLogoFrom = (strpos($headersFrom[0], '200') !== false) ? $transfer['team_logo_path_from'] : '' . '?' . rand();
                    $teamLogoFromCountry = (strpos($headersFrom[0], '200') !== false) ? $transfer['country_flag_path_from'] : '' . '?' . rand();
                    $teamTo = !empty($transfer['team_name_to']) ? $transfer['team_name_to'] : '';
                    $countryTo = !empty($transfer['country_name_to']) ? $transfer['country_name_to'] : ' ';
                    $headersTo = get_headers($transfer['team_logo_path_to'], 1);
                    $teamLogoTo = (strpos($headersTo[0], '200') !== false) ? $transfer['team_logo_path_to'] : '' . '?' . rand();
                    $teamLogoToCountry = (strpos($headersTo[0], '200') !== false) ? $transfer['country_flag_path_to'] . '?' . rand() : '';
                    /* End Old Working code  */
                    $teamFrom = !empty($transfer['team_name_from']) ? $transfer['team_name_from'] : ' ';
                    $countryFrom = !empty($transfer['country_name_from']) ? $transfer['country_name_from'] : ' ';

                    // Check if 'team_logo_path_from' is a valid URL and then retrieve headers
                    $teamLogoFrom = '';
                    $teamLogoFromCountry = '';
                    if (!empty($transfer['team_logo_path_from']) && filter_var($transfer['team_logo_path_from'], FILTER_VALIDATE_URL)) {
                        $headersFrom = @get_headers($transfer['team_logo_path_from'], 1);
                        if ($headersFrom && strpos($headersFrom[0], '200') !== false) {
                            $teamLogoFrom = $transfer['team_logo_path_from'];
                            $teamLogoFromCountry = $transfer['country_flag_path_from'];
                        } else {
                            $teamLogoFrom = '?' . rand();
                            $teamLogoFromCountry = '?' . rand();
                        }
                    }

                    $teamTo = !empty($transfer['team_name_to']) ? $transfer['team_name_to'] : '';
                    $countryTo = !empty($transfer['country_name_to']) ? $transfer['country_name_to'] : ' ';

                    // Check if 'team_logo_path_to' is a valid URL and then retrieve headers
                    $teamLogoTo = '';
                    $teamLogoToCountry = '';
                    if (!empty($transfer['team_logo_path_to']) && filter_var($transfer['team_logo_path_to'], FILTER_VALIDATE_URL)) {
                        $headersTo = @get_headers($transfer['team_logo_path_to'], 1);
                        if ($headersTo && strpos($headersTo[0], '200') !== false) {
                            $teamLogoTo = $transfer['team_logo_path_to'];
                            $teamLogoToCountry = $transfer['country_flag_path_to'] . '?' . rand();
                        } else {
                            $teamLogoTo = '?' . rand();
                            $teamLogoToCountry = '';
                        }
                    }


                    $content .= '<tr>
                                                        <td>' . $session . '</td>
                                                        <td>' . $formattedDate . '</td>
                                                        <td style="white-space: nowrap;">
                                                        <img src="' . $teamLogoFrom . '" style="width: 20px; vertical-align: middle; margin-right: 5px; margin-top: -2px;">
                                                        ' . $teamFrom . ', ' . $countryFrom . '
                                                        <img src="' . $teamLogoFromCountry . '" style="width: 20px; vertical-align: middle; margin-right: 5px; margin-top: -2px;">
                                                        </td>
                                                        <td style="white-space: nowrap;">
                                                        <img src="' . $teamLogoTo . '" style="width: 20px; vertical-align: middle; margin-right: 5px; margin-top: -2px;">
                                                        ' . $teamTo . ', ' . $countryTo . '
                                                        <img src="' . $teamLogoToCountry . '" style="width: 20px; vertical-align: middle; margin-right: 5px; margin-top: -2px;">
                                                        </td>
                                                        </tr>';
                }
                $content .= '</tbody>';
                $content .= '</table>';
                $content .= '</div>';
            }

            if (!empty($performanceDetail)) {
                $content .= '<div class="performance_data">';
                $content .= ' <h2 style="margin-top: 5px;padding: 0 10px;">Performance Data</h2>';
                $content .= '<table width="100%" border="0" cellspacing="0" cellpadding="0" class="table_perform">';
                $content .= '<thead>';
                $content .= '  <tr>';
                $content .= '  <th><img src="' . $flagPath . 'flag-1.png" style="width: 15px;vertical-align: middle;margin-right: 4px;float: left;margin-top: 3px;"><span>Team</span></th>';
                $content .= ' <th><img src="' . $flagPath . 'Icons6.png" style="width: 15px;vertical-align: middle;margin-right: 4px;float: left;"><span>Saison</span></th>';
                $content .= ' <th><img src="' . $flagPath . 'Icons13.png" style="width: 15px;vertical-align: middle;margin-right: 4px;float: left;"><span>Matches</span></th>';
                $content .= ' <th><img src="' . $flagPath . 'Icons17.png" style="width: 15px;vertical-align: middle;margin-right: 4px;float: left;"><span>Goals</span></th>';
                $content .= ' <th><img src="' . $flagPath . 'Icons14.png" style="width: 15px;vertical-align: middle;margin-right: 4px;float: left;"><span>Coach<br /> during debut</span></th>';
                $content .= ' <th><img src="' . $flagPath . 'Icons7.png" style="width: 15px;vertical-align: middle;margin-right: 4px;float: left;"><span>Age of<br /> player</span></th>';
                $content .= ' </tr>';
                $content .= ' </thead>';
                $content .= '<tbody>';
                foreach ($performanceDetail as $performance) {
                    // team_logo_path	, country_flag_path	
                    $session = !empty($performance['session']) ? $performance['session'] : '';
                    $teamName = !empty($performance['team_name']) ? $performance['team_name'] : '';
                    $matches = !empty($performance['matches']) ? $performance['matches'] : '';
                    $goals = !empty($performance['goals']) ? $performance['goals'] : '';
                    $coach = !empty($performance['coach']) ? $performance['coach'] : '';
                    $player_age = !empty($performance['player_age']) ? $performance['player_age'] : '';
                    $team_img = '';
                    $team_logo_path = $performance['team_logo_path'];
                    $team_logo_path = get_headers($team_logo_path, 1);
                    if (strpos($team_logo_path[0], '200') !== false) {
                        $team_logo_path = $performance['team_logo_path'];
                        $team_img = '<img src="' . $team_logo_path . '" style="width: 15px;vertical-align: middle;margin-right: 4px;float: left;">';
                    }
                    $content .= '<tr>';
                    $content .= '<td>' . $team_img . $teamName . '</td>';
                    $content .= '<td>' . $session . '</td>';
                    $content .= '<td>' . $matches . '</td>';
                    $content .= '<td>' . $goals . '</td>';
                    $content .= '<td>' . $coach . '</td>';
                    $content .= '<td>' . $player_age . ' Years</td>';
                    $content .= '</tr>';
                }
                $content .= '</tbody>';
                $content .= ' </table>';
                $content .= ' </div>';
            }
            $content .= ' <table width="100%" border="0" cellspacing="0" cellpadding="0" class="footer_table">
                                <tr>
                                    <td style="text-align: center;font-weight: bold;">Succer You Sports AG | www.socceryou.ch </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </body>
                </html>
            ';
            $stylesheet = file_get_contents(FCPATH . 'public/assets/css/user-profile-pdf.css');


            $url =  isset($_SERVER['HTTPS']) &&
                $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
            $url .= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            $parse_url = parse_url($url);
            $uri = new \CodeIgniter\HTTP\URI($url);

            $queryString =  $uri->getQuery();
            parse_str($queryString, $searchParams);
            if (isset($searchParams['content']) && $searchParams['content'] == 'html') {
                echo $content;
                // die;
            } else {
                // $mpdf->WriteHTML($stylesheet, 1);
                $mpdf->WriteHTML($content);
                $this->response->setHeader('Content-Type', 'application/pdf');
                $mpdf->Output(ucfirst(strtolower($fullName)) . '_' . strtolower($role_name) . '_dynamic.pdf', 'I');
                // $mpdf->Output($fullName.'_'.$role_name.'_dynamic.pdf','I');
            }
        } catch (\Mpdf\MpdfException $e) {
            echo $e->getMessage();
        }


    }



    public function loadEmail()
    {
        $message = '<p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industrys standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.</p>';

        $footer = getEmailFooter(2);
        
        return view('emailTemplateView', ['message' => $message,  'footer' => $footer ]);
    }

    public function amritTest(){
        $blogModel = new BlogModel();
        
        // Corrected join query with the field 'lang_language'
        // $blogModel->join('languages l', 'l.lang_language = pages.language');
        
        // Call the correct method from your model (ensure it's implemented properly)
        $arr = $blogModel->Testting(); // Adjust this if necessary
        
        // Print the array of results
        foreach($arr as $a){
            echo '<pre>';
            print_r($a); // Print each result in a readable format
            echo '</pre>';
        }
    }
    
    
}
