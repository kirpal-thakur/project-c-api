<?php

namespace App\Controllers;
use Mpdf;

class Home extends BaseController
{
    public function index(): string
    {
        return view('welcome_message');
    }


    

    public function generatePDF(){

        try {
            $flagPath = base_url() .'public/assets/images/';


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
                                                    <img src="'.$flagPath.'logo.png" style="width: 150px;vertical-align: top;margin-top: -4px;">
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
                                                    <img src="'.$flagPath.'fc-logo.png" style="width: 32px;float:left;margin-right: 4px;vertical-align:bottom;"> FC Thun
                                                </td>
                                            </tr>
                                            <tr>
                                            	<td>
                                                    <table width="100%" border="0" cellspacing="0" cellpadding="0" class="Inner_header1">
                                                    	<tr>
	                                                        <td>
	                                                            <strong>Height</strong><br />
	                                                            <img src="'.$flagPath.'Icons19.png" style="width: 25px;vertical-align: middle;margin-right: 4px;margin-top: 3px;float: left;vertical-align:bottom;"> 175cm
	                                                        </td>
	                                                        <td>
	                                                            <strong>Weight</strong><br />
	                                                            <img src="'.$flagPath.'Icons18.png" style="width: 25px;vertical-align: middle;margin-right: 4px;margin-top: 3px;float: left;vertical-align:bottom;"> 72kg
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
                                                    		<td style="vertical-align: middle;padding: 5px;"><img src="'.$flagPath.'Icons3.png" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;vertical-align:middle;">facebook.com/elton</td>
                                                    	</tr>
                                                   		<tr>
                                                   			<td style="vertical-align: middle;padding: 5px;"><img src="'.$flagPath.'Icons2.png" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;vertical-align:middle;">instagram.com/elton</td>
                                                   		</tr>
                                                   	</table>
                                            	</td>
                                        	</tr>
                                        	<tr>
                                            	<td class="bnr_img" style="vertical-align:bottom;">
                                                    <img src="'.$flagPath.'banner-pic.png" style="max-width: 100%;">
                                            	</td>
                                        	</tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            <table width="100%" border="0" cellspacing="0" cellpadding="0" class="player_detail" style="background: #f8f8f8;">
                            	<tr>
                                    <td colspan="3" style="padding: 0;">
                                        <img src="'.$flagPath.'players-1.png" style="width: 23%;margin-left: 4px;">
                                        <img src="'.$flagPath.'players-2.png" style="width: 23%;margin-left: 6px;">
                                        <img src="'.$flagPath.'players-3.png" style="width: 23%;margin-left: 6px;">
                                        <img src="'.$flagPath.'players-4.png" style="width: 23%;margin-left: 6px;">
                                    </td>
                                </tr>
                                <tr>
                                    <td class="detail_list">
                                        <h3>Age</h3>
                                        <p><img src="'.$flagPath.'Icons20.png" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;"> 20 Years</p>
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
                                        <p><img src="'.$flagPath.'flag-1.png" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;"> Switzerland</p>
                                        <p><img src="'.$flagPath.'Icons8.png" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;"> Kosovo</p>
                                    </td>
                                    <td class="detail_list no_border">
                                        <h3>Current market value</h3>
                                        <p>€54.5M</p>
                                    </td>
                                    <td class="detail_list no_border">
                                        <h3>International Player</h3>
                                        <p><img src="'.$flagPath.'Icons8.png" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;"> Kosovo</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="detail_list">
                                        <h3>Date of birth</h3>
                                        <p><img src="'.$flagPath.'Icons11.png" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;"> Apr 21, 2024</p>
                                    </td>
                                    <td colspan="2" class="detail_list">
                                        <h3>Last change</h3>
                                        <p>Apr 07, 2023</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="detail_list">
                                        <h3>Place of birth</h3>
                                        <p><img src="'.$flagPath.'flag-1.png" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;"> Zurich, Switzerland</p>
                                    </td>
                                    <td colspan="2" rowspan="4" class="detail_list no_border">
                                    	<table width="100%" border="0" cellspacing="0" cellpadding="0">
                                        	<tr>
		                                        <td class="court_position">
                                                    <img src="'.$flagPath.'Playground-image.png" class="ground_fix">
                                                    <img src="'.$flagPath.'playground-point1.png" class="pos_st dot_posg">
                                                    <img src="'.$flagPath.'playground-point2.png" class="pos_cam dot_posg">
                                                    <img src="'.$flagPath.'playground-point3.png" class="pos_lm dot_posg">
                                                    <img src="'.$flagPath.'playground-point4.png" class="pos_cm dot_posg">
                                                    <img src="'.$flagPath.'playground-point5.png" class="pos_rm dot_posg">
                                                    <img src="'.$flagPath.'playground-point6.png" class="pos_cdm dot_posg">
                                                    <img src="'.$flagPath.'playground-point7.png" class="pos_lwb dot_posg">
                                                    <img src="'.$flagPath.'playground-point8.png" class="pos_lb dot_posg">
                                                    <img src="'.$flagPath.'playground-point9.png" class="pos_rb dot_posg">
                                                    <img src="'.$flagPath.'playground-point10.png" class="pos_rwb dot_posg">
                                                    <img src="'.$flagPath.'playground-point11.png" class="pos_gk dot_posg">
                                                </td>
		                                    </tr>
		                                    <tr>
		                                        <td class="plyr_pos" style="padding: 0;">
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
                                        <p><img src="'.$flagPath.'Icons15.png" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;"> 2017 - 2025</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="detail_list">
                                        <h3>League</h3>
                                        <p><img src="'.$flagPath.'Icons16.png" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;"> Challenge League</p>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="detail_list no_border">
                                        <h3>Foot</h3>
                                        <p><img src="'.$flagPath.'Icons21.png" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;"> Right</p>
                                    </td>
                                </tr>
                            </table>
                            <div class="transfer_history" style="page-break-before:always;">
                                <h2 style="margin-top: 5px;padding: 0 10px;">Transfer History</h2>
                                <table width="100%" border="0" cellspacing="0" cellpadding="0" class="table_transfer">
	                                <thead>
	                                    <tr>
	                                        <th style="white-space: nowrap;"><img src="'.$flagPath.'Icons6.png" style="width: 15px;vertical-align: middle;margin-right: 4px;float: left;"><span>Saison</span></th>
	                                        <th style="white-space: nowrap;"><img src="'.$flagPath.'Icons20.png" style="width: 15px;vertical-align: middle;margin-right: 4px;float: left;"><span>Date</span></th>
	                                        <th>Moving From</th>
	                                        <th>Moving To</th>
	                                    </tr>
	                                </thead>
	                                <tbody>
	                                    <tr>
	                                        <td>2023</td>
	                                        <td>19.07.2022</td>
	                                        <td style="white-space: nowrap;"><img src="'.$flagPath.'fcsicon-icon.png" style="width: 20px;vertical-align: middle;margin-right: 5px;margin-top: -2px;"> YF Juventus Zürich II</td>
	                                        <td style="white-space: nowrap;"><img src="'.$flagPath.'fcsicon-icon.png" style="width: 20px;vertical-align: middle;margin-right: 5px;margin-top: -2px;"> FC Thun, Switzerland</td>
	                                    </tr>
	                                    <tr>
	                                        <td>2023</td>
	                                        <td>19.07.2022</td>
	                                        <td style="white-space: nowrap;"><img src="'.$flagPath.'fcsicon-icon.png" style="width: 20px;vertical-align: middle;margin-right: 5px;margin-top: -2px;"> YF Juventus Zürich II</td>
	                                        <td style="white-space: nowrap;"><img src="'.$flagPath.'fcsicon-icon.png" style="width: 20px;vertical-align: middle;margin-right: 5px;margin-top: -2px;"> FC Thun, Switzerland</td>
	                                    </tr>
	                                    <tr>
	                                        <td>2023</td>
	                                        <td>19.07.2022</td>
	                                        <td style="white-space: nowrap;"><img src="'.$flagPath.'fcsicon-icon.png" style="width: 20px;vertical-align: middle;margin-right: 5px;margin-top: -2px;"> YF Juventus Zürich II</td>
	                                        <td style="white-space: nowrap;"><img src="'.$flagPath.'fcsicon-icon.png" style="width: 20px;vertical-align: middle;margin-right: 5px;margin-top: -2px;"> FC Thun, Switzerland</td>
	                                    </tr>
	                                    <tr>
	                                        <td>2023</td>
	                                        <td>19.07.2022</td>
	                                        <td style="white-space: nowrap;"><img src="'.$flagPath.'fcsicon-icon.png" style="width: 20px;vertical-align: middle;margin-right: 5px;margin-top: -2px;"> YF Juventus Zürich II</td>
	                                        <td style="white-space: nowrap;"><img src="'.$flagPath.'fcsicon-icon.png" style="width: 20px;vertical-align: middle;margin-right: 5px;margin-top: -2px;"> FC Thun, Switzerland</td>
	                                    </tr>
	                                    <tr>
	                                        <td>2023</td>
	                                        <td>19.07.2022</td>
	                                        <td style="white-space: nowrap;"><img src="'.$flagPath.'fcsicon-icon.png" style="width: 20px;vertical-align: middle;margin-right: 5px;margin-top: -2px;"> YF Juventus Zürich II</td>
	                                        <td style="white-space: nowrap;"><img src="'.$flagPath.'fcsicon-icon.png" style="width: 20px;vertical-align: middle;margin-right: 5px;margin-top: -2px;"> FC Thun, Switzerland</td>
	                                    </tr>
	                                </tbody>
                                </table>
                            </div>
                            <div class="performance_data">
                                <h2 style="margin-top: 5px;padding: 0 10px;">Performance Data</h2>
                                <table width="100%" border="0" cellspacing="0" cellpadding="0" class="table_perform">
                                	<thead>
	                                    <tr>
	                                        <th><img src="'.$flagPath.'flag-1.png" style="width: 15px;vertical-align: middle;margin-right: 4px;float: left;margin-top: 3px;"><span>Team</span></th>
	                                        <th><img src="'.$flagPath.'Icons6.png" style="width: 15px;vertical-align: middle;margin-right: 4px;float: left;"><span>Saison</span></th>
	                                        <th><img src="'.$flagPath.'Icons13.png" style="width: 15px;vertical-align: middle;margin-right: 4px;float: left;"><span>Matches</span></th>
	                                        <th><img src="'.$flagPath.'Icons17.png" style="width: 15px;vertical-align: middle;margin-right: 4px;float: left;"><span>Goals</span></th>
	                                        <th><img src="'.$flagPath.'Icons14.png" style="width: 15px;vertical-align: middle;margin-right: 4px;float: left;"><span>Coach<br /> during debut</span></th>
	                                        <th><img src="'.$flagPath.'Icons7.png" style="width: 15px;vertical-align: middle;margin-right: 4px;float: left;"><span>Age of<br /> player</span></th>
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

            $stylesheet = file_get_contents(FCPATH .'public/assets/css/user-profile-pdf.css'); // external css

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
