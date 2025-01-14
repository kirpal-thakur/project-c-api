<?php

/*
 | --------------------------------------------------------------------
 | App Namespace
 | --------------------------------------------------------------------
 |
 | This defines the default Namespace that is used throughout
 | CodeIgniter to refer to the Application directory. Change
 | this constant to change the namespace that all application
 | classes should use.
 |
 | NOTE: changing this will require manually modifying the
 | existing namespaces of App\* namespaced-classes.
 */
defined('APP_NAMESPACE') || define('APP_NAMESPACE', 'App');

/*
 | --------------------------------------------------------------------------
 | Composer Path
 | --------------------------------------------------------------------------
 |
 | The path that Composer's autoload file is expected to live. By default,
 | the vendor folder is in the Root directory, but you can customize that here.
 */
defined('COMPOSER_PATH') || define('COMPOSER_PATH', ROOTPATH . 'vendor/autoload.php');

/*
 |--------------------------------------------------------------------------
 | Timing Constants
 |--------------------------------------------------------------------------
 |
 | Provide simple ways to work with the myriad of PHP functions that
 | require information to be in seconds.
 */
defined('SECOND') || define('SECOND', 1);
defined('MINUTE') || define('MINUTE', 60);
defined('HOUR')   || define('HOUR', 3600);
defined('DAY')    || define('DAY', 86400);
defined('WEEK')   || define('WEEK', 604800);
defined('MONTH')  || define('MONTH', 2_592_000);
defined('YEAR')   || define('YEAR', 31_536_000);
defined('DECADE') || define('DECADE', 315_360_000);

/*
 | --------------------------------------------------------------------------
 | Exit Status Codes
 | --------------------------------------------------------------------------
 |
 | Used to indicate the conditions under which the script is exit()ing.
 | While there is no universal standard for error codes, there are some
 | broad conventions.  Three such conventions are mentioned below, for
 | those who wish to make use of them.  The CodeIgniter defaults were
 | chosen for the least overlap with these conventions, while still
 | leaving room for others to be defined in future versions and user
 | applications.
 |
 | The three main conventions used for determining exit status codes
 | are as follows:
 |
 |    Standard C/C++ Library (stdlibc):
 |       http://www.gnu.org/software/libc/manual/html_node/Exit-Status.html
 |       (This link also contains other GNU-specific conventions)
 |    BSD sysexits.h:
 |       http://www.gsp.com/cgi-bin/man.cgi?section=3&topic=sysexits
 |    Bash scripting:
 |       http://tldp.org/LDP/abs/html/exitcodes.html
 |
 */
defined('EXIT_SUCCESS')        || define('EXIT_SUCCESS', 0);        // no errors
defined('EXIT_ERROR')          || define('EXIT_ERROR', 1);          // generic error
defined('EXIT_CONFIG')         || define('EXIT_CONFIG', 3);         // configuration error
defined('EXIT_UNKNOWN_FILE')   || define('EXIT_UNKNOWN_FILE', 4);   // file not found
defined('EXIT_UNKNOWN_CLASS')  || define('EXIT_UNKNOWN_CLASS', 5);  // unknown class
defined('EXIT_UNKNOWN_METHOD') || define('EXIT_UNKNOWN_METHOD', 6); // unknown class member
defined('EXIT_USER_INPUT')     || define('EXIT_USER_INPUT', 7);     // invalid user input
defined('EXIT_DATABASE')       || define('EXIT_DATABASE', 8);       // database error
defined('EXIT__AUTO_MIN')      || define('EXIT__AUTO_MIN', 9);      // lowest automatically-assigned error code
defined('EXIT__AUTO_MAX')      || define('EXIT__AUTO_MAX', 125);    // highest automatically-assigned error code

/**
 * @deprecated Use \CodeIgniter\Events\Events::PRIORITY_LOW instead.
 */
define('EVENT_PRIORITY_LOW', 200);

/**
 * @deprecated Use \CodeIgniter\Events\Events::PRIORITY_NORMAL instead.
 */
define('EVENT_PRIORITY_NORMAL', 100);

/**
 * @deprecated Use \CodeIgniter\Events\Events::PRIORITY_HIGH instead.
 */
define('EVENT_PRIORITY_HIGH', 10);


/* ///////////// Custom Constants //////////////  */

defined('SECRET_KEY')               || define('SECRET_KEY', 'thisismysecretekey');    
defined('FROM_EMAIL')               || define('FROM_EMAIL', 'info@socceryou.ch');    
defined('FROM_NAME')                || define('FROM_NAME', 'Succer You Sports AG');    
defined('ADMIN_EMAIL')              || define('ADMIN_EMAIL', 'info@socceryou.ch, testmails.cts@gmail.com');    
defined('DEFAULT_LANGUAGE')         || define('DEFAULT_LANGUAGE', 1);     // 1 = English
defined('PER_PAGE')                 || define('PER_PAGE', 10);    
defined('MAX_FILE_SIZE')            || define('MAX_FILE_SIZE', 2000000 );   // 2MB in bytes  
defined('FEATURED_COUNT')           || define('FEATURED_COUNT', 6 );    
defined('UPLOAD_DIRECTORY')         || define('UPLOAD_DIRECTORY', WRITEPATH . 'uploads/' );  

defined('ADMIN_ACCESS')             || define('ADMIN_ACCESS', 'admin.access' );  
defined('EDITOR_ACCESS')            || define('EDITOR_ACCESS', 'admin.edit' );  
defined('VIEWER_ACCESS')            || define('VIEWER_ACCESS', 'admin.view' );  
defined('TRANSFER_OWNERSHIP')       || define('TRANSFER_OWNERSHIP', 'transfer.ownership' );  
defined('HELP_EMAIL')               || define('HELP_EMAIL', 'help@socceryou.ch' );  


defined('ALLOWED_FILE_EXTENTION')   || define('ALLOWED_FILE_EXTENTION', ['png', 'jpg', 'jpeg', 'gif', 'mp4'] );   
defined('ALLOWED_IMAGE_EXTENTION')  || define('ALLOWED_IMAGE_EXTENTION', ['png', 'jpg', 'jpeg', 'gif']);   
defined('ALLOWED_VEDIO_EXTENTION')  || define('ALLOWED_VEDIO_EXTENTION', ['mp4'] );   

defined('PLAYER_FOOT')              || define('PLAYER_FOOT', [
                                                            'l' => 'Left',
                                                            'R' => 'Right',
                                                            'b' => 'Both'
                                                        ]);

    
defined('LEAGUE_LEVELS')            || define('LEAGUE_LEVELS', [
                                                            1 => 'Challenge League 1',
                                                            2 => 'Challenge League 2',
                                                            3 => 'Challenge League 3'
                                                        ]);


defined('NATIONALITIES')            || define('NATIONALITIES', [
                                                            1 => 'Belgien',
                                                            2 => 'Dänemark',
                                                            3 => 'Deutschland',
                                                            4 => 'England',
                                                            5 => 'Frankreich',
                                                            6 => 'Italien',
                                                            7 => 'Portugal',
                                                            8 => 'Schweden',
                                                            9 => 'Schweiz',
                                                            10 => 'Spanien',
                                                            11 => 'Switzerland'
                                                        ]);   
                                                        
defined('HEIGHT_UNITS')             || define('HEIGHT_UNITS', [
                                                            'c' => 'cm',
                                                            'i' => 'Inches'
                                                        ]);                                                        


defined('WEIGHT_UNITS')             || define('WEIGHT_UNITS', [
                                                            'kg'    => 'kg',
                                                            'p'     => 'Pounds'
                                                        ]); 

defined('USER_STATUSES')            || define('USER_STATUSES', [
                                                            1     => 'Pending',
                                                            2     => 'Verified',
                                                            3     => 'Rejected'
                                                        ]);                                                         
                                                       
defined('SPEED_UNITS')              || define('SPEED_UNITS', [
                                                            'km'    => 'km',
                                                            'm'     => 'Miles'
                                                        ]);   
                                                        
defined('CURRENCY_UNITS')           || define('CURRENCY_UNITS', [
                                                            1    => '$',
                                                            2    => '£'
                                                        ]);  

defined('STATUSES')                 || define('STATUSES', [
                                                            1     => 'Active',
                                                            2     => 'Inactive'
                                                        ]);                                                          

defined('FREQUENCIES')              || define('FREQUENCIES', [
                                                            1     => 'Once a day',
                                                            2     => 'Once 2 Hrs',
                                                            3     => 'Once a week',
                                                            4     => 'Twice a day',
                                                            5     => 'Once a month',
                                                            6     => 'One time only'
                                                        ]);  

defined('POPUP_FOR')                || define('POPUP_FOR', [
                                                            1     => 'Talent-Paid',
                                                            2     => 'Talent-Free',
                                                            3     => 'Club-Paid'
                                                        ]);  

defined('DISCOUNT_TYPE')            || define('DISCOUNT_TYPE', [
                                                            1     => 'Fixed',
                                                            2     => 'Percentage'
                                                        ]);  

defined('COUPON_STATUSES')          || define('COUPON_STATUSES', [
                                                            1     => 'Draft',
                                                            2     => 'Published',
                                                            3     => 'Expired',
                                                        ]);          
                                                        
defined('BLOG_STATUSES')            || define('BLOG_STATUSES', [
                                                            1     => 'Draft',
                                                            2     => 'Published',   
                                                        ]);          

defined('ADVERTISEMENT_TYPES')      || define('ADVERTISEMENT_TYPES', [
                                                            'square'            => '250 x 250 - Square',
                                                            'small_square'      => '200 x 200 - Small Square',
                                                            'banner'            => '468 x 60 - Banner',
                                                            'leaderboard'       => '728 x 90 - Leaderboard',
                                                            'large_leaderboard' => '730 x 90 - Large Leaderboard',
                                                            'inline_rectangel'  => '300 x 250 - Inline Rectangel',
                                                            'large_rectangel'   => '336 x 280 - Large Rectangel',
                                                            'skyscraper'        => '120 x 600 - Skyscraper',
                                                            'wide_skyscraper'   => '160 x 600 - Wide Skyscraper',
                                                        ]);

defined('PREMIUM_PACKAGES')         || define('PREMIUM_PACKAGES', [1, 2]);
defined('BOOSTER_PACKAGES')         || define('BOOSTER_PACKAGES', [3, 4]);
defined('COUNTRY_PACKAGES')         || define('COUNTRY_PACKAGES', [5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24]);
defined('DEMO_PACKAGES')            || define('DEMO_PACKAGES', [25, 26]);

// defined('REPRESENTATORS_GROUP')     || define('REPRESENTATORS_GROUP', ['superadmin-representator', 'scout-representator', 'club-representator']);
defined('REPRESENTATORS_ROLES')     || define('REPRESENTATORS_ROLES', [5,6,7]);
defined('CLUB_SCOUT_PLAYER_ROLES')  || define('CLUB_SCOUT_PLAYER_ROLES', [2,3,4]);
defined('ADMIN_ROLES')              || define('ADMIN_ROLES', [1]);
defined('PLAYER_ROLE')              || define('PLAYER_ROLE', [4]);
defined('CLUB_ROLE')                || define('CLUB_ROLE', [2]);
defined('SCOUT_ROLE')               || define('SCOUT_ROLE', [3]);
