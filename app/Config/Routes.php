<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');


service('auth')->routes($routes);

// $routes->get('excel/import', 'ExcelController::importExcel');
// $routes->post('excel/upload', 'ExcelController::uploadExcel');
// $routes->get('/subscription', 'StripeController::index');
$routes->get('/success', 'StripeController::success');
$routes->get('/error', 'StripeController::error');
// $routes->post('/subscription/createSubscription', 'StripeController::createSubscription');
//$routes->post('/stripe-webhook', 'StripeController::handle');
$routes->get('/email-testing', 'StripeController::emailTesting');


// $routes->get('/subscription-form', 'StripeController::subscriptionForm');
// $routes->post('create-subscription', 'StripeController::createSubscription');
// $routes->post('stripe-webhook', 'StripeController::webhook');

$routes->get('/subscription-form', 'StripeController::subscriptionForm');
$routes->post('subscription/subscribe', 'StripeController::subscribe');

$routes->get('upgrade-subscription-form', 'StripeController::upgradeSubscriptionForm');
$routes->post('subscription/upgrade', 'StripeController::upgradeSubscription');

// $routes->post('stripe-webhook', 'Api\StripeController::handle');
$routes->get('subscription/cancel', 'StripeController::cancelForm');
$routes->post('subscription/cancel', 'StripeController::cancel');



$routes->get('generate-PDF', 'Home::generatePDF');
$routes->get('staticPdf', 'Home::staticPdf');
$routes->get('generate-InvoicePDF', 'Home::generateInvoicePDF');
// $routes->get('download-PDF', 'Home::downloadUserPdf');
$routes->get('load-email', 'Home::loadEmail');

$routes->get('frontend_test',       'Home::amritTest');




$routes->get('uploads/(:segment)', 'Api\AuthController::displayFile/$1');
$routes->get('uploads/logos/(:segment)', 'Api\AuthController::displayTeamLogo/$1');
$routes->get('uploads/documents/(:segment)', 'Api\AuthController::displayDocuments/$1');
$routes->get('uploads/pdf-icons/(:segment)', 'Api\AuthController::displayPDFIcons/$1');
$routes->get('get-positions-image',          'Home::getPositionImage');
$routes->get('uploads/exports/(:segment)', 'Api\AuthController::displayCsv/$1');
$routes->get('uploads/frontend/(:segment)', 'Api\AuthController::frontEnd/$1');

$routes->get('users-test',             'TestController::getUsersTesting');       /////////////////// testing ////////////
// $routes->get('users-test',             'TestController::getUsersTesting', ['filter' => 'loginAuth']);       /////////////////// testing ////////////



$routes->group('api', ['namespace' => 'App\Controllers\Api'], function ($routes) {


   header('Access-Control-Allow-Origin: *');
   header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Authorization, Access-Control-Request-Method");
   header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");

   // $method = $_SERVER['REQUEST_METHOD'] ?? '';
   $method = $_SERVER['REQUEST_METHOD'];
   if ($method == "OPTIONS") {
      die();
   }

   // $routes->post('stripe-webhook', 'StripeController::handle');
   $routes->post('stripe-webhook', 'StripeWebhookEventController::handle');
   // $routes->post('stripe-webhook', 'StripeController::handle_new');
   // $routes->get('process-stripe-webhook', 'StripeController::processPendingEventsLater');
   $routes->get('process-stripe-webhook', 'StripeWebhookEventController::processPendingEventsLater');

   $routes->post('create-stripe-customer', 'StripeController::createStripeCustomer', ['filter' => 'loginAuth']);


   //$routes->post('create-payment-intent', 'StripeController::createPaymentIntent');
   $routes->post('add-payment-method',    'StripeController::addPaymentMethod');
   $routes->get('export-user-csv',        'UserController::exportUsers');
   $routes->get('download-PDF',           'UserController::downloadUserPdf');
   $routes->get('export-single-user/(:num)',              'UserController::downloadUserPdf/$1');

   //$routes->post('add-email-template-test',                   'EmailTemplateController::addEmailTemplate');

   //$routes->get('users',                  'AuthController::getPlayers');  // route disabled temporary
   $routes->get('users', 'UserController::getUsers');
   $routes->get('users-frontend',         'UserController::getUsersOnFrontend');
   $routes->get('get-countries',          'CountryController::getCountries');
   $routes->get('get-leagues',            'LeagueController::getLeagues');
   $routes->get('get-positions',          'PositionController::getPositions');
   $routes->get('get-packages',           'PackageController::getPackages');
   $routes->get('get-packages/(:any)',    'PackageController::getPackages/$1');
   $routes->get('get-packages-by-domain/(:any)/(:any)',           'PackageController::getPackagesByDomain/$1/$2');
   $routes->get('get-packages-by-domain/(:any)/(:any)/(:any)',    'PackageController::getPackagesByDomain/$1/$2/$3');

   // test_by_amrit
   $routes->get('get-activity',               'UserController::getActivity', ['filter' => 'loginAuth']); //
   $routes->post('delete-activity',           'UserController::deleteActivity', ['filter' => 'loginAuth']);
   $routes->get('get-subscription',           'UserSubscriptionController::getSubscriptionPlans'); //
   $routes->post('common-profile-upload',     'UserController::UploadProfileImageCommon');
   //
   $routes->get('admin-access',           'AuthController::adminAccessDenied');
   $routes->get('player-access',          'AuthController::playerAccessDenied');
   $routes->get('club-access',            'AuthController::clubAccessDenied');
   $routes->get('scout-access',           'AuthController::scoutAccessDenied');
   $routes->get('invalid-access',         'AuthController::accessDenied');

   $routes->post('register',              'AuthController::register');
   $routes->post('login',                 'AuthController::login');

   $routes->get('get-domains',            'DomainController::getDomains');
   $routes->get('get-currencies',         'DomainController::getCurrencies');

   $routes->get('get-roles',              'RoleController::getRoles');
   $routes->get('get-languages',          'LanguageController::getLanguages');
   $routes->get('get-language/(:num)',    'LanguageController::getLanguage/$1');
   $routes->get('get-ads',                'AdvertisementController::frontendAds');




   $routes->get('delete-user/(:num)',     'AuthController::deleteUser/$1', ['filter' => 'loginAuth']);
   //$routes->post('delete-user',         'AuthController::deleteUser', ['filter' => 'loginAuth']);
   $routes->post('change-password',       'AuthController::changePassword', ['filter' => 'loginAuth']);
   $routes->post('reset-password',        'AuthController::resetPassword', ['filter' => 'loginAuth']);
   $routes->get('logout',                 'AuthController::logout', ['filter' => 'loginAuth']);




   $routes->get('verify-email/(:any)/(:any)',   'AuthController::verifyEmail/$1/$2');
   $routes->get('verify-club-email/(:any)',     'AuthController::verifyClubEmail/$1');     // not using
   $routes->post('forgot-password',             'AuthController::forgotPassword');
   $routes->get('magic-login/(:any)',           'AuthController::magicLogin/$1');

   $routes->get('validate-email-confirmation-token/(:any)/(:any)',         'AuthController::verifyEmail/$1/$2');
   $routes->get('validate-forgot-password-token/(:any)',                   'AuthController::magicLogin/$1');



   $routes->get('dashboard',              'AuthController::dashboard', ['filter' => 'loginAuth']);
   //$routes->get('profile',                'AuthController::profile', ['filter' => 'loginAuth']);
   $routes->get('profile',                'UserController::userProfile', ['filter' => 'loginAuth']);
   $routes->get('search',                 'UserController::searchBar', ['filter' => 'loginAuth']);


   $routes->post('add-favorite',          'FavoriteController::addFavorite', ['filter' => 'loginAuth']);
   $routes->get('get-favorites',          'FavoriteController::getFavorites', ['filter' => 'loginAuth']);
   $routes->get('get-favorites-profile',  'FavoriteController::getFavoritesWithProfile'); // test_by_amrit

   $routes->post('delete-favorites',      'FavoriteController::deleteFavorites', ['filter' => 'loginAuth']);

   $routes->get('get-clubs-list',         'ClubController::getClubs');
   // $routes->get('get-clubs-list',         'ClubController::getClubs', ['filter' => 'loginAuth']);
   $routes->get('get-teams',              'TeamController::getTeams');
   $routes->get('get-club-teams/(:num)',  'TeamController::getTeams/$1', ['filter' => 'loginAuth']);
   $routes->post('update-user-language',  'AuthController::updateUserLanguage', ['filter' => 'loginAuth']);
   $routes->post('delete-csv-file',       'AuthController::deleteCSVFile', ['filter' => 'loginAuth']);

   $routes->get('users-frontend-with-login',          'UserController::getUsersOnFrontend', ['filter' => 'loginAuth']);

   $routes->get('get-representator-roles',            'RepresentatorRoleController::getRepresentatorRoles', ['filter' => 'loginAuth']);
   $routes->post('create-payment-intent/(:num)',      'StripeController::createPaymentIntent/$1', ['filter' => 'loginAuth']);



   //$routes->post('update-profile', 'AuthController::updateProfile', ['filter' => 'loginAuth']);
   //$routes->post('players', 'AuthController::getPlayers', ['filter' => 'loginAuth']);

   // google sign up
   $routes->get('google-login',           'AuthController::googleLogin'); // Google sign-in link
   $routes->get('google-callback',        'AuthController::googleCallback'); // Google callback URL

   $routes->post('google-signin',         'AuthController::googleSignIn'); // Google sign-in link

   //ADMIN Routes
   //$routes->get('admin/users',                        'UserController::getUsers');

   // APIs to get player details
   $routes->get('get-transfer-detail/(:num)',            'TeamTransferController::getTeamTransfer/$1');
   $routes->get('get-performance-reports/(:num)',        'PerformanceReportController::getPerformanceReports/$1');
   $routes->get('get-performance-detail/(:num)',         'PerformanceDetailController::getPerformanceDetail/$1');
   $routes->get('get-gallery-highlights/(:num)',         'GalleryController::getGalleryHighlights/$1');
   $routes->get('get-gallery/(:num)',                    'GalleryController::getGallery/$1');
   $routes->post('track-advertisement',                  'AdvertisementController::trackAdvertisement', ['filter' => 'loginAuth']);
   $routes->get('user-profile/(:num)',                   'UserController::userProfile/$1');



   $routes->group('admin', ['filter' => ['loginAuth', 'adminAuth']], function ($routes) {
      $routes->get('users',                                 'UserController::getUsers');
      $routes->get('users/(:num)',                          'UserController::getUsers/$1');
      $routes->get('export-users',                          'UserController::downloadCsv');
      $routes->get('profile/(:num)',                        'UserController::userProfile/$1');
      $routes->post('update-user-status',                   'UserController::updateUserStatus');
      $routes->post('delete-user',                          'UserController::deleteUser');
      $routes->post('change-password/(:num)',               'AuthController::changePassword/$1');


      // $routes->post('update-profile/(:num)',                'UserController::updateProfileAdmin/$1');
      $routes->post('update-profile/(:num)',                'UserController::updateProfile/$1');
      $routes->post('update-general-info/(:num)',           'UserController::updateGeneralInfoAdmin/$1');
      $routes->post('update-market-value/(:num)',           'UserController::playerMarketValueAdmin/$1');

      $routes->get('get-gallery/(:num)',                    'GalleryController::getGallery/$1');
      $routes->get('get-gallery-highlights/(:num)',         'GalleryController::getGalleryHighlights/$1');
      $routes->post('upload-gallery-image/(:num)',          'GalleryController::uploadGalleryImageAdmin/$1');
      $routes->post('delete-gallery-file',                  'GalleryController::deleteGalleryFileAdmin');

      $routes->post('upload-profile-image/(:num)',          'UserController::uploadProfileImageAdmin/$1');
      $routes->post('upload-cover-image/(:num)',            'UserController::uploadCoverImageAdmin/$1');

      $routes->get('get-profile-image/(:num)',              'UserController::getProfileImageAdmin/$1');
      $routes->get('get-cover-image/(:num)',                'UserController::getCoverImageAdmin/$1');

      $routes->get('delete-profile-image/(:num)',           'UserController::deleteProfileImageAdmin/$1');
      $routes->get('delete-cover-image/(:num)',             'UserController::deleteCoverImageAdmin/$1');


      $routes->get('get-performance-detail/(:num)',         'PerformanceDetailController::getPerformanceDetail/$1');
      $routes->post('edit-performance-detail/(:num)',       'PerformanceDetailController::editPerformanceDetailAdmin/$1');

      $routes->get('get-transfer-detail/(:num)',            'TeamTransferController::adminGetTeamTransfers/$1');
      $routes->post('edit-transfer-detail/(:num)',          'TeamTransferController::editTeamTransferAdmin/$1');

      $routes->get('get-performance-reports/(:num)',        'PerformanceReportController::getPerformanceReports/$1');


      // CLUB
      $routes->post('add-club-history/(:num)',              'UserController::addClubHistory/$1');
      $routes->get('get-club-history/(:num)',               'UserController::getClubHistory/$1');
      $routes->post('edit-club-history/(:num)',             'UserController::updateClubHistoryAdmin/$1');

      $routes->get('get-club-players/(:num)',               'ClubPlayerController::getClubPlayersAdmin/$1');
      $routes->post('add-club-player/(:num)',               'ClubPlayerController::addClubPlayerAdmin/$1');
      $routes->post('edit-club-player/(:num)',              'ClubPlayerController::EditClubPlayerAdmin/$1');

      // $routes->post('add-sighting/(:num)',                  'SightingController::addSightingAdmin/$1');
      $routes->post('add-sighting/(:num)',                  'SightingController::addSighting/$1');
      $routes->post('delete-sighting',                      'SightingController::deleteSightingAdmin');
      // $routes->get('get-sightings/(:num)',                  'SightingController::getSightingsAdmin/$1');
      $routes->get('get-sightings/(:num)',                  'SightingController::getSightings/$1');
      $routes->get('get-sighting/(:num)',                   'SightingController::getSighting/$1');
      $routes->post('edit-sighting-cover/(:num)',           'SightingController::editSightingCover/$1');
      $routes->post('edit-sighting-detail/(:num)',          'SightingController::editSightingDetail/$1');
      $routes->post('edit-sighting-about/(:num)',           'SightingController::editSightingAbout/$1');
      $routes->post('add-sighting-attachments/(:num)',      'SightingController::addSightingAttachments/$1');
      $routes->get('delete-sighting-attachment/(:num)',     'SightingController::deleteSightingAttachment/$1');
      $routes->post('add-sighting-invites/(:num)',          'SightingController::addSightingInvites/$1');
      $routes->get('delete-sighting-invite/(:num)',         'SightingController::deleteSightingInvite/$1');
      $routes->get('delete-sighting-cover/(:num)',          'SightingController::deleteSightingCover/$1');


      // SCOUT
      $routes->get('get-company-history/(:num)',            'UserController::getCompanyHistory/$1');
      $routes->post('add-company-history/(:num)',           'UserController::addCompanyHistory/$1');
      $routes->post('edit-company-history/(:num)',          'UserController::editCompanyHistory/$1');

      $routes->post('add-scout-player/(:num)',              'ScoutPlayerController::addScoutPlayer/$1');
      $routes->get('delete-scout-player/(:num)',            'ScoutPlayerController::deleteScoutPlayer/$1');
      $routes->get('get-scout-players/(:num)',              'ScoutPlayerController::getScoutPlayers/$1');

      // Favorites
      $routes->get('get-favorites/(:num)',                  'FavoriteController::getFavorites/$1');
      $routes->post('delete-favorites',                     'FavoriteController::deleteFavorites');

      // System Popups
      $routes->post('add-system-popup',                     'SystemPopUpController::addSystemPopUp');
      $routes->get('get-system-popups',                     'SystemPopUpController::getSystemPopUps');
      $routes->get('get-system-popup/(:num)',               'SystemPopUpController::getSystemPopUp/$1');
      $routes->post('edit-system-popup/(:num)',             'SystemPopUpController::editSystemPopUp/$1');
      $routes->post('delete-system-popup',                  'SystemPopUpController::deleteSystemPopUp');

      // Email Template
      $routes->post('add-email-template',                   'EmailTemplateController::addEmailTemplate');
      $routes->post('edit-email-template/(:num)',           'EmailTemplateController::editEmailTemplate/$1');
      $routes->get('get-email-templates',                   'EmailTemplateController::getEmailTemplates');
      $routes->get('get-email-template/(:num)',             'EmailTemplateController::getEmailTemplate/$1');
      $routes->post('delete-email-template',                'EmailTemplateController::deleteEmailTemplate');

      // Coupon
      $routes->post('add-coupon',                           'CouponController::addCoupon');
      $routes->post('edit-coupon/(:num)',                   'CouponController::editCoupon/$1');
      $routes->get('get-coupons',                           'CouponController::getCoupons');
      $routes->get('get-coupon/(:num)',                     'CouponController::getCoupon/$1');
      $routes->post('delete-coupon',                        'CouponController::deleteCoupon');
      $routes->post('publish-coupon',                       'CouponController::publishCoupon');
      $routes->post('draft-coupon',                         'CouponController::draftCoupon');
      $routes->post('expire-coupon',                        'CouponController::expireCoupon');

      // addBlog
      $routes->post('add-blog',                             'BlogController::addBlog');
      $routes->post('edit-blog/(:num)',                     'BlogController::editBlog/$1');
      $routes->get('get-blogs',                             'BlogController::getBlogs');
      // $routes->get('get-blog/(:any)',                       'BlogController::getBlog/$1');
      $routes->get('get-blog',                              'BlogController::getBlog');
      $routes->post('delete-blog',                          'BlogController::deleteBlog');
      $routes->post('publish-blog',                         'BlogController::publishBlog');
      $routes->post('draft-blog',                           'BlogController::draftBlog');

      // Advertisement
      $routes->post('add-advertisement',                    'AdvertisementController::addAdvertisement');
      $routes->post('edit-advertisement/(:num)',            'AdvertisementController::editAdvertisement/$1');
      $routes->get('get-advertisements',                    'AdvertisementController::getAdvertisements');
      $routes->get('get-advertisement/(:num)',              'AdvertisementController::getAdvertisement/$1');
      $routes->post('delete-advertisement',                 'AdvertisementController::deleteAdvertisement');
      $routes->post('publish-advertisement',                'AdvertisementController::publishAdvertisement');
      $routes->post('draft-advertisement',                  'AdvertisementController::draftAdvertisement');
      $routes->post('expire-advertisement',                 'AdvertisementController::expireAdvertisement');

      // Pages
      $routes->post('add-page',                             'PageController::addPage');
      $routes->post('edit-page/(:num)',                     'PageController::editPage/$1');
      $routes->get('get-pages',                             'PageController::getPages');
      $routes->get('get-page/(:num)',                       'PageController::getPage/$1');
      $routes->post('delete-page',                          'PageController::deletePage');
      $routes->post('publish-page',                         'PageController::publishPage');
      $routes->post('draft-page',                           'PageController::draftPage');
      $routes->get('get-pagecontent/(:num)',                'PageMetaController::getFullPageById/$1',['namespace' => 'App\Controllers\Frontend']);

      // SETTINGS
      $routes->post('settings/profile',                     'UserController::updateProfile');
      $routes->post('settings/upload-profile-image',        'UserController::uploadProfileImage');

      // statistics data
      $routes->get('get-users-count-monthly/(:num)/(:num)',          'UserController::getUsersCountMonthly/$1/$2');
      $routes->get('get-subscriptions-count-monthly/(:num)/(:num)',  'UserController::getSubscriptionsCountMonthly/$1/$2');
      $routes->get('get-sales-count-monthly/(:num)/(:num)',          'UserController::getSalesCountMonthly/$1/$2');

      $routes->get('get-users-count-yearly/(:num)',                  'UserController::getUsersCountYearly/$1');
      $routes->get('get-users-count-yearly/(:num)/(:num)',           'UserController::getUsersCountYearly/$1/$2');               // by domain
      $routes->get('get-subscriptions-count-yearly/(:num)',          'UserController::getSubscriptionsCountYearly/$1');
      $routes->get('get-subscriptions-count-yearly/(:num)/(:num)',   'UserController::getSubscriptionsCountYearly/$1/$2');       // by domain
      $routes->get('get-sales-count-yearly/(:num)',                  'UserController::getSalesCountYearly/$1');
      $routes->get('get-sales-count-yearly/(:num)/(:num)',           'UserController::getSalesCountYearly/$1/$2');               // by domain
      $routes->get('get-graph-data/(:num)',                          'UserController::getGraphDataYearly/$1');
      $routes->get('get-graph-data/(:num)/(:num)',                   'UserController::getGraphDataYearly/$1/$2');                // by domain
      $routes->get('get-graph-data/(:num)/(:num)/(:num)',            'UserController::getGraphDataYearly/$1/$2/$3');             // by domain and language



      $routes->get('get-role-payment-types',                         'RolePaymentTypeController::getRolePaymentTypes');
      $routes->get('get-purchase-history/(:num)',                    'UserSubscriptionController::getUserPurchaseHistory/$1');
      $routes->get('get-transactions',                               'UserSubscriptionController::getTransactions');
      $routes->post('verify-club-application/(:num)',                'AuthController::verifyClubApplication/$1');    // not using
      $routes->get('get-payment-methods/(:num)',                     'PaymentMethodController::getPaymentMethods/$1');
      $routes->get('export-purchase-history/(:num)',                 'UserSubscriptionController::getUserPurchaseHistoryExport/$1');


      $routes->post('add-club',                                      'AuthController::addClub');   // not using

      //Representator
      $routes->post('add-representator',                             'UserController::addRepresentator');      // to add representator in admin profile
      $routes->get('get-representators',                             'UserController::getRepresentators');

      $routes->post('add-representator/(:num)',                      'UserController::addRepresentator/$1');   // pass club or scout ID in param
      $routes->get('get-representators/(:num)',                      'UserController::getRepresentators/$1');   // pass club or scout ID in param
      $routes->post('update-representator-role/(:num)',              'UserController::updateRepresentatorRole/$1');
      $routes->get('delete-representator/(:num)',                    'UserController::deleteRepresentator/$1');

      // test_by_amrit
      $routes->post('upload-profile-photo/(:num)',                   'UserController::UploadProfilePhoto/$1');    // not using

      $routes->post('export-favorites/(:num)',                        'FavoriteController::exportFavorites/$1');
      $routes->get('export-performance-detail/(:num)',               'PerformanceDetailController::exportPerformanceDetailAdmin/$1');
      $routes->get('export-transfer-detail/(:num)',                  'TeamTransferController::exportTeamTransferAdmin/$1');
      $routes->get('export-scout-players/(:num)',                    'ScoutPlayerController::exportScoutPlayers/$1');
      $routes->post('export-sightings/(:num)',                       'SightingController::exportSightingsAdmin/$1');
      $routes->get('export-club-players/(:num)',                     'ClubPlayerController::exportClubPlayersAdmin/$1');
      $routes->get('get-page-ads/(:num)',                            'PageController::getPageAds/$1');
   });

   // Players Routes
   $routes->group('player', ['filter' => ['loginAuth', 'playerAuth']], function ($routes) {
      //$routes->post('update-profile', 'AuthController::updateProfile', ['filter' => 'loginAuth']);
      //$routes->post('update-profile',              'UserController::updateProfile');
      $routes->post('update-general-info',         'UserController::updateGeneralInfo');

      /* $routes->post('upload-profile-image',     'UserController::uploadProfileImage');
      $routes->post('upload-cover-image',          'UserController::uploadCoverImage');

      $routes->get('get-profile-image',            'UserController::getProfileImage');
      $routes->get('get-cover-image',              'UserController::getCoverImage');

      $routes->get('delete-profile-image',         'UserController::deleteProfileImage');
      $routes->get('delete-cover-image',           'UserController::deleteCoverImage');

      $routes->post('upload-gallery-image',        'GalleryController::uploadGalleryImage');
      $routes->get('get-gallery',                  'GalleryController::getGallery');
      $routes->post('delete-gallery-file',         'GalleryController::deleteGalleryFile');
      $routes->post('set-featured-file',           'GalleryController::SetFeaturedFile');
      $routes->get('unset-featured-file/(:num)',   'GalleryController::UnSetFeaturedFile/$1'); */

      $routes->post('upload-performance-report',         'PerformanceReportController::addPerformanceReport');
      $routes->post('delete-performance-report',         'PerformanceReportController::deletePerformanceReport');
      $routes->get('get-performance-reports',            'PerformanceReportController::getPerformanceReports');

      $routes->post('add-transfer-detail',               'TeamTransferController::addTeamTransfer');
      $routes->get('get-transfer-detail',                'TeamTransferController::getTeamTransfer');
      $routes->post('edit-transfer-detail/(:num)',       'TeamTransferController::editTeamTransfer/$1');
      $routes->get('delete-transfer-detail/(:num)',      'TeamTransferController::deleteTeamTransfer/$1');

      $routes->post('add-performance-detail',            'PerformanceDetailController::addPerformanceDetail');
      $routes->get('get-performance-detail',             'PerformanceDetailController::getPerformanceDetail');
      $routes->post('edit-performance-detail/(:num)',    'PerformanceDetailController::editPerformanceDetail/$1');
      $routes->get('delete-performance-detail/(:num)',   'PerformanceDetailController::deletePerformanceDetail/$1');

      $routes->post('download-performance-reports',      'PerformanceReportController::downloadPerformanceReports');
      $routes->post('update-scout-request/(:num)',       'ScoutPlayerController::updateScoutRequest/$1');
      $routes->post('update-sighting-invite-response/(:num)',                  'SightingController::updateSightingInviteResponse/$1');
   });

   // multiRoleAuth ('player', 'club', 'scout')
   $routes->group('user', ['filter' => ['loginAuth']], function ($routes) {
      $routes->get('profile/(:num)',                     'UserController::userProfile/$1');
      $routes->post('update-profile',                    'UserController::updateProfile');

      $routes->post('upload-profile-image',              'UserController::uploadProfileImage');
      $routes->post('upload-cover-image',                'UserController::uploadCoverImage');

      $routes->get('get-profile-image',                  'UserController::getProfileImage');
      $routes->get('get-cover-image',                    'UserController::getCoverImage');

      $routes->get('delete-profile-image',               'UserController::deleteProfileImage');
      $routes->get('delete-cover-image',                 'UserController::deleteCoverImage');

      $routes->post('upload-gallery-image',              'GalleryController::uploadGalleryImage');
      $routes->get('get-gallery',                        'GalleryController::getGallery');
      $routes->get('get-gallery-highlights',             'GalleryController::getGalleryHighlights');

      $routes->post('delete-gallery-file',               'GalleryController::deleteGalleryFile');
      $routes->post('set-featured-file',                 'GalleryController::SetFeaturedFile');
      $routes->get('unset-featured-file/(:num)',         'GalleryController::UnSetFeaturedFile/$1');
      $routes->get('get-purchase-history',               'UserSubscriptionController::getUserPurchaseHistory');
      $routes->get('get-active-packages',                'UserSubscriptionController::getActivePackages');
      $routes->post('upgrade-subscription',              'StripeController::upgradeSubscription');
      $routes->post('cancel-subscription',               'StripeController::cancelSubscription');

      // $routes->get('upgrade-subscription/(:num)/(:num)', 'StripeController::upgradeSubscription/$1/$2');


      $routes->post('delete-nationality/(:num)',         'UserNationalityController::deleteUserNationality/$1');
      $routes->get('get-payment-methods',                'PaymentMethodController::getPaymentMethods');

      $routes->get('export-purchase-history',            'UserSubscriptionController::getUserPurchaseHistoryExport');

      $routes->post('settings/newsletter',               'UserController::updateNewsletterStatus');
      // $routes->get('settings/newsletter',                 'UserController::getNewsletterStatus');

      $routes->get('get-packages',                       'PackageController::getPackages');
      $routes->get('get-packages-new',                   'PackageController::getPackagesNew');
      $routes->get('delete-my-account',                  'UserController::deleteMyAccount');
      $routes->get('get-active-domains',                 'PackageController::getActiveDomains');
      $routes->post('track-booster-profile',             'BoosterStatisticController::addBoosterAction');
      $routes->get('get-booster-stats',                  'BoosterStatisticController::getBoosterAction');
      $routes->post('update-booster-audience',           'BoosterAudiencController::updateBoosterAudience');
      $routes->post('validate-coupon',                   'StripeController::validateCoupon');
   });

   // clubAuth
   $routes->group('club', ['filter' => ['loginAuth', 'clubAuth']], function ($routes) {

      $routes->get('get-club-history',                   'UserController::getClubHistory');
      $routes->post('add-club-history',                  'UserController::addClubHistory');
      $routes->post('edit-club-history',                 'UserController::updateClubHistory');

      $routes->post('add-club-player',                   'ClubPlayerController::addClubPlayer');
      $routes->get('get-club-players/(:num)',            'ClubPlayerController::getClubPlayers/$1');
      $routes->post('edit-club-player/(:num)',           'ClubPlayerController::EditClubPlayer/$1');
      $routes->get('delete-club-player/(:num)',          'ClubPlayerController::deleteClubPlayer/$1');

      $routes->post('add-sighting',                      'SightingController::addSighting');
      $routes->post('delete-sighting',                   'SightingController::deleteSighting');
      $routes->get('get-sightings',                      'SightingController::getSightings');
      $routes->get('get-sighting/(:num)',                'SightingController::getSighting/$1');
      $routes->post('edit-sighting-cover/(:num)',        'SightingController::editSightingCover/$1');
      $routes->post('edit-sighting-detail/(:num)',       'SightingController::editSightingDetail/$1');
      $routes->post('edit-sighting-about/(:num)',        'SightingController::editSightingAbout/$1');
      $routes->post('add-sighting-attachments/(:num)',   'SightingController::addSightingAttachments/$1');
      $routes->get('delete-sighting-attachment/(:num)',  'SightingController::deleteSightingAttachment/$1');
      $routes->post('add-sighting-invites/(:num)',       'SightingController::addSightingInvites/$1');
      $routes->get('delete-sighting-invite/(:num)',      'SightingController::deleteSightingInvite/$1');
      $routes->get('delete-sighting-cover/(:num)',       'SightingController::deleteSightingCover/$1');

      $routes->post('add-representator',                 'UserController::addRepresentator');
      $routes->get('get-representators',                 'UserController::getRepresentators');
      $routes->post('update-representator-role/(:num)',  'UserController::updateRepresentatorRole/$1');
      $routes->get('delete-representator/(:num)',        'UserController::deleteRepresentator/$1');
   });

   // scoutAuth
   $routes->group('scout', ['filter' => ['loginAuth', 'scoutAuth']], function ($routes) {

      $routes->get('get-company-history',                'UserController::getCompanyHistory');
      $routes->post('edit-company-history',              'UserController::editCompanyHistory');

      // $routes->post('add-scout-player',                  'ClubPlayerController::addScoutPlayer');
      // $routes->get('get-scout-players',                  'ClubPlayerController::getScoutPlayers');
      // $routes->get('delete-scout-player/(:num)',         'ClubPlayerController::deleteScoutPlayer/$1');

      $routes->post('add-scout-player',                  'ScoutPlayerController::addScoutPlayer');
      $routes->get('delete-scout-player/(:num)',         'ScoutPlayerController::deleteScoutPlayer/$1');
      $routes->get('get-scout-players',                  'ScoutPlayerController::getScoutPlayers');

      //representator
      $routes->post('add-representator',                 'UserController::addRepresentator');
      $routes->get('get-representators',                 'UserController::getRepresentators');
      $routes->post('update-representator-role/(:num)',  'UserController::updateRepresentatorRole/$1');
      $routes->get('delete-representator/(:num)',        'UserController::deleteRepresentator/$1');
   });

   // RepresentatorAuth
   /* $routes->group('representator', ['filter' => ['loginAuth', 'representatorAuth']], function ($routes) {
      $routes->post('update-profile',                       'UserController::updateProfile');
      $routes->post('upload-profile-image',                 'UserController::uploadProfileImage');
      //$routes->get('get-club-history/(:num)',               'UserController::getClubHistoryAdmin/$1');

      // $routes->group('scout',  function ($routes) {
      $routes->group('admin', ['filter' => 'repAdminAuth'], function ($routes) {
         $routes->get('get-company-history',                'UserController::getCompanyHistory');
         $routes->post('add-company-history',               'UserController::addCompanyHistory');
         $routes->post('edit-company-history',              'UserController::editCompanyHistory');
         $routes->get('get-scout-players',                  'ScoutPlayerController::getScoutPlayers');

         $routes->get('get-profile-image',                  'UserController::getProfileImage');
         $routes->get('get-cover-image',                    'UserController::getCoverImage');
         $routes->get('get-gallery',                        'GalleryController::getGallery');
         $routes->get('get-gallery-highlights',             'GalleryController::getGalleryHighlights');
         $routes->get('get-purchase-history',               'UserSubscriptionController::getUserPurchaseHistory');
         $routes->get('get-favorites',                      'FavoriteController::getFavorites');


         $routes->post('upload-profile-image',              'UserController::uploadProfileImage');
         $routes->post('upload-cover-image',                'UserController::uploadCoverImage');
         $routes->post('upload-gallery-image',              'GalleryController::uploadGalleryImage');

         $routes->post('set-featured-file',                 'GalleryController::SetFeaturedFile');

         $routes->get('delete-profile-image',               'UserController::deleteProfileImage');
         $routes->get('delete-cover-image',                 'UserController::deleteCoverImage');
         $routes->post('delete-gallery-file',               'GalleryController::deleteGalleryFile');

         //club
         $routes->get('get-club-history',                   'UserController::getClubHistory');
         $routes->post('add-club-history',                  'UserController::addClubHistory');

         $routes->post('edit-club-history',                 'UserController::updateClubHistory');

         $routes->post('add-club-player',                   'ClubPlayerController::addClubPlayer');
         $routes->get('get-club-players/(:num)',            'ClubPlayerController::getClubPlayers/$1');  // pass team_id in param
         $routes->post('edit-club-player/(:num)',           'ClubPlayerController::EditClubPlayer/$1');
         $routes->get('delete-club-player/(:num)',          'ClubPlayerController::deleteClubPlayer/$1');

         $routes->get('get-sightings',                      'SightingController::getSightings');

         $routes->post('add-sighting',                      'SightingController::addSighting');
         $routes->post('delete-sighting',                   'SightingController::deleteSighting');
         $routes->get('get-sighting/(:num)',                'SightingController::getSighting/$1');
         $routes->post('edit-sighting-cover/(:num)',        'SightingController::editSightingCover/$1');
         $routes->post('edit-sighting-detail/(:num)',       'SightingController::editSightingDetail/$1');
         $routes->post('edit-sighting-about/(:num)',        'SightingController::editSightingAbout/$1');
         $routes->post('add-sighting-attachments/(:num)',   'SightingController::addSightingAttachments/$1');
         $routes->get('delete-sighting-attachment/(:num)',  'SightingController::deleteSightingAttachment/$1');
         $routes->post('add-sighting-invites/(:num)',       'SightingController::addSightingInvites/$1');
         $routes->get('delete-sighting-invite/(:num)',      'SightingController::deleteSightingInvite/$1');
         $routes->get('delete-sighting-cover/(:num)',       'SightingController::deleteSightingCover/$1');
      });

      $routes->group('editor', ['filter' => 'repEditorAuth'], function ($routes) {
         $routes->get('get-company-history',                'UserController::getCompanyHistory');
         $routes->post('edit-company-history',              'UserController::editCompanyHistory');

         $routes->get('get-scout-players',                  'ScoutPlayerController::getScoutPlayers');

         $routes->get('get-profile-image',                  'UserController::getProfileImage');
         $routes->get('get-cover-image',                    'UserController::getCoverImage');
         $routes->get('get-gallery',                        'GalleryController::getGallery');
         $routes->get('get-gallery-highlights',             'GalleryController::getGalleryHighlights');
         $routes->get('get-purchase-history',               'UserSubscriptionController::getUserPurchaseHistory');
         $routes->get('get-favorites',                      'FavoriteController::getFavorites');


         $routes->post('upload-profile-image',              'UserController::uploadProfileImage');
         $routes->post('upload-cover-image',                'UserController::uploadCoverImage');
         $routes->post('upload-gallery-image',              'GalleryController::uploadGalleryImage');

         $routes->post('set-featured-file',                 'GalleryController::SetFeaturedFile');

         //club
         $routes->get('get-club-history',                   'UserController::getClubHistory');
         $routes->post('edit-club-history',                 'UserController::updateClubHistory');
         $routes->get('get-club-players/(:num)',            'ClubPlayerController::getClubPlayers/$1');  // pass team_id in param
         $routes->post('edit-club-player/(:num)',           'ClubPlayerController::EditClubPlayer/$1');

         $routes->get('get-sightings',                      'SightingController::getSightings');
         $routes->post('edit-sighting-cover/(:num)',        'SightingController::editSightingCover/$1');
         $routes->post('edit-sighting-detail/(:num)',       'SightingController::editSightingDetail/$1');
         $routes->post('edit-sighting-about/(:num)',        'SightingController::editSightingAbout/$1');
      });

      $routes->group('viewer', ['filter' => 'repViewerAuth'], function ($routes) {
         $routes->get('get-company-history',                'UserController::getCompanyHistory');
         $routes->get('get-scout-players',                  'ScoutPlayerController::getScoutPlayers');
         $routes->get('get-profile-image',                  'UserController::getProfileImage');
         $routes->get('get-cover-image',                    'UserController::getCoverImage');

         $routes->get('get-gallery',                        'GalleryController::getGallery');
         $routes->get('get-gallery-highlights',             'GalleryController::getGalleryHighlights');
         $routes->get('get-purchase-history',               'UserSubscriptionController::getUserPurchaseHistory');
         $routes->get('get-favorites',                      'FavoriteController::getFavorites');


         //club
         $routes->get('get-club-history',                   'UserController::getClubHistory');
         $routes->get('get-club-players/(:num)',            'ClubPlayerController::getClubPlayers/$1');  // pass team_id in param

         $routes->get('get-sightings',                      'SightingController::getSightings');
      });
      // });

   }); */


   // scoutAuth By Neeraj
   $routes->group('scout', ['filter' => ['loginAuth', 'scoutAuth']], function ($routes) {
      $routes->post('add-company-history',               'UserController::addCompanyHistory');
   });
});

$routes->group('frontend', ['namespace' => 'App\Controllers\Frontend'], function ($routes) {

   header('Access-Control-Allow-Origin: *');
   header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Authorization, Access-Control-Request-Method");
   header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");

   // $method = $_SERVER['REQUEST_METHOD'] ?? '';
   // $method = $_SERVER['REQUEST_METHOD'];
   // if ($method == "OPTIONS") {
   //    die();
   // }
   ##### New Routes #####
   $routes->post('test-save', 'PageMetaController::testSaveData',['filter' => 'loginAuth']);
   $routes->get('test-get', 'PageMetaController::testGetData',['filter' => 'loginAuth']);
   $routes->post('save-homepage', 'PageMetaController::homePageMetaData',['filter' => 'loginAuth']);
   $routes->post('save-player-list', 'PageMetaController::playerList',['filter' => 'loginAuth']);
   $routes->post('save-talentpage', 'PageMetaController::talentPageMetaData',['filter' => 'loginAuth']);
   $routes->post('save-club-and-scout-page', 'PageMetaController::clubsAndScoutsMetaData',['filter' => 'loginAuth']);
   $routes->post('save-contactpage', 'PageMetaController::contactPageMetaData',['filter' => 'loginAuth']);
   $routes->post('save-newspage', 'PageMetaController::newsAndMediaMetaData',['filter' => 'loginAuth']);
   $routes->post('save-tabs-homepage', 'PageMetaController::homePageTabsMetaData');
   // $routes->get('get-homepage-data', 'PageMetaController::getPageMetaData'); # comment on 10-1-2025
   $routes->post('save-aboutpage', 'PageMetaController::aboutPageMetaData',['filter' => 'loginAuth']);
   // $routes->get('get-aboutpage', 'PageMetaController::getAboutPage');  # comment on 10-1-2025
   $routes->get('get-page-by-id', 'PageMetaController::getPageByID');
   // $routes->get('get-page-by-slug', 'PageMetaController::getPageBySlug');
   $routes->get('get-page-by-slug', 'PageMetaController::getPageByType');
   $routes->post('save-pricingpage', 'PageMetaController::savePricePageData',['filter' => 'loginAuth']);
   $routes->post('save-faqpage', 'PageMetaController::saveFaqPageData',['filter' => 'loginAuth']);
   $routes->post('save-content-page', 'PageMetaController::addContentPage',['filter' => 'loginAuth']);
   $routes->post('save-contact-form', 'PageMetaController::contactFormSubmit');
   // $routes->get('get-frontend-pages/(:num)', 'PageMetaController::getFrontendData/$1');
   $routes->get('get-frontend-pages',       'PageMetaController::getFrontendPages');
   $routes->get('get-single-news/(:num)',       'PageMetaController::getSingleNews/$1');

   ##### End New Routes #####
   // $routes->post('save-homepage', 'PageMetaController::homePageMetaData');
   // $routes->post('save-talentpage', 'PageMetaController::talentPageMetaData');
   // $routes->post('save-club-and-scout-page', 'PageMetaController::clubsAndScoutsMetaData');
   // $routes->post('save-contactpage', 'PageMetaController::contactPageMetaData');
   // $routes->post('save-newspage', 'PageMetaController::newsAndMediaMetaData');
   // $routes->post('save-tabs-homepage', 'PageMetaController::homePageTabsMetaData');
   // $routes->get('get-homepage-data', 'PageMetaController::getPageMetaData');
   // $routes->post('save-aboutpage', 'AboutController::aboutPageMetaData');
   // $routes->get('get-aboutpage', 'AboutController::getAboutPage');
   // $routes->get('get-page-by-id', 'AboutController::getPageByID');
   // $routes->post('save-pricingpage', 'AboutController::savePricePageData'); // function is in About controller
   // $routes->post('save-faqpage', 'AboutController::saveFaqPageData'); // function is in About controller
   // $routes->post('save-content-page', 'ContentPageController::addContentPage'); // function is in About controller
});
