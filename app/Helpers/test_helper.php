<?php

function getPlayersTesting($whereClause = [], $metaQuery = [], $search = '', $orderBy = '', $order = '', $limit = 10, $offset = 0, $noLimit = false, $lang_id = null)
{
    $db = \Config\Database::connect();
    $language = \Config\Services::language();
    $lang_id = $lang_id ?? DEFAULT_LANGUAGE;
    $language->setLocale(getLanguageCode($lang_id)); 
    // echo ' >>>>>>>>>>>>>> lang_id >>>>  ' . $lang_id;
    // echo ' >>>> ' . timeAgo('2025-01-08 14:22:48', $lang_id = 1); exit;


    $flagPath =  base_url() . 'uploads/logos/';

    // Start building the main query
    $builder = $db->table('users');
    // timeAgo(users.last_active)
    $builder->select('users.*, 
                        user_roles.role_name as role_name,
                        languages.language as language,
                        domains.domain_name as domain_name,
                        domains.location as user_location,
                        auth.secret as email,
                        packages.title as package_name,
                        pm.last4 as last4,
                        pm.brand as brand,
                        pm.exp_month as exp_month,
                        pm.exp_year as exp_year,
                        us.status as subscription_status,
                        us.plan_period_start as plan_period_start,
                        us.plan_period_end as plan_period_end,
                        us.plan_interval as plan_interval,
                        us.coupon_used as coupon_used,
                        
                        TIMESTAMPDIFF(SECOND, `users`.`last_active`, NOW()) AS activity_diff_seconds,
                        TIMESTAMPDIFF(SECOND, `users`.`last_active`, NOW()) AS registration_diff_seconds,
                        
                        CASE
                            WHEN TIMESTAMPDIFF(SECOND, `users`.`last_active`, NOW()) < 60 THEN 
                                CONCAT(TIMESTAMPDIFF(SECOND, `users`.`last_active`, NOW()), " secs ago")
                            WHEN TIMESTAMPDIFF(MINUTE, `users`.`last_active`, NOW()) < 60 THEN 
                                CONCAT(TIMESTAMPDIFF(MINUTE, `users`.`last_active`, NOW()), " mins ago")
                            WHEN TIMESTAMPDIFF(HOUR, `users`.`last_active`, NOW()) < 24 THEN 
                                CONCAT(TIMESTAMPDIFF(HOUR, `users`.`last_active`, NOW()), " hours ago")
                            WHEN TIMESTAMPDIFF(DAY, `users`.`last_active`, NOW()) < 30 THEN 
                                  CONCAT(TIMESTAMPDIFF(DAY, `users`.`last_active`, NOW()), " days ago")
                            WHEN TIMESTAMPDIFF(MONTH, `users`.`last_active`, NOW()) < 12 THEN 
                                CONCAT(TIMESTAMPDIFF(MONTH, `users`.`last_active`, NOW()), " months ago")
                            ELSE 
                                CONCAT(TIMESTAMPDIFF(YEAR, `users`.`last_active`, NOW()), " years ago")
                        END AS last_activity_time,

                        CASE
                            WHEN TIMESTAMPDIFF(SECOND, `users`.`created_at`, NOW()) < 60 THEN 
                                CONCAT(TIMESTAMPDIFF(SECOND, `users`.`created_at`, NOW()), " secs ago")
                            WHEN TIMESTAMPDIFF(MINUTE, `users`.`created_at`, NOW()) < 60 THEN 
                                CONCAT(TIMESTAMPDIFF(MINUTE, `users`.`created_at`, NOW()), " mins ago")
                            WHEN TIMESTAMPDIFF(HOUR, `users`.`created_at`, NOW()) < 24 THEN 
                                CONCAT(TIMESTAMPDIFF(HOUR, `users`.`created_at`, NOW()), " hours ago")
                            WHEN TIMESTAMPDIFF(DAY, `users`.`created_at`, NOW()) < 30 THEN 
                                CONCAT(TIMESTAMPDIFF(DAY, `users`.`created_at`, NOW()), " days ago")
                            WHEN TIMESTAMPDIFF(MONTH, `users`.`created_at`, NOW()) < 12 THEN 
                                CONCAT(TIMESTAMPDIFF(MONTH, `users`.`created_at`, NOW()), " months ago")
                            ELSE 
                                CONCAT(TIMESTAMPDIFF(YEAR, `users`.`created_at`, NOW()), " years ago")
                        END AS registration_time,

                        JSON_ARRAYAGG(
                            JSON_OBJECT(
                                "country_name", c.country_name, 
                                "flag_path", CONCAT("' . $flagPath . '", c.country_flag)
                            )
                        ) AS user_nationalities,

                        l.league_name as league_name,
                        l.league_logo as league_logo,
                        CONCAT("' . $flagPath . '", l.league_logo) as league_logo_path
                    ');



    // $builder->join('(SELECT user_id, secret FROM auth_identities WHERE type = "email_password") auth', 'auth.user_id = users.id', 'LEFT');
    $builder->join('auth_identities auth', 'auth.user_id = users.id AND type = "email_password"', 'LEFT');
    $builder->join('user_roles', 'user_roles.id = users.role', 'INNER');
    $builder->join('languages', 'languages.id = users.lang', 'INNER');
    $builder->join('domains', 'domains.id = users.user_domain', 'INNER');
    $builder->join('user_nationalities un', 'un.user_id = users.id', 'LEFT');
    $builder->join('countries c', 'c.id = un.country_id', 'LEFT');
    $builder->join('(SELECT user_id, brand, exp_month, exp_year, last4 FROM payment_methods WHERE is_default = 1) pm', 'pm.user_id = users.id', 'LEFT');
    $builder->join('(SELECT user_id, meta_value FROM user_meta WHERE meta_key = "current_club") um', 'um.user_id = users.id', 'LEFT');
    $builder->join('league_clubs lc', 'lc.club_id = um.meta_value', 'LEFT');
    $builder->join('leagues l', 'l.id = lc.league_id', 'LEFT');
    if (isset($whereClause['selectedUserIds']) && !empty($whereClause['selectedUserIds'])) { 
        if(is_array($whereClause['selectedUserIds'])){

        }else{
            $whereClause['selectedUserIds'] = explode(',',$whereClause['selectedUserIds']);
        }
    //   echo '<pre>'; print_r($whereClause['selectedUserIds']); die;
        $builder->whereIn('users.id', $whereClause['selectedUserIds']);
        unset($whereClause['selectedUserIds']); // unset after use
    }
    // START: membership check
    if (isset($whereClause['membership'])) {

        if ($whereClause['membership'] == 'paid') {
            $builder->join('(SELECT id as max_id, user_id, status, package_id, plan_period_start, plan_period_end, plan_interval, coupon_used FROM user_subscriptions WHERE id IN (SELECT MAX(id) as max_id FROM user_subscriptions GROUP BY user_id) AND status = "active") us', 'us.user_id = users.id', 'INNER');
        } else {
            $builder->join('user_subscriptions us', 'us.user_id = users.id', 'LEFT');
        }
    } else {
        $builder->join('(SELECT id as max_id, user_id, status, package_id, plan_period_start, plan_period_end, plan_interval, coupon_used FROM user_subscriptions WHERE id IN (SELECT MAX(id) as max_id FROM user_subscriptions GROUP BY user_id)) us', 'us.user_id = users.id', 'LEFT');
    }
    // END: membership check


    $builder->join('packages', 'packages.id = us.package_id', 'LEFT');

    $builder->where('users.deleted_at', NULL);
    $builder->where("users.status IS NOT NULL");
    $builder->whereNotIn("users.role", [5]);

    if (auth()->id()) {
        $builder->whereNotIn("users.id", [auth()->id()]);
    }

    // Build the query with the same conditions
    if (isset($whereClause) && count($whereClause) > 0) {
        foreach ($whereClause as $key => $value) {
            if ($key == 'membership') {
                continue;
            } elseif ($key == 'email') {
                $builder->like("auth.secret", $value);
            } elseif ($key == 'location') {
                $builder->like("domains.id", $value);
            } /*elseif ($key == 'last_active') {
                $builder->where("users.last_active >= ", ( CURDATE() " - INTERVAL". $value ));
            } */ else {
                $builder->where("users.$key", $value);
            }
        }
    }

    if (isset($metaQuery) && count($metaQuery) > 0) {
        foreach ($metaQuery as $meta) {
            $metaBuilder = $db->table('user_meta');
            $metaBuilder->select('user_id')
                ->where('meta_key', $meta['meta_key']);

            $metaValue = is_array($meta['meta_value']) ? $meta['meta_value'] : (is_numeric($meta['meta_value']) ? (int)$meta['meta_value'] : $meta['meta_value']);

            if ($meta['operator'] == "=") {
                $metaBuilder->where('meta_value', $metaValue);
            } elseif ($meta['operator'] == ">=") {
                $metaBuilder->where('meta_value >=', $metaValue);
            } elseif ($meta['operator'] == "<=") {
                $metaBuilder->where('meta_value <=', $metaValue);
            } elseif ($meta['operator'] == "IN") {
                $metaBuilder->whereIn('meta_value', $metaValue);
            } else {
                $metaBuilder->like('meta_value', $metaValue);
            }

            $metaSubquery = $metaBuilder->getCompiledSelect();
            $builder->where("users.id IN ($metaSubquery)");
        }
    }

    // START: membership check
    // check only users with free membership
    if (isset($whereClause['membership']) && $whereClause['membership'] == 'free') {
        $builder->where("us.user_id", NULL);
    }
    // END: membership check


    if (isset($search) && !empty($search)) {
        $builder->groupStart();
        $builder->orLike('users.username', $search);
        $builder->orLike('users.first_name', $search);
        $builder->orLike('users.last_name', $search);
        $builder->orLike('auth.secret', $search);
        $builder->groupEnd();
    }

    $builder->groupBy('users.id');

    // Count the total number of results
    // $countBuilder  = $db->newQuery()->select('count(*) as total_count')->fromSubquery($builder, 'counter_table');
    // $countQuery = $countBuilder->get();
    // $totalCount = $countQuery->getRow()->total_count;

    // Count the total number of results
    $countBuilder = clone $builder;
    $countQuery = $countBuilder->get();
    $totalCount =  $countQuery->getNumRows();


    // Add Sorting
    if (!empty($orderBy)) {
        $builder->orderBy($orderBy, $order);
    } else {
        $builder->orderBy('id', 'DESC');
    }

    // Add pagination
    if ($noLimit != true) {
        $builder->limit($limit, $offset);
    }

    // Execute the main query
    $query = $builder->get();
    // echo '>>>>>>>> getLastQuery >>>>> '. $db->getLastQuery(); //exit;

    $result = $query->getResultArray();

    // Process the results to merge metadata into user objects
    $users = [];
    foreach ($result as $row) {

        // pr($row);
        $userId = $row['id'];
        if (!isset($users[$userId])) {

            // $row['last_activity_time'] = timeAgo($row['last_active'], $lang_id);
            // $row['registration_time']  = timeAgo($row['created_at'], $lang_id);
            // echo $row['last_active'];
            // echo ' >>>> ' . timeAgo($row['last_active'], $lang_id = 1); exit;
            // echo ' >>>> ' . timeAgo('2025-01-08 14:22:48', $lang_id = 1); exit;
            // $last_active = timeAgo($row['last_active'], $lang_id = 1);
            // echo ' >>>>>>>> ' . $row['last_active']; echo "<br>";
            // echo ' >>>>>>>> ' . $last_active; echo "<br>";
            // echo ' >>>> ' . timeAgo('2025-01-09 06:23:33', $lang_id = 1);
            // echo ' >>>> ' . timeAgo('2025-01-08 14:22:48', $lang_id = 1); //exit;

            // echo ' .......' . floor($row['diff_seconds'] / 60);
            if ($row['activity_diff_seconds'] < 60) {
                $last_active =  lang('Time.ago.seconds', [$row['activity_diff_seconds']]);
            } elseif ($row['activity_diff_seconds'] < 3600) {
                $minutes = floor($row['activity_diff_seconds'] / 60);
                $last_active = lang('Time.ago.minutes', [$minutes]);
            } elseif ($row['activity_diff_seconds'] < 86400) {
                $hours = floor($row['activity_diff_seconds'] / 3600);
                $last_active = lang('Time.ago.hours', [$hours]);
            } elseif ($row['activity_diff_seconds'] < 2592000) {
                $days = floor($row['activity_diff_seconds'] / 86400);
                $last_active = lang('Time.ago.days', [$days]);
            } elseif ($row['activity_diff_seconds'] < 31536000) {
                $month = floor($row['activity_diff_seconds'] / 2592000);
                $last_active = lang('Time.ago.months', [$month]);
            } else {
                $years = floor($row['activity_diff_seconds'] / 31536000);
                $last_active = lang('Time.ago.years', [$years]);
            }
            
            // elseif ($row['diff_seconds'] < 3600) {
            //     $min = floor($row['diff_seconds'] / 60);
            //     $last_active = lang('Time.ago.minutes', $min);
            // } elseif ($row['diff_seconds'] < 86400) {
            //     $last_active = lang('Time.ago.hours', floor($row['diff_seconds'] / 3600));
            // } elseif ($row['diff_seconds'] < 2592000) {
            //     $last_active = lang('Time.ago.days', floor($row['diff_seconds'] / 86400));
            // } elseif ($row['diff_seconds'] < 31536000) {
            //     $last_active = lang('Time.ago.months', floor($row['diff_seconds'] / 2592000));
            // } else {
            //     $last_active = lang('Time.ago.years', floor($row['diff_seconds'] / 31536000));
            // }
            // echo ' >>>>>>>> ' .$userId . ' ... '. $last_active; echo "<br>";


            $users[$userId] = [
                'id'                            => $row['id'],
                'username'                      => $row['username'],
                'email'                         => $row['email'],
                'created_at'                    => $row['created_at'],
                'updated_at'                    => $row['updated_at'],
                'first_name'                    => $row['first_name'],
                'last_name'                     => $row['last_name'],
                'role_name'                     => $row['role_name'],
                'language'                      => $row['language'],
                'newsletter'                    => $row['newsletter'],
                'user_domain'                   => $row['domain_name'],
                'user_location'                 => $row['user_location'],
                'last_active'                   => $row['last_active'],
                'status'                        => $row['status'],
                'last4'                         => $row['last4'],
                'brand'                         => $row['brand'],
                'exp_month'                     => $row['exp_month'],
                'exp_year'                      => $row['exp_year'],
                'package_name'                  => $row['package_name'],
                'subscription_status'           => $row['subscription_status'],
                'plan_period_start'             => $row['plan_period_start'],
                'plan_period_end'               => $row['plan_period_end'],
                'plan_interval'                 => $row['plan_interval'],
                'coupon_used'                   => $row['coupon_used'],
                'last_activity_time'            => $row['last_activity_time'],
                'last_activity_time_1'            => $last_active,
                
                'user_nationalities'            => $row['user_nationalities'],
                'registration_time'             => $row['registration_time'],
                'league_name'                   => $row['league_name'],
                'league_logo'                   => $row['league_logo'],
                'league_logo_path'              => $row['league_logo_path'],
                'meta' => []
            ];
        }
    }

    $imagePath = base_url() . 'uploads/';

    // Get all meta data for each user
    $userIds = array_keys($users);
    if (!empty($userIds)) {
        $metaBuilder = $db->table('user_meta');
        $metaBuilder->whereIn('user_id', $userIds);
        $metaQuery = $metaBuilder->get();
        $metaResult = $metaQuery->getResultArray();

        foreach ($metaResult as $metaRow) {
            //pr($metaRow);
            $userId = $metaRow['user_id'];
            $users[$userId]['meta'][$metaRow['meta_key']] = $metaRow['meta_value'];
            if ($metaRow['meta_key'] == 'profile_image') {
                $users[$userId]['meta']['profile_image_path'] = $imagePath . $metaRow['meta_value'];
            }
            if ($metaRow['meta_key'] == 'cover_image') {
                $users[$userId]['meta']['cover_image_path'] = $imagePath . $metaRow['meta_value'];
            }
        }
    }

    $userData = array_values($users);
    return [
        'totalCount' => $totalCount,
        'users' => $userData
    ];
}



if (!function_exists('timeAgo')) {
    function timeAgo($timestamp = null, $lang_id = null)
    {
        // echo ' ..... $timestamp ... ' . $timestamp; //exit;
        $timeDifference = time() - strtotime($timestamp);
        // echo ' ..... $timeDifference ... ' . $timeDifference; //echo '<br>';  //exit;

        $language = \Config\Services::language();
        $language->setLocale(getLanguageCode($lang_id));

        if ($timeDifference < 60) {
            return lang('Time.ago.seconds', [$timeDifference]);
        } elseif ($timeDifference < 3600) {
            return lang('Time.ago.minutes', [floor($timeDifference / 60)]);
        } elseif ($timeDifference < 86400) {
            return lang('Time.ago.hours', [floor($timeDifference / 3600)]);
        } elseif ($timeDifference < 2592000) {
            return lang('Time.ago.days', [floor($timeDifference / 86400)]);
        } elseif ($timeDifference < 31536000) {
            return lang('Time.ago.months', [floor($timeDifference / 2592000)]);
        } else {
            return lang('Time.ago.years', [floor($timeDifference / 31536000)]);
        }
    }
}