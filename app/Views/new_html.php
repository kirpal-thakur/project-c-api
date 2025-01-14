<?php

?>
<!DOCTYPE html>
<html lang="en">

<head>
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
            max-width: 100%;
            overflow: hidden;
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

        .ground_fix11 {
            width: 100%;
            max-width: 400px;
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

        .debug-view.show-view {
            border: none !important;
        }

        .show-view .debug-view-path {
            display: none !important;
        }

        #Maintable {
            margin-left: 50px;
        }

        /* My Style */
        .mt-5 {
            margin-top: 5px;
        }

        .top-8 {
            top: -8px !important;
        }

        .top-10px {
            top: -10px !important;
        }

        .top-20px {
            top: -20px !important;
        }

        .main_position {
            filter: saturate(3);
            filter: saturate(2) hue-rotate(90deg);
            /* Adjust saturation and hue */
        }
    </style>
    <title>Soccer You</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- <link rel="stylesheet" href="https://api.socceryou.ch/public/assets/css/user-profile-pdf.css?<= rand(); ?>"> -->
</head>

<body>
    <?php
    $flagPath = base_url() . 'public/assets/images/';
    $content = '';
    $all_positions = array(
        '1' => ['img' => 'playground-point1.png', 'class' => 'pos_gk', 'name' => 'Goalkeeper'], // Goalkeeper
        '2' => ['img' => 'playground-point2.png', 'class' => 'pos_cb', 'name' => 'Center back'], // Center back
        '3' => ['img' => 'playground-point4.png', 'class' => 'pos_rb', 'name' => 'Right full-back'], // Right full-back
        '4' => ['img' => 'playground-point5.png', 'class' => 'pos_lb', 'name' => 'Left full-back'], // Left full-back
        '5' => ['img' => 'playground-point6.png', 'class' => 'pos_cdm', 'name' => 'Defensive midfielder'], // Defensive midfielder
        '6' => ['img' => 'playground-point7.png', 'class' => 'pos_cm', 'name' => 'Central midfielder'], // Central midfielder
        '7' => ['img' => 'playground-point8.png', 'class' => 'pos_cam', 'name' => 'Attacking midfielder'], // Attacking midfielder
        '8' => ['img' => 'playground-point11.png', 'class' => 'pos_st', 'name' => 'Hanging striker'], // Hanging striker
        '9' => ['img' => 'playground-point10.png', 'class' => 'pos_lm', 'name' => 'Left winger'], // Left winger
        '10' => ['img' => 'playground-point9.png', 'class' => 'pos_rm', 'name' => 'Right winger'], // Right winger
        '11' => ['img' => 'playground-point8.png', 'class' => 'pos_cf', 'name' => 'Center forward'], // Center forward
        '12' => ['img' => 'playground-point9.png', 'class' => 'pos_rs', 'name' => 'Right striker'], // Right striker
        '13' => ['img' => 'playground-point10.png', 'class' => 'pos_ls', 'name' => 'Left striker']  // Left striker
    );

    if (isset($userData) && !empty($userData)) {
        $userData['positions'] = json_decode($userData['positions'], true);
    }

    $positions = isset($userData['positions']) ? $userData['positions'] : [];
    $mainPosition = '';
    $otherPositions = [];

    foreach ($positions as $position) {
        $posId = trim($position['position_id'] ?? '');
        $isMain = !empty($position['main_position']);

        // Check if the position ID exists in the $all_positions array
        if (array_key_exists($posId, $all_positions)) {
            if ($isMain) {
                $mainPosition = $posId; // Store main position ID
            } else {
                $otherPositions[] = $posId; // Store other position IDs
            }
        }
    }
    ?>

    <table width="100%" border="0" cellspacing="0" cellpadding="0" id="Maintable">
        <tr>
            <td class="court_position">
                <img src="<?= $flagPath; ?>Playground-image.png" class="ground_fix">
                <?php
                // Display main position with different styling
                if ($mainPosition && array_key_exists($mainPosition, $all_positions)) {
                    $mainImg = $all_positions[$mainPosition]['img'];
                    $mainClass = $all_positions[$mainPosition]['class'];

                    if ($mainClass == 'pos_gk') {
                        $mainClass = 'pos_gk mt-5 main_position';
                    } else if ($mainClass == 'pos_cam') {
                        $class = 'pos_cam top-8 main_position'; //top-10px
                    } else if ($mainClass == 'pos_lm' || $mainClass == 'pos_rm') {
                        $mainClass = $mainClass . ' top-10px main_position'; //top-10px
                    } else if ($mainClass == 'pos_rs' || $mainClass == 'pos_ls') {
                        $mainClass = $mainClass . ' top-20px main_position'; //top-10px
                    } else if ($class == 'pos_cf') {
                        $mainClass = 'pos_cf top-20px main_position';
                    }
                    echo '<img src="' . $flagPath . $mainImg . '" alt="' . $mainPosition . '" class="' . $mainClass . ' dot_posg">'; // Highlight main position
                }

                // Display other positions only once
                $displayedPositions = [$mainPosition]; // Start with the main position
                foreach ($otherPositions as $otherPos) {
                    if (!in_array($otherPos, $displayedPositions)) {
                        $img = $all_positions[$otherPos]['img'];
                        $class = $all_positions[$otherPos]['class'];

                        if ($class == 'pos_gk') {
                            $class = 'pos_gk mt-5';
                        } else if ($class == 'pos_cam') {
                            $class = 'pos_cam top-8'; //top-10px
                        } else if ($class == 'pos_lm' || $class == 'pos_rm') {
                            $class = $class . ' top-10px'; //top-10px
                        } else if ($class == 'pos_rs' || $class == 'pos_ls') {
                            $class = $class . ' top-20px'; //top-10px
                        } else if ($class == 'pos_cf') {
                            $class = 'pos_cf top-20px';
                        }
                        echo '<img src="' . $flagPath . $img . '" alt="' . $otherPos . '" class="' . $class . ' dot_posg">';
                        $displayedPositions[] = $otherPos; // Mark as displayed
                    }
                }
                ?>
            </td>
        </tr>
    </table>
    <!-- <br>
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>
    <br> -->
    <?php
    // $sno = 1;
    // foreach ($displayedPositions as $pos_id) {
    //     echo '<h1>' . $sno . ' --------------- ' . $all_positions[$pos_id]['name'] . '</h1>';
    //     $sno++;
    // }
    ?>
</body>

</html>