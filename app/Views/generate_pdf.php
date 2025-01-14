<?php
// echo '<pre>'; print_r($userData); die;
$fullName = $userData['first_name'] . ' ' . $userData['last_name'];
$role = $userData['role_name'];
// Current club of the user
$current_club_name = $userData['current_club_name'];
// User's height with height unit
$height = isset($userData['meta']['height']) && isset($userData['meta']['height_unit'])
    ? $userData['meta']['height'] . ' ' . $userData['meta']['height_unit']
    : 'N/A';
// User's weight with weight unit
$weight = isset($userData['meta']['weight']) && isset($userData['meta']['weight_unit'])
    ? $userData['meta']['weight'] . ' ' . $userData['meta']['weight_unit']
    : 'N/A';
// User's team joining date
$in_team_since = $userData['meta']['in_team_since'] ?? 'N/A';
// User's top speed with speed unit
$top_speed = isset($userData['meta']['top_speed']) && isset($userData['meta']['top_speed_unit'])
    ? $userData['meta']['top_speed'] . ' ' . $userData['meta']['top_speed_unit']
    : 'N/A';
// User's date of birth
$date_of_birth = $userData['meta']['date_of_birth'] ?? 'N/A';
// Market value last updated date
$market_value_last_updated = $userData['meta']['market_value_last_updated'] ?? 'N/A';
// User's market value with currency or unit
$market_value = isset($userData['meta']['market_value']) && isset($userData['meta']['market_value_unit'])
    ? $userData['meta']['market_value'] . ' ' . $userData['meta']['market_value_unit']
    : 'N/A';

$user_nationalities = $userData['user_nationalities'];
if (!empty($userData['user_nationalities'])) {
    $user_nationalities = json_decode($user_nationalities, true);
}

?>
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
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script>
        setTimeout(function() {
            $('#mera_html_div').hide();
            $('#set_image_here #text').hide();
        }, 3000);
        // GetImage();
    </script>
    <style type="text/css">
        body {
            padding: 0 !important;
            margin: 0 !important;
            display: block !important;
            min-width: 100% !important;
            width: 100% !important;
            -webkit-text-size-adjust: none
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: "roboto";
            font-size: 14px;
            line-height: 1.35;
        }

        table {
            border-collapse: collapse;
            border: 0;
            font-family: "roboto";
        }

        table td,
        table th {
            vertical-align: top;
            padding: 10px;
            text-align: left;
            line-height: 1.35;
        }

        .current_club {
            font-size: 20px;
        }

        .performance_data,
        .transfer_history {
            width: 100%;
            background: #f8f8f8;
            padding: 12px;
            margin-top: 15px;
        }

        .performance_data tbody tr,
        .transfer_history tbody tr {
            background: #fff;
        }

        .social_links {
            width: 160px;
            float: right;
            font-size: 13px;
        }

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

        .court_position {
            position: relative;
        }

        img.ground_fix {
            position: absolute;
            left: 0;
            top: 0;
        }

        img.dot_posg {
            position: absolute;
            left: 0;
            top: 0;
        }
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
                                    <img src="<?= $flagPath ?>logo.png" style="width: 150px;vertical-align: top;margin-top: -4px;">
                                    <span style="font-size: 20px;padding-left: 5px;"><?= $role; ?> PROFILE</span>
                                </td>
                            </tr>
                            <tr>
                                <td style="line-height: 1;">
                                    <h1 style="width: 100%;float: left;font-size: 40px;margin: 20px 0;"><?= $fullName; ?><h1>
                                </td>
                            </tr>
                            <tr>
                                <td class="current_club">
                                    <strong>Current Club</strong><br />
                                    <?php
                                    if (!empty($userData['club_logo_path'])) {
                                        $club_logo_path = $userData['club_logo_path'];
                                    } else {
                                        $club_logo_path = 'https://api.socceryou.ch/no-img.png';
                                    }
                                    $clubLogo = get_headers($club_logo_path, 1);
                                    if (strpos($clubLogo[0], '200') !== false) {
                                        echo '<img src="' . $club_logo_path . '" style="width: 32px;float:left;margin-right: 4px;vertical-align:bottom;">';
                                    }
                                    echo  $current_club_name;
                                    echo  $userData['team_type'] ? ' (' . $userData['team_type'] . ')' : '';
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <table width="100%" border="0" cellspacing="0" cellpadding="0" class="Inner_header1">
                                        <tr>
                                            <td>
                                                <strong>Height</strong><br />
                                                <img src="<?= $flagPath; ?>Icons19.png" style="width: 25px;vertical-align: middle;margin-right: 4px;margin-top: 3px;float: left;vertical-align:bottom;"> <?= $height ?>
                                            </td>
                                            <td>
                                                <strong>Weight</strong><br />
                                                <img src="<?= $flagPath; ?>Icons18.png" style="width: 25px;vertical-align: middle;margin-right: 4px;margin-top: 3px;float: left;vertical-align:bottom;"> <?= $weight ?>
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
                                        <?php
                                        $content = '';
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
                                        echo $content;
                                        ?>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td class="bnr_img" style="vertical-align:bottom;">
                                    <?php
                                    if (!empty($userData['meta']['profile_image_path'])) {
                                        $profile_image_path = $userData['meta']['profile_image_path'];
                                    } else {
                                        $profile_image_path = $flagPath . 'banner-pic.png';
                                    }
                                    ?>
                                    <img src="<?= $profile_image_path ?>" style="max-width: 100%;">
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <table width="100%" border="0" cellspacing="0" cellpadding="0" class="player_detail" style="background: #f8f8f8;">
                <tr>
                    <td colspan="3" style="padding: 0;">
                        <?php
                        if (!empty($images)) {
                            foreach ($images as $image) {
                                $imagepathFull = $imagePath . $image['file_name'];
                                $headers = get_headers($imagepathFull, 1);
                                if (strpos($headers[0], '200') !== false) {
                                    echo '<img src="' . $imagePath . $image['file_name'] . '" style="width: 23%;margin-left: 4px;">';
                                }
                            }
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td class="detail_list">
                        <h3>Age</h3>
                        <p><img src="<?= $flagPath ?>Icons20.png" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;"><?= $age ?> Years</p>
                    </td>
                    <td class="detail_list no_border">
                        <h3>In the team since</h3>
                        <p><?= $in_team_since ?></p>
                    </td>
                    <td class="detail_list no_border">
                        <h3>Top Speed</h3>
                        <p><?= $top_speed ?></p>
                    </td>
                </tr>
                <tr>
                    <td class="detail_list">
                        <h3>Nationality</h3>
                        <?php
                        if (!empty($user_nationalities)) {
                            foreach ($user_nationalities as $nationality) {
                                $headers = get_headers($nationality['flag_path'], 1);
                                if (strpos($headers[0], '200') !== false) {
                                    echo '<p>
                                                            <img src="' . $nationality['flag_path'] . '" style="width: 25px; vertical-align: middle; margin-right: 4px; float: left;">
                                                            ' . $nationality['country_name'] . '
                                                        </p>';
                                } else if (!empty($nationality['country_name'])) {
                                    echo '<p>' . $nationality['country_name'] . '</p>';
                                }
                            }
                        }
                        ?>
                    </td>
                    <td class="detail_list no_border">
                        <h3>Current market value</h3>
                        <p><?= $market_value ?></p>
                    </td>
                    <?php
                    if (isset($userData['meta']['international_player']) && !empty($userData['meta']['international_player'])) {
                        $international = trim($userData['meta']['international_player']);
                        // $international = get_headers($userData['meta']['international_player'], 1);
                        // if (strpos($international[0], '200') !== false) {
                    ?>
                        <td class="detail_list no_border">
                            <h3>International Player</h3>
                            <p><?= $international ?></p>
                        </td>
                    <?php
                    }
                    ?>
                </tr>
                <tr>
                    <td class="detail_list">
                        <h3>Date of birth</h3>
                        <p><img src="<?= $flagPath ?>Icons11.png" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;"><?= $date_of_birth ?></p>
                    </td>
                    <td colspan="2" class="detail_list">
                        <h3>Last change</h3>
                        <?php
                        if (!empty($userData['meta']['last_change'])) {
                            echo '<p>' . $userData['meta']['last_change'] . '</p>';
                        } else {
                            echo '<p>-</p>';
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td class="detail_list">
                        <h3>Place of birth</h3>
                        <p><img src="<?= $flagPath ?>flag-1.png" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;"><?= $userData['meta']['place_of_birth'] ?></p>
                    </td>
                    <td colspan="2" rowspan="4" class="detail_list no_border">
                        <table width="100%" border="0" cellspacing="0" cellpadding="0">
                            <tr>
                                <td class="court_position">
                                    <!-- Pending -->
                                    <div id="set_image_here">
                                        <?php

                                        if (isset($userData['meta']['pdf_image']) && !empty($userData['meta']['pdf_image'])) {
                                            // echo '<img src="' . $userData['meta']['pdf_image'] . '" alt="playground & positions" style="width: 350px;">';
                                            echo '<img src="' . base_url() . $fileName . '" alt="playground & positions" style="width: 350px;">';
                                        }
                                        ?>
                                        <div id="text"><?php print_r($positionsTxt); ?></div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="plyr_pos" style="padding: 0;">
                                    <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                        <tr>
                                            <?php
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
                                                    echo '<td><h3>Other position(s)</h3>' . implode("/", $otherPositions) . '</td>';
                                                }
                                            }
                                            ?>
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
                        <?php
                        $contract = '-';
                        if (isset($userData['meta']['contract_start']) && !empty($userData['meta']['contract_end'])) {
                            $contract = $userData['meta']['contract_start'] . ' - ' . $userData['meta']['contract_end'];
                        }
                        ?>
                        <p><img src="<?= $flagPath ?>Icons15.png" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;"><?= $contract; ?></p>
                    </td>
                </tr>
                <tr>
                    <td class="detail_list">
                        <?php
                        if (isset($userData['league_name']) && !empty($userData['league_name'])) {
                            echo '<p>';
                            if (isset($userData['league_logo_path']) && !empty($userData['league_logo_path'])) {
                                $league_logo_path = $userData['league_logo_path'];
                                $leaguepath = get_headers($league_logo_path, 1);
                                if (strpos($leaguepath[0], '200') !== false) {
                                    echo '<h3>League</h3>';
                                    echo '<img src="' . $league_logo_path . '" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;">';
                                }
                            }
                            echo $userData['league_name'] . '<p>';
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td class="detail_list no_border">
                        <?php
                        $foot = '';
                        if (!empty($userData['meta']['foot'])) {
                            $foot = trim(strtolower($userData['meta']['foot']));
                            $foot_image = get_headers($flagPath . '/foot/' . $foot . '.svg', 1);
                            // echo $foot_image; die;
                            // echo '<pre>'; print_r($foot_image); die;
                            if (strpos($foot_image[0], '200') !== false) {
                                $foot_image = $flagPath . '/foot/' . $foot . '.svg';
                                echo '<h3>Foot</h3>';
                                echo '<p><img src="' . $foot_image . '" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;"> ' . ucfirst($foot) . '</p>';
                            }
                        }
                        ?>
                    </td>
                </tr>
            </table>
            <div class="transfer_history" style="page-break-before:always;">
                <h2 style="margin-top: 5px;padding: 0 10px;">Transfer History</h2>
                <table width="100%" border="0" cellspacing="0" cellpadding="0" class="table_transfer">
                    <thead>
                        <tr>
                            <th style="white-space: nowrap;"><img src="<?= $flagPath ?>Icons6.png" style="width: 15px;vertical-align: middle;margin-right: 4px;float: left;"><span>Saison</span></th>
                            <th style="white-space: nowrap;"><img src="<?= $flagPath ?>Icons20.png" style="width: 15px;vertical-align: middle;margin-right: 4px;float: left;"><span>Date</span></th>
                            <th>Moving From</th>
                            <th>Moving To</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (!empty($transferDetail)) {

                            foreach ($transferDetail as $transfer) {
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
                        ?>
                                <tr>

                                    <td><?= $session ?></td>
                                    <td><?= $formattedDate ?></td>
                                    <td style="white-space: nowrap;">
                                        <img src="<?= $teamLogoFrom ?>" style="width: 20px; vertical-align: middle; margin-right: 5px; margin-top: -2px;">
                                        <?= $teamFrom . ' ,' . $countryFrom ?>
                                    </td>
                                    <td style="white-space: nowrap;">
                                        <img src="<?= $teamLogoTo ?> " style="width: 20px; vertical-align: middle; margin-right: 5px; margin-top: -2px;">
                                        <?= $teamTo . ' ,' . $countryTo ?>
                                    </td>
                                </tr>
                        <?php
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <div class="performance_data">
                <h2 style="margin-top: 5px;padding: 0 10px;">Performance Data</h2>
                <table width="100%" border="0" cellspacing="0" cellpadding="0" class="table_perform">
                    <thead>
                        <tr>
                            <th><img src="<?= $flagPath ?>flag-1.png" style="width: 15px;vertical-align: middle;margin-right: 4px;float: left;margin-top: 3px;"><span>Team</span></th>
                            <th><img src="<?= $flagPath ?>Icons6.png" style="width: 15px;vertical-align: middle;margin-right: 4px;float: left;"><span>Saison</span></th>
                            <th><img src="<?= $flagPath ?>Icons13.png" style="width: 15px;vertical-align: middle;margin-right: 4px;float: left;"><span>Matches</span></th>
                            <th><img src="<?= $flagPath ?>Icons17.png" style="width: 15px;vertical-align: middle;margin-right: 4px;float: left;"><span>Goals</span></th>
                            <th><img src="<?= $flagPath ?>Icons14.png" style="width: 15px;vertical-align: middle;margin-right: 4px;float: left;"><span>Coach<br /> during debut</span></th>
                            <th><img src="<?= $flagPath ?>Icons7.png" style="width: 15px;vertical-align: middle;margin-right: 4px;float: left;"><span>Age of<br /> player</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (!empty($performanceDetail)) {
                            foreach ($performanceDetail as $performance) {
                                $session = !empty($performance['session']) ? $performance['session'] : ' ';
                                $teamName = !empty($performance['team_name']) ? $performance['team_name'] : ' ';
                                $matches = !empty($performance['matches']) ? $performance['matches'] : ' ';
                                $goals = !empty($performance['goals']) ? $performance['goals'] : ' ';
                                $coach = !empty($performance['coach']) ? $performance['coach'] : '';
                                $player_age = !empty($performance['player_age']) ? $performance['player_age'] : ' ';
                        ?>
                                <tr>
                                    <td><?= $teamName ?></td>
                                    <td><?= $session ?></td>
                                    <td><?= $matches ?></td>
                                    <td><?= $goals ?></td>
                                    <td><?= $coach ?></td>
                                    <td><?= $player_age ?> Years</td>
                                </tr>
                        <?php
                            }
                        }
                        ?>
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

<?php
// $mpdf->WriteHTML($content);
// $this->response->setHeader('Content-Type', 'application/pdf');
// $mpdf->Output();
?>