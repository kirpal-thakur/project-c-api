<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\Shield\Models\UserModel;
use CodeIgniter\Shield\Models\UserIdentityModel;
use CodeIgniter\Shield\Entities\User;
use App\Models\UserMetaDataModel;
use App\Models\UserNationalityModel;
use CodeIgniter\Files\File;
use App\Models\ClubPlayerModel;
use App\Models\PlayerPositionModel;
use App\Models\ActivityModel;
use App\Models\ClubModel;
use App\Models\OwnershipTransferDataModel;
use App\Models\FavoriteModel;
use App\Models\TeamTransferModel;
use App\Models\PerformanceDetailModel;
use App\Models\GalleryModel;
use App\Models\ScoutPlayerModel;
use App\Models\TeamModel;
use App\Models\EmailTemplateModel;
use App\Models\CountryModel;


use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use DateTime;
use Mpdf;

class UserController extends ResourceController
{
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);
        $this->db = \Config\Database::connect();
        $this->userNationalityModel = new UserNationalityModel();
        $this->clubPlayerModel = new ClubPlayerModel();
        $this->playerPositionModel = new PlayerPositionModel();
        $this->clubModel = new ClubModel();
        $this->userIdentityModel = new UserIdentityModel();
        $this->ownershipTransferDataModel = new OwnershipTransferDataModel();
        $this->favoriteModel = new FavoriteModel();
    }

    public function updateProfile($id = null)
    {
        $userMetaObject = new UserMetaDataModel();
        $response = [];

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $user_id = $id ?? auth()->id();
        $user_role = auth()->user()->role;
        $res = '';

        // if (auth()->user()->inGroup('superadmin', 'superadmin-representator') && (auth()->user()->can('admin.access') || auth()->user()->can('admin.edit') || auth()->user()->can('transfer.ownership'))) {

        // if(auth()->user()->inGroup('player') ||
        //     auth()->user()->inGroup('club') ||
        //     auth()->user()->inGroup('scout') ||
        //     (auth()->user()->inGroup('superadmin') && auth()->user()->can('admin.access')) ||
        //     (auth()->user()->inGroup('superadmin-representator') && (auth()->user()->can('admin.access') || auth()->user()->can('admin.edit') || auth()->user()->can('transfer.ownership') ))
        //     ){

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP) || hasAccess(auth()->user(), EDITOR_ACCESS)) {
            if ($this->request->getVar('user') && count($this->request->getVar('user')) > 0) {

                $save_data = [];

                foreach ($this->request->getVar('user') as $key => $value) {
                    if ($key == 'nationality') {
                        $res = '';
                        if ($user_role == 4) {
                            $max_nationality = 2;
                        } else {
                            $max_nationality = 1;
                        }

                        if ($user_role == 4) {
                            $getNationality = $this->userNationalityModel->where('user_id', $user_id)->findAll();

                            $countries = array_column($getNationality, 'country_id');

                            $addCountries = array_diff($value, $countries);
                            $delCountries = array_diff($countries, $value);

                            if ($delCountries) {
                                $this->userNationalityModel
                                    ->where('user_id', $user_id)
                                    ->whereIn('country_id', $delCountries)
                                    ->delete();
                            }

                            if ($addCountries) {
                                foreach ($addCountries as $country) {

                                    //  check if new Nationality exists
                                    $natExist = $this->userNationalityModel
                                        ->where('user_id', $user_id)
                                        ->where('country_id', $country)
                                        ->first();

                                    $natCount = $this->userNationalityModel->where('user_id', $user_id)->countAllResults();

                                    if (!$natExist && $natCount < 2) {

                                        // insert new Nationality data
                                        $in_data = [
                                            'user_id'       => $user_id,
                                            'country_id'    => $country,
                                        ];

                                        $res = $this->userNationalityModel->save($in_data);
                                    }
                                }
                            }
                        } else {
                            $natData = [
                                'user_id'       => $user_id,
                                'country_id'    => $value[0],
                            ];

                            $natExist = $this->userNationalityModel->where('user_id', $user_id)->first();
                            if ($natExist) {
                                $natData['id'] = $natExist['id'];
                                $res = $this->userNationalityModel->save($natData);
                            } else {
                                $res = $this->userNationalityModel->save($natData);
                            }
                        }

                        // Skip the nationality key when saving meta data
                        continue;
                    }

                    if ($key == 'current_team') {

                        if (strtolower($value) != "null" && $value > 0) {

                            $isExist = $this->clubPlayerModel
                                ->select('club_players.*')
                                ->where('player_id', $user_id)
                                ->where('status', 1)
                                ->first();

                            if (!$isExist) {
                                $club_data = [
                                    'team_id'       => $value,
                                    'player_id'     => $user_id,
                                ];
                                $res = $this->clubPlayerModel->save($club_data);
                            } else {
                                // if current team is different from saved record then, update status to inactive
                                if ($isExist['team_id'] != $value) {

                                    // update status to inactive
                                    $up_data = [
                                        'id'        => $isExist['id'],
                                        'status'    => 2,          // 2 = inactive
                                    ];
                                    $res = $this->clubPlayerModel->save($up_data);

                                    // insert new club data
                                    $in_data = [
                                        'team_id'       => $value,
                                        'player_id'     => $user_id,
                                    ];
                                    $res = $this->clubPlayerModel->save($in_data);
                                }
                            }
                        } else {

                            $isExist = $this->clubPlayerModel
                                ->select('club_players.*')
                                ->where('player_id', $user_id)
                                ->where('status', 1)
                                ->first();

                            if ($isExist) {
                                $save_data = ['id' => $isExist['id'], 'status' => 2];
                                $res = $this->clubPlayerModel->save($save_data);   // 2 = inactive
                            }
                        }

                        // Skip the current_club key when saving meta data
                        continue;
                    }

                    if ($key == 'first_name') {
                        $users = auth()->getProvider();

                        $user = $users->findById($user_id);
                        $user->fill([
                            'first_name' => $value,
                        ]);
                        $res = $users->save($user);

                        continue;
                    }

                    if ($key == 'last_name') {
                        $users = auth()->getProvider();

                        $user = $users->findById($user_id);
                        $user->fill([
                            'last_name' => $value
                        ]);
                        $res = $users->save($user);

                        continue;
                    }

                    if ($key == 'main_position') {

                        $isMainExist = $this->playerPositionModel
                            ->where('user_id', $user_id)
                            ->where('is_main', 1)
                            ->first();

                        if ($isMainExist) {
                            if ($isMainExist['position_id'] != $value) {
                                $res = $this->playerPositionModel->save(
                                    [
                                        'id' => $isMainExist['id'],
                                        'position_id'   => $value
                                    ]
                                );
                            }
                        } else {

                            // insert new main_position data
                            $in_data = [
                                'user_id'       => $user_id,
                                'position_id'   => $value,
                                'is_main'       => 1,
                            ];
                            $res = $this->playerPositionModel->save($in_data);
                        }
                        // Skip the current_club key when saving meta data
                        continue;
                    }

                    if ($key == 'other_position') {

                        // get alll saved other_positions
                        $getPositions = $this->playerPositionModel
                            ->select('position_id')
                            ->where('user_id', $user_id)
                            ->where('is_main', NULL)
                            ->findall();

                        $otherPositions = array_column($getPositions, 'position_id');

                        $addPosition = array_diff($value, $otherPositions);
                        $delPosition = array_diff($otherPositions, $value);

                        if ($delPosition) {
                            $this->playerPositionModel
                                ->where('user_id', $user_id)
                                ->whereIn('position_id', $delPosition)
                                ->delete();
                        }

                        if ($addPosition) {
                            foreach ($addPosition as $position) {

                                //  check if new position exists in main position
                                $isOtherExist = $this->playerPositionModel
                                    ->where('user_id', $user_id)
                                    ->where('position_id', $position)
                                    ->first();

                                if (!$isOtherExist) {
                                    // insert new other_position data
                                    $in_data = [
                                        'user_id'       => $user_id,
                                        'position_id'   => $position,
                                    ];
                                    $res = $this->playerPositionModel->save($in_data);
                                }
                            }
                        }

                        // Skip the current_club key when saving meta data
                        continue;
                    }

                    if ($key == 'show_tour') {
                        $users = auth()->getProvider();

                        $user = $users->findById($user_id);
                        $user->fill([
                            'show_tour' => $value,
                        ]);
                        $res = $users->save($user);

                        continue;
                    }

                    if ($key == 'current_club') {

                        $oldClubID = getUserMeta($user_id, 'current_club');

                        if ($oldClubID != $value) {

                            $oldClubInfo = getClubOwnerDetail($oldClubID);
                            $newClubInfo = getClubOwnerDetail($value);

                            $emailType = 'Talent changes Club';
                            // $getEmailTemplate = getEmailTemplate($emailType, 2, CLUB_ROLE );
                            $getEmailTemplate = getEmailTemplate($emailType, $oldClubInfo->lang, CLUB_ROLE);

                            if ($getEmailTemplate) {

                                $replacements = [
                                    '{clubName}'        => $oldClubInfo->club_name,
                                    '{talentName}'      => auth()->user()->first_name,
                                    '{newClubName}'     => $newClubInfo->club_name,
                                    '{faqLink}'         => '<a href="'.getPageInUserLanguage($oldClubInfo->user_domain, $oldClubInfo->lang, "faq").'">faq</a>',
                                    '{helpEmail}'       => '<a href="mailto:'. HELP_EMAIL .'">'. HELP_EMAIL .'</a>',
                                ];

                                $subjectReplacements = [
                                    '{talentName}' => auth()->user()->first_name,
                                ];

                                // Replace placeholders with actual values in the email body
                                $subject = strtr($getEmailTemplate['subject'], $subjectReplacements);
                                $content = strtr($getEmailTemplate['content'], $replacements);
                                $message = view('emailTemplateView', ['message' => $content, 'disclaimerText' => getEmailDisclaimer($oldClubInfo->lang), 'footer' => getEmailFooter($oldClubInfo->lang)]);

                                $toEmail = $oldClubInfo->email;
                                // $toEmail = 'pratibhaprajapati.cts@gmail.com';

                                $emailData = [
                                    'fromEmail'     => FROM_EMAIL,
                                    'fromName'      => FROM_NAME,
                                    'toEmail'       => $toEmail,
                                    'subject'       => $subject,
                                    'message'       => $message,
                                ];
                                sendEmail($emailData);
                                // echo ' >>>>>>>>>>>> email send to >>>>> '. $toEmail . sendEmail($emailData);
                            }
                        }
                    }

                    // check if meta key exist
                    $userInfo = $userMetaObject->where('user_id', $user_id)->where('meta_key', $key)->first();

                    $save_data['id'] = '';
                    if ($userInfo) {
                        $save_data['id'] = $userInfo['id'];
                    }
                    $save_data['user_id'] = $user_id;
                    $save_data['meta_key'] = $key;

                    // Check if $value is an array and serialize it
                    if (is_array($value)) {
                        $save_data['meta_value'] = serialize($value);
                    } else {
                        $save_data['meta_value'] = $value;
                    }

                    // if($key == 'birth_country'){
                    //     $birthInfo = $userMetaObject->where('user_id', $user_id)->where('meta_key', 'birth_country_flag')->first();
                    //     $birthInfo_data['id'] = '';
                    //     if ($birthInfo) {
                    //         $birthInfo_data['id'] = $birthInfo['id'];
                    //     }
                    //     $birth_country_logo = '';
                    //     $countryModel = new CountryModel();

                    //     // Fetch country_name and country_code from countries table
                    //     $countries = $countryModel->select('country_name, country_code, country_flag')->where('id', $value)->first();
                    //     if(isset($countries) && !empty($countries['country_flag'])){
                    //         $birth_country_logo = $countries['country_flag'];
                    //     }
                    //     $birthInfo_data['user_id'] = $user_id;
                    //     $birthInfo_data['meta_key'] = 'birth_country_flag';
                    //     $birthInfo_data['meta_value'] = $birth_country_logo;
                    // }

                    // save data
                    // $res = $userMetaObject->save($save_data);
                    if ($userMetaObject->save($save_data)) {
                        $response = [
                            "status"    => true,
                            "message"   => lang('App.profile_updatedSuccess'),
                            "data"      => []
                        ];
                    } else {
                        $response = [
                            "status"    => false,
                            "message"   => lang('App.profile_updatedfailure'),
                            "data"      => []
                        ];
                    }
                }

                $activityMsg = [
                    'en' => ' updated personal details.',
                    'de' => ' hat die persönlichen Daten aktualisiert.',
                    'fr' => ' a mis à jour les informations personnelles.',
                    'it' => ' ha aggiornato i dettagli personali.',
                    'es' => ' ha actualizado los detalles personales.',
                    'pt' => ' atualizou os detalhes pessoais.',
                    'da' => ' har opdateret personlige oplysninger.',
                    'sv' => ' har uppdaterat personliga detaljer.'
                ];
                $activity_data = array();
                $languageService = \Config\Services::language();
                $languages = ['en', 'de', 'fr', 'it', 'es', 'pt', 'da', 'sv'];
                $activity_data = [
                    'user_id'               => auth()->id(),
                    'activity_type_id'      => 2, // update activity type
                    'ip'                    => $this->request->getIPAddress()
                ];
                foreach ($languages as $lang) {
                    $languageService->setLocale($lang);
                    $translated_message = $activityMsg[$lang];
                    $activity_data['activity_' . $lang] = $translated_message;
                }
                $activity_data['en'] = $activityMsg['en'];
                createActivityLog($activity_data);
            }
        } else {
            $response = [
                "status"    => false,
                "message"   => lang('App.permissionDenied'),
                "data"      => []
            ];
        }

        return $this->respondCreated($response);
    }

    // to update general information logged-in player
    // post
    public function updateGeneralInfo()
    {

        $userMetaObject = new UserMetaDataModel();

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            $userId = auth()->id();

            $save_data = [];
            foreach ($this->request->getVar('user') as $key => $value) {
                // check if meta key exist
                $userInfo = $userMetaObject->where('user_id', $userId)->where('meta_key', $key)->first();
                if ($key == 'show_tour') {
                    $users = auth()->getProvider();

                    $user = $users->findById($userId);
                    $user->fill([
                        'show_tour' => $value,
                    ]);
                    $res = $users->save($user);

                    continue;
                }
                if ($key == 'main_position') {

                    $isMainExist = $this->playerPositionModel
                        ->where('user_id', $userId)
                        ->where('is_main', 1)
                        ->first();

                    if ($isMainExist) {
                        if ($isMainExist['position_id'] != $value) {
                            $this->playerPositionModel->save(
                                [
                                    'id' => $isMainExist['id'],
                                    'position_id'   => $value
                                ]
                            );
                        }
                    } else {

                        // insert new main_position data
                        $in_data = [
                            'user_id'       => $userId,
                            'position_id'   => $value,
                            'is_main'       => 1,
                        ];
                        $this->playerPositionModel->save($in_data);
                    }
                    // Skip the current_club key when saving meta data
                    continue;
                }
                if ($key == 'other_position') {

                    // get alll saved other_positions
                    $getPositions = $this->playerPositionModel
                        ->select('position_id')
                        ->where('user_id', $userId)
                        ->where('is_main', NULL)
                        ->findall();

                    $otherPositions = array_column($getPositions, 'position_id');

                    $addPosition = array_diff($value, $otherPositions);
                    $delPosition = array_diff($otherPositions, $value);

                    if ($delPosition) {
                        $this->playerPositionModel
                            ->where('user_id', $userId)
                            ->whereIn('position_id', $delPosition)
                            ->delete();
                    }

                    if ($addPosition) {
                        foreach ($addPosition as $position) {

                            //  check if new position exists in main position
                            $isOtherExist = $this->playerPositionModel
                                ->where('user_id', $userId)
                                ->where('position_id', $position)
                                ->first();

                            if (!$isOtherExist) {
                                // insert new other_position data
                                $in_data = [
                                    'user_id'       => $userId,
                                    'position_id'   => $position,
                                ];
                                $this->playerPositionModel->save($in_data);
                            }
                        }
                    }

                    // Skip the current_club key when saving meta data
                    continue;
                }
                $save_data['id'] = '';
                if ($userInfo) {
                    $save_data['id'] = $userInfo['id'];
                }
                $save_data['user_id'] = $userId;
                $save_data['meta_key'] = $key;
                $save_data['meta_value'] = $value;
                if ($save_data['meta_key'] == 'market_value_unit') {
                    $save_data['meta_value'] = 'EUR';
                }
                // save data
                $res = $userMetaObject->save($save_data);
            }
            if ($res) {
                $activityMsg = [
                    'en' => ' updated General Details.',
                    'de' => ' hat die allgemeinen Daten aktualisiert.',
                    'fr' => ' a mis à jour les informations générales.',
                    'it' => ' ha aggiornato i dettagli generali.',
                    'es' => ' ha actualizado los detalles generales.',
                    'pt' => ' atualizou os detalhes gerais.',
                    'da' => ' har opdateret generelle oplysninger.',
                    'sv' => ' har uppdaterat allmänna detaljer.'
                ];
                $activity_data = array();
                $languageService = \Config\Services::language();
                $languages = ['en', 'de', 'fr', 'it', 'es', 'pt', 'da', 'sv'];
                $activity_data = [
                    'user_id'               => auth()->id(),
                    'activity_type_id'      => 2, // update activity type
                    'ip'                    => $this->request->getIPAddress()
                ];
                foreach ($languages as $lang) {
                    $languageService->setLocale($lang);
                    $translated_message = $activityMsg[$lang];
                    $activity_data['activity_' . $lang] = $translated_message;
                }
                $activity_data['en'] = $activityMsg['en'];
                createActivityLog($activity_data);
                $response = [
                    "status"    => true,
                    "message"   => lang('App.profile_updatedSuccess'),
                    "data"      => ['userId' => $userId]
                ];
            } else {
                $response = [
                    "status"    => false,
                    "message"   => lang('App.profile_updatedfailure'),
                    "data"      => []
                ];
            }
        } else {
            $response = [
                "status"    => false,
                "message"   => lang('App.permissionDenied'),
                "data"      => []
            ];
        }

        return $this->respondCreated($response);
    }

    public function deleteUser()
    {
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            $id = $this->request->getVar("id");

            if ($id && count($id) > 0) {

                foreach ($id as $userId) {

                    // Get the User Provider (UserModel by default)
                    $users = auth()->getProvider();
                    $user = $users->findById($userId);

                    // $del_res = $user->delete($userId);
                    if ($user) {
                        $users->delete($userId);
                        //$del_res = $users->delete($id, true);

                        $emailTemplateModel = new EmailTemplateModel();

                        $emailType = 'Admin Deleted Users Account';
                        $getEmailTemplate = $emailTemplateModel->where('type', $emailType)
                            ->where('language', $user->lang)
                            // ->where('language', 2)
                            ->whereIn('email_for', [0])
                            ->first();

                        if ($getEmailTemplate) {

                            $replacements = [
                                '{firstName}' => $user->first_name,
                            ];

                            $subject = $getEmailTemplate['subject'];

                            // Replace placeholders with actual values in the email body
                            $content = strtr($getEmailTemplate['content'], $replacements);
                            $message = view('emailTemplateView', ['message' => $content, 'disclaimerText' => getEmailDisclaimer($user->lang), 'footer' => getEmailFooter($user->lang)]);

                            $toEmail = $user->email;
                            // $toEmail = 'pratibhaprajapati.cts@gmail.com';

                            $emailData = [
                                'fromEmail'     => FROM_EMAIL,
                                'fromName'      => FROM_NAME,
                                'toEmail'       => $toEmail,
                                'subject'       => $subject,
                                'message'       => $message,
                            ];
                            // sendEmail($emailData);
                        }

                        // $activity_data = [
                        //     'user_id'               => auth()->id(),
                        //     'activity_type_id'      => 3,      // deleted
                        //     'activity'              => 'deleted user',
                        //     'old_data'              => serialize($id),
                        //     'ip'                    => $this->request->getIPAddress()
                        // ];
                        // createActivityLog($activity_data);
                        $activity_data = array();
                        $languageService = \Config\Services::language();
                        $languages = ['en', 'de', 'fr', 'it', 'es', 'pt', 'da', 'sv'];
                        $activity_data = [
                            'user_id'               => auth()->id(),
                            'activity_type_id'      => 7, // Register activity type
                            'ip'                    => $this->request->getIPAddress()
                        ];
                        foreach ($languages as $lang) {
                            $languageService->setLocale($lang);
                            $translated_message = lang('App.delete_user_success');
                            $activity_data['activity_' . $lang] = $translated_message;
                        }
                        createActivityLog($activity_data);
                    } else {
                        $response = [
                            "status"    => false,
                            "message"   => lang('App.invalid_userID'),
                            // "data"      => ['user_data' => $del_res]
                        ];
                    }

                    // Return a success message as a JSON response
                    $response = [
                        "status"    => true,
                        "message"   => lang('App.delete_user_success'),
                        // "data"      => ['user_data' => $del_res]
                    ];
                }
            } else {
                $response = [
                    "status"    => false,
                    "message"   => lang('App.invalid_userID'),
                    "data"      => []
                ];
            }
        } else {
            $response = [
                "status"    => false,
                "message"   => lang('App.permissionDenied'),
                "data"      => []
            ];
        }

        return $this->respondCreated($response);
    }


    public function deleteMyAccount()
    {
        // Get the current logged-in user
        $user = auth()->user();

        // Check if user exists
        if (!$user) {
            $response = [
                "status"    => false,
                "message"   => lang('App.invalid_userID'),
                "data"      => []
            ];
        } else {

            // Delete the user from the database
            $users = model('UserModel');
            if ($users->delete($user->id)) {

                // $emailTemplateModel = new EmailTemplateModel();
                $emailType = 'Delete My Account';

                // $getEmailTemplate = $emailTemplateModel->where('type', $emailType)
                //     ->where('language', $user->lang)
                //     ->whereIn('email_for', [0])
                //     ->first();

                $getEmailTemplate = getEmailTemplate($emailType, $user->lang, [0]);

                if ($getEmailTemplate) {

                    $language = \Config\Services::language();
                    $language->setLocale(getLanguageCode($user->lang));

                    $replacements = [
                        '{firstName}'   => $user->first_name,
                        '{faqLink}'     => '<a href="'.getPageInUserLanguage($user->user_domain, $user->lang, "faq").'">'. lang('Email.deleteMyAccount_help') .'</a>',
                    ];

                    $subject = $getEmailTemplate['subject'];

                    // Replace placeholders with actual values in the email body
                    $content = strtr($getEmailTemplate['content'], $replacements);
                    $message = view('emailTemplateView', ['message' => $content, 'disclaimerText' => getEmailDisclaimer($user->lang), 'footer' => getEmailFooter($user->lang)]);

                    $toEmail = $user->email;
                    // $toEmail = 'pratibhaprajapati.cts@gmail.com';
                    $emailData = [
                        'fromEmail'     => FROM_EMAIL,
                        'fromName'      => FROM_NAME,
                        'toEmail'       => $toEmail,
                        'subject'       => $subject,
                        'message'       => $message,
                    ];
                    sendEmail($emailData);
                }

                // email to admin
                $adminEmailType = 'Delete My Account Admin';
                // $getAdminEmailTemplate = $emailTemplateModel->where('type', $adminEmailType)
                //     ->where('language', 2)
                //     ->whereIn('email_for', ADMIN_ROLES)
                //     ->first();

                $getAdminEmailTemplate = getEmailTemplate($adminEmailType, 2, ADMIN_ROLES);


                if ($getAdminEmailTemplate) {

                    $replacements = [
                        '{firstName}'   => $user->first_name,
                        '{email}'       => $user->email,
                        '{userRole}'    => getRoleNameByID($user->role)
                    ];

                    $subject = $getAdminEmailTemplate['subject'];

                    // Replace placeholders with actual values in the email body
                    $content = strtr($getAdminEmailTemplate['content'], $replacements);
                    $message = view('emailTemplateView', ['message' => $content, 'disclaimerText' => getEmailDisclaimer(2), 'footer' => getEmailFooter(2)]);

                    // $toEmail = $user->email;
                    // $toEmail = 'pratibhaprajapati.cts@gmail.com';
                    $emailData = [
                        'fromEmail'     => FROM_EMAIL,
                        'fromName'      => FROM_NAME,
                        'toEmail'       => ADMIN_EMAIL,
                        'subject'       => $subject,
                        'message'       => $message,
                    ];
                    sendEmail($emailData);
                }


                // $activity_data = [
                //     'user_id'               => auth()->id(),
                //     'activity_type_id'      => 3,      // deleted
                //     'activity'              => 'deleted account',
                //     'old_data'              => auth()->id(),
                //     'ip'                    => $this->request->getIPAddress()
                // ];
                // createActivityLog($activity_data);

                $activity_data = array();
                $languageService = \Config\Services::language();
                $languages = ['en', 'de', 'fr', 'it', 'es', 'pt', 'da', 'sv'];
                $activity_data = [
                    'user_id'               => auth()->id(),
                    'activity_type_id'      => 7, // Register activity type
                    'ip'                    => $this->request->getIPAddress()
                ];
                foreach ($languages as $lang) {
                    $languageService->setLocale($lang);
                    $translated_message = lang('App.accountDeletedSuccess');
                    $activity_data['activity_' . $lang] = $translated_message;
                }
                createActivityLog($activity_data);

                // Return a success message as a JSON response
                $response = [
                    "status"    => true,
                    "message"   => lang('App.accountDeletedSuccess'),
                    "data"      => ['user_data' => $user]
                ];
                // auth()->logout();

            } else {
                $response = [
                    "status"    => false,
                    "message"   => lang('App.accountDeleteFailed'),
                    "data"      => []
                ];
            }

            // Log out the user after deletion
            // $auth->logout();
        }
        return $this->respondCreated($response);
    }

    public function getUsers($lang_id = null)
    {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $url =  isset($_SERVER['HTTPS']) &&
            $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
        $url .= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $parse_url = parse_url($url);
        $uri = new \CodeIgniter\HTTP\URI($url);

        $queryString =  $uri->getQuery();
        parse_str($queryString, $searchParams);

        $whereClause = [];
        $metaQuery = [];
        $orderBy =  $order = $search = '';
        $noLimit =  $countOnly = FALSE;


        $limit = $searchParams['limit'] ?? PER_PAGE;
        $offset = $searchParams['offset'] ?? 0;

        if (isset($searchParams['whereClause']) && count($searchParams['whereClause']) > 0) {
            $whereClause = $searchParams['whereClause'];
        }

        if (isset($searchParams['metaQuery']) && count($searchParams['metaQuery']) > 0) {
            $metaQuery = $searchParams['metaQuery'];
        }

        if (isset($searchParams['search']) && !empty($searchParams['search'])) {
            $search = $searchParams['search'];
        }

        if (isset($searchParams['orderBy']) && !empty($searchParams['orderBy'])) {
            $orderBy = $searchParams['orderBy'];
        }

        if (isset($searchParams['order']) && !empty($searchParams['order'])) {
            $order = $searchParams['order'];
        }

        if (isset($searchParams['noLimit']) && !empty($searchParams['noLimit'])) {
            $noLimit = $searchParams['noLimit'];
        }

        if (isset($searchParams['countOnly']) && !empty($searchParams['countOnly'])) {
            $countOnly = $searchParams['countOnly'];
        }

        //$users = getPlayers($whereClause, $metaQuery, $search, $orderBy, $order, $limit, $offset, $noLimit, $countOnly);
        $users = getPlayers($whereClause, $metaQuery, $search, $orderBy, $order, $limit, $offset, $noLimit, $lang_id);
        //pr($users); exit;

        if ($users) {
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => [
                    'totalCount'    => $users['totalCount'],
                    'userData'      => $users['users']
                ]
            ];
        } else {
            $response = [
                "status"    => false,
                "message"   => lang('App.noDataFound'),
                "data"      => []
            ];
        }

        // $activity_data = [
        //     'user_id'               => auth()->id(),
        //     'activity_type_id'      => 9,      // viewed
        //     'activity'              => 'viewed user',
        //     'ip'                    => $this->request->getIPAddress()
        // ];
        // createActivityLog($activity_data);

        return $this->respondCreated($response);
    }

    public function getUsersOnFrontend()
    {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $url =  isset($_SERVER['HTTPS']) &&
            $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
        $url .= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $parse_url = parse_url($url);
        $uri = new \CodeIgniter\HTTP\URI($url);

        $queryString =  $uri->getQuery();
        parse_str($queryString, $searchParams);

        $whereClause = [];
        $metaQuery = [];
        $orderBy =  $order = $search = '';
        $noLimit =  $countOnly = FALSE;
        $loggedInID = auth()->id() ?? '';

        $limit = $searchParams['limit'] ?? PER_PAGE;
        $offset = $searchParams['offset'] ?? 0;

        if (isset($searchParams['whereClause']) && count($searchParams['whereClause']) > 0) {
            $whereClause = $searchParams['whereClause'];
        }

        if (isset($searchParams['metaQuery']) && count($searchParams['metaQuery']) > 0) {
            $metaQuery = $searchParams['metaQuery'];
        }

        if (isset($searchParams['search']) && !empty($searchParams['search'])) {
            $search = $searchParams['search'];
        }

        if (isset($searchParams['orderBy']) && !empty($searchParams['orderBy'])) {
            $orderBy = $searchParams['orderBy'];
        }

        if (isset($searchParams['order']) && !empty($searchParams['order'])) {
            $order = $searchParams['order'];
        }

        if (isset($searchParams['noLimit']) && !empty($searchParams['noLimit'])) {
            $noLimit = $searchParams['noLimit'];
        }

        if (isset($searchParams['countOnly']) && !empty($searchParams['countOnly'])) {
            $countOnly = $searchParams['countOnly'];
        }

        //$users = getPlayersOnFrontend($whereClause, $metaQuery, $orderBy, $order, $limit, $offset, $noLimit, $countOnly);
        $users = getPlayersOnFrontend($whereClause, $metaQuery, $search, $orderBy, $order, $limit, $offset, $noLimit);

        if ($users) {
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => ['userData' => $users]
            ];
        } else {
            $response = [
                "status"    => false,
                "message"   => lang('App.noDataFound'),
                "data"      => []
            ];
        }

        // $activity_data = [
        //     'user_id'               => auth()->id(),
        //     'activity_type_id'      => 9,      // viewed
        //     'activity'              => 'viewed user',
        //     'ip'                    => $this->request->getIPAddress()
        // ];
        // createActivityLog($activity_data);

        return $this->respondCreated($response);
    }

    public function updateUserStatus()
    {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        // if (auth()->user()->inGroup('superadmin', 'superadmin-representator') && (auth()->user()->can('admin.access') || auth()->user()->can('admin.edit') || auth()->user()->can('transfer.ownership'))) {

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP) || hasAccess(auth()->user(), EDITOR_ACCESS)) {

            if (($this->request->getVar("id") && count($this->request->getVar("id")) > 0) && !empty($this->request->getVar("status"))) {
                $ids = $this->request->getVar("id");

                foreach ($ids as $id) {
                    $user = getUserByID($id);

                    if ($user) {

                        $first_name = $user->first_name;
                        $last_name  = $user->last_name;
                        $email      = $user->email;

                        $builder = $this->db->table('users');
                        $builder->set('status', $this->request->getVar("status"));
                        $builder->where('id', $id);

                        if ($builder->update()) {

                            // for club: update status is clubs table that this club is reserved now
                            if ($user->role == 2 && ($this->request->getVar("status") == 2)) {

                                $userMetaObject = new UserMetaDataModel();
                                $club_data = $userMetaObject->where('user_id', $id)->where('meta_key', 'pre_club_id')->first();

                                if ($club_data) {
                                    $this->clubModel->save([
                                        'id'        => $club_data['meta_value'],
                                        'is_taken'  => 1,   // yes
                                        'taken_by'  => $id
                                    ]);
                                }
                            }

                            //1 = 'Pending' 2 = 'Verified' 3 = 'Rejected'
                            $keywordTranslations = array();
                            if ($this->request->getVar("status") == 2) {
                                $activityStatus = 'Verified';
                                $status = 'approves';
                            } elseif ($this->request->getVar("status") == 3) {
                                $activityStatus  = 'Rejected';
                                $status = 'rejects';
                            } else {
                                $activityStatus = 'Pending';
                                $status = 'Pending';
                            }

                            $keywordTranslations = [
                                'Verified' => [
                                    'en' => 'Verified',
                                    'de' => 'Verifiziert',
                                    'it' => 'Verificato',
                                    'es' => 'Verificado',
                                    'da' => 'Verificeret',
                                    'sv' => 'Verifierad',
                                ],
                                'Rejected' => [
                                    'en' => 'Rejected',
                                    'de' => 'Abgelehnt',
                                    'it' => 'Rifiutato',
                                    'es' => 'Rechazado',
                                    'da' => 'Afvist',
                                    'sv' => 'Avvisad',
                                ],
                                'Pending' => [
                                    'en' => 'Pending',
                                    'de' => 'Ausstehend',
                                    'it' => 'In sospeso',
                                    'es' => 'Pendiente',
                                    'da' => 'Afventer',
                                    'sv' => 'Väntande',
                                ],
                            ];
                            $emailTemplateModel = new EmailTemplateModel();
                            $emailType = '';
                            if ($user->role == 2) {
                                $emailType = 'Admin ' . $status . ' profile Club';
                            }

                            if ($user->role == 3) {
                                $emailType = 'Admin ' . $status . ' profile Scout';
                            }

                            if ($user->role == 4) {
                                $emailType = 'Admin ' . $status . ' profile Talent';
                            }

                            // $userRole = [$user->role];
                            // $getEmailTemplate = $emailTemplateModel->where('type', $emailType)
                            //     ->where('language', $user->lang)
                            //     // ->where('language', 2)
                            //     ->whereIn('email_for', $userRole)
                            //     ->first();

                            $getEmailTemplate = getEmailTemplate($emailType, $user->lang, [$user->role] );


                            if ($getEmailTemplate) {

                                $replacements = [
                                    '{firstName}'           => $user->first_name,
                                    '{loginLink}'           => '<a href="'.getPageInUserLanguage($user->user_domain, $user->lang).'">'. lang('Email.login') .'</a>',
                                    '{PricingLink}'         => '<a href="'.getPageInUserLanguage($user->user_domain, $user->lang, 'pricing').'">'. lang('Email.premiumMembership') .'</a>',
                                    '{helpEmail}'           => '<a href="mailto:'. HELP_EMAIL .'">'. HELP_EMAIL .'</a>',

                                ];

                                $subject = $getEmailTemplate['subject'];

                                // Replace placeholders with actual values in the email body
                                $content = strtr($getEmailTemplate['content'], $replacements);
                                $message = view('emailTemplateView', ['message' => $content, 'disclaimerText' => getEmailDisclaimer($user->lang), 'footer' => getEmailFooter($user->lang)]);

                                $toEmail = $user->email;
                                // $toEmail = 'pratibhaprajapati.cts@gmail.com';
                                $emailData = [
                                    'fromEmail'     => FROM_EMAIL,
                                    'fromName'      => FROM_NAME,
                                    'toEmail'       => $toEmail,
                                    'subject'       => $subject,
                                    'message'       => $message,
                                ];
                                sendEmail($emailData);
                            }

                            $activity_data = array();
                            $languageService = \Config\Services::language();
                            $languages = ['en', 'de', 'fr', 'it', 'es', 'pt', 'da', 'sv'];
                            $activity_data = [
                                'user_id'               => auth()->id(),
                                'activity_type_id'      => 7, // Register activity type
                                'ip'                    => $this->request->getIPAddress()
                            ];
                            foreach ($languages as $lang) {
                                $languageService->setLocale($lang);
                                if (isset($keywordTranslations[$activityStatus][$lang]) && !empty($keywordTranslations[$activityStatus][$lang])) {
                                    $status = $keywordTranslations[$activityStatus][$lang];
                                } else {
                                    $status = $keywordTranslations[$activityStatus]['en'];
                                }
                                $translated_message = lang('App.updateUserStatus', ['userName' => '[USER_NAME_' . $id . ']', 'status' => $status]);
                                $activity_data['activity_' . $lang] = $translated_message;
                            }
                            createActivityLog($activity_data);
                            // $activity = 'updated [USER_NAME_' . $id . '] Profile status to ' . $activityStatus;
                            // $activity_data = [
                            //     'user_id'               => auth()->id(),
                            //     'activity_on_id'        => $id,
                            //     'activity_type_id'      => 2,        // updated
                            //     'activity'              => $activity,
                            //     'ip'                    => $this->request->getIPAddress()
                            // ];
                            // createActivityLog($activity_data);

                            $response = [
                                "status"    => true,
                                "message"   => lang('App.userStatusUpdated'),
                                "data"      => []
                            ];
                        } else {
                            $response = [
                                "status"    => false,
                                "message"   => lang('App.userStatusUpdateFailed'),
                                "data"      => []
                            ];
                        }
                    }
                }
            } else {
                $response = [
                    "status"    => false,
                    "message"   => lang('App.invalid_userID'),
                    "data"      => []
                ];
            }
        } else {
            $response = [
                "status"    => false,
                "message"   => lang('App.permissionDenied'),
                "data"      => []
            ];
        }
        return $this->respondCreated($response);
    }

    public function uploadProfileImage()
    {

        $userMetaObject = new UserMetaDataModel();

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        if (empty($this->request->getVar("lang"))) {
            $currentUserLang = currentUserLang(auth()->id());
            if (isset($currentUserLang) && !empty($currentUserLang->slug)) {
                $currentLang = $currentUserLang->slug;
            }
        }
        $this->request->setLocale(getLanguageCode($currentLang));

        $userId = auth()->id();

        // check if representator
        if (in_array(auth()->user()->role, REPRESENTATORS_ROLES)) {
            $authUser = auth()->user();
            $userId = $authUser->parent_id;
        }

        $validationRule = [
            'profile_image' => [
                'label' => 'Profile Image',
                'rules' => [
                    'uploaded[profile_image]',
                    'is_image[profile_image]',
                    'mime_in[profile_image,image/jpg,image/jpeg,image/gif,image/png,image/webp]',
                    'max_size[profile_image,1000]',
                    //'max_dims[profile_image,1024,768]',
                ],
                'errors' => [
                    'uploaded'  => lang('App.uploaded', ['file' => 'jpg, jpeg, gif, png, webp']),
                    'is_image'  => lang('App.is_image', ['file' => 'jpg, jpeg, gif, png, webp']),
                    'mime_in'   => lang('App.mime_in', ['file' => 'jpg, jpeg, gif, png, webp']),
                    'max_size'  => lang('App.max_size', ['siz' => '1000']),
                    'max_dims'  => lang('App.max_dims'),
                ],
            ],
        ];

        if (! $this->validateData([], $validationRule)) {
            $errors = $this->validator->getErrors();

            $response = [
                "status"    => false,
                "message"   => lang('App.profile_updatedfailure'),
                "data"      => ['errors' => $errors]
            ];
        } else {

            $is_uploaded = removeImageBG($this->request->getFile('profile_image'));
            //$profile_image = $this->request->getFile('profile_image');        // old code

            //if (! $profile_image->hasMoved()) {       // old code
            if ($is_uploaded && $is_uploaded['status'] == "success") {

                //$filepath = WRITEPATH . 'uploads/' . $profile_image->store('');       // old code

                $userInfo = $userMetaObject->where('user_id', $userId)->where('meta_key', 'profile_image')->first();
                $save_data['id'] = '';
                if ($userInfo) {
                    $save_data['id'] = $userInfo['id'];

                    // remove file if existing
                    if (file_exists(WRITEPATH . 'uploads/' . $userInfo['meta_value'])) {
                        unlink(WRITEPATH . 'uploads/' . $userInfo['meta_value']);
                    }
                }
                $save_data['user_id'] = $userId;
                $save_data['meta_key'] = 'profile_image';
                //$save_data['meta_value'] = $profile_image->getName();     // old Code
                $save_data['meta_value'] = $is_uploaded['fileName'];
                // save data
                $res = $userMetaObject->save($save_data);

                //$data = ['uploaded_fileinfo' => $profile_image->getName()];        // old Code
                $data = ['uploaded_fileinfo' => $is_uploaded['fileName']];
                // $activity = lang('App.uploaded_profile_image', ['imageName' => $is_uploaded['fileName']]);
                // // create Activity log
                // $activity_data = [
                //     'user_id'               => auth()->id(),
                //     'activity_type_id'      => 2,      // updated
                //     'activity'              => $activity,
                //     'ip'                    => $this->request->getIPAddress()
                // ];
                // createActivityLog($activity_data);
                $activity_data = array();
                $languageService = \Config\Services::language();
                $languages = ['en', 'de', 'fr', 'it', 'es', 'pt', 'da', 'sv'];
                $activity_data = [
                    'user_id'               => auth()->id(),
                    'activity_type_id'      => 2, // Register activity type
                    'ip'                    => $this->request->getIPAddress()
                ];
                foreach ($languages as $lang) {
                    $languageService->setLocale($lang);
                    $translated_message = lang('App.uploaded_profile_image', ['imageName' => $save_data['meta_value']]);
                    $activity_data['activity_' . $lang] = $translated_message;
                }
                createActivityLog($activity_data);

                $response = [
                    "status"    => true,
                    "message"   => lang('App.profile_updatedSuccess'),
                    "data"      => $data
                ];
            } else {
                $data = ['errors' => $is_uploaded];
                $response = [
                    "status"    => false,
                    "message"   => lang('App.profile_updatedfailure'),
                    "data"      => $data
                ];
            }
        }
        return $this->respondCreated($response);
    }

    public function getProfileImage()
    {

        $userMetaObject = new UserMetaDataModel();

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $userId = auth()->id();

        // check if representator
        if (in_array(auth()->user()->role, REPRESENTATORS_ROLES)) {
            $authUser = auth()->user();
            $userId = $authUser->parent_id;
        }

        $userData = $userMetaObject->where('user_id', $userId)->where('meta_key', 'profile_image')->first();
        $profile_image =  base_url() . 'uploads/' . $userData['meta_value'];
        $userData['profile_image_path'] = $profile_image;

        if ($userData) {

            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => ['userData' => $userData]
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

    public function uploadCoverImage()
    {

        $userMetaObject = new UserMetaDataModel();

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        if (empty($this->request->getVar("lang"))) {
            $currentUserLang = currentUserLang(auth()->id());
            if (isset($currentUserLang) && !empty($currentUserLang->slug)) {
                $currentLang = $currentUserLang->slug;
            }
        }

        $this->request->setLocale(getLanguageCode($currentLang));

        $userId = auth()->id();

        $validationRule = [
            'cover_image' => [
                'label' => 'Cover Image',
                'rules' => [
                    'uploaded[cover_image]',
                    'is_image[cover_image]',
                    'mime_in[cover_image,image/jpg,image/jpeg,image/gif,image/png,image/webp]',
                    'max_size[cover_image,5000]',
                    //'max_dims[cover_image,1024,768]',
                ],
                'errors' => [
                    'uploaded'  => lang('App.uploaded', ['file' => 'jpg, jpeg, gif, png, webp']),
                    'is_image'  => lang('App.is_image', ['file' => 'jpg, jpeg, gif, png, webp']),
                    'mime_in'   => lang('App.mime_in', ['file' => 'jpg, jpeg, gif, png, webp']),
                    'max_size'  => lang('App.max_size', ['siz' => '5000']),
                    'max_dims'  => lang('App.max_dims'),
                ],
            ],
        ];

        if (! $this->validateData([], $validationRule)) {
            $errors = $this->validator->getErrors();

            $response = [
                "status"    => false,
                "message"   => lang('App.profile_updatedfailure'),
                "data"      => ['errors' => $errors]
            ];
        } else {

            $cover_image = $this->request->getFile('cover_image');

            if (! $cover_image->hasMoved()) {
                $filepath = WRITEPATH . 'uploads/' . $cover_image->store('');

                $userInfo = $userMetaObject->where('user_id', $userId)->where('meta_key', 'cover_image')->first();

                $save_data['id'] = '';
                if ($userInfo) {
                    $save_data['id'] = $userInfo['id'];

                    // remove file if existing
                    if (file_exists(WRITEPATH . 'uploads/' . $userInfo['meta_value'])) {
                        unlink(WRITEPATH . 'uploads/' . $userInfo['meta_value']);
                    }
                }
                $save_data['user_id'] = $userId;
                $save_data['meta_key'] = 'cover_image';
                $save_data['meta_value'] = $cover_image->getName();
                // save data
                $res = $userMetaObject->save($save_data);

                $data = ['uploaded_fileinfo' => $cover_image->getName()];
                $activity_data = array();
                $languageService = \Config\Services::language();
                $languages = ['en', 'de', 'fr', 'it', 'es', 'pt', 'da', 'sv'];
                $activity_data = [
                    'user_id'               => auth()->id(),
                    'activity_type_id'      => 2,      // updated
                    'ip'                    => $this->request->getIPAddress()
                ];
                foreach ($languages as $lang) {
                    $languageService->setLocale($lang);
                    $translated_message = lang('App.uploaded_cover_image', ['imageName' => $cover_image->getName()]);
                    $activity_data['activity_' . $lang] = $translated_message;
                }
                createActivityLog($activity_data);

                $response = [
                    "status"    => true,
                    "message"   => lang('App.profile_updatedSuccess'),
                    "data"      => $data
                ];
            } else {
                $data = ['errors' => 'The file has already been moved.'];
                $response = [
                    "status"    => false,
                    "message"   => lang('App.profile_updatedfailure'),
                    "data"      => $data
                ];
            }
        }
        return $this->respondCreated($response);
    }

    public function getCoverImage()
    {

        $userMetaObject = new UserMetaDataModel();

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $userId = auth()->id();

        // check if representator
        if (in_array(auth()->user()->role, REPRESENTATORS_ROLES)) {
            $authUser = auth()->user();
            $userId = $authUser->parent_id;
        }

        $userData = $userMetaObject->where('user_id', $userId)->where('meta_key', 'cover_image')->first();
        if (!isset($userData['meta_value']) || empty($userData['meta_value'])) { // amrit
            $userData['meta_value'] = '';
        }
        $cover_image =  base_url() . 'uploads/' . $userData['meta_value'];
        $userData['cover_image_path'] = $cover_image;

        if ($userData) {

            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => ['userData' => $userData]
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

    public function deleteProfileImage()
    {

        $userMetaObject = new UserMetaDataModel();

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        if (empty($this->request->getVar("lang"))) {
            $currentUserLang = currentUserLang(auth()->id());
            if (isset($currentUserLang) && !empty($currentUserLang->slug)) {
                $currentLang = $currentUserLang->slug;
            }
        }
        $this->request->setLocale(getLanguageCode($currentLang));

        $userId = auth()->id();

        // check if representator
        if (in_array(auth()->user()->role, REPRESENTATORS_ROLES)) {
            $authUser = auth()->user();
            $userId = $authUser->parent_id;
        }

        $userData = $userMetaObject->where('user_id', $userId)->where('meta_key', 'profile_image')->first();

        if ($userData) {
            if (file_exists(WRITEPATH . 'uploads/' . $userData['meta_value'])) {
                unlink(WRITEPATH . 'uploads/' . $userData['meta_value']);
                $res = $userMetaObject->delete($userData['id']);
            }

            if ($res) {
                // $data = ['uploaded_fileinfo' => $cover_image->getName()];
                $activity_data = array();
                $languageService = \Config\Services::language();
                $languages = ['en', 'de', 'fr', 'it', 'es', 'pt', 'da', 'sv'];
                $activity_data = [
                    'user_id'               => auth()->id(),
                    'activity_type_id'      => 3,      // updated
                    'ip'                    => $this->request->getIPAddress()
                ];
                foreach ($languages as $lang) {
                    $languageService->setLocale($lang);
                    $translated_message = lang('App.removed_profile_image', ['imageName' => $userData['meta_value']]);
                    $activity_data['activity_' . $lang] = $translated_message;
                }
                createActivityLog($activity_data);

                $response = [
                    "status"    => true,
                    "message"   => 'Profile image deleted successfully',
                    "data"      => []
                ];
            } else {
                $response = [
                    "status"    => false,
                    "message"   => 'Profile image not deleted',
                    "data"      => []
                ];
            }
        } else {
            $response = [
                "status"    => false,
                "message"   => lang('App.noDataFound'),
                "data"      => []
            ];
        }
        return $this->respondCreated($response);
    }

    public function deleteCoverImage()
    {

        $userMetaObject = new UserMetaDataModel();

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        if (empty($this->request->getVar("lang"))) {
            $currentUserLang = currentUserLang(auth()->id());
            if (isset($currentUserLang) && !empty($currentUserLang->slug)) {
                $currentLang = $currentUserLang->slug;
            }
        }

        $this->request->setLocale(getLanguageCode($currentLang));

        $userId = auth()->id();
        $userData = $userMetaObject->where('user_id', $userId)->where('meta_key', 'cover_image')->first();

        if ($userData) {
            if (file_exists(WRITEPATH . 'uploads/' . $userData['meta_value'])) {
                unlink(WRITEPATH . 'uploads/' . $userData['meta_value']);
                $res = $userMetaObject->delete($userData['id']);
            }

            if ($res) {
                $activity_data = array();
                $languageService = \Config\Services::language();
                $languages = ['en', 'de', 'fr', 'it', 'es', 'pt', 'da', 'sv'];
                $activity_data = [
                    'user_id'               => auth()->id(),
                    'activity_type_id'      => 3,      // updated
                    'ip'                    => $this->request->getIPAddress()
                ];
                foreach ($languages as $lang) {
                    $languageService->setLocale($lang);
                    $translated_message = lang('App.removed_cover_image', ['imageName' => $userData['meta_value']]);
                    $activity_data['activity_' . $lang] = $translated_message;
                }
                createActivityLog($activity_data);

                $response = [
                    "status"    => true,
                    "message"   => 'Cover image deleted successfully',
                    "data"      => []
                ];
            } else {
                $response = [
                    "status"    => false,
                    "message"   => 'Cover image not deleted',
                    "data"      => []
                ];
            }
        } else {
            $response = [
                "status"    => false,
                "message"   => lang('App.noDataFound'),
                "data"      => []
            ];
        }
        return $this->respondCreated($response);
    }


    public function addRepresentator($userId = null)
    {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        if (empty($this->request->getVar("lang"))) {
            $currentUserLang = currentUserLang(auth()->id());
            if (isset($currentUserLang) && !empty($currentUserLang->slug)) {
                $currentLang = $currentUserLang->slug;
            }
        }
        $this->request->setLocale(getLanguageCode($currentLang));

        // if (auth()->user()->inGroup('superadmin', 'superadmin-representator') && (auth()->user()->can('admin.access') || auth()->user()->can('transfer.ownership'))) {
        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            $adminId = $userId ?? auth()->id();
            // $adminId = auth()->id();

            $rules = [
                "email"         => "required|valid_email|is_unique[auth_identities.secret]",
                "site_role"     => "required",
            ];

            if (!$this->validate($rules)) {
                $response = [
                    "status"    => false,
                    "message"   => $this->validator->getErrors(),
                    "data"      => []
                ];
            } else {

                $users = auth()->getProvider();
                $adminInfo = $users->findById($adminId);

                $lang           = $adminInfo->lang;
                $user_domain    = $adminInfo->user_domain;
                $password       = uniqid();

                $userEntity = [
                    "email"         => $this->request->getVar("email"),
                    "password"      => $password,
                    "role"          => 5,
                    "lang"          => $lang,
                    "user_domain"   => $user_domain,
                    "status"        => 1,
                    "parent_id"     => $adminId,
                    "added_by"      => auth()->id(),
                ];

                $group = '';

                if (auth()->user()->role == 1) {
                    $group = 'superadmin-representator';
                }

                if (auth()->user()->role == 2) {
                    $group = 'club-representator';
                }

                if (auth()->user()->role == 3) {
                    $group = 'scout-representator';
                }

                $userData = new User($userEntity);

                if ($users->save($userData)) {

                    $userInfo = $users->findByCredentials(['email' => $this->request->getVar("email")]); // find registered users
                    $userInfo->addGroup($group);    // add user to group
                    $userInfo->addPermission($this->request->getVar("site_role"));         // add user permission

                    $adminSubject = 'New Representator registered on Succer You Sports AG';
                    $adminMessage = 'Dear Admin, <br>' . $adminInfo->first_name . ' ' . $adminInfo->last_name . ' has added new representator (' . $this->request->getVar("email") . ')';

                    $adminEmailData = [
                        'fromEmail'     => FROM_EMAIL,
                        'fromName'      => FROM_NAME,
                        'toEmail'       => ADMIN_EMAIL,
                        'subject'       => $adminSubject,
                        'message'       => $adminMessage,
                    ];
                    sendEmail($adminEmailData);


                    $tokenLink = base_url() . 'api/verify-email/' . base64_encode($this->request->getVar("email")) . '/' . base64_encode($userInfo->created_at);
                    $userSubject = 'Inviation to become Representator on Succer You Sports AG';
                    $userMessage = '';
                    $userMessage .= 'Dear ' . $this->request->getVar("email") . ', <br>' . $adminInfo->first_name . ' ' . $adminInfo->last_name . ' has invited you to become representator on Succer You Sports AG';
                    $userMessage .= '<br>Please Click <a href="' . $tokenLink . '">Here</a> to activate your account';
                    $userMessage .= '<br>Below is your login detail';
                    $userMessage .= '<br>Email: ' . $this->request->getVar("email");
                    $userMessage .= '<br>Password: ' . $password;

                    $userEmailData = [
                        'fromEmail'     => FROM_EMAIL,
                        'fromName'      => FROM_NAME,
                        'toEmail'       => $this->request->getVar("email"),
                        'subject'       => $userSubject,
                        'message'       => $userMessage,
                    ];
                    sendEmail($userEmailData);
                    // added_representator_user
                    // create Activity log
                    // $activity = lang('App.added_representator_user', ['userEmail' => $this->request->getVar("email")]);
                    // $activity_data = [
                    //     'user_id'               => $adminId,
                    //     'activity_on_id'        => $userInfo->id,
                    //     'activity_type_id'      => 7,        // register
                    //     'activity'              => $activity,
                    //     'ip'                    => $this->request->getIPAddress()
                    // ];
                    // createActivityLog($activity_data);
                    $activity_data = array();
                    $languageService = \Config\Services::language();
                    $languages = ['en', 'de', 'fr', 'it', 'es', 'pt', 'da', 'sv'];
                    $activity_data = [
                        'user_id'               => auth()->id(),
                        'activity_type_id'      => 3,      // updated
                        'ip'                    => $this->request->getIPAddress()
                    ];
                    foreach ($languages as $lang) {
                        $languageService->setLocale($lang);
                        $translated_message = lang('App.added_representator_user', ['userEmail' => $this->request->getVar("email")]);
                        $activity_data['activity_' . $lang] = $translated_message;
                    }
                    createActivityLog($activity_data);

                    $response = [
                        "status"    => true,
                        "message"   => lang('App.representatorAdded'),
                        "data"      => []
                    ];
                } else {
                    $response = [
                        "status"    => true,
                        "message"   => lang('App.representatorAddFailed'),
                        "data"      => []
                    ];
                }
            }
        } else {
            $response = [
                "status"    => false,
                "message"   => lang('App.permissionDenied'),
                "data"      => []
            ];
        }

        return $this->respondCreated($response);
    }

    public function updateRepresentatorRole($userId = null)
    {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        if (empty($this->request->getVar("lang"))) {
            $currentUserLang = currentUserLang(auth()->id());
            if (isset($currentUserLang) && !empty($currentUserLang->slug)) {
                $currentLang = $currentUserLang->slug;
            }
        }
        $this->request->setLocale(getLanguageCode($currentLang));

        // if (auth()->user()->inGroup('superadmin', 'superadmin-representator') && (auth()->user()->can('admin.access') || auth()->user()->can('transfer.ownership'))) {

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            $rules = [
                "site_role"     => "required",
            ];

            if (!$this->validate($rules)) {
                $response = [
                    "status"    => false,
                    "message"   => $this->validator->getErrors(),
                    "data"      => []
                ];
            } else {
                $users = auth()->getProvider();
                $representatorInfo = $users->findById($userId);

                if ($representatorInfo->syncPermissions($this->request->getVar("site_role"))) {

                    // $authRole = getUserRoleByID(auth()->user()->role);
                    $authRole = auth()->user()->role;
                    $representatorName = $representatorInfo->first_name ?? $representatorInfo->email;

                    // Replace email for "transfer-ownership" role
                    // get represtator's email data
                    $emailBuilder = $this->db->table('auth_identities au');
                    $emailQuery = $emailBuilder->select('au.*')->where('user_id', $userId)->where('type', 'email_password')->get();
                    $emailData = $emailQuery->getRow();

                    if ($this->request->getVar("site_role") == "transfer-ownership") {
                        $authUser = auth()->user();

                        $replaceEmail = $representatorInfo->email . '_transfered';
                        $emailBuilder->where('id', $emailData->id);
                        $resss = $emailBuilder->update(['secret' => $replaceEmail]);

                        $replacePassword = uniqid();
                        $fromEmail = $authUser->email;

                        $authUser->fill([
                            'email'     => $representatorInfo->email,
                            'password'  => $replacePassword
                        ]);

                        if ($users->save($authUser)) {

                            $del_res = $users->delete($userId);


                            // send email to representators
                            $userSubject = 'Ownership transfered to you on Succer You Sports AG';
                            $userMessage = '';
                            $userMessage .= 'Dear ' . $representatorName . ',';
                            $userMessage .= '<br>Congratulations! Ownership transfered to you by club/scout owner on Succer You Sports AG';
                            $userMessage .= '<br> Below is your login details';
                            $userMessage .= '<br> Email : ' . $representatorInfo->email;
                            $userMessage .= '<br> Password : ' . $replacePassword;

                            $userEmailData = [
                                'fromEmail'     => FROM_EMAIL,
                                'fromName'      => FROM_NAME,
                                'toEmail'       => $representatorInfo->email,
                                'subject'       => $userSubject,
                                'message'       => $userMessage,
                            ];
                            sendEmail($userEmailData);


                            $adminSubject = 'Your ownership transfered to Representator on Succer You Sports AG';
                            $adminMessage = '';
                            $adminMessage .= 'Dear ' . $fromEmail . ',';
                            $adminMessage .= '<br>Your ownership transfered to Representator on Succer You Sports AG';

                            $adminEmailData = [
                                'fromEmail'     => FROM_EMAIL,
                                'fromName'      => FROM_NAME,
                                'toEmail'       => $fromEmail,
                                'subject'       => $adminSubject,
                                'message'       => $adminMessage,
                            ];
                            sendEmail($adminEmailData);

                            $transferData = [
                                'from_user_id'  => auth()->id(),
                                'from_email'    => $fromEmail,
                                'to_user_id'    => $userId,
                                'to_email'      => $representatorInfo->email,
                                'status'        => 2,
                            ];
                            $this->ownershipTransferDataModel->save($transferData);
                        }
                    } else {

                        if ($authRole == "Scout" || $authRole == "Club") {

                            // send email to representators
                            $userSubject = 'Your Role updated on Succer You Sports AG';
                            $userMessage = '';
                            $userMessage .= 'Dear ' . $representatorName . ',';
                            $userMessage .= '<br>Your role has been updated by admin on Succer You Sports AG';

                            $userEmailData = [
                                'fromEmail'     => FROM_EMAIL,
                                'fromName'      => FROM_NAME,
                                'toEmail'       => $representatorInfo->email,
                                'subject'       => $userSubject,
                                'message'       => $userMessage,
                            ];
                            sendEmail($userEmailData);
                        }

                        // if($authRole == "Club"){ }

                        if ($authRole == "Super Admin") {
                            // pr($representatorInfo);
                            // echo '>>>>>>>>>>> representatorInfo->parent_id >>> ' . $representatorInfo->parent_id;
                            $authInfo = getUserByID($representatorInfo->parent_id);
                            // pr($authInfo); exit;

                            // Admin email to club/scout if role is updated by super admin
                            $adminSubject = 'Representator Role updated on Succer You Sports AG';
                            $adminMessage = '';
                            $adminMessage .= 'Dear ' . $authInfo->first_name . ' ' . $authInfo->last_name . ',';
                            $adminMessage .= '<br>Super Admin has updated  ' . $representatorName . ' role on Succer You Sports AG';

                            $adminEmailData = [
                                'fromEmail'     => FROM_EMAIL,
                                'fromName'      => FROM_NAME,
                                'toEmail'       => $authInfo->email,
                                'subject'       => $adminSubject,
                                'message'       => $adminMessage,
                            ];
                            sendEmail($adminEmailData);

                            $userSubject = 'Your Role updated on Succer You Sports AG';
                            $userMessage = '';
                            $userMessage .= 'Dear ' . $representatorName . ',';
                            $userMessage .= '<br>Your role has been updated by Super admin on Succer You Sports AG';

                            $userEmailData = [
                                'fromEmail'     => FROM_EMAIL,
                                'fromName'      => FROM_NAME,
                                'toEmail'       => $representatorInfo->email,
                                'subject'       => $userSubject,
                                'message'       => $userMessage,
                            ];
                            sendEmail($userEmailData);
                        }
                    }
                    // updated_representator_role
                    $activity_data = array();
                    $languageService = \Config\Services::language();
                    $languages = ['en', 'de', 'fr', 'it', 'es', 'pt', 'da', 'sv'];
                    $activity_data = [
                        'user_id'               => auth()->id(),
                        'activity_type_id'      => 3,      // updated
                        'ip'                    => $this->request->getIPAddress()
                    ];
                    foreach ($languages as $lang) {
                        $languageService->setLocale($lang);
                        $translated_message = lang('App.updated_representator_role');
                        $activity_data['activity_' . $lang] = $translated_message;
                    }
                    createActivityLog($activity_data);
                    $response = [
                        "status"    => true,
                        "message"   => lang('App.representatorRoleUpdateSuccess'),
                        "data"      => []
                    ];
                } else {
                    $response = [
                        "status"    => true,
                        "message"   => lang('App.representatorRoleUpdateFailed'),
                        "data"      => []
                    ];
                }
            }
        } else {
            $response = [
                "status"    => false,
                "message"   => lang('App.permissionDenied'),
                "data"      => []
            ];
        }

        return $this->respondCreated($response);
    }

    public function deleteRepresentator($user_id = null)
    {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        if (empty($this->request->getVar("lang"))) {
            $currentUserLang = currentUserLang(auth()->id());
            if (isset($currentUserLang) && !empty($currentUserLang->slug)) {
                $currentLang = $currentUserLang->slug;
            }
        }
        $this->request->setLocale(getLanguageCode($currentLang));
        // if (auth()->user()->inGroup('superadmin', 'superadmin-representator') && (auth()->user()->can('admin.access') || auth()->user()->can('transfer.ownership'))) {

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            // Get the User Provider (UserModel by default)
            $users = auth()->getProvider();
            $del_res = $users->delete($user_id);

            if ($del_res == true) {

                $activity_data = [
                    'user_id'               => auth()->id(),
                    'activity_type_id'      => 3,      // deleted
                    'activity'              => 'deleted user',
                    'old_data'              => $user_id,
                    'ip'                    => $this->request->getIPAddress()
                ];
                createActivityLog($activity_data);

                // Return a success message as a JSON response
                $response = [
                    "status"    => true,
                    "message"   => lang('App.delete_user_success'),
                    "data"      => []
                ];
            } else {
                $response = [
                    "status"    => false,
                    "message"   => lang('App.invalid_userID'),
                    "data"      => []
                ];
            }
        } else {
            $response = [
                "status"    => false,
                "message"   => lang('App.permissionDenied'),
                "data"      => []
            ];
        }

        return $this->respondCreated($response);
    }

    public function getRepresentators($userId = null)
    {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        if (empty($this->request->getVar("lang"))) {
            $currentUserLang = currentUserLang(auth()->id());
            if (isset($currentUserLang) && !empty($currentUserLang->slug)) {
                $currentLang = $currentUserLang->slug;
            }
        }
        $this->request->setLocale(getLanguageCode($currentLang));

        $adminId = $userId ?? auth()->id();

        $imagePath = base_url() . 'uploads/';
        $flagPath =  base_url() . 'uploads/logos/';

        $builder = $this->db->table('users u');

        $builder->select('
                    u.*,
                    user_roles.role_name as role_name,
                    languages.language as language,
                    domains.domain_name as user_domain,
                    domains.location as user_location,
                    auth.secret as email,
                    apu.permission as permission,

                    (SELECT
                        JSON_OBJECTAGG(
                                um.meta_key, um.meta_value
                        )
                        FROM user_meta um
                        WHERE um.user_id = u.id
                    ) AS meta
                ');

        $builder->join('auth_identities auth', 'auth.user_id = u.id AND type = "email_password"', 'LEFT');
        $builder->join('user_roles', 'user_roles.id = u.role', 'LEFT');
        $builder->join('languages', 'languages.id = u.lang', 'LEFT');
        $builder->join('domains', 'domains.id = u.user_domain', 'LEFT');
        $builder->join('auth_permissions_users apu', 'apu.user_id = u.id', 'LEFT');

        $builder->where('u.role', 5);
        $builder->where('u.deleted_at IS NULL');
        $builder->where('u.parent_id', $adminId);
        // $builder->orWhere('u.id', $adminId);
        $builder->orderBy('
                CASE
                    WHEN u.parent_id IS NULL THEN 1
                    ELSE 2
                END
            ', '', false);

        $builder->orderBy('u.id', 'DESC');



        $query   = $builder->get();
        $representators = $query->getResultArray();

        // echo '>>>>>>>>>> getLastQuery >>>>> ' . $this->d b->getLastQuery();
        if (isset($userId) && !empty($userId)) {
            $current_user_id = $userId;
        } else {
            $current_user_id = auth()->id();
        }
        $currentUser = $this->userProfile($current_user_id);
        if (!empty($currentUser)) {
            $currentUser = json_decode($currentUser->getBody(), true);
        }
        # echo '<pre>'; print_r($currentUser); die;
        if (isset($currentUser['data']) && !empty($currentUser['data']['user_data'])) {
            $currentUser = $currentUser['data']['user_data'];
        } else {
            $currentUser = [];
        }
        if ($representators) {
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => [
                    'representators'    => $representators,
                    'currentUser'       => $currentUser,
                    'uploads_path'      => $imagePath,
                    'flag_path'         => $flagPath,
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

    ///////////////////////// ADMIN APIs //////////////////////////////

    // Get API to send User profile detail
    public function userProfile($user_id = null)
    {
        $response = [];
        // echo '>>>> profil e>>>> ' ; exit;
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        if (empty($this->request->getVar("lang"))) {
            $currentUserLang = currentUserLang(auth()->id());
            if (isset($currentUserLang) && !empty($currentUserLang->slug)) {
                $currentLang = $currentUserLang->slug;
            }
        }
        $this->request->setLocale(getLanguageCode($currentLang));

        $userMetaObject = new UserMetaDataModel();

        $id = $user_id ?? auth()->id();

        $imagePath = base_url() . 'uploads/';
        $flagPath =  base_url() . 'uploads/logos/';

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
                                    "country_id", c.id,
                                    "country_name", c.country_name,
                                    "flag_path", CONCAT("' . $flagPath . '", c.country_flag)
                                )
                            )
                            FROM user_nationalities un
                            LEFT JOIN countries c ON c.id = un.country_id
                            WHERE un.user_id = users.id) AS user_nationalities,

                            t.id as team_id,
                            t.team_type as team_type,
                            um2.meta_value as current_club_name,
                            CONCAT("' . $imagePath . '", um3.meta_value) as club_logo_path,
                            CONCAT("' . $flagPath . '", c3.country_flag) as current_club_country_path,

                            l.league_name as league_name,
                            l.league_logo as league_logo,
                            CONCAT("' . $flagPath . '", l.league_logo) as league_logo_path,

                            c1.id as int_player_country_id,
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

                            cb.club_name as pre_current_club_name,
                            CONCAT("' . $imagePath . '", cb.club_logo) as pre_current_club_logo_path,
                            CONCAT("' . $flagPath . '", c2.country_flag) as pre_current_club_country_path,
                            apu.permission as permission
                        ');


        //     (SELECT JSON_ARRAYAGG(
        //         JSON_OBJECT(
        //             "package_name", p.title ,
        //             "interval", pd.interval,
        //             "stripe_subscription_id", us.stripe_subscription_id
        //         )
        //     )
        //     FROM user_subscriptions us
        //     INNER JOIN package_details pd ON pd.id = us.package_id
        //     INNER JOIN packages p ON p.id = pd.id
        //     WHERE us.user_id = users.id
        //     AND us.status = "active"
        // ) As active_subscriptions,

        $builder->join('auth_identities auth', 'auth.user_id = users.id AND type = "email_password"', 'LEFT');
        $builder->join('user_roles', 'user_roles.id = users.role', 'LEFT');
        $builder->join('languages', 'languages.id = users.lang', 'LEFT');
        $builder->join('domains', 'domains.id = users.user_domain', 'LEFT');
        $builder->join('payment_methods pm', 'pm.user_id = users.id AND pm.is_default = 1', 'LEFT');

        $builder->join('(SELECT user_id, status, plan_period_start, plan_period_end, plan_interval, coupon_used FROM user_subscriptions WHERE id =  (SELECT MAX(id)  FROM user_subscriptions WHERE user_id = ' . $id . ')) us', 'us.user_id = users.id', 'LEFT');

        // to get club details
        $builder->join('club_players cp', 'cp.player_id = users.id AND cp.status = "active"', 'LEFT');
        $builder->join('teams t', 't.id = cp.team_id', 'LEFT');
        $builder->join('user_meta um2', 'um2.user_id = t.club_id AND um2.meta_key = "club_name"', 'LEFT');
        $builder->join('countries c3', 'c3.id = t.country_id', 'LEFT');

        $builder->join('user_meta um3', 'um3.user_id = t.club_id AND um3.meta_key = "profile_image"', 'LEFT');

        // to get club's league
        $builder->join('league_clubs lc', 'lc.club_id = t.club_id', 'LEFT');
        $builder->join('leagues l', 'l.id = lc.league_id', 'LEFT');

        $builder->join('user_meta um4', 'um4.user_id = users.id AND um4.meta_key = "international_player"', 'LEFT');
        $builder->join('countries c1', 'c1.id = um4.meta_value', 'LEFT');

        // additoinal current club of player/talent
        $builder->join('user_meta um5', 'um5.user_id = users.id AND um5.meta_key = "pre_club_id"', 'LEFT');
        $builder->join('clubs cb', 'cb.id = um5.meta_value', 'LEFT');
        $builder->join('countries c2', 'c2.id = cb.country_id', 'LEFT');
        $builder->join('auth_permissions_users apu', 'apu.user_id = users.id', 'LEFT');

        // get positions
        // $builder->join('player_positions pp', 'pp.user_id = users.id AND pp.user_id = '.$id, 'LEFT');
        // $builder->join('positions p', 'p.id = pp.position_id', 'LEFT');

        $builder->where('users.id', $id);
        // $builder->groupBy('users.id');
        $userData   = $builder->get()->getRow();
        // echo '>>>>>>>> getLastQuery >>>>> '. $this->db->getLastQuery(); //exit;

        $prevSql = 'SELECT id, role FROM users WHERE id < ' . $id . ' ORDER BY id DESC LIMIT 1';
        $prevQuery = $this->db->query($prevSql);
        $prevRes   = $prevQuery->getRow();

        $nextSql = 'SELECT id, role FROM users WHERE id > ' . $id . ' ORDER BY id ASC LIMIT 1';
        $nextQuery = $this->db->query($nextSql);
        $nextRes   = $nextQuery->getRow();

        $totalSql = 'SELECT count(*) as total_user FROM users';
        $totalQuery = $this->db->query($totalSql);
        $totalRes   = $totalQuery->getRow();

        $currentSql = 'SELECT * FROM ( SELECT ROW_NUMBER() OVER (ORDER BY id) AS serial_number, id FROM users ) AS numbered_records WHERE id = ' . $id;
        $currentQuery = $this->db->query($currentSql);
        $currentRes   = $currentQuery->getRow();
        // echo '>>>>>>>>>> $totalQuery >> ' . $totalRes->total_user ;
        // pr($totalQuery);


        if ($userData) {

            // get user subscriptions details
            $userSubscriptionController = new UserSubscriptionController();
            $getActivePackages = $userSubscriptionController->getUserActivePackages($id);
            $userData->active_subscriptions = $getActivePackages;

            // check if user is in favorites of logged user
            if ($user_id != null) {
                $userData->marked_favorite = false;
                $favorite = $this->favoriteModel->where('user_id', auth()->id())->where('favorite_id', $user_id)->first();
                if ($favorite) {
                    $userData->marked_favorite = true;
                }
            }

            // add user's meta data to user info
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
                    /* By amrit */
                    if ($data['meta_key'] == 'birth_country') {
                        $countryModel = new CountryModel();
                        $countries = $countryModel->select('country_name, country_code, country_flag')->where('id', $data['meta_value'])->first();
                        $birth_country_logo = 'flag-1.png';
                        if (isset($countries) && !empty($countries['country_flag'])) {
                            $birth_country_logo = $countries['country_flag'];
                        }
                        $meta['birth_country_flag'] = $flagPath . $birth_country_logo;
                    }
                    /* By amrit */
                }
            }

            $userData->email = $userData->email;
            $userData->meta = $meta;

            // Return a success message as a JSON response
            $response = [
                "status"    => true,
                "message"   => lang('User found'),
                "data"      => [
                    'user_data' => $userData,
                    'pagination'    => [
                        'total'     => $totalRes->total_user,
                        'prev'      => [
                            'id'    => ($prevRes ? $prevRes->id : null),
                            'role'  => ($prevRes ? $prevRes->role : null),
                        ],
                        // 'prev'      => $prevRes->id,
                        'next'      => [
                            'id'    => ($nextRes ? $nextRes->id : null),
                            'role'  => ($nextRes ? $nextRes->role : null),

                        ],
                        'is_first'   => (!$prevRes ? true : false),
                        'is_last'   => (!$nextRes ? true : false),
                        // 'next'      => $nextRes->id,
                        'current'   => $currentRes->serial_number,
                    ]
                ]
            ];
        } else {
            // Invalid token
            $response = [
                "status"    => false,
                "message"   => lang('App.invalid_link'),
                "data"      => []
            ];
        }

        return $this->respondCreated($response);
    }

    // Post API to update user profile by admin
    public function updateProfileAdmin_old($id = null)
    {
        $userMetaObject = new UserMetaDataModel();
        $response = [];

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $user_id = $id;

        if ($this->request->getVar('user') && count($this->request->getVar('user')) > 0) {

            $save_data = [];
            foreach ($this->request->getVar('user') as $key => $value) {

                // check if meta key exist
                $userInfo = $userMetaObject->where('user_id', $user_id)->where('meta_key', $key)->first();

                $save_data['id'] = '';
                if ($userInfo) {
                    $save_data['id'] = $userInfo['id'];
                }
                $save_data['user_id'] = $user_id;
                $save_data['meta_key'] = $key;

                // Check if $value is an array and serialize it
                if (is_array($value)) {
                    $save_data['meta_value'] = serialize($value);
                } else {
                    $save_data['meta_value'] = $value;
                }

                // save data
                $res = $userMetaObject->save($save_data);
            }

            if ($res) {
                $response = [
                    "status"    => true,
                    "message"   => lang('App.profile_updatedSuccess'),
                    "data"      => []
                ];
            } else {
                $response = [
                    "status"    => false,
                    "message"   => lang('App.profile_updatedfailure'),
                    "data"      => []
                ];
            }
        }

        return $this->respondCreated($response);
    }

    // to update general information of user by admin
    // post
    public function updateGeneralInfoAdmin($id = null)
    {

        $userMetaObject = new UserMetaDataModel();

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $userId = $id;

        $save_data = [];
        foreach ($this->request->getVar('user') as $key => $value) {

            // check if meta key exist
            $userInfo = $userMetaObject->where('user_id', $userId)->where('meta_key', $key)->first();

            $save_data['id'] = '';
            if ($userInfo) {
                $save_data['id'] = $userInfo['id'];
            }
            $save_data['user_id'] = $userId;
            $save_data['meta_key'] = $key;
            $save_data['meta_value'] = $value;

            // save data
            $res = $userMetaObject->save($save_data);
        }

        if ($res) {
            $response = [
                "status"    => true,
                "message"   => lang('App.profile_updatedSuccess'),
                "data"      => []
            ];
        } else {
            $response = [
                "status"    => false,
                "message"   => lang('App.profile_updatedfailure'),
                "data"      => []
            ];
        }

        return $this->respondCreated($response);
    }

    // to update market value of user by admin
    // post
    public function playerMarketValueAdmin($id = null)
    {

        $userMetaObject = new UserMetaDataModel();

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $userId = $id;

        $save_data = [];
        foreach ($this->request->getVar('market_value') as $key => $value) {

            // check if meta key exist
            $userInfo = $userMetaObject->where('user_id', $userId)->where('meta_key', $key)->first();

            $save_data['id'] = '';
            if ($userInfo) {
                $save_data['id'] = $userInfo['id'];
            }
            $save_data['user_id'] = $userId;
            $save_data['meta_key'] = $key;
            $save_data['meta_value'] = $value;

            // save data
            $res = $userMetaObject->save($save_data);
        }

        if ($res) {
            $response = [
                "status"    => true,
                "message"   => 'Market Value updated',
                "data"      => []
            ];
        } else {
            $response = [
                "status"    => false,
                "message"   => 'Market Value not updated. Please try again',
                "data"      => []
            ];
        }

        return $this->respondCreated($response);
    }

    // Get Player's cover photo in admin
    // GET API
    public function getCoverImageAdmin($userId = null)
    {

        $userMetaObject = new UserMetaDataModel();

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $userData = $userMetaObject->where('user_id', $userId)->where('meta_key', 'cover_image')->first();
        $cover_image =  base_url() . 'uploads/' . $userData['meta_value'];
        $userData['cover_image_path'] = $cover_image;

        if ($userData) {

            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => ['userData' => $userData]
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

    public function uploadCoverImageAdmin($userId = null)
    {

        $userMetaObject = new UserMetaDataModel();

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        if (empty($this->request->getVar("lang"))) {
            $currentUserLang = currentUserLang(auth()->id());
            if (isset($currentUserLang) && !empty($currentUserLang->slug)) {
                $currentLang = $currentUserLang->slug;
            }
        }
        $this->request->setLocale(getLanguageCode($currentLang));

        // if (auth()->user()->inGroup('superadmin', 'superadmin-representator') && (auth()->user()->can('admin.access') || auth()->user()->can('admin.edit') || auth()->user()->can('transfer.ownership'))) {

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            $validationRule = [
                'cover_image' => [
                    'label' => 'Cover Image',
                    'rules' => [
                        'uploaded[cover_image]',
                        'is_image[cover_image]',
                        'mime_in[cover_image,image/jpg,image/jpeg,image/gif,image/png,image/webp]',
                        'max_size[cover_image,1000]',
                        //'max_dims[cover_image,1024,768]',
                    ],
                    'errors' => [
                        'uploaded'  => lang('App.uploaded', ['file' => 'jpg, jpeg, gif, png, webp']),
                        'is_image'  => lang('App.is_image', ['file' => 'jpg, jpeg, gif, png, webp']),
                        'mime_in'   => lang('App.mime_in', ['file' => 'jpg, jpeg, gif, png, webp']),
                        'max_size'  => lang('App.max_size', ['siz' => '1000']),
                        'max_dims'  => lang('App.max_dims'),
                    ],
                ],
            ];

            if (! $this->validateData([], $validationRule)) {
                $errors = $this->validator->getErrors();

                $response = [
                    "status"    => false,
                    "message"   => lang('App.profile_updatedfailure'),
                    "data"      => ['errors' => $errors]
                ];
            } else {

                $cover_image = $this->request->getFile('cover_image');

                if (! $cover_image->hasMoved()) {
                    $filepath = WRITEPATH . 'uploads/' . $cover_image->store('');

                    $userInfo = $userMetaObject->where('user_id', $userId)->where('meta_key', 'cover_image')->first();

                    $save_data['id'] = '';
                    if ($userInfo) {
                        $save_data['id'] = $userInfo['id'];
                    }
                    $save_data['user_id'] = $userId;
                    $save_data['meta_key'] = 'cover_image';
                    $save_data['meta_value'] = $cover_image->getName();
                    // save data
                    $res = $userMetaObject->save($save_data);

                    $data = ['uploaded_fileinfo' => $cover_image->getName()];
                    $activity_data = array();
                    $languageService = \Config\Services::language();
                    $languages = ['en', 'de', 'fr', 'it', 'es', 'pt', 'da', 'sv'];
                    $activity_data = [
                        'user_id'               => auth()->id(),
                        'activity_type_id'      => 7, // Register activity type
                        'ip'                    => $this->request->getIPAddress()
                    ];
                    foreach ($languages as $lang) {
                        $languageService->setLocale($lang);
                        $translated_message = lang('App.uploaded_new_cover_image', ['userName' => '[USER_NAME_' . $userId . ']', 'imageName' => $save_data['meta_value']]);
                        $activity_data['activity_' . $lang] = $translated_message;
                    }
                    createActivityLog($activity_data);

                    $response = [
                        "status"    => true,
                        "message"   => lang('App.profile_updatedSuccess'),
                        "data"      => $data
                    ];
                } else {
                    $data = ['errors' => 'The file has already been moved.'];
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.profile_updatedfailure'),
                        "data"      => $data
                    ];
                }
            }
        } else {
            $response = [
                "status"    => false,
                "message"   => lang('App.permissionDenied'),
                "data"      => []
            ];
        }
        return $this->respondCreated($response);
    }

    public function deleteCoverImageAdmin($userId = null)
    {

        $userMetaObject = new UserMetaDataModel();

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        if (empty($this->request->getVar("lang"))) {
            $currentUserLang = currentUserLang(auth()->id());
            if (isset($currentUserLang) && !empty($currentUserLang->slug)) {
                $currentLang = $currentUserLang->slug;
            }
        }
        $this->request->setLocale(getLanguageCode($currentLang));

        // if (auth()->user()->inGroup('superadmin', 'superadmin-representator') && (auth()->user()->can('admin.access') || auth()->user()->can('transfer.ownership'))) {

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            $userData = $userMetaObject->where('user_id', $userId)->where('meta_key', 'cover_image')->first();

            if ($userData) {
                if (file_exists(WRITEPATH . 'uploads/' . $userData['meta_value'])) {
                    unlink(WRITEPATH . 'uploads/' . $userData['meta_value']);
                    $res = $userMetaObject->delete($userData['id']);
                }

                if ($res) {
                    // $activity = lang('App.deleted_cover_image', ['userName' => '[USER_NAME_' . $userId . ']', 'imageName' => $userData['meta_value']]);
                    // // create Activity log
                    // $activity_data = [
                    //     'user_id'               => auth()->id(),
                    //     'activity_on_id'        => $userId,
                    //     'activity_type_id'      => 3,      // deleted
                    //     'activity'              => $activity,
                    //     'ip'                    => $this->request->getIPAddress()
                    // ];
                    // createActivityLog($activity_data);
                    $activity_data = array();
                    $languageService = \Config\Services::language();
                    $languages = ['en', 'de', 'fr', 'it', 'es', 'pt', 'da', 'sv'];
                    $activity_data = [
                        'user_id'               => auth()->id(),
                        'activity_type_id'      => 7, // Register activity type
                        'ip'                    => $this->request->getIPAddress()
                    ];
                    foreach ($languages as $lang) {
                        $languageService->setLocale($lang);
                        $translated_message = lang('App.deleted_cover_image', ['userName' => '[USER_NAME_' . $userId . ']', 'imageName' => $userData['meta_value']]);
                        $activity_data['activity_' . $lang] = $translated_message;
                    }
                    createActivityLog($activity_data);
                    $response = [
                        "status"    => true,
                        "message"   => 'Cover image deleted successfully',
                        "data"      => []
                    ];
                } else {
                    $response = [
                        "status"    => false,
                        "message"   => 'Cover image not deleted',
                        "data"      => []
                    ];
                }
            } else {
                $response = [
                    "status"    => false,
                    "message"   => lang('App.noDataFound'),
                    "data"      => []
                ];
            }
        } else {
            $response = [
                "status"    => false,
                "message"   => lang('App.permissionDenied'),
                "data"      => []
            ];
        }
        return $this->respondCreated($response);
    }

    public function getProfileImageAdmin($userId = null)
    {

        $userMetaObject = new UserMetaDataModel();

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $userData = $userMetaObject->where('user_id', $userId)->where('meta_key', 'profile_image')->first();
        $profile_image =  base_url() . 'uploads/' . $userData['meta_value'];
        $userData['profile_image_path'] = $profile_image;

        if ($userData) {

            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => ['userData' => $userData]
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

    public function uploadProfileImageAdmin($userId = null)
    {

        $userMetaObject = new UserMetaDataModel();

        // if (auth()->user()->inGroup('superadmin', 'superadmin-representator') && (auth()->user()->can('admin.access') || auth()->user()->can('admin.edit') || auth()->user()->can('transfer.ownership'))) {

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
            if (empty($this->request->getVar("lang"))) {
                $currentUserLang = currentUserLang(auth()->id());
                if (isset($currentUserLang) && !empty($currentUserLang->slug)) {
                    $currentLang = $currentUserLang->slug;
                }
            }
            $this->request->setLocale(getLanguageCode($currentLang));

            $validationRule = [
                'profile_image' => [
                    'label' => 'Profile Image',
                    'rules' => [
                        'uploaded[profile_image]',
                        'is_image[profile_image]',
                        'mime_in[profile_image,image/jpg,image/jpeg,image/gif,image/png,image/webp]',
                        'max_size[profile_image,1000]',
                        //'max_dims[profile_image,1024,768]',
                    ],
                    'errors' => [
                        'uploaded'  => lang('App.uploaded', ['file' => 'jpg, jpeg, gif, png, webp']),
                        'is_image'  => lang('App.is_image', ['file' => 'jpg, jpeg, gif, png, webp']),
                        'mime_in'   => lang('App.mime_in', ['file' => 'jpg, jpeg, gif, png, webp']),
                        'max_size'  => lang('App.max_size', ['siz' => '1000']),
                        'max_dims'  => lang('App.max_dims'),
                    ],
                ],
            ];

            if (! $this->validateData([], $validationRule)) {
                $errors = $this->validator->getErrors();

                $response = [
                    "status"    => false,
                    "message"   => lang('App.profile_updatedfailure'),
                    "data"      => ['errors' => $errors]
                ];
            } else {

                $is_uploaded = removeImageBG($this->request->getFile('profile_image'));
                //$profile_image = $this->request->getFile('profile_image');        // old code

                //if (! $profile_image->hasMoved()) {       // old code
                if ($is_uploaded && $is_uploaded['status'] == "success") {

                    //$filepath = WRITEPATH . 'uploads/' . $profile_image->store('');       // old code

                    $userInfo = $userMetaObject->where('user_id', $userId)->where('meta_key', 'profile_image')->first();

                    $save_data['id'] = '';
                    if ($userInfo) {
                        $save_data['id'] = $userInfo['id'];
                    }
                    $save_data['user_id'] = $userId;
                    $save_data['meta_key'] = 'profile_image';
                    //$save_data['meta_value'] = $profile_image->getName();     // old Code
                    $save_data['meta_value'] = $is_uploaded['fileName'];

                    // save data
                    $res = $userMetaObject->save($save_data);

                    //$data = ['uploaded_fileinfo' => $profile_image->getName()];        // old Code
                    $data = ['uploaded_fileinfo' => $is_uploaded['fileName']];

                    // create Activity log
                    // $activity = lang('App.updated_cover_image', ['imageName' => $data['uploaded_fileinfo']]);
                    // $activity = "successfully updated [USER_NAME_" . $userId . "]'s Profile Image (" . $is_uploaded['fileName'] . ").";
                    // $activity_data = [
                    //     'user_id'               => auth()->id(),
                    //     'activity_on_id'        => $userId,
                    //     'activity_type_id'      => 2,      // updated
                    //     'activity'              => $activity,
                    //     'ip'                    => $this->request->getIPAddress()
                    // ];
                    // createActivityLog($activity_data);
                    $activity_data = array();
                    $languageService = \Config\Services::language();
                    $languages = ['en', 'de', 'fr', 'it', 'es', 'pt', 'da', 'sv'];
                    $activity_data = [
                        'user_id'               => auth()->id(),
                        'activity_type_id'      => 3,      // updated
                        'ip'                    => $this->request->getIPAddress()
                    ];
                    foreach ($languages as $lang) {
                        $languageService->setLocale($lang);
                        $translated_message = lang('App.profile_image_updated', ['userId' => $userId, 'imageName' => $data['uploaded_fileinfo']]);
                        $activity_data['activity_' . $lang] = $translated_message;
                    }
                    createActivityLog($activity_data);

                    $response = [
                        "status"    => true,
                        "message"   => lang('App.profile_updatedSuccess'),
                        "data"      => $data
                    ];
                } else {
                    $data = ['errors' => 'The file has already been moved.'];
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.profile_updatedfailure'),
                        "data"      => $data
                    ];
                }
            }
        } else {
            $response = [
                "status"    => false,
                "message"   => lang('App.permissionDenied'),
                "data"      => []
            ];
        }
        return $this->respondCreated($response);
    }

    public function deleteProfileImageAdmin($userId = null)
    {

        $userMetaObject = new UserMetaDataModel();

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        if (empty($this->request->getVar("lang"))) {
            $currentUserLang = currentUserLang(auth()->id());
            if (isset($currentUserLang) && !empty($currentUserLang->slug)) {
                $currentLang = $currentUserLang->slug;
            }
        }
        $this->request->setLocale(getLanguageCode($currentLang));

        // if (auth()->user()->inGroup('superadmin', 'superadmin-representator') && (auth()->user()->can('admin.access') || auth()->user()->can('transfer.ownership'))) {

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            $userData = $userMetaObject->where('user_id', $userId)->where('meta_key', 'profile_image')->first();

            if ($userData) {
                if (file_exists(WRITEPATH . 'uploads/' . $userData['meta_value'])) {
                    unlink(WRITEPATH . 'uploads/' . $userData['meta_value']);
                    $res = $userMetaObject->delete($userData['id']);
                }

                if ($res) {

                    // create Activity log
                    // $activity = lang('App.deleted_profile_image', ['imageName' => $userData['meta_value']]);
                    // $activity_data = [
                    //     'user_id'               => auth()->id(),
                    //     'activity_on_id'        => $userId,
                    //     'activity_type_id'      => 3,      // deleted
                    //     'activity'              => $activity,
                    //     'ip'                    => $this->request->getIPAddress()
                    // ];
                    // createActivityLog($activity_data);
                    $activity_data = array();
                    $languageService = \Config\Services::language();
                    $languages = ['en', 'de', 'fr', 'it', 'es', 'pt', 'da', 'sv'];
                    $activity_data = [
                        'user_id'               => auth()->id(),
                        'activity_type_id'      => 3,      // updated
                        'ip'                    => $this->request->getIPAddress()
                    ];
                    foreach ($languages as $lang) {
                        $languageService->setLocale($lang);
                        $translated_message = lang('App.deleted_profile_image', ['imageName' => $userData['meta_value']]);
                        $activity_data['activity_' . $lang] = $translated_message;
                    }
                    createActivityLog($activity_data);
                    $response = [
                        "status"    => true,
                        "message"   => 'Profile image deleted successfully',
                        "data"      => []
                    ];
                } else {
                    $response = [
                        "status"    => false,
                        "message"   => 'Profile image not deleted',
                        "data"      => []
                    ];
                }
            } else {
                $response = [
                    "status"    => false,
                    "message"   => lang('App.noDataFound'),
                    "data"      => []
                ];
            }
        } else {
            $response = [
                "status"    => false,
                "message"   => lang('App.permissionDenied'),
                "data"      => []
            ];
        }
        return $this->respondCreated($response);
    }


    // Club - get club History
    public function getClubHistory($id = null)
    {

        $userMetaObject = new UserMetaDataModel();

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $userID = $id ?? auth()->id();

        // check if representator
        if (in_array(auth()->user()->role, REPRESENTATORS_ROLES)) {
            $authUser = auth()->user();
            $userID = $authUser->parent_id;
        }

        $clubHistory = $userMetaObject->where('user_id', $userID)->where('meta_key', 'club_history')->first();

        if ($clubHistory) {

            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => ['club_history' => $clubHistory]
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


    // club - add club History Admin
    public function addClubHistory($id = null)
    {

        $userMetaObject = new UserMetaDataModel();

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        if (empty($this->request->getVar("lang"))) {
            $currentUserLang = currentUserLang(auth()->id());
            if (isset($currentUserLang) && !empty($currentUserLang->slug)) {
                $currentLang = $currentUserLang->slug;
            }
        }
        $this->request->setLocale(getLanguageCode($currentLang));

        // if (auth()->user()->inGroup('superadmin', 'superadmin-representator') && (auth()->user()->can('admin.access') || auth()->user()->can('transfer.ownership'))) {

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            $rules = [
                'club_history'        => 'required',
            ];

            $validation = \Config\Services::validation();

            // Validate text fields
            if (!$this->validate($rules)) {
                $errors = $validation->getErrors();
                $response = [
                    "status"    => false,
                    "message"   => lang('App.provideValidData'),
                    "data"      => ['errors' => $errors]
                ];
            } else {

                $userID = $id ?? auth()->id();

                // check if representator
                if (in_array(auth()->user()->role, REPRESENTATORS_ROLES)) {
                    $authUser = auth()->user();
                    $userID = $authUser->parent_id;
                }

                $clubHistory = $userMetaObject->where('user_id', $userID)->where('meta_key', 'club_history')->first();
                if (!$clubHistory) {

                    $save_data = [
                        'meta_key'      => 'club_history',
                        'meta_value'    => $this->request->getVar("club_history"),
                    ];

                    // save data
                    if ($userMetaObject->save($save_data)) {

                        // create Activity log
                        // $activity = lang('App.added_company_history');
                        // $activity_data = [
                        //     'user_id'               => auth()->id(),
                        //     'activity_on_id'        => $userID,
                        //     'activity_type_id'      => 1,        // created
                        //     'activity'              => $activity,
                        //     'ip'                    => $this->request->getIPAddress()
                        // ];
                        // createActivityLog($activity_data);
                        $activity_data = array();
                        $languageService = \Config\Services::language();
                        $languages = ['en', 'de', 'fr', 'it', 'es', 'pt', 'da', 'sv'];
                        $activity_data = [
                            'user_id'               => auth()->id(),
                            'activity_type_id'      => 1,      // updated
                            'ip'                    => $this->request->getIPAddress()
                        ];
                        foreach ($languages as $lang) {
                            $languageService->setLocale($lang);
                            $translated_message = lang('App.added_company_history');
                            $activity_data['activity_' . $lang] = $translated_message;
                        }
                        createActivityLog($activity_data);

                        $response = [
                            "status"    => true,
                            "message"   => lang('App.clubHistoryAdded'),
                            "data"      => []
                        ];
                    } else {
                        $response = [
                            "status"    => false,
                            "message"   => lang('App.clubHistoryAddFailed'),
                            "data"      => []
                        ];
                    }
                } else {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.recordExist'),
                        "data"      => []
                    ];
                }
            }
        } else {

            $response = [
                "status"    => false,
                "message"   => lang('App.permissionDenied'),
                "data"      => []
            ];
        }
        return $this->respondCreated($response);
    }

    // Club - update Club History
    public function updateClubHistory($id = null)
    {

        $userMetaObject = new UserMetaDataModel();

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        // if (auth()->user()->inGroup('superadmin', 'superadmin-representator') && (auth()->user()->can('admin.access') || auth()->user()->can('admin.edit') || auth()->user()->can('transfer.ownership'))) {

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP) || hasAccess(auth()->user(), EDITOR_ACCESS)) {

            $rules = [
                'club_history'        => 'required',
            ];

            $validation = \Config\Services::validation();

            // Validate text fields
            if (!$this->validate($rules)) {
                $errors = $validation->getErrors();
                $response = [
                    "status"    => false,
                    "message"   => lang('App.provideValidData'),
                    "data"      => ['errors' => $errors]
                ];
            } else {

                $userID = $id ?? auth()->id();

                // check if representator
                if (in_array(auth()->user()->role, REPRESENTATORS_ROLES)) {
                    $authUser = auth()->user();
                    $userID = $authUser->parent_id;
                }

                $clubHistory = $userMetaObject->where('user_id', $userID)->where('meta_key', 'club_history')->first();

                if ($clubHistory) {

                    $save_data = [
                        'id'            => $clubHistory['id'],
                        'meta_value'    => $this->request->getVar("club_history"),
                    ];

                    // save data
                    $res = $userMetaObject->save($save_data);

                    if ($userMetaObject->save($save_data)) {

                        $response = [
                            "status"    => true,
                            "message"   => lang('App.clubHistoryUpdated'),
                            "data"      => []
                        ];
                    } else {
                        $response = [
                            "status"    => false,
                            "message"   => lang('App.clubHistoryUpdateFailed'), //lang('App.recordNotExist')
                            "data"      => []
                        ];
                    }
                } else {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.recordNotExist'),
                        "data"      => []
                    ];
                }
            }
        } else {
            $response = [
                "status"    => false,
                "message"   => lang('App.permissionDenied'),
                "data"      => []
            ];
        }

        return $this->respondCreated($response);
    }

    // Club - get Club History
    public function getClubHistoryAdmin($id = null)
    {

        $userMetaObject = new UserMetaDataModel();

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $clubHistory = $userMetaObject->where('user_id', $id)->where('meta_key', 'club_history')->first();

        if ($clubHistory) {

            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => ['club_history' => $clubHistory]
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

    // Club - update Club History Admin
    public function updateClubHistoryAdmin($id = null)
    {

        $userMetaObject = new UserMetaDataModel();

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        if (empty($this->request->getVar("lang"))) {
            $currentUserLang = currentUserLang(auth()->id());
            if (isset($currentUserLang) && !empty($currentUserLang->slug)) {
                $currentLang = $currentUserLang->slug;
            }
        }
        $this->request->setLocale(getLanguageCode($currentLang));

        // if (auth()->user()->inGroup('superadmin', 'superadmin-representator') && (auth()->user()->can('admin.access') || auth()->user()->can('admin.edit') || auth()->user()->can('transfer.ownership'))) {

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            $rules = [
                'club_history'        => 'required',
            ];

            $validation = \Config\Services::validation();

            // Validate text fields
            if (!$this->validate($rules)) {
                $errors = $validation->getErrors();
                $response = [
                    "status"    => false,
                    "message"   => lang('App.provideValidData'),
                    "data"      => ['errors' => $errors]
                ];
            } else {

                $clubHistory = $userMetaObject->where('user_id', $id)->where('meta_key', 'club_history')->first();

                if ($clubHistory) {

                    $save_data = [
                        'id'            => $clubHistory['id'],
                        //'meta_key'      => 'club_history',
                        'meta_value'    => $this->request->getVar("club_history"),
                    ];

                    // save data
                    // $res = $userMetaObject->save($save_data);

                    if ($userMetaObject->save($save_data)) {
                        $response = [
                            "status"    => true,
                            "message"   => lang('App.clubHistoryUpdated'),
                            "data"      => []
                        ];
                    } else {
                        $response = [
                            "status"    => false,
                            "message"   => lang('App.clubHistoryUpdateFailed'),
                            "data"      => []
                        ];
                    }
                } else {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.recordNotExist'),
                        "data"      => []
                    ];
                }
            }
        } else {
            $response = [
                "status"    => false,
                "message"   => lang('App.permissionDenied'),
                "data"      => []
            ];
        }

        return $this->respondCreated($response);
    }

    // Scout - get company History
    public function getCompanyHistory($id = null)
    {

        $userMetaObject = new UserMetaDataModel();

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        if (empty($this->request->getVar("lang"))) {
            $currentUserLang = currentUserLang(auth()->id());
            if (isset($currentUserLang) && !empty($currentUserLang->slug)) {
                $currentLang = $currentUserLang->slug;
            }
        }
        $this->request->setLocale(getLanguageCode($currentLang));

        $userID = $id ?? auth()->id();

        // check if representator
        if (in_array(auth()->user()->role, REPRESENTATORS_ROLES)) {
            $authUser = auth()->user();
            $userID = $authUser->parent_id;
        }

        $companyHistory = $userMetaObject->where('user_id', $userID)->where('meta_key', 'company_history')->first();

        if ($companyHistory) {

            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => ['company_history' => $companyHistory]
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

    // Scout - add/edit company History Admin
    public function addCompanyHistory($id = null)
    {

        $userMetaObject = new UserMetaDataModel();

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        if (empty($this->request->getVar("lang"))) {
            $currentUserLang = currentUserLang(auth()->id());
            if (isset($currentUserLang) && !empty($currentUserLang->slug)) {
                $currentLang = $currentUserLang->slug;
            }
        }
        $this->request->setLocale(getLanguageCode($currentLang));

        // if (auth()->user()->inGroup('superadmin', 'superadmin-representator') && (auth()->user()->can('admin.access') || auth()->user()->can('transfer.ownership'))) {

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            $userID = $id ?? auth()->id();

            // check if representator
            if (in_array(auth()->user()->role, REPRESENTATORS_ROLES)) {
                $authUser = auth()->user();
                $userID = $authUser->parent_id;
            }

            $rules = [
                'company_history'        => 'required',
            ];

            $validation = \Config\Services::validation();

            // Validate text fields
            if (!$this->validate($rules)) {
                $errors = $validation->getErrors();
                $response = [
                    "status"    => false,
                    "message"   => lang('App.provideValidData'),
                    "data"      => ['errors' => $errors]
                ];
            } else {

                $companyHistory = $userMetaObject->where('user_id', $userID)->where('meta_key', 'company_history')->first();

                $save_data = [
                    'user_id'       => $userID,
                    'meta_key'      => 'company_history',
                    'meta_value'    => $this->request->getVar("company_history"),
                ];

                if (!$companyHistory) {

                    // save data
                    if ($userMetaObject->save($save_data)) {

                        // create Activity log
                        // $activity = lang('App.added_company_history');
                        // $activity_data = [
                        //     'user_id'               => auth()->id(),
                        //     'activity_on_id'        => $userID,
                        //     'activity_type_id'      => 1,        // created
                        //     'activity'              => $activity,
                        //     'ip'                    => $this->request->getIPAddress()
                        // ];
                        // createActivityLog($activity_data);
                        $activity_data = array();
                        $languageService = \Config\Services::language();
                        $languages = ['en', 'de', 'fr', 'it', 'es', 'pt', 'da', 'sv'];
                        $activity_data = [
                            'user_id'               => auth()->id(),
                            'activity_type_id'      => 1,      // updated
                            'ip'                    => $this->request->getIPAddress()
                        ];
                        foreach ($languages as $lang) {
                            $languageService->setLocale($lang);
                            $translated_message = lang('App.added_company_history');
                            $activity_data['activity_' . $lang] = $translated_message;
                        }
                        createActivityLog($activity_data);

                        $response = [
                            "status"    => true,
                            "message"   => lang('App.companyHistoryAdded'),
                            "data"      => []
                        ];
                    } else {
                        $response = [
                            "status"    => false,
                            "message"   => lang('App.companyHistoryAddFailed'),
                            "data"      => []
                        ];
                    }

                } else {

                    // Update existing record
                    if ($userMetaObject->update($companyHistory['id'], $save_data)) {

                        $activity_data = array();
                        $languageService = \Config\Services::language();
                        $languages = ['en', 'de', 'fr', 'it', 'es', 'pt', 'da', 'sv'];
                        $activity_data = [
                            'user_id'               => auth()->id(),
                            'activity_type_id'      => 1,      // updated
                            'ip'                    => $this->request->getIPAddress()
                        ];
                        foreach ($languages as $lang) {
                            $languageService->setLocale($lang);
                            $translated_message = lang('App.added_company_history');
                            $activity_data['activity_' . $lang] = $translated_message;
                        }
                        createActivityLog($activity_data);

                        $response = [
                            "status"    => true,
                            "message"   => lang('App.companyHistoryAdded'),
                            "data"      => []
                        ];
                    } else {
                        $response = [
                            "status"    => false,
                            "message"   => lang('App.companyHistoryAddFailed'),
                            "data"      => []
                        ];
                    }
                }
            }
        } else {
            $response = [
                "status"    => false,
                "message"   => lang('App.permissionDenied'),
                "data"      => []
            ];
        }

        return $this->respondCreated($response);
    }

    // Scout - edit company History Admin
    public function editCompanyHistory($id = null)
    {

        $userMetaObject = new UserMetaDataModel();

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        // if (auth()->user()->inGroup('superadmin', 'superadmin-representator') && (auth()->user()->can('admin.access') || auth()->user()->can('admin.edit') || auth()->user()->can('transfer.ownership'))) {

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP) || hasAccess(auth()->user(), EDITOR_ACCESS)) {

            $userID = $id ?? auth()->id();

            // check if representator
            if (in_array(auth()->user()->role, REPRESENTATORS_ROLES)) {
                $authUser = auth()->user();
                $userID = $authUser->parent_id;
            }

            $rules = [
                'company_history'        => 'required',
            ];

            $validation = \Config\Services::validation();

            // Validate text fields
            if (!$this->validate($rules)) {
                $errors = $validation->getErrors();
                $response = [
                    "status"    => false,
                    "message"   => lang('App.provideValidData'),
                    "data"      => ['errors' => $errors]
                ];
            } else {

                $companyHistory = $userMetaObject->where('user_id', $userID)->where('meta_key', 'company_history')->first();
                if ($companyHistory) {
                    $save_data = [
                        'id'            => $companyHistory['id'],
                        'meta_key'      => 'company_history',
                        'meta_value'    => $this->request->getVar("company_history"),
                    ];

                    // save data
                    if ($userMetaObject->save($save_data)) {
                        $response = [
                            "status"    => true,
                            "message"   => lang('App.companyHistoryUpdated'),
                            "data"      => []
                        ];
                    } else {
                        $response = [
                            "status"    => false,
                            "message"   => lang('App.companyHistoryUpdateFailed'),
                            "data"      => []
                        ];
                    }
                } else {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.recordNotFound'),
                        "data"      => []
                    ];
                }
            }
        } else {
            $response = [
                "status"    => false,
                "message"   => lang('App.permissionDenied'),
                "data"      => []
            ];
        }
        return $this->respondCreated($response);
    }


    // Dashboard APIs to get statistics data

    // get total users
    public function getUsersCountYearly($year = null, $domain_id = null)
    {
        // Raw subquery to create the list of all months
        $monthsSubquery = "
        SELECT 'Jan' AS month, 1 AS month_num UNION ALL
        SELECT 'Feb', 2 UNION ALL
        SELECT 'Mar', 3 UNION ALL
        SELECT 'Apr', 4 UNION ALL
        SELECT 'May', 5 UNION ALL
        SELECT 'Jun', 6 UNION ALL
        SELECT 'Jul', 7 UNION ALL
        SELECT 'Aug', 8 UNION ALL
        SELECT 'Sep', 9 UNION ALL
        SELECT 'Oct', 10 UNION ALL
        SELECT 'Nov', 11 UNION ALL
        SELECT 'Dec', 12
        ";

        // Subquery to count users for each month in 2024
        /* $userDataSubquery = $this->db->table('users')
            ->select('COUNT(id) AS user_count, MONTH(created_at) AS month_num');
            if( $domain_id != null){
                $userDataSubquery->where('user_domain', $domain_id);
            }

            $userDataSubquery->where('YEAR(created_at)', $year)
            ->groupBy('MONTH(created_at)')
            ->getCompiledSelect(); */

        $builder = $this->db->table('users');
        $builder->select('COUNT(id) AS user_count, MONTH(created_at) AS month_num');
        if ($domain_id != null) {
            $builder->where('user_domain', $domain_id);
        }

        $builder->where('YEAR(created_at)', $year);
        $builder->groupBy('MONTH(created_at)');
        $userDataSubquery = $builder->getCompiledSelect();

        // Final query combining the above subqueries
        $sql = "
            SELECT months.month, IFNULL(user_data.user_count, 0) AS user_count
            FROM ($monthsSubquery) AS months
            LEFT JOIN ($userDataSubquery) AS user_data ON months.month_num = user_data.month_num
            ORDER BY months.month_num
        ";

        // Execute the query
        $query = $this->db->query($sql);

        if ($result = $query->getResult()) {
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => [
                    'year'          => $year,
                    'result'        => $result,
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

    public function getUsersCountMonthly($year = null, $month = null)
    {

        $builder = $this->db->table('users');
        $builder->selectCount('id')
            ->where('MONTH(created_at)', $month)
            ->where('YEAR(created_at)', $year);

        $query = $builder->get();

        if ($result = $query->getRow()) {
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => [
                    'count'         => $result->id,
                    'month'         => getMonthName($month),
                    'year'          => $year,

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

    // get total Subscriptions
    public function getSubscriptionsCountMonthly($year = null, $month = null)
    {

        $builder = $this->db->table('user_subscriptions');
        $builder->selectCount('id')
            ->where('MONTH(created_at)', $month)
            ->where('YEAR(created_at)', $year);

        $query = $builder->get();

        if ($result = $query->getRow()) {
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => [
                    'count'         => $result->id,
                    'month'         => getMonthName($month),
                    'year'          => $year,

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


    // get total  subscriptions
    public function getSubscriptionsCountYearly($year = null, $domain_id = null)
    {
        // Raw subquery to create the list of all months
        $monthsSubquery = "
        SELECT 'Jan' AS month, 1 AS month_num UNION ALL
        SELECT 'Feb', 2 UNION ALL
        SELECT 'Mar', 3 UNION ALL
        SELECT 'Apr', 4 UNION ALL
        SELECT 'May', 5 UNION ALL
        SELECT 'Jun', 6 UNION ALL
        SELECT 'Jul', 7 UNION ALL
        SELECT 'Aug', 8 UNION ALL
        SELECT 'Sep', 9 UNION ALL
        SELECT 'Oct', 10 UNION ALL
        SELECT 'Nov', 11 UNION ALL
        SELECT 'Dec', 12
        ";

        // Subquery to count users for each month in 2024
        $builder = $this->db->table('user_subscriptions us');
        $builder->select('COUNT(us.id) AS subscription_count, MONTH(us.created_at) AS month_num');

        if ($domain_id != null) {
            $builder->join('users u', 'u.id = us.user_id AND u.user_domain = ' . $domain_id, 'LEFT');
            $builder->where('u.user_domain', $domain_id);
        }
        $builder->where('us.invoice_status', 'paid');
        $builder->where('YEAR(us.created_at)', $year);
        $builder->groupBy('MONTH(us.created_at)');

        $userDataSubquery = $builder->getCompiledSelect();
        // Final query combining the above subqueries
        $sql = "
            SELECT months.month, IFNULL(subscription_data.subscription_count, 0) AS subscription_count
            FROM ($monthsSubquery) AS months
            LEFT JOIN ($userDataSubquery) AS subscription_data ON months.month_num = subscription_data.month_num
            ORDER BY months.month_num
        ";
        // echo '>>>>>>>>>>>>>>>> sql >>> ' . $sql;

        // Execute the query
        $query = $this->db->query($sql);


        if ($result = $query->getResult()) {
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => [
                    'year'          => $year,
                    'result'        => $result,
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


    // get total Sales
    public function getSalesCountMonthly($year = null, $month = null)
    {

        $builder = $this->db->table('user_subscriptions');
        $builder->select('SUM(amount_paid) as sales')
            ->where('invoice_status', 'paid')
            ->where('MONTH(created_at)', $month)
            ->where('YEAR(created_at)', $year);

        $query = $builder->get();

        if ($result = $query->getRow()) {
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => [
                    'count'         => $result->sales,
                    'month'         => getMonthName($month),
                    'year'          => $year,

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


    // get total  Sales
    public function getSalesCountYearly($year = null, $domain_id = null)
    {
        // Raw subquery to create the list of all months
        $monthsSubquery = "
        SELECT 'Jan' AS month, 1 AS month_num UNION ALL
        SELECT 'Feb', 2 UNION ALL
        SELECT 'Mar', 3 UNION ALL
        SELECT 'Apr', 4 UNION ALL
        SELECT 'May', 5 UNION ALL
        SELECT 'Jun', 6 UNION ALL
        SELECT 'Jul', 7 UNION ALL
        SELECT 'Aug', 8 UNION ALL
        SELECT 'Sep', 9 UNION ALL
        SELECT 'Oct', 10 UNION ALL
        SELECT 'Nov', 11 UNION ALL
        SELECT 'Dec', 12
        ";

        // Subquery to count users for each month in 2024
        $builder = $this->db->table('user_subscriptions us');
        $builder->select('SUM(us.amount_paid) as sales, MONTH(us.created_at) AS month_num');

        if ($domain_id != null) {
            $builder->join('users u', 'u.id = us.user_id AND u.user_domain = ' . $domain_id, 'LEFT');
            $builder->where('u.user_domain', $domain_id);

            // get currency code
            $currencyBuilder = $this->db->table('domains');
            $currencyBuilder->select('currency')->where('id', $domain_id);
            $currencyResult = $currencyBuilder->get()->getRow();
        }
        $builder->where('us.invoice_status', 'paid');
        $builder->where('YEAR(us.created_at)', $year);
        $builder->groupBy('MONTH(us.created_at)');

        $userDataSubquery = $builder->getCompiledSelect();

        // Final query combining the above subqueries
        $sql = "
            SELECT months.month, IFNULL(subscription_data.sales, 0) AS sales
            FROM ($monthsSubquery) AS months
            LEFT JOIN ($userDataSubquery) AS subscription_data ON months.month_num = subscription_data.month_num
            ORDER BY months.month_num
        ";

        // Execute the query
        $query = $this->db->query($sql);

        if ($result = $query->getResult()) {

            $data = [
                'year'          => $year,
                'result'        => $result,
            ];

            if ($currencyResult) {
                $data['currency'] = $currencyResult->currency;
            }

            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => $data
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

    // get total  Sales
    public function getGraphDataYearly_old($year = null)
    {

        $data = [];
        $data['year'] = $year;
        // Raw subquery to create the list of all months
        $monthsSubquery = "
            SELECT 'Jan' AS month, 1 AS month_num UNION ALL
            SELECT 'Feb', 2 UNION ALL
            SELECT 'Mar', 3 UNION ALL
            SELECT 'Apr', 4 UNION ALL
            SELECT 'May', 5 UNION ALL
            SELECT 'Jun', 6 UNION ALL
            SELECT 'Jul', 7 UNION ALL
            SELECT 'Aug', 8 UNION ALL
            SELECT 'Sep', 9 UNION ALL
            SELECT 'Oct', 10 UNION ALL
            SELECT 'Nov', 11 UNION ALL
            SELECT 'Dec', 12
        ";

        // Subquery to count users for each month in 2024
        $userDataSubquery = $this->db->table('users')
            ->select('COUNT(id) AS user_count, MONTH(created_at) AS month_num')
            ->where('YEAR(created_at)', $year)
            ->groupBy('MONTH(created_at)')
            ->getCompiledSelect();

        // Final query combining the above subqueries
        $userSql = "
            SELECT months.month, IFNULL(user_data.user_count, 0) AS user_count
            FROM ($monthsSubquery) AS months
            LEFT JOIN ($userDataSubquery) AS user_data ON months.month_num = user_data.month_num
            ORDER BY months.month_num
        ";

        // Execute the query
        $userQuery = $this->db->query($userSql);
        if ($userResult = $userQuery->getResult()) {
            $labels = array_column($userResult, 'month');
            $values = array_column($userResult, 'user_count');

            $data['users'] = [
                'labels'        => $labels,
                'values'        => $values,
                'total'         => array_sum($values),
            ];
        }

        // Subquery to count subscriptions for each month in 2024
        $subscriptionsSubquery = $this->db->table('user_subscriptions')
            ->select('COUNT(id) AS subscription_count, MONTH(created_at) AS month_num')
            ->where('status', 'active')
            ->where('YEAR(created_at)', $year)
            ->groupBy('MONTH(created_at)')
            ->getCompiledSelect();

        // Final query combining the above subqueries
        $subscriptionsSql = "
            SELECT months.month, IFNULL(subscription_data.subscription_count, 0) AS subscription_count
            FROM ($monthsSubquery) AS months
            LEFT JOIN ($subscriptionsSubquery) AS subscription_data ON months.month_num = subscription_data.month_num
            ORDER BY months.month_num
        ";

        // Execute the query
        $subscriptionsQuery = $this->db->query($subscriptionsSql);
        if ($subscriptionsResult = $subscriptionsQuery->getResult()) {
            $labels = array_column($subscriptionsResult, 'month');
            $values = array_column($subscriptionsResult, 'subscription_count');

            $data['subscriptions'] = [
                'labels'        => $labels,
                'values'        => $values,
                'total'         => array_sum($values),
            ];
        }


        // Subquery to count sales for each month in 2024
        $salesDataSubquery = $this->db->table('user_subscriptions')
            ->select('SUM(amount_paid) as sales, MONTH(created_at) AS month_num')
            ->where('invoice_status', 'paid')
            ->where('YEAR(created_at)', $year)
            ->groupBy('MONTH(created_at)')
            ->getCompiledSelect();

        // Final query combining the above subqueries
        $salesSql = "
            SELECT months.month, IFNULL(subscription_data.sales, 0) AS sales
            FROM ($monthsSubquery) AS months
            LEFT JOIN ($salesDataSubquery) AS subscription_data ON months.month_num = subscription_data.month_num
            ORDER BY months.month_num
        ";

        // Execute the query
        $salesQuery = $this->db->query($salesSql);
        if ($salesResult = $salesQuery->getResult()) {
            $labels = array_column($salesResult, 'month');
            $values = array_column($salesResult, 'sales');

            $data['sales'] = [
                'labels'        => $labels,
                'values'        => $values,
                'total'         => round(array_sum($values), 2),
            ];
        }

        if ($data) {
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => $data
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

    // get total  Sales by domain
    public function getGraphDataYearly($year = null, $domain_id = null, $lang_id = null)
    {

        $data = [];
        $data['year'] = $year;

        if($lang_id == 1) {
            $monthsSubquery = "
                SELECT 'Jan' AS month, 1 AS month_num UNION ALL
                SELECT 'Feb', 2 UNION ALL
                SELECT 'Mar', 3 UNION ALL
                SELECT 'Apr', 4 UNION ALL
                SELECT 'May', 5 UNION ALL
                SELECT 'Jun', 6 UNION ALL
                SELECT 'Jul', 7 UNION ALL
                SELECT 'Aug', 8 UNION ALL
                SELECT 'Sep', 9 UNION ALL
                SELECT 'Oct', 10 UNION ALL
                SELECT 'Nov', 11 UNION ALL
                SELECT 'Dec', 12
            ";
        } else {

            // Raw subquery to create the list of all months
            $monthsSubquery = "
                SELECT 'Jan' AS month, 1 AS month_num UNION ALL
                SELECT 'Februar', 2 UNION ALL
                SELECT 'März', 3 UNION ALL
                SELECT 'Apr.', 4 UNION ALL
                SELECT 'Mai', 5 UNION ALL
                SELECT 'Jun', 6 UNION ALL
                SELECT 'Juli', 7 UNION ALL
                SELECT 'Aug', 8 UNION ALL
                SELECT 'Sep', 9 UNION ALL
                SELECT 'Okt.', 10 UNION ALL
                SELECT 'Nov.', 11 UNION ALL
                SELECT 'Dez.', 12
            ";
        }

        // Subquery to count users for each month in 2024
        // $userDataSubquery = $this->db->table('users')
        //     ->select('COUNT(id) AS user_count, MONTH(created_at) AS month_num')
        //     ->where('YEAR(created_at)', $year)
        //     ->groupBy('MONTH(created_at)')
        //     ->getCompiledSelect();

        $userBuilder = $this->db->table('users');
        $userBuilder->select('COUNT(id) AS user_count, MONTH(created_at) AS month_num');
        if ($domain_id != null) {
            $userBuilder->where('user_domain', $domain_id);
        }

        $userBuilder->where('YEAR(created_at)', $year);
        $userBuilder->groupBy('MONTH(created_at)');
        $userDataSubquery = $userBuilder->getCompiledSelect();

        // Final query combining the above subqueries
        $userSql = "
            SELECT months.month, IFNULL(user_data.user_count, 0) AS user_count
            FROM ($monthsSubquery) AS months
            LEFT JOIN ($userDataSubquery) AS user_data ON months.month_num = user_data.month_num
            ORDER BY months.month_num
        ";

        // Execute the query
        $userQuery = $this->db->query($userSql);
        if ($userResult = $userQuery->getResult()) {
            $labels = array_column($userResult, 'month');
            $values = array_column($userResult, 'user_count');

            $data['users'] = [
                'labels'        => $labels,
                'values'        => $values,
                'total'         => array_sum($values),
            ];
        }

        // Subquery to count subscriptions for each month in 2024
        // $subscriptionsSubquery = $this->db->table('user_subscriptions')
        //     ->select('COUNT(id) AS subscription_count, MONTH(created_at) AS month_num')
        //     ->where('status', 'active')
        //     ->where('YEAR(created_at)', $year)
        //     ->groupBy('MONTH(created_at)')
        //     ->getCompiledSelect();

        $subscriptionsBuilder = $this->db->table('user_subscriptions us');
        $subscriptionsBuilder->select('COUNT(us.id) AS subscription_count, MONTH(us.created_at) AS month_num');

        if ($domain_id != null) {
            $subscriptionsBuilder->join('users u', 'u.id = us.user_id AND u.user_domain = ' . $domain_id, 'LEFT');
            $subscriptionsBuilder->where('u.user_domain', $domain_id);
        }
        $subscriptionsBuilder->where('us.invoice_status', 'paid');
        $subscriptionsBuilder->where('YEAR(us.created_at)', $year);
        $subscriptionsBuilder->groupBy('MONTH(us.created_at)');

        $subscriptionsSubquery = $subscriptionsBuilder->getCompiledSelect();

        // Final query combining the above subqueries
        $subscriptionsSql = "
            SELECT months.month, IFNULL(subscription_data.subscription_count, 0) AS subscription_count
            FROM ($monthsSubquery) AS months
            LEFT JOIN ($subscriptionsSubquery) AS subscription_data ON months.month_num = subscription_data.month_num
            ORDER BY months.month_num
        ";

        // Execute the query
        $subscriptionsQuery = $this->db->query($subscriptionsSql);
        if ($subscriptionsResult = $subscriptionsQuery->getResult()) {
            $labels = array_column($subscriptionsResult, 'month');
            $values = array_column($subscriptionsResult, 'subscription_count');

            $data['subscriptions'] = [
                'labels'        => $labels,
                'values'        => $values,
                'total'         => array_sum($values),
            ];
        }


        // Subquery to count sales for each month in 2024
        // $salesDataSubquery = $this->db->table('user_subscriptions')
        //     ->select('SUM(amount_paid) as sales, MONTH(created_at) AS month_num')
        //     ->where('invoice_status', 'paid')
        //     ->where('YEAR(created_at)', $year)
        //     ->groupBy('MONTH(created_at)')
        //     ->getCompiledSelect();


        $salesBuilder = $this->db->table('user_subscriptions us');
        $salesBuilder->select('SUM(us.amount_paid) as sales, MONTH(us.created_at) AS month_num');

        if ($domain_id != null) {
            $salesBuilder->join('users u', 'u.id = us.user_id AND u.user_domain = ' . $domain_id, 'LEFT');
            $salesBuilder->where('u.user_domain', $domain_id);

            // get currency code
            $currencyBuilder = $this->db->table('domains');
            $currencyBuilder->select('currency')->where('id', $domain_id);
            $currencyResult = $currencyBuilder->get()->getRow();
        }

        $salesBuilder->where('us.invoice_status', 'paid');
        $salesBuilder->where('YEAR(us.created_at)', $year);
        $salesBuilder->groupBy('MONTH(us.created_at)');

        $salesDataSubquery = $salesBuilder->getCompiledSelect();

        // Final query combining the above subqueries
        $salesSql = "
            SELECT months.month, IFNULL(subscription_data.sales, 0) AS sales
            FROM ($monthsSubquery) AS months
            LEFT JOIN ($salesDataSubquery) AS subscription_data ON months.month_num = subscription_data.month_num
            ORDER BY months.month_num
        ";

        // Execute the query
        $salesQuery = $this->db->query($salesSql);
        // echo " >>>>>>>>>> getGraphDataYearly getLastQuery>>>>>>> " . $this->db->getLastQuery();

        if ($salesResult = $salesQuery->getResult()) {
            $labels = array_column($salesResult, 'month');
            $values = array_column($salesResult, 'sales');

            $data['sales'] = [
                'labels'        => $labels,
                'values'        => $values,
                'total'         => round(array_sum($values), 2),
            ];

            if ($domain_id != null) {
                $data['sales']['currency'] = $currencyResult->currency;
            }
        }

        if ($data) {
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => $data
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


    public function updateNewsletterStatus($userId = null)
    {
        $userId = $userId ?? auth()->id();

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP) || hasAccess(auth()->user(), EDITOR_ACCESS)) {

            $rules = ['newsletter' => 'required'];

            if (!$this->validate($rules)) {
                $response = [
                    "status"    => false,
                    "message"   => $this->validator->getErrors(),
                    "data"      => []
                ];
            } else {

                // get user detail
                $builder = $this->db->table('users u');
                $builder->select('u.*, ai.secret as email');
                $builder->join('auth_identities ai', 'ai.user_id = u.id AND ai.type = "email_password"', 'LEFT');
                $builder->where('u.id', $userId);
                $query = $builder->get();
                $userInfo = $query->getRow();

                if ($this->request->getVar("newsletter") == 1) {
                    $newsletterStatus = 'subscribed';
                    $message = 'App.newsletterSubscribed';
                } else {
                    $newsletterStatus = 'unsubscribed';
                    $message = 'App.newsletterUnsubscribed';
                }

                $builder->set('newsletter', $this->request->getVar("newsletter"));
                $builder->where('id', $userId);
                if ($builder->update()) {

                    // add or remove user to mailchimp
                    $mailchimpData = [
                        'email'     => $userInfo->email,
                        'status'    => $newsletterStatus,
                        'firstname' => $userInfo->first_name,
                        'lastname'  => $userInfo->last_name
                    ];
                    $mailchimpRes = syncMailchimp($mailchimpData);
                    $activityMsg = lang($message);
                    // echo $activityMsg; die;
                    // create Activity log
                    // $activity_data = [
                    //     'user_id'               => $userId,
                    //     'activity_type_id'      => 2,        // updated
                    //     'activity'              => $activityMsg . '',
                    //     'ip'                    => $this->request->getIPAddress()
                    // ];
                    // createActivityLog($activity_data);
                    $activity_data = array();
                    $languageService = \Config\Services::language();
                    $languages = ['en', 'de', 'fr', 'it', 'es', 'pt', 'da', 'sv'];
                    $activity_data = [
                        'user_id'               => auth()->id(),
                        'activity_type_id'      => 2,      // updated
                        'ip'                    => $this->request->getIPAddress()
                    ];
                    foreach ($languages as $lang) {
                        $languageService->setLocale($lang);
                        $translated_message = lang($message);
                        $activity_data['activity_' . $lang] = $translated_message;
                    }
                    createActivityLog($activity_data);

                    $response = [
                        "status"    => true,
                        "message"   => lang($message),
                        "data"      => ['mailchimpRes' => $mailchimpRes]
                    ];
                } else {

                    // create Activity log
                    // $activity_data = [
                    //     'user_id'               => $userId,
                    //     'activity_type_id'      => 2,        // updated
                    //     'activity'              => 'newsletter subscription failed',
                    //     'ip'                    => $this->request->getIPAddress()
                    // ];
                    // createActivityLog($activity_data);

                    $response = [
                        "status"    => false,
                        "message"   => lang('App.newsletterFailed'),
                        "data"      => []
                    ];
                }
            }
        } else {
            $response = [
                "status"    => false,
                "message"   => lang('App.permissionDenied'),
                "data"      => []
            ];
        }
        return $this->respondCreated($response);
    }

    /* public function getNewsletterStatus($userId = null){
        $userId = $userId ?? auth()->id();

        // get user detail
        $builder = $this->db->table('users u');
        $builder->select('u.*, ai.secret as email');
        $builder->join('auth_identities ai', 'ai.user_id = u.id AND ai.type = "email_password"', 'LEFT');
        $builder->where('u.id', $userId);
        $query = $builder->get();
        $userInfo = $query->getRow();

    } */
    // export CSV
    public function exportUsers()
    {
        $data = 'Here is some text!';
        $name = 'mytext.txt';

        return $this->response->download($name, $data);
    }

    public function getActivity()
    {
        $userId = auth()->id(); // Assuming this fetches the current user's ID
        $userlang = userCurrentLang($userId);
        $ActivityModel = new ActivityModel();
        // $userlang =
        // Fetch query parameters
        $url =  isset($_SERVER['HTTPS']) &&
            $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
        $url .= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $parse_url = parse_url($url);
        $uri = new \CodeIgniter\HTTP\URI($url);

        $queryString =  $uri->getQuery();
        parse_str($queryString, $searchParams);
        // echo '<pre>'; print_r($searchParams); die;
        // $limit = $searchParams['limit'] ?? 10; // Default to 10 if limit is not provided
        // $offset = $searchParams['offset'] ?? 0; // Default to 0 for the first page
        // $search = $searchParams['search'] ?? ''; // Optional search parameter
        $limit = is_numeric($searchParams['limit'] ?? null) ? (int) $searchParams['limit'] : 10;
        $offset = is_numeric($searchParams['offset'] ?? null) ? (int) $searchParams['offset'] : 0;
        $search = $searchParams['search'] ?? ''; // Optional search parameter
        $countOnly = $searchParams['count_only'] ?? false;

        // Get total count of activities for pagination purposes
        $totalCount = $ActivityModel->getActivityCount($userId, $search);

        // If count_only is true, return only the total count
        if ($countOnly) {
            return $this->respond([
                'status' => true,
                'message' => lang('App.dataFound'),
                'data' => ['totalCount' => $totalCount]
            ]);
        }

        // Fetch activities using the model
        $activities = $ActivityModel->getActivities($userId, $limit, $offset, $userlang);

        if ($activities) {
            $response = [
                'status' => true,
                'message' => lang('App.dataFound'),
                'data' => [
                    'totalCount' => $totalCount,
                    'limit' => $limit,
                    'offset' => $offset,
                    'userData' => $activities
                ]
            ];
        } else {
            $response = [
                'status' => false,
                'message' => lang('App.noDataFound'),
                'data' => []
            ];
        }

        return $this->respondCreated($response);
    }

    public function deleteActivity()
    {
        $activityModel = new ActivityModel();
        $user_id = auth()->id();

        $ids = $this->request->getVar("id");
        if (is_array($ids) && count($ids) > 1) {
            if ($user_id == 1) {
                $activityModel->whereIn('id', $ids)->delete();
            } else {
                $activityModel->where('user_id', $user_id)->whereIn('id', $ids)->delete();
            }
            $data = ['success' => count($ids) . ' Activities deleted successfully'];
            $response = [
                "status" => true,
                "data"   => $data
            ];
        } else {
            // If there is only one id to delete
            if (!empty($ids) && count($ids) == 1) {
                // Check if the array has exactly one id, then delete based on that id
                //
                if ($user_id == 1) {
                    $activityModel->where('id', $ids[0])->delete();
                    // $activityModel->whereIn('id', $ids)->delete();
                } else {
                    $activityModel->where('user_id', $user_id)->where('id', $ids[0])->delete();
                }
                // echo $ids[0]; die;
                // echo $this->db->getLastQuery(); die(' single delete');
                $data = ['success' => 'Activity deleted successfully'];
                $response = [
                    "status" => true,
                    "data"   => $data
                ];
            } else {
                // If no valid id was passed
                $data = ['errors' => 'Failed to delete activity'];
                $response = [
                    "status" => false,
                    "data"   => $data
                ];
            }
        }
        return $this->respondCreated($response);
    }

    /* public function UploadProfileImageCommon(){
        $userMetaObject = new UserMetaDataModel();

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $userId = auth()->id();
        // $userId = '58';
        $validationRule = [
            'profile_image' => [
                'label' => 'Profile Image',
                'rules' => [
                    'uploaded[profile_image]',
                    'is_image[profile_image]',
                    'mime_in[profile_image,image/jpg,image/jpeg,image/gif,image/png,image/webp]',
                    'max_size[profile_image,1000]',
                    //'max_dims[profile_image,1024,768]',
                ],
                'errors' => [
                    'uploaded'  => lang('App.uploaded', ['file' => 'jpg, jpeg, gif, png, webp']),
                    'is_image'  => lang('App.is_image', ['file' => 'jpg, jpeg, gif, png, webp']),
                    'mime_in'   => lang('App.mime_in', ['file' => 'jpg, jpeg, gif, png, webp']),
                    'max_size'  => lang('App.max_size', ['siz' => '1000']),
                    'max_dims'  => lang('App.max_dims'),
                ],
            ],
        ];

        if (! $this->validateData([], $validationRule)) {
            $errors = $this->validator->getErrors();

            $response = [
                "status"    => false,
                // "message"   => lang('App.profile_updatedfailure'),
                "data"      => ['errors' => $errors]
            ];
        } else{

            // $is_uploaded = removeImageBG($this->request->getFile('profile_image'));
            $profile_image = $this->request->getFile('profile_image');        // old code

            if (! $profile_image->hasMoved()) {       // old code
            // if($is_uploaded && $is_uploaded['status'] == "success"){

                //$filepath = WRITEPATH . 'uploads/' . $profile_image->store('');       // old code

                $userInfo = $userMetaObject->where('user_id', $userId)->where('meta_key', 'profile_image')->first();
                $save_data['id'] = '';
                if($userInfo) {
                    $save_data['id'] = $userInfo['id'];

                    // remove file if existing
                    if(file_exists(WRITEPATH . 'uploads/' . $userInfo['meta_value'])){
                        unlink(WRITEPATH . 'uploads/' . $userInfo['meta_value']);
                    }
                }
                $save_data['user_id'] = $userId;
                $save_data['meta_key'] = 'profile_image';
                $save_data['meta_value'] = $profile_image->getName();     // old Code
                // $save_data['meta_value'] = $is_uploaded['fileName'];
                // save data
                $res = $userMetaObject->save($save_data);

                $data = ['uploaded_fileinfo' => $profile_image->getName()];        // old Code
                // $data = ['uploaded_fileinfo' => $is_uploaded['fileName'] ];

                // create Activity log
                $activity_data = [
                    'user_id'               => auth()->id(),
                    'activity_type_id'      => 2,      // updated
                    'activity'              => 'uploaded profile image ' . $data['uploaded_fileinfo'],
                    'ip'                    => $this->request->getIPAddress()
                ];
                createActivityLog($activity_data);

                $response = [
                    "status"    => true,
                    "message"   => lang('App.profile_updatedSuccess'),
                    "data"      => $data
                ];
            } else {
                $data = ['errors' => 'File not uploaded due to some error'];
                $response = [
                    "status"    => false,
                    // "message"   => lang('App.profile_updatedfailure'),
                    "data"      => $data
                ];
            }
        }
        return $this->respondCreated($response);
    } */
    public function UploadProfilePhoto($userId = null)
    { /* For Scout, Player By Admin */
        $userMetaObject = new UserMetaDataModel();
        if (empty($userId) || !is_numeric($userId)) {
            return $this->respondCreated(['status' => false, 'data' => 'Parameter is not valid for Upload Image']);
        }
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        //$userId = auth()->id();
        // $userId = '58';
        $validationRule = [
            'profile_image' => [
                'label' => 'Profile Image',
                'rules' => [
                    'uploaded[profile_image]',
                    'is_image[profile_image]',
                    'mime_in[profile_image,image/jpg,image/jpeg,image/gif,image/png,image/webp]',
                    'max_size[profile_image,1000]',
                    //'max_dims[profile_image,1024,768]',
                ],
                'errors' => [
                    'uploaded'  => lang('App.uploaded', ['file' => 'jpg, jpeg, gif, png, webp']),
                    'is_image'  => lang('App.is_image', ['file' => 'jpg, jpeg, gif, png, webp']),
                    'mime_in'   => lang('App.mime_in', ['file' => 'jpg, jpeg, gif, png, webp']),
                    'max_size'  => lang('App.max_size', ['siz' => '1000']),
                    'max_dims'  => lang('App.max_dims'),
                ],
            ],
        ];

        if (! $this->validateData([], $validationRule)) {
            $errors = $this->validator->getErrors();

            $response = [
                "status"    => false,
                // "message"   => lang('App.profile_updatedfailure'),
                "data"      => ['errors' => $errors]
            ];
        } else {

            // $is_uploaded = removeImageBG($this->request->getFile('profile_image'));
            $profile_image = $this->request->getFile('profile_image');        // old code

            if (! $profile_image->hasMoved()) {       // old code
                // if($is_uploaded && $is_uploaded['status'] == "success"){

                //$filepath = WRITEPATH . 'uploads/' . $profile_image->store('');       // old code

                $userInfo = $userMetaObject->where('user_id', $userId)->where('meta_key', 'profile_image')->first();
                $save_data['id'] = '';
                if ($userInfo) {
                    $save_data['id'] = $userInfo['id'];

                    // remove file if existing
                    if (file_exists(WRITEPATH . 'uploads/' . $userInfo['meta_value'])) {
                        unlink(WRITEPATH . 'uploads/' . $userInfo['meta_value']);
                    }
                }
                $save_data['user_id'] = $userId;
                $save_data['meta_key'] = 'profile_image';
                $newName = $profile_image->getRandomName();
                $save_data['meta_value'] = $newName;     // old Code
                // $save_data['meta_value'] = $is_uploaded['fileName'];
                // save data
                $res = $userMetaObject->save($save_data);
                // Define the upload directory path (relative to your project)
                $uploadPath = WRITEPATH . 'uploads/'; // Make sure this directory exists or create it

                // Optionally: You can generate a unique name to avoid filename conflicts


                // Move the file to the specified directory with a new name
                $profile_image->move($uploadPath, $newName);
                // $uploadedFilePath = $uploadPath . $newName;
                $data = ['uploaded_fileinfo' => $profile_image->getName()];        // old Code
                // $data = ['uploaded_fileinfo' => $is_uploaded['fileName'] ];

                // create Activity log
                $activity = lang('App.uploaded_profile_image', ['userName' => '[USER_NAME_' . $userId . ']', 'imageName' => $data['uploaded_fileinfo']]);
                // create Activity log
                // $activity_data = [
                //     'user_id'               => auth()->id(),
                //     'activity_type_id'      => 2,      // updated
                //     'activity'              => $activity,
                //     'ip'                    => $this->request->getIPAddress()
                // ];
                // createActivityLog($activity_data);
                $activity_data = array();
                $languageService = \Config\Services::language();
                $languages = ['en', 'de', 'fr', 'it', 'es', 'pt', 'da', 'sv'];
                $activity_data = [
                    'user_id'               => auth()->id(),
                    'activity_type_id'      => 2,      // updated
                    'ip'                    => $this->request->getIPAddress()
                ];
                foreach ($languages as $lang) {
                    $languageService->setLocale($lang);
                    $translated_message = lang('App.uploaded_profile_image', ['userName' => '[USER_NAME_' . $userId . ']', 'imageName' => $data['uploaded_fileinfo']]);
                    $activity_data['activity_' . $lang] = $translated_message;
                }
                createActivityLog($activity_data);

                $response = [
                    "status"    => true,
                    "message"   => lang('App.profile_updatedSuccess'),
                    "data"      => $data
                ];
            } else {
                $data = ['errors' => 'File not uploaded due to some error'];
                $response = [
                    "status"    => false,
                    // "message"   => lang('App.profile_updatedfailure'),
                    "data"      => $data
                ];
            }
        }
        return $this->respondCreated($response);
    }

    public function downloadCsv()
    {
        $all = $this->request->getVar();
        // echo '<pre>'; print_r($all); die;
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        if (empty($this->request->getVar("lang"))) {
            $currentUserLang = currentUserLang(auth()->id());
            if (isset($currentUserLang) && !empty($currentUserLang->slug)) {
                $currentLang = $currentUserLang->slug;
            }
        }
        $this->request->setLocale(getLanguageCode($currentLang));

        $url =  isset($_SERVER['HTTPS']) &&
            $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
        $url .= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $parse_url = parse_url($url);
        $uri = new \CodeIgniter\HTTP\URI($url);
        $whereClause = [];
        $queryString =  $uri->getQuery();
        parse_str($queryString, $searchParams);
        if (empty($searchParams) && !empty($this->request->getVar("data"))) {
            $searchParams = $this->request->getVar("data");
            if (is_object($searchParams)) {
                $searchParams = (array) $searchParams;
            }
            foreach ($searchParams as $key => $value) {
                // Check if the key contains 'whereClause'
                if ($key == 'selectedUserIds') {
                    $whereClause[$key] = $value;
                }
                if (strpos($key, 'whereClause[') === 0) {
                    // Remove the 'whereClause[' and ']' to get the inner key
                    $subKey = substr($key, 12, -1);  // Extract the subkey like 'membership', 'role', 'status'

                    // Add the key-value pair to the whereClause array
                    $whereClause[$subKey] = $value;
                }
            }
        }



        $metaQuery = [];
        $orderBy =  $order = $search = '';
        $noLimit =  $countOnly = FALSE;


        $limit = $searchParams['limit'] ?? PER_PAGE;
        $offset = $searchParams['offset'] ?? 0;
        // echo '<pre>'; print_r($searchParams); die;
        if (isset($searchParams['whereClause']) && count($searchParams['whereClause']) > 0) {
            $whereClause = $searchParams['whereClause'];
        }
        if (isset($searchParams['selectedUserIds']) && !empty($searchParams['selectedUserIds'])) {
            $whereClause['selectedUserIds'] = $searchParams['selectedUserIds'];
        }
        if (isset($searchParams['metaQuery']) && count($searchParams['metaQuery']) > 0) {
            $metaQuery = $searchParams['metaQuery'];
        }

        if (isset($searchParams['search']) && !empty($searchParams['search'])) {
            $search = $searchParams['search'];
        }

        if (isset($searchParams['orderBy']) && !empty($searchParams['orderBy'])) {
            $orderBy = $searchParams['orderBy'];
        }

        if (isset($searchParams['order']) && !empty($searchParams['order'])) {
            $order = $searchParams['order'];
        }

        if (isset($searchParams['noLimit']) && !empty($searchParams['noLimit'])) {
            $noLimit = $searchParams['noLimit'];
        }

        if (isset($searchParams['countOnly']) && !empty($searchParams['countOnly'])) {
            $countOnly = $searchParams['countOnly'];
        }
        //$users = getPlayers($whereClause, $metaQuery, $search, $orderBy, $order, $limit, $offset, $noLimit, $countOnly);
        $users = getPlayers($whereClause, $metaQuery, $search, $orderBy, $order, $limit, $offset, $noLimit);
        /* ##### excel code By Amrit ##### */
        // echo '<pre>'; print_r($users); die;
        // Create a new Spreadsheet object
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set the headers
        $sheet->setCellValue('A1', 'First Name');
        $sheet->setCellValue('B1', 'Last Name');
        $sheet->setCellValue('C1', 'User Type');
        $sheet->setCellValue('D1', 'Language');
        $sheet->setCellValue('E1', 'Location');
        $sheet->setCellValue('F1', 'Joined Date - Time');
        $sheet->setCellValue('G1', 'Email');
        $sheet->setCellValue('H1', 'Membership');
        $sheet->setCellValue('I1', 'Status');
        $sheet->setCellValue('J1', 'Profile');

        $row = 2; // Start from the second row
        if (isset($users['users']) && !empty($users['totalCount'])) {
            foreach ($users['users'] as $user) {
                if (isset($user['package_name']) && !empty($user['package_name'])) {
                    $membership = 'Paid';
                } else {
                    $membership = 'Free';
                }
                if ($user['status'] == "1") {
                    $status = 'Pending';
                } elseif ($user['status'] == "2") {
                    $status = 'Approved';
                } elseif ($user['status'] == "3") {
                    $status = 'Rejected';
                } else {
                    $status = 'Unknown';
                }
                // $sheet->setCellValue('A' . $row, htmlspecialchars($user['first_name'] . ' ' . $user['last_name']));
                $sheet->setCellValue('A' . $row, htmlspecialchars($user['first_name']));
                $sheet->setCellValue('B' . $row, htmlspecialchars($user['last_name']));
                $sheet->setCellValue('C' . $row, htmlspecialchars($user['role_name']));
                $sheet->setCellValue('D' . $row, htmlspecialchars($user['language']));
                $sheet->setCellValue('E' . $row, htmlspecialchars($user['user_location']));
                $sheet->setCellValue('F' . $row, htmlspecialchars($user['created_at']));
                $sheet->setCellValue('G' . $row, htmlspecialchars($user['email']));
                $sheet->setCellValue('H' . $row, htmlspecialchars($membership));
                $sheet->setCellValue('I' . $row, htmlspecialchars($status));
                $sheet->setCellValue('J' . $row, htmlspecialchars(isset($user['meta']['profile_image_path']) ? $user['meta']['profile_image_path'] : ''));
                // $sheet->setCellValue('I' . $row, implode(PHP_EOL,array('amrit','sharma')));
                // $sheet->getStyle('I' . $row)->getAlignment()->setWrapText(true);
                // $sheet->getRowDimension($row)->setRowHeight(40);
                $row++;
            }
        }
        // Define the directory to save the file
        // $saveDirectory = 'uploads/temp_excel';  // Replace with your actual path
        $saveDirectory = WRITEPATH  . 'uploads/exports/';
        // $filename = 'users_export_' . date('Y-m-d_H-i-s') . '.xlsx';

        $filename = 'users_export_' . date('Y-m-d_H-i-s') . '.xlsx';
        $folder_nd_file = 'uploads/exports/' . $filename;

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
        // $activity_data = [
        //     'user_id'               => auth()->id(),
        //     'activity_type_id'      => 1,      // viewed
        //     'activity'              => 'Csv Downloded by user',
        //     'ip'                    => $this->request->getIPAddress()
        // ];
        $activity_data = array();
        $languageService = \Config\Services::language();
        $languages = ['en', 'de', 'fr', 'it', 'es', 'pt', 'da', 'sv'];
        $activity_data = [
            'user_id'               => auth()->id(),
            'activity_type_id'      => 2,      // updated
            'ip'                    => $this->request->getIPAddress()
        ];
        foreach ($languages as $lang) {
            $languageService->setLocale($lang);
            $translated_message = 'Csv Downloded by user (' . $lang . ')';
            // $translated_message = lang('App.uploaded_profile_image', ['userName' => '[USER_NAME_' . $userId . ']', 'imageName' => $data['uploaded_fileinfo']]);
            $activity_data['activity_' . $lang] = $translated_message;
        }
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

        if (isset($users['totalCount']) && $users['totalCount'] > 50) {
            $user_id = auth()->id();
            $user = getUserByID($user_id);
            if (isset($user) && !empty($user)) {
                // $user->email = 'amritpalsharma.cts@gmail.com';
                $userName = trim($user->first_name, ' ') . ' ' . trim($user->last_name, ' ');
                $emailMsg = lang('App.userCsvDownloadMessage');
                $emailMsg = str_replace('[userFullName]', $userName, $emailMsg);
                $emailMsg = str_replace('[CSV_LINK]', base_url() . $folder_nd_file, $emailMsg);
                $data = [
                    'fromEmail' => FROM_EMAIL,
                    'fromName' => FROM_NAME,
                    'toEmail' => $user->email,
                    'subject' => 'User Data CSV',
                    'message' => $emailMsg,
                    'attachmentPath' => $filePath // Path to the generated CSV file
                ];
                $res = sendEmailWithAttachment($data);
                $response['message'] = 'CSV sent in Email SucccessFully.';
                // $activity = lang('App.received_in_email', ['imageName' => $filename]);
                // $activity_data['activity'] = $activity;
                // createActivityLog($activity_data);
                $activity_data = array();
                $languageService = \Config\Services::language();
                $languages = ['en', 'de', 'fr', 'it', 'es', 'pt', 'da', 'sv'];
                $activity_data = [
                    'user_id'               => auth()->id(),
                    'activity_type_id'      => 2,      // updated
                    'ip'                    => $this->request->getIPAddress()
                ];
                foreach ($languages as $lang) {
                    $languageService->setLocale($lang);
                    $translated_message = lang('App.received_in_email', ['imageName' => $filename]);
                    // $translated_message = lang('App.uploaded_profile_image', ['userName' => '[USER_NAME_' . $userId . ']', 'imageName' => $data['uploaded_fileinfo']]);
                    $activity_data['activity_' . $lang] = $translated_message;
                }
                createActivityLog($activity_data);
                return $this->respondCreated($response);
            }
        }
        createActivityLog($activity_data);
        return $this->respondCreated($response);
    }

    public function downloadUserPdf($userId = null)
    {
        set_time_limit(120);
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        if (empty($this->request->getVar("lang"))) {
            $currentUserLang = currentUserLang(auth()->id());
            if (isset($currentUserLang) && !empty($currentUserLang->slug)) {
                $currentLang = $currentUserLang->slug;
            }
        }
        // echo $currentLang; die;
        // $currentLang = 2;
        $this->request->setLocale(getLanguageCode($currentLang));
        if (empty($userId)) {
            $userId = auth()->id();
        }
        $id = $userId;
        $pdf_error = false;
        if (!empty($userId)) {
            $flagPath = base_url() . 'public/assets/images/';
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
                    "country_id", c.id,
                    "country_name", c.country_name,
                    "flag_path", CONCAT("' . $flagPath . '", c.country_flag)
                )
            )
            FROM user_nationalities un
            LEFT JOIN countries c ON c.id = un.country_id
            WHERE un.user_id = users.id) AS user_nationalities,

            t.id as team_id,
            t.team_type as team_type,
            um2.meta_value as current_club_name,
            CONCAT("' . $imagePath . '", um3.meta_value) as club_logo_path,
            CONCAT("' . $flagPath . '", c3.country_flag) as current_club_country_path,

            l.league_name as league_name,
            l.league_logo as league_logo,
            CONCAT("' . $flagPath . '", l.league_logo) as league_logo_path,

            c1.id as int_player_country_id,
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

            cb.club_name as pre_current_club_name,
            CONCAT("' . $imagePath . '", cb.club_logo) as pre_current_club_logo_path,
            CONCAT("' . $flagPath . '", c2.country_flag) as pre_current_club_country_path,
            apu.permission as permission
            ');

            $builder->join('auth_identities auth', 'auth.user_id = users.id AND type = "email_password"', 'LEFT');
            $builder->join('user_roles', 'user_roles.id = users.role', 'LEFT');
            $builder->join('languages', 'languages.id = users.lang', 'LEFT');
            $builder->join('domains', 'domains.id = users.user_domain', 'LEFT');
            $builder->join('payment_methods pm', 'pm.user_id = users.id AND pm.is_default = 1', 'LEFT');

            $builder->join('(SELECT user_id, status, plan_period_start, plan_period_end, plan_interval, coupon_used FROM user_subscriptions WHERE id =  (SELECT MAX(id)  FROM user_subscriptions WHERE user_id = ' . $id . ')) us', 'us.user_id = users.id', 'LEFT');

            $builder->join('club_players cp', 'cp.player_id = users.id AND cp.status = "active"', 'LEFT');
            $builder->join('teams t', 't.id = cp.team_id', 'LEFT');
            $builder->join('user_meta um2', 'um2.user_id = t.club_id AND um2.meta_key = "club_name"', 'LEFT');
            $builder->join('countries c3', 'c3.id = t.country_id', 'LEFT');

            $builder->join('user_meta um3', 'um3.user_id = t.club_id AND um3.meta_key = "profile_image"', 'LEFT');

            $builder->join('league_clubs lc', 'lc.club_id = t.club_id', 'LEFT');
            $builder->join('leagues l', 'l.id = lc.league_id', 'LEFT');

            $builder->join('user_meta um4', 'um4.user_id = users.id AND um4.meta_key = "international_player"', 'LEFT');
            $builder->join('countries c1', 'c1.id = um4.meta_value', 'LEFT');

            $builder->join('user_meta um5', 'um5.user_id = users.id AND um5.meta_key = "pre_club_id"', 'LEFT');
            $builder->join('clubs cb', 'cb.id = um5.meta_value', 'LEFT');
            $builder->join('countries c2', 'c2.id = cb.country_id', 'LEFT');
            $builder->join('auth_permissions_users apu', 'apu.user_id = users.id', 'LEFT');


            $builder->where('users.id', $id);
            $userData   = $builder->get()->getRow();
            // Check if the variable is an instance of stdClass
            if (is_object($userData) && get_class($userData) === 'stdClass') {
                // Convert stdClass object to an array
                $userData = (array) $userData;
            }

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
            /* #### Gallery For All #### */
            $galleryModel = new GalleryModel();
            $images = $galleryModel->where('user_id', $userId)->whereIn('file_type', ALLOWED_IMAGE_EXTENTION)->orderBy('id', 'DESC')->findAll();
            // $images = $galleryModel->where('user_id', $userId)->where('is_featured', 1)->whereIn('file_type', ALLOWED_IMAGE_EXTENTION)->orderBy('id', 'DESC')->findAll();
            /* #### End Gallery For All #### */

            /* ##### Common PDF Header For ALL ##### */
            $content = '
            <!DOCTYPE html>
            <html lang="en">
            <head>
            <title>SoccerYou Sports AG</title>
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
            .current_club {font-size: 20px;}
            .performance_data, .transfer_history { width: 100%;background: #f8f8f8;padding: 12px;margin-top: 15px; }
            .performance_data tbody tr, .transfer_history tbody tr {background: #fff;}
            .social_links {width: 160px;float: right;font-size: 13px;}
            table.player_detail td.detail_list {width: 33.33% !important; border-bottom: 1px solid #fff;padding: 5px;}
            table.player_detail {border-collapse: separate;border-spacing: 10px;}
            table.player_detail td.detail_list.no_border {border: 0;}
            .player_detail h3 {font-size: 13px;}
            .court_position {position: relative;}
            img.ground_fix {position: absolute;left: 0;top: 0; }
            img.dot_posg {position: absolute;left: 0;top: 0;}
            img.ground_fix {max-width: 400px;}
            .ground_fix11 {width: 100%; max-width: 400px; /* mix-blend-mode: darken; To remove white space */}
            .approved_svg{width: 20px;margin-left: -12px;margin-top: 28px;}
            .pos_unset{position: unset !important;}
            .team_player_profile{width: 50px;height: 50px;object-fit: cover;border-radius: 50%;} .team_player_profile_status{max-width: 22px;position: absolute;margin-top: 25px;margin-left: -15px;}
            .line_height_50{line-height: 50px;}
            .gallery-imgs{  height: 200px; object-fit: cover;}
            </style>
            </head>';
            if (isset($userData['role_name']) && !empty($userData['role_name'])) {
                $role_name = strtoupper($userData['role_name']);
            }
            if (!empty($userData['first_name']) && !empty($userData['last_name'])) {
                $fullName = $userData['first_name'] . ' ' . $userData['last_name'];
            }
            /* ##### End Common PDF Header For ALL ##### */
            /* ##### ~~ Player PDF ~~ ##### */
            if (isset($userData['role']) && strtoupper($userData['role']) == '4') { // Player
                /* ##### screenshot from image ##### */
                // ApiFlash API key
                $apiKey = 'df05353ad1824362a90d9105eafec3bc'; // THis is Paid API key of timo account
                #  $apiKey = 'dae8abc5f2ef456499246022fce40215'; // THis is Free API key of testmails account
                // Directory to save screenshots
                $imageDir = 'public/screenshots';
                $imageName = 'temp_sc_' . rand(1500, 9999); // Random base name
                $imagePath = $imageDir . '/' . $imageName; // Path without extension
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
                // echo '<pre>'; print_r($context); die;
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
                /* #### Performance Detail #### */
                $logoPath = base_url() . 'uploads/logos/';
                $profilePath = base_url() . 'uploads/';
                $performanceDetailModel = new PerformanceDetailModel();
                $builder = $performanceDetailModel->builder();
                $builder->select('
                performance_details.*,
                cb.club_name as team_name,
                cb.club_logo as team_club_logo,
                CONCAT("' . $profilePath . '", cb.club_logo ) AS team_club_logo_path,
                c.country_name as country_name,
                c.country_flag as country_flag,
                CONCAT( "' . $logoPath . '", c.country_flag) AS country_flag_path');

                $builder->join('teams t', 't.id = performance_details.team_id', 'LEFT');
                $builder->join('clubs cb', 'cb.id = t.club_id', 'LEFT');
                $builder->join('countries c', 'c.id = t.country_id', 'LEFT');
                $builder->where('performance_details.user_id', $userId);
                $builder->orderBy('performance_details.id', 'DESC');
                $performanceQuery = $builder->get();
                $performanceDetail = $performanceQuery->getResultArray();
                /* #### End Performance Detail #### */

                /* ##### Team Transfer Detail ##### */
                $teamTransferModel = new TeamTransferModel();
                $logoPath = base_url() . 'uploads/logos/';
                $profilePath = base_url() . 'uploads/';
                $logoPath = base_url() . 'uploads/logos/';
                $profilePath = base_url() . 'uploads/';
                $builder = $teamTransferModel->builder();
                $builder->select('
                team_transfers.*,

                cb1.club_name as team_name_from,
                t1.team_type as team_type_from,
                cb1.club_logo as team_logo_from,
                CONCAT("' . $profilePath . '", cb1.club_logo) AS team_logo_path_from,
                c1.country_name as country_name_from,
                c1.country_flag as country_flag_from,
                CONCAT("' . $logoPath . '", c1.country_flag) AS country_flag_path_from,

                cb2.club_name as team_name_to,
                t2.team_type as team_type_to,
                cb2.club_logo as team_logo_to,
                CONCAT("' . $profilePath . '", cb2.club_logo) AS team_logo_path_to,
                c2.country_name as country_name_to,
                c2.country_flag as country_flag_to,
                CONCAT("' . $logoPath . '", c2.country_flag) AS country_flag_path_to
                ');

                $builder->join('teams t1', 't1.id = team_transfers.team_from', 'LEFT');
                $builder->join('clubs cb1', 'cb1.id = t1.club_id', 'LEFT');
                $builder->join('countries c1', 'c1.id = t1.country_id', 'LEFT');

                $builder->join('teams t2', 't2.id = team_transfers.team_to', 'LEFT');
                $builder->join('clubs cb2', 'cb2.id = t2.club_id', 'LEFT');
                $builder->join('countries c2', 'c2.id = t2.country_id', 'LEFT');

                $builder->where('team_transfers.user_id', $userId);
                $builder->orderBy('team_transfers.id', 'DESC');
                $transferQuery = $builder->get();
                $transferDetail = $transferQuery->getResultArray();
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
                if (isset($userData['first_name']) && !empty($userData['last_name'])) {
                    $fullName = trim($userData['first_name']) . ' ' . trim($userData['last_name']);
                }
                if (isset($userData['current_club_name']) && !empty($userData['current_club_name'])) {
                    $current_club_name = $userData['current_club_name'];
                }
                if (empty($userData['current_club_name']) && !empty($userData['pre_current_club_name'])) {
                    $current_club_name = $userData['pre_current_club_name'];
                }
                if (isset($userData['role_name']) && !empty($userData['role_name'])) {
                    $role_name = $userData['role_name'];
                }
                if (isset($userData['meta']['height']) && !empty($userData['meta']['height'])) {
                    $height = $userData['meta']['height'];
                    if (!empty($userData['meta']['height_unit'])) {
                        $height .= ' ' . $userData['meta']['height_unit'];
                    }
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
                $content .= '<strong>' . lang('App.current_club') . '</strong><br />';
                if (!empty($userData['club_logo_path'])) {
                    $club_logo_path = $userData['club_logo_path'];
                } else if (isset($userData['pre_current_club_logo_path']) && !empty($userData['pre_current_club_logo_path'])) {
                    $club_logo_path = $userData['pre_current_club_logo_path'];
                } else {
                    $club_logo_path = 'https://apitest.socceryou.ch/no-img.png';
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
                $content .= '<strong>' . lang('App.height') . '</strong><br />';
                $content .= '<img src="' . $flagPath . 'Icons19.png" style="width: 25px;vertical-align: middle;margin-right: 4px;margin-top: 3px;float: left;vertical-align:bottom;">' . $height;
                $content .= '</td>';
                $content .= '<td>';
                $content .= '<strong>' . lang('App.weight') . '</strong><br />';
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
                // echo '<pre>'; print_r($userData); die;
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
                // echo '<pre>'; print_r($images); die;
                if (!empty($images)) {
                    $img_file_path    = base_url() . 'uploads/';
                    foreach ($images as $image) {
                        $imagepathFull = $img_file_path . $image['file_name'];
                        // Check if it's a URL or local file
                        if (filter_var($imagepathFull, FILTER_VALIDATE_URL)) {
                            // It's a URL, so get headers
                            $headers = get_headers($imagepathFull, 1);
                            if (!empty($headers[0]) && strpos($headers[0], '200') !== false) {
                                $content .= '<img src="' . $imagepathFull . '" style="width: 23%;margin-left: 4px;margin-top: 4px;" class="gallery-imgs">';
                            }
                        } elseif (file_exists($imagepathFull)) {
                            // It's a local file, check if it exists
                            $content .= '<img src="' . $imagepathFull . '" style="width: 23%;margin-left: 4px; margin-top: 4px;" class="gallery-imgs">';
                        }
                    }
                }
                // echo $content; die(' ~$content');
                $dob =  '';
                if (!empty($userData['meta']['date_of_birth'])) {
                    $dob = $userData['meta']['date_of_birth'];
                }
                $age = calculateAge($dob);
                $in_team_since = '';
                if (!empty($userData['meta']['in_team_since']) && trim($userData['meta']['in_team_since']) != 'Invalid date') {
                    // $in_team_since = $userData['meta']['in_team_since'];
                    $dateTime = new DateTime($userData['meta']['in_team_since']);
                    $in_team_since = $dateTime->format('d.M.Y');
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
                $content .= '    <h3>' . lang('App.age') . '</h3>';
                $content .= '   <p><img src="' . $flagPath . 'Icons20.png" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;"> ' . $age . ' Years</p>';
                $content .= '     </td>';
                if (!empty($in_team_since)) {
                    $content .= '    <td class="detail_list no_border">';
                    $content .= '       <h3>' . lang('App.in_team_since') . '</h3>';
                    $content .= '          <p>' . $in_team_since . '</p>';
                    $content .= '     </td>';
                }
                if (!empty($top_speed)) {
                    $content .= '      <td class="detail_list no_border">';
                    $content .= '          <h3>' . lang('App.top_speed') . '</h3>';
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
                    $content .= '<h3>' . lang('App.nationality') . '</h3>';
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
                    $content .= '          <h3>' . lang('App.market_value') . '</h3>';
                    $content .= '          <p>' . $market_value . '</p>';
                    $content .= '      </td>';
                }
                $content .= '      <td class="detail_list no_border">';
                $internationl_logo = $flagPath . 'Icons8.png';
                if (!empty($international_player)) {
                    $internationl_logo = base_url() . 'public/assets/images/' . $international_player . '.svg';
                    $internationl_flag = get_headers($internationl_logo, 1);
                    if (strpos($internationl_flag[0], '200') !== false) {
                        $content .= '<h3>' . lang('App.international_player') . '</h3>';
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
                $content .= '          <h3>' . lang('App.dob') . '</h3>';
                $content .= '          <p><img src="' . $flagPath . 'Icons11.png" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;"> ' . $date_of_birth . '</p>';
                $content .= '      </td>';

                if (!empty($userData['meta']['last_change']) && trim($userData['meta']['last_change']) != 'Invalid date') {
                    $content .= '      <td colspan="2" class="detail_list">';
                    $content .= '          <h3>' . lang('App.last_change') . '</h3>';
                    $dateTime = new DateTime($userData['meta']['last_change']);
                    $last_change = $dateTime->format('d.M.Y');
                    $content .= '<p>' . $last_change . '</p>';
                    $content .= '</td>';
                }
                $content .= '</tr>';
                $content .= '<tr>';
                $content .= '<td class="detail_list">';
                $content .= '<h3>' . lang('App.place_of_birth') . '</h3>';
                if (!empty($userData['meta']['place_of_birth'])) {
                    $parts = explode(',', $userData['meta']['place_of_birth']);
                    $country = trim(end($parts));
                    $countryModel = new CountryModel();

                    // Fetch country_name and country_code from countries table
                    $countries = $countryModel->select('country_name, country_code, country_flag')
                        ->where('country_name', $country)  // Replace 'keyword' with the term you're searching for
                        ->findAll();
                    //  echo '<pre>'; print_r($countries); die;
                    // $country_flag = strtolower(getCountryCode($country));
                    // $birth_country_logo = 'https://flagcdn.com/'.$country_code.'.svg';
                    $birth_country_logo = $flagPath . 'flag-1.png';
                    $place_of_birth_logo =  $flagPath . 'flag-1.png';
                    if (isset($countries) && !empty($countries['0']['country_flag'])) {
                        $birth_country_logo = $logoPath . '' . $countries['0']['country_flag'];
                    }
                    $fileContents = file_get_contents($birth_country_logo);
                    // Check if the file is a valid SVG by looking for SVG tags
                    if (strpos($fileContents, '<svg') !== false && strpos($fileContents, '</svg>') !== false) {
                        $place_of_birth_logo = $birth_country_logo;
                    }
                    //  else {
                    //
                    // }
                    //   echo $abc; die;
                    // echo pdfImageExists($birth_country_logo); die;
                    // if (pdfImageExists($birth_country_logo)) {

                    // } else {

                    // }
                    // echo $place_of_birth_logo; die;
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
                            $content .= '<td><h3>' . lang('App.main_position') . '</h3>' . $position['position_name'] . '</td>';
                        } else if (empty($position['main_position']) && !empty($position['position_name'])) {
                            $otherPositions[] = $position['position_name'];
                        }
                    }
                    if (!empty($otherPositions)) {
                        $content .= '<td><h3>' . lang('App.other_position') . '</h3>' . implode("/", $otherPositions) . '</td>';
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
                $content .= '<h3>' . lang('App.contract') . '</h3>';
                $content .= '<p><img src="' . $flagPath . 'Icons15.png" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;">' . $contract . '</p>';
                $content .= '</td>';
                $content .= '</tr>';
                $content .= '<tr>';
                $content .= '<td class="detail_list">';
                if (isset($userData['league_name']) && !empty($userData['league_name'])) {
                    if (isset($userData['league_logo_path']) && !empty($userData['league_logo_path'])) {
                        $league_logo_path = $userData['league_logo_path'];
                        $content .= '<h3>' . lang('App.leauge') . '</h3>';
                        $leaguepath = get_headers($league_logo_path, 1);
                        if (strpos($leaguepath[0], '200') !== false) {
                            $content .= '<p>';
                            $content .= '<img src="' . $league_logo_path . '?' . rand() . '" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;">';
                        }
                    }
                    $content .= $userData['league_name'] . '<p>';
                }
                // $content .= '</div>';
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
                        $content .= '<h3>' . lang('App.foot') . '</h3>';
                        $content .= '<p><img src="' . $foot_image . '" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;"> ' . ucfirst(strtolower($foot)) . '</p>';
                    }
                }
                $content .= '</td>';
                $content .= '</tr>';
                $content .= '</table>';
                if (!empty($transferDetail)) {
                    $content .= '<div class="transfer_history" style="page-break-before:always;">';
                    $content .= '   <h2 style="margin-top: 5px;padding: 0 10px;">' . lang('App.transfer_history') . '</h2>';
                    $content .= '  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="table_transfer">';
                    $content .= '     <thead>';
                    $content .= '         <tr>';
                    $content .= '         <th style="white-space: nowrap;"><img src="' . $flagPath . 'Icons6.png" style="width: 15px;vertical-align: middle;margin-right: 4px;float: left;"><span>' . lang('App.saison') . '</span></th>';
                    $content .= '         <th style="white-space: nowrap;"><img src="' . $flagPath . 'Icons20.png" style="width: 15px;vertical-align: middle;margin-right: 4px;float: left;"><span>' . lang('App.date') . '</span></th>';
                    $content .= '         <th>' . lang('App.moving_from') . '</th>';
                    $content .= '         <th>' . lang('App.moving_to') . '</th>';
                    $content .= '     </tr>';
                    $content .= '  </thead>';
                    $content .= '  <tbody>';
                    foreach ($transferDetail as $transfer) {

                        $session = !empty($transfer['session']) ? $transfer['session'] : ' ';
                        // $formattedDate = !empty($date) ? $date : ' ';
                        $formattedDate = '';
                        if (isset($transfer['date_of_transfer']) && !empty($transfer['date_of_transfer']) && trim($transfer['date_of_transfer']) != 'Invalid date') {
                            $date = $transfer['date_of_transfer'];
                            $dateTime = new DateTime($date);
                            $formattedDate = $dateTime->format('d.m.y');
                        }

                        $teamFrom = !empty($transfer['team_name_from']) ? $transfer['team_name_from'] : ' ';
                        $countryFrom = !empty($transfer['country_name_from']) ? $transfer['country_name_from'] : ' ';
                        if (empty($transfer['team_logo_path_from'])) {
                            $transfer['team_logo_path_from'] = base_url() . 'uploads/pdf-icons/no_pic.png';
                        }
                        $headersFrom = get_headers($transfer['team_logo_path_from'], 1);
                        $teamLogoFrom = (strpos($headersFrom[0], '200') !== false) ? $transfer['team_logo_path_from'] : '';
                        $teamLogoFromCountry = (strpos($headersFrom[0], '200') !== false) ? $transfer['country_flag_path_from'] : '';
                        $teamTo = !empty($transfer['team_name_to']) ? $transfer['team_name_to'] : '';
                        $countryTo = !empty($transfer['country_name_to']) ? $transfer['country_name_to'] : ' ';
                        if (empty($transfer['team_logo_path_to'])) {
                            $transfer['team_logo_path_to'] = base_url() . 'uploads/pdf-icons/no_pic.png';
                        }
                        $headersTo = get_headers($transfer['team_logo_path_to'], 1);
                        $teamLogoTo = (strpos($headersTo[0], '200') !== false) ? $transfer['team_logo_path_to'] : '';
                        $teamLogoToCountry = (strpos($headersTo[0], '200') !== false) ? $transfer['country_flag_path_to'] : '';
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

                // echo '<pre>';
                // print_r($performanceDetail);
                // die(' Done');
                if (!empty($performanceDetail)) {
                    $content .= '<div class="performance_data">';
                    $content .= ' <h2 style="margin-top: 5px;padding: 0 10px;">' . lang('App.performance_data') . '</h2>';
                    $content .= '<table width="100%" border="0" cellspacing="0" cellpadding="0" class="table_perform">';
                    $content .= '<thead>';
                    $content .= '  <tr>';
                    $content .= '  <th><img src="' . $flagPath . 'flag-1.png" style="width: 15px;vertical-align: middle;margin-right: 4px;float: left;margin-top: 3px;"><span>' . lang('App.team') . '</span></th>';
                    $content .= ' <th><img src="' . $flagPath . 'Icons6.png" style="width: 15px;vertical-align: middle;margin-right: 4px;float: left;"><span>' . lang('App.saison') . '</span></th>';
                    $content .= ' <th><img src="' . $flagPath . 'Icons13.png" style="width: 15px;vertical-align: middle;margin-right: 4px;float: left;"><span>' . lang('App.matches') . '</span></th>';
                    $content .= ' <th><img src="' . $flagPath . 'Icons17.png" style="width: 15px;vertical-align: middle;margin-right: 4px;float: left;"><span>' . lang('App.goals') . '</span></th>';
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
                        if (isset($performance['team_club_logo_path']) && !empty($performance['team_club_logo_path'])) {
                            $team_logo_path = get_headers($performance['team_club_logo_path'], 1);
                            if (strpos($team_logo_path[0], '200') !== false) {
                                $team_logo_path = $performance['team_club_logo_path'];
                                $team_img = '<img src="' . $team_logo_path . '" style="width: 15px;vertical-align: middle;margin-right: 4px;float: left;">';
                            }
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
            }

            /* ##### ~~ End Player PDF ~~ ##### */

            /* ##### ~~ Scout PDF ~~ ##### */
            if (isset($userData['role']) && strtoupper($userData['role']) == '3') { // SCOUT

                $company_name = '-';
                if (isset($userData['meta']['company_name']) && !empty($userData['meta']['company_name'])) {
                    $company_name = trim($userData['meta']['company_name']);
                }
                $company_history = '-';
                if (isset($userData['meta']['company_history']) && !empty($userData['meta']['company_history'])) {
                    $company_history = trim($userData['meta']['company_history']);
                }
                $contact_number = '-';
                if (isset($userData['meta']['contact_number']) && !empty($userData['meta']['contact_number'])) {
                    $contact_number = trim($userData['meta']['contact_number']);
                }
                $designation = '-';
                if (isset($userData['meta']['designation']) && !empty($userData['meta']['designation'])) {
                    $designation = trim($userData['meta']['designation']);
                }
                $address = '-';
                if (isset($userData['meta']['address']) && !empty($userData['meta']['address'])) {
                    $address = trim($userData['meta']['address']);
                }
                $zipcode = '-';
                if (isset($userData['meta']['zipcode']) && !empty($userData['meta']['zipcode'])) {
                    $zipcode = trim($userData['meta']['zipcode']);
                }
                $city = '-';
                if (isset($userData['meta']['city']) && !empty($userData['meta']['city'])) {
                    $city = trim($userData['meta']['city']);
                }

                $scout_id = $userId;
                // check if representator
                // if (in_array(auth()->user()->role, REPRESENTATORS_ROLES)) {
                //     $authUser = auth()->user();
                //     $scout_id = $authUser->parent_id;
                // }
                $imagePath = base_url() . 'uploads/';
                $logoPath = base_url() . 'uploads/logos/';
                $pdf_icons = base_url() . 'uploads/pdf-icons/';
                $this->scoutPlayerModel = new ScoutPlayerModel();
                $scoutPlayers = $this->scoutPlayerModel
                    ->select('scout_players.*,
                                            u.first_name,
                                            u.last_name,
                                            u.status as user_status,
                                            l.language,
                                            CONCAT("' . $imagePath . '", um.meta_value) AS profile_image_path,

                                            cp.team_id,
                                            cp.end_date as expiry_date,

                                            t.team_type,
                                            um2.meta_value AS club_name,
                                            CONCAT("' . $imagePath . '", um3.meta_value) AS club_logo_path,
                                            c.country_name,
                                            CONCAT("' . $logoPath . '", c.country_flag) AS country_flag_path

                                        ')
                    ->join('users u', 'u.id = scout_players.player_id', 'INNER')
                    ->join('languages l', 'l.id = u.lang', 'INNER')
                    ->join('(SELECT user_id, meta_value FROM user_meta WHERE meta_key = "profile_image") um', 'um.user_id = scout_players.player_id', 'LEFT')
                    ->join('(SELECT team_id, player_id, end_date FROM club_players WHERE status = "active") cp', 'cp.player_id = scout_players.player_id', 'LEFT')
                    ->join('teams t', 't.id = cp.team_id', 'INNER')
                    ->join('(SELECT user_id, meta_value FROM user_meta WHERE meta_key = "club_name") um2', 'um2.user_id = t.club_id', 'LEFT')
                    ->join('(SELECT user_id, meta_value FROM user_meta WHERE meta_key = "profile_image") um3', 'um3.user_id = t.club_id', 'LEFT')
                    ->join('countries c', 'c.id = t.country_id', 'INNER')
                    ->where('scout_players.is_accepted', 1)
                    ->where('scout_players.scout_id', $scout_id)
                    ->findAll();

                $content .= '<body class="body" style="padding:0 !important; margin:0 !important; display:block !important; min-width:100% !important; width:100% !important;-webkit-text-size-adjust:none">';
                $content .= '<div width="100%" border="0" cellspacing="0" cellpadding="0">';
                $content .= '<div style="width:100%; min-width:650px; padding:0; margin:0 auto; font-weight:normal;">';
                $content .= '<table width="100%" border="0" cellspacing="0" cellpadding="0" class="header">';
                $content .= '<tr>';
                $content .= '<td style="width: 50%;padding-left: 0;">';
                $content .= '<table width="100%" border="0" cellspacing="0" cellpadding="0" class="Inner_header">';
                if (!empty($role_name)) {
                    $content .= '<tr>';
                    $content .= '<td>';
                    $content .= '<img src="' . $flagPath . 'logo.png" style="width: 150px;vertical-align: top;margin-top: -4px;">';
                    $content .= '<span style="font-size: 20px;padding-left: 5px;">' . strtoupper($role_name) . ' PROFILE</span>';
                    $content .= '</td>';
                    $content .= '</tr>';
                }
                if (!empty($fullName)) {
                    $content .= '<tr>';
                    $content .= '<td style="line-height: 1;">';
                    $content .= '<h1 style="width: 100%;float: left;font-size: 40px;margin: 20px 0;">' . $fullName . '</h1>';
                    $content .= '</td>';
                    $content .= '</tr>';
                }
                if (isset($userData['meta']['designation']) && !empty($userData['meta']['designation'])) {
                    $designation = trim($userData['meta']['designation']);
                    $content .= '<tr>';
                    $content .= '<td class="current_club">';
                    $content .= '<strong>' . lang('App.designation') . '</strong><br />';
                    $content .= '<p><img src="' . $pdf_icons . 'designation.svg" style="width: 20px;vertical-align: middle;margin-right: 4px;margin-top: 3px;float: left;vertical-align:bottom;">';
                    $content .= '' . $designation . '</p>';
                    $content .= '</td>';
                    $content .= '</tr>';
                }
                $content .= ' <tr>';
                $content .= ' <td>';
                $content .= '  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="Inner_header1">';
                if (isset($userData['meta']['company_name']) && !empty($userData['meta']['company_name'])) {
                    $company_name = trim($userData['meta']['company_name']);
                    $content .= '      <tr>';
                    $content .= '<td>';
                    $content .= '<strong>' . lang('App.company_name') . '</strong><br />';
                    $content .= '<p><img src="' . $pdf_icons . 'foot__ball.svg" style="width: 20px;vertical-align: middle;margin-right: 4px;margin-top: 3px;float: left;vertical-align:bottom;">' . $company_name . '</p>';
                    $content .= '</td>';
                    $content .= '      </tr>';
                }
                if (isset($userData['meta']['contact_number']) && !empty($userData['meta']['contact_number'])) {
                    $contact_number = trim($userData['meta']['contact_number']);
                    $content .= '      <tr>';
                    $content .= '<td>';
                    $content .= '<strong>' . lang('App.contact_number') . '</strong><br />';
                    $content .= '<p><img src="' . $pdf_icons . 'mobile.svg" style="width: 20px;vertical-align: middle;margin-right: 4px;margin-top: 3px;float: left;vertical-align:bottom;">' . $contact_number . '</p>';
                    $content .= '</td>';
                    $content .= '      </tr>';
                }
                $content .= '  </table>';
                $content .= ' </td>';
                $content .= ' </tr>';
                $content .= '  </table>';
                $content .= ' </td>';
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
                $content .= '</table>';
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
                $content .= '</table>';
                $content .= '  </td>';
                $content .= '</tr>';
                $content .= '</table>';
                if (isset($userData['meta']['company_history']) && !empty($userData['meta']['company_history'])) {
                    $company_history = trim($userData['meta']['company_history']);
                    $content .= '<table width="100%" border="0" cellspacing="0" cellpadding="0" style="background: #fff;">';
                    $content .= '<tr colspan="3">';
                    $content .= '<td style="width: 100%;">';
                    $content .= '<h1 style="text-align: center;">' . lang('App.about_scout') . '</h1>';
                    $content .= '<p>' . $company_history . '</p>';
                    $content .= '</td>';
                    $content .= '</tr>';
                    $content .= '</table>';
                }

                $content .= '<table width="100%" border="0" cellspacing="0" cellpadding="0" class="player_detail" style="background: #fff;">';
                if (!empty($images)) {
                    $content .= '<tr>';
                    $content .= '  <td colspan="3" style="padding: 0;">';
                    foreach ($images as $image) {
                        $imagepathFull = $imagePath . $image['file_name'];
                        if (filter_var($imagepathFull, FILTER_VALIDATE_URL)) {
                            $headers = get_headers($imagepathFull, 1);
                            if (!empty($headers[0]) && strpos($headers[0], '200') !== false) {
                                $content .= '<img src="' . $imagepathFull . '" style="width: 23%;margin-left: 4px;">';
                            }
                        } elseif (file_exists($imagepathFull)) {
                            $content .= '<img src="' . $imagepathFull . '" style="width: 23%;margin-left: 4px;">';
                        }
                    }
                    $content .= '</td>';
                    $content .= '</tr>';
                }
                $content .= '  <tr>';
                if (isset($userData['meta']['address']) && !empty($userData['meta']['address'])) {
                    $address = trim($userData['meta']['address']);
                    $content .= '  <td class="detail_list">';
                    $content .= '    <h3>' . lang('App.address') . '</h3>';
                    $content .= '   <p><img src="' . $pdf_icons . 'location-pin.svg" style="width: 20px;vertical-align: middle;margin-right: 4px;float: left;"> ' . $address . '</p>';
                    $content .= '     </td>';
                }
                if (isset($userData['meta']['zipcode']) && !empty($userData['meta']['zipcode'])) {
                    $zipcode = trim($userData['meta']['zipcode']);
                    // $content .= '  <tr>';
                    $content .= '  <td class="detail_list">';
                    $content .= '    <h3>' . lang('App.zip_code') . '</h3>';
                    $content .= '   <p><img src="' . $pdf_icons . 'post-box.svg" style="width: 20px;vertical-align: middle;margin-right: 4px;float: left;"> ' . $zipcode . '</p>';
                    $content .= '     </td>';
                    // $content .= '  </tr>';
                }
                if (isset($userData['meta']['city']) && !empty($userData['meta']['city'])) {
                    $city = trim($userData['meta']['city']);
                    $content .= '      <td class="detail_list no_border">';
                    $content .= '          <h3>' . lang('App.city') . '</h3>';
                    $content .= '   <p><img src="' . $pdf_icons . 'location-pin.svg" style="width: 20px;vertical-align: middle;margin-right: 4px;float: left;"> ' . $city . '</p>';
                    // $content .= '          <p>' . $city . '</p>';
                    $content .= '      </td>';
                }
                $content .= '  </tr>';
                $content .= '  <tr>';
                if (isset($userData['meta']['website']) && !empty($userData['meta']['website'])) {
                    $website = trim($userData['meta']['website']);
                    $content .= '  <td class="detail_list">';
                    $content .= '    <h3>' . lang('App.website') . '</h3>';
                    $content .= '   <p><img src="' . $pdf_icons . 'website.svg" style="width: 20px;vertical-align: middle;margin-right: 4px;float: left;"> ' . $website . '</p>';
                    $content .= '     </td>';
                }
                if (isset($userData['user_nationalities']) && !empty($userData['user_nationalities'])) {
                    $user_nationalities = json_decode($userData['user_nationalities'], true);
                    $country_name = $user_nationalities['0']['country_name'];
                    $content .= '  <td class="detail_list">';
                    $content .= '    <h3>' . lang('App.country') . '</h3>';
                    $content .= '   <p>';
                    $user_nationalities['0']['flag_path'] = str_replace('public/assets/images/', 'uploads/logos/', $user_nationalities['0']['flag_path']);
                    if (pdfImageExists($user_nationalities['0']['flag_path'])) {
                        $content .= '<img src="' . $user_nationalities['0']['flag_path'] . '" style="width: 25px; vertical-align: middle; margin-right: 4px; float: left;">';
                    }
                    $content .= $country_name;
                    $content .= '</p>';
                    $content .= '     </td>';
                }
                $content .= '  </tr>';
                $content .= '</table>';
                if (isset($scoutPlayers) && !empty($scoutPlayers)) {
                    //     echo '<pre>'; print_r($scoutPlayers); die;
                    $content .= '<div class="transfer_history">';
                    // $content .= '<div class="transfer_history" style="page-break-before:always;">';
                    $content .= '<h2 style="margin-top: 5px;padding: 0 10px;">' . lang('App.portfolio') . '</h2>';
                    $content .= '<table width="100%" border="0" cellspacing="0" cellpadding="0" class="table_transfer">';
                    $content .= '<thead>';
                    $content .= '<tr>';
                    $content .= '<th style="white-space: nowrap;"><span>' . lang('App.name') . '</span></th>';
                    $content .= '<th style="white-space: nowrap;"><span>' . lang('App.language') . '</span></th>';
                    $content .= '<th style="white-space: nowrap;"><span>' . lang('App.club') . '</span></th>';
                    $content .= '</tr>';
                    $content .= '</thead>';
                    $content .= '<tbody>';
                    foreach ($scoutPlayers as $scoutPlayer) {
                        $status_icon = '';
                        if (isset($scoutPlayer['is_accepted']) && $scoutPlayer['is_accepted'] == 'accepted') {
                            $status_icon = 'approved_icon';
                        } else if (isset($scoutPlayer['is_accepted']) && $scoutPlayer['is_accepted'] == 'rejected') {
                            $status_icon = 'rejected_icon';
                        }
                        $content .= '<tr>';
                        ##### First TD #####
                        $no_user_img = base_url() . 'uploads/pdf-icons/no_pic.png';
                        $pdf_icons_path = base_url() . 'uploads/pdf-icons/';
                        $playerName =  trim($scoutPlayer['first_name']) . ' ' . trim($scoutPlayer['last_name']);
                        $ProfileImg = $no_user_img;
                        ############################
                        if (!empty($scoutPlayer['profile_image_path'])) {
                            // $profile_image_path = get_headers($scoutPlayer['profile_image_path'], 1);
                            // if (strpos($profile_image_path[0], '200') !== false) {
                            // $ProfileImg =  $scoutPlayer['profile_image_path'];
                            // }
                            if (pdfImageExists($scoutPlayer['profile_image_path'])) {
                                $ProfileImg =  $scoutPlayer['profile_image_path'];
                            }
                        }
                        ############################
                        $content .= '<td>';
                        $content .= '<table class="name_tab">';
                        $content .= '<tbody><tr>';
                        $content .= '<td style="padding: 0; vertical-align: middle;">';
                        $content .= '<img src="' . $ProfileImg . '" class="team_player_profile">';
                        $content .= '<img src="' . $pdf_icons_path . $status_icon . '.svg" class="team_player_profile_status">';
                        $content .= '</td>';
                        $content .= '<td style="vertical-align: middle;">' . $playerName . '</td>';
                        $content .= '</tr>';
                        $content .= '</tbody></table>';
                        $content .= '</td>';
                        ##### End First TD #####
                        ##### 2nd TD #####
                        $content .= '<td>';
                        $content .=  trim($scoutPlayer['language']);
                        $content .= '</td>';
                        ##### End 2nd TD #####
                        ##### 3rd TD #####
                        $content .= '<td>';
                        // $club_logo_path = get_headers($scoutPlayer['club_logo_path'], 1);
                        if (pdfImageExists($scoutPlayer['club_logo_path'])) {
                            // $ProfileImg =  $scoutPlayer['profile_image_path'];
                            $club_logo = $scoutPlayer['club_logo_path'];
                            $content .= '<img src="' . $club_logo . '" style="width: 20px; vertical-align: middle; margin-right: 5px; margin-top: -2px;">';
                        }
                        $content .= $scoutPlayer['club_name'];
                        // $country_flag_path = get_headers($scoutPlayer['country_flag_path'], 1);
                        if (pdfImageExists($scoutPlayer['country_flag_path'])) {
                            $country_flag = $scoutPlayer['country_flag_path'];
                            $content .= '<img src="' . $country_flag . '" style="width: 20px; vertical-align: middle; margin-right: 5px; margin-top: -2px;">';
                        }
                        $content .= '</td>';
                        ##### End 3rd TD #####
                        ##### 4th TD #####
                        ##### End 4th TD #####
                        $content .= '</tr>';
                    }
                    // $content .= '';
                    $content .= '</tbody>';
                    $content .= '</table>';
                    $content .= '</div>';
                }
            }
            /* ##### ~~ End Scout PDF ~~ ##### */
            /* ##### ~~ CLUB PDF ~~ ##### */
            if (isset($userData['role']) && strtoupper($userData['role']) == '2') { // CLUB
                $company_name = '-';
                $club_id = $userId;
                $this->teamModel    = new TeamModel();
                $teamsQuery = $this->teamModel
                    ->select('
                        teams.*,
                        um.meta_value as team_name,
                        um2.meta_value as team_club_logo,
                        CONCAT("' . $imagePath . '", um2.meta_value ) AS team_club_logo_path,
                        c.country_name
                    ')
                    ->join('(SELECT user_id, meta_value FROM user_meta WHERE meta_key = "club_name" AND user_id = ' . $club_id . ' ) um', 'um.user_id = teams.club_id', 'INNER')
                    ->join('(SELECT user_id, meta_value FROM user_meta WHERE meta_key = "profile_image" AND user_id = ' . $club_id . ' ) um2', 'um2.user_id = teams.club_id', 'INNER')
                    ->join('countries c', 'c.id = teams.country_id', 'INNER');

                if ($club_id != null) {
                    $teamsQuery->where('club_id', $club_id);
                }
                $club_and_team = array();
                $teams = $teamsQuery->findAll();
                if (isset($teams) && !empty($teams)) {
                    $coo = 1;
                    foreach ($teams as $Team) {
                        $team_id = $Team['id'];
                        if (!empty($team_id) && is_numeric($team_id)) {
                            $imagePath = base_url() . 'uploads/';
                            $flagPath =  base_url() . 'uploads/logos/';
                            $builder = $this->db->table('club_players cp');
                            $builder->select('cp.*,
                            u.first_name,
                            u.last_name,
                            u.status as user_status,
                            d.location as location,
                            um2.meta_value as profile_image,
                            CONCAT("' . $imagePath . '", um2.meta_value) AS profile_image_path,

                            (SELECT JSON_ARRAYAGG(
                            JSON_OBJECT(
                                "country_name", c.country_name,
                                "flag_path", CONCAT("' . $flagPath . '", c.country_flag)
                            )
                            )
                            FROM user_nationalities un
                            LEFT JOIN countries c ON c.id = un.country_id
                            WHERE un.user_id = cp.player_id) AS user_nationalities,

                            t.team_type as team_type,
                            um3.meta_value as current_club_name,
                            CONCAT("' . $imagePath . '", um4.meta_value) as club_logo_path,

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
                                WHERE pp.user_id = cp.player_id
                            ) As positions,

                            (SELECT
                                JSON_OBJECTAGG(
                                        umm.meta_key, umm.meta_value
                                )
                                FROM user_meta umm
                                WHERE umm.user_id = cp.player_id
                            ) AS meta

                            ');

                            $builder->join('users u', 'u.id = cp.player_id', 'INNER');
                            $builder->join('domains d', 'd.id = u.user_domain', 'INNER');
                            $builder->join('user_meta um2', 'um2.user_id = cp.player_id AND um2.meta_key = "profile_image"', 'LEFT');

                            // to get club details
                            // $builder->join('cp cp', 'cp.player_id = cp.player_id AND cp.status = "active"', 'LEFT');
                            $builder->join('teams t', 't.id = cp.team_id', 'LEFT');
                            $builder->join('user_meta um3', 'um3.user_id = t.club_id AND um3.meta_key = "club_name"', 'LEFT');
                            $builder->join('user_meta um4', 'um4.user_id = t.club_id AND um4.meta_key = "profile_image"', 'LEFT');

                            // to get club's league
                            $builder->join('league_clubs lc', 'lc.club_id = t.club_id', 'LEFT');
                            $builder->join('leagues l', 'l.id = lc.league_id', 'LEFT');

                            $builder->join('user_meta um5', 'um5.user_id = cp.player_id AND um5.meta_key = "international_player"', 'LEFT');
                            $builder->join('countries c1', 'c1.id = um5.meta_value', 'LEFT');

                            $builder->where('cp.team_id', $team_id);
                            // getResultArray
                            $query = $builder->get();
                            $playersDetail = $query->getResultArray();
                            $team_name = $Team['team_name'];
                            $club_and_team['club_' . $coo] = ['team_name' => $team_name, 'Players' => $playersDetail];
                        } else {
                            $club_and_team['club_' . $coo] = array();
                        }
                        $coo++;
                    }
                } else {
                    $club_and_team[] = array();
                }
                if (isset($userData['meta']['company_name']) && !empty($userData['meta']['company_name'])) {
                    $company_name = trim($userData['meta']['company_name']);
                }
                $company_history = '-';
                if (isset($userData['meta']['company_history']) && !empty($userData['meta']['company_history'])) {
                    $company_history = trim($userData['meta']['company_history']);
                }
                $contact_number = '-';
                if (isset($userData['meta']['contact_number']) && !empty($userData['meta']['contact_number'])) {
                    $contact_number = trim($userData['meta']['contact_number']);
                }
                $designation = '-';
                if (isset($userData['meta']['designation']) && !empty($userData['meta']['designation'])) {
                    $designation = trim($userData['meta']['designation']);
                }
                $address = '-';
                if (isset($userData['meta']['address']) && !empty($userData['meta']['address'])) {
                    $address = trim($userData['meta']['address']);
                }
                $zipcode = '-';
                if (isset($userData['meta']['zipcode']) && !empty($userData['meta']['zipcode'])) {
                    $zipcode = trim($userData['meta']['zipcode']);
                }
                $city = '-';
                if (isset($userData['meta']['city']) && !empty($userData['meta']['city'])) {
                    $city = trim($userData['meta']['city']);
                }

                $scout_id = $userId;
                // check if representator
                // if (in_array(auth()->user()->role, REPRESENTATORS_ROLES)) {
                //     $authUser = auth()->user();
                //     $scout_id = $authUser->parent_id;
                // }

                $flagPath = base_url() . 'public/assets/images/';
                $pdf_icons = base_url() . 'uploads/pdf-icons/';
                $content .= '<body class="body" style="padding:0 !important; margin:0 !important; display:block !important; min-width:100% !important; width:100% !important;-webkit-text-size-adjust:none">';
                $content .= '<div width="100%" border="0" cellspacing="0" cellpadding="0">';
                $content .= '<div style="width:100%; min-width:650px; padding:0; margin:0 auto; font-weight:normal;">';
                $content .= '<table width="100%" border="0" cellspacing="0" cellpadding="0" class="header">';
                $content .= '<tr>';
                $content .= '<td style="width: 50%;padding-left: 0;">';
                $content .= '<table width="100%" border="0" cellspacing="0" cellpadding="0" class="Inner_header">';
                if (!empty($role_name)) {
                    $content .= '<tr>';
                    $content .= '<td>';
                    $content .= '<img src="' . $flagPath . 'logo.png" style="width: 150px;vertical-align: top;margin-top: -4px;">';
                    $content .= '<span style="font-size: 20px;padding-left: 5px;">' . strtoupper($role_name) . ' PROFILE</span>';
                    $content .= '</td>';
                    $content .= '</tr>';
                }
                if (!empty($fullName)) {
                    $content .= '<tr>';
                    $content .= '<td style="line-height: 1;">';
                    $content .= '<h1 style="width: 100%;float: left;font-size: 40px;margin: 20px 0;">' . $fullName . '</h1>';
                    $content .= '</td>';
                    $content .= '</tr>';
                }
                if (isset($userData['meta']['designation']) && !empty($userData['meta']['designation'])) {
                    $designation = trim($userData['meta']['designation']);
                    $content .= '<tr>';
                    $content .= '<td class="current_club">';
                    // $content .= '<strong>Designation</strong><br />';
                    $content .= '<strong>' . lang('App.designation') . '</strong><br />';
                    // $content .= '' . $designation;
                    $content .= '<p><img src="' . $pdf_icons . 'designation.svg" style="width: 20px;vertical-align: middle;margin-right: 4px;margin-top: 3px;float: left;vertical-align:bottom;">';
                    $content .= '' . $designation . '</p>';
                    $content .= '</td>';
                    $content .= '</tr>';
                }
                $content .= ' <tr>';
                $content .= ' <td>';
                $content .= '  <table width="100%" border="0" cellspacing="0" cellpadding="0" class="Inner_header1">';
                if (isset($userData['meta']['company_name']) && !empty($userData['meta']['company_name'])) {
                    $company_name = trim($userData['meta']['company_name']);
                    $content .= '      <tr>';
                    $content .= '<td>';
                    $content .= '<strong>' . lang('App.company_name') . '</strong><br />';
                    $content .= '<img src="' . $pdf_icons . 'foot__ball.svg" style="width: 25px;vertical-align: middle;margin-right: 4px;margin-top: 3px;float: left;vertical-align:bottom;">' . $company_name;
                    $content .= '</td>';
                    $content .= '      </tr>';
                }
                if (isset($userData['meta']['contact_number']) && !empty($userData['meta']['contact_number'])) {
                    $contact_number = trim($userData['meta']['contact_number']);
                    $content .= '      <tr>';
                    $content .= '<td>';
                    // $content .= '<strong>Contact Number</strong><br />';
                    $content .= '<strong>' . lang('App.contact_number') . '</strong><br />';
                    $content .= '<p><img src="' . $pdf_icons . 'mobile.svg" style="width: 20px;vertical-align: middle;margin-right: 4px;margin-top: 3px;float: left;vertical-align:bottom;">' . $contact_number . '</p>';
                    // $content .= '<img src="' . $flagPath . 'Icons18.png" style="width: 25px;vertical-align: middle;margin-right: 4px;margin-top: 3px;float: left;vertical-align:bottom;">' . $contact_number;
                    $content .= '</td>';
                    $content .= '      </tr>';
                }
                $content .= '  </table>';
                $content .= ' </td>';
                $content .= ' </tr>';
                $content .= '  </table>';
                $content .= ' </td>';
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
                $content .= '</table>';
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
                $content .= '</table>';
                $content .= '  </td>';
                $content .= '</tr>';
                $content .= '</table>';
                if (isset($userData['meta']['club_history']) && !empty($userData['meta']['club_history'])) {
                    $club_history = trim($userData['meta']['club_history']);
                    $content .= '<table width="100%" border="0" cellspacing="0" cellpadding="0" style="background: #fff;">';
                    $content .= '<tr colspan="3">';
                    $content .= '<td style="width: 100%;">';
                    $content .= '<h1 style="text-align: center;">' . lang('App.club_history') . '</h1>';
                    $content .= '<p>' . $club_history . '</p>';
                    $content .= '</td>';
                    $content .= '</tr>';
                    $content .= '</table>';
                }

                $content .= '<table width="100%" border="0" cellspacing="0" cellpadding="0" class="player_detail" style="background: #fff;">';
                if (!empty($images)) {
                    $content .= '<tr>';
                    $content .= '  <td colspan="3" style="padding: 0;">';
                    foreach ($images as $image) {
                        $imagepathFull = $imagePath . $image['file_name'];
                        if (filter_var($imagepathFull, FILTER_VALIDATE_URL)) {
                            $headers = get_headers($imagepathFull, 1);
                            if (!empty($headers[0]) && strpos($headers[0], '200') !== false) {
                                $content .= '<img src="' . $imagepathFull . '" style="width: 23%;margin-left: 4px;">';
                            }
                        } elseif (file_exists($imagepathFull)) {
                            $content .= '<img src="' . $imagepathFull . '" style="width: 23%;margin-left: 4px;">';
                        }
                    }
                    $content .= '</td>';
                    $content .= '</tr>';
                }
                $content .= '  <tr>';
                if (isset($userData['meta']['address']) && !empty($userData['meta']['address'])) {
                    $address = trim($userData['meta']['address']);
                    $content .= '  <td class="detail_list">';
                    $content .= '    <h3>' . lang('App.address') . '</h3>'; // '
                    $content .= '   <p><img src="'  . $pdf_icons . 'location-pin.svg" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;"> ' . $address . '</p>';
                    $content .= '     </td>';
                }
                if (isset($userData['meta']['zipcode']) && !empty($userData['meta']['zipcode'])) {
                    $zipcode = trim($userData['meta']['zipcode']);
                    // $content .= '  <tr>';
                    $content .= '  <td class="detail_list">';
                    $content .= '    <h3>' . lang('App.zip_code') . '</h3>';
                    $content .= '   <p><img src="' . $pdf_icons . 'post-box.svg" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;"> ' . $zipcode . '</p>';
                    $content .= '     </td>';
                    // $content .= '  </tr>';
                }
                if (isset($userData['meta']['city']) && !empty($userData['meta']['city'])) {
                    $city = trim($userData['meta']['city']);
                    $content .= '      <td class="detail_list no_border">';
                    $content .= '          <h3>' . lang('App.city') . '</h3>';
                    // $content .= '          <p>' . $city . '</p>';
                    $content .= '   <p><img src="' . $pdf_icons . 'location-pin.svg" style="width: 20px;vertical-align: middle;margin-right: 4px;float: left;"> ' . $city . '</p>';
                    $content .= '      </td>';
                }
                $content .= '  </tr>';
                $content .= '  <tr>';
                if (isset($userData['meta']['website']) && !empty($userData['meta']['website'])) {
                    $website = trim($userData['meta']['website']);
                    $content .= '  <td class="detail_list">';
                    $content .= '    <h3>' . lang('App.website') . '</h3>';
                    $content .= '   <p><img src="' . $pdf_icons . 'website.svg" style="width: 25px;vertical-align: middle;margin-right: 4px;float: left;"> ' . $website . '</p>';
                    $content .= '     </td>';
                }
                if (isset($userData['user_nationalities']) && !empty($userData['user_nationalities'])) {
                    $user_nationalities = json_decode($userData['user_nationalities'], true);
                    $country_name = $user_nationalities['0']['country_name'];
                    $content .= '  <td class="detail_list">';
                    $content .= '    <h3>' . lang('App.country') . '</h3>';
                    $content .= '   <p>';
                    $user_nationalities['0']['flag_path'] = str_replace('public/assets/images/', 'uploads/logos/', $user_nationalities['0']['flag_path']);
                    $nationality_flag = get_headers($user_nationalities['0']['flag_path'], 1);
                    if (strpos($nationality_flag[0], '200') !== false) {
                        $content .= '<img src="' . $user_nationalities['0']['flag_path'] . '" style="width: 25px; vertical-align: middle; margin-right: 4px; float: left;">';
                    }
                    $content .= $country_name;
                    $content .= '</p>';
                    $content .= '     </td>';
                }
                $content .= '  </tr>';
                $content .= '</table>';
                if (isset($club_and_team) && !empty($club_and_team)) {
                    // echo '<pre>'; print_r($club_and_team); die;
                    $content .= '<div class="transfer_history" style="page-break-before:always;">';
                    $content .= '<h2 style="margin-top: 5px;padding: 0 10px;">' . lang('App.portfolio') . '</h2>';
                    $content .= '<table width="100%" border="0" cellspacing="0" cellpadding="0" class="table_transfer">';
                    $content .= '<thead>';
                    $content .= '<tr>';
                    $content .= '<th style="white-space: nowrap;"><span>' . lang('App.team_name') . '</span></th>';
                    $content .= '<th style="white-space: nowrap;"><span>' . lang('App.player_name') . '</span></th>';
                    $content .= '<th style="white-space: nowrap;"><span>' . lang('App.joining_date') . '</span></th>';
                    $content .= '<th style="white-space: nowrap;"><span>' . lang('App.exit_date') . '</span></th>';
                    $content .= '<th style="white-space: nowrap;"><span>' . lang('App.location') . '</span></th>';
                    $content .= '</tr>';
                    $content .= '</thead>';
                    $content .= '<tbody>';
                    foreach ($club_and_team  as $club_key => $club_nd_team) {
                        if (!empty($club_nd_team['team_name']) && count($club_nd_team['Players']) > 0) {
                            $team_name = $club_nd_team['team_name'] . '';
                            $avoid_same = array();
                            $no_user_img = base_url() . 'uploads/pdf-icons/no_pic.png';
                            foreach ($club_nd_team['Players'] as $player) {
                                // $club_nd_team['Players']
                                if (!in_array($player['id'], $avoid_same)) {
                                    $playerName = trim($player['first_name']) . ' ' . trim($player['last_name']);
                                    $join_date = trim($player['join_date']);
                                    $end_date = trim($player['end_date']);
                                    $location = trim($player['location']);
                                    $profile_image_path = trim($player['profile_image_path']);
                                    if (!empty($profile_image_path)) {
                                        $profile = get_headers($profile_image_path, 1);
                                        if (strpos($profile[0], '200') !== false) {
                                            $profile_image_path = trim($player['profile_image_path']);
                                        } else {
                                            $profile_image_path = $no_user_img;
                                        }
                                    } else {
                                        $profile_image_path = $no_user_img;
                                    }

                                    if (!empty($join_date) && $join_date !== '0000-00-00') {
                                        // Validate if it's a proper date
                                        $timestamp = strtotime($join_date);
                                        if ($timestamp) {
                                            $join_date = date('d.m.Y', $timestamp);
                                        } else {
                                            $join_date = '';
                                        }
                                    } else {
                                        $join_date = '';
                                    }
                                    if (!empty($end_date) && $end_date !== '0000-00-00') {
                                        // Validate if it's a proper date
                                        $timestamp = strtotime($end_date);
                                        if ($timestamp) {
                                            $end_date = date('d.m.Y', $timestamp);
                                        } else {
                                            $end_date = '-';
                                        }
                                    } else {
                                        $end_date = '-';
                                    }
                                    $content .= '<tr>';
                                    ### Team Name ###
                                    $content .= '<td style="line-height: 50px;">' . $team_name . '</td>';
                                    ### End Team Name ###
                                    ### Player Name ###
                                    if ($player['user_status'] == "1") {
                                        $icon_img = 'pending';
                                    } elseif ($player['user_status'] == "2") {
                                        // $icon_img = 'approved';
                                        $icon_img = 'approved';
                                    } elseif ($player['user_status'] == "3") {
                                        $icon_img = 'rejected';
                                    }
                                    $status_icon = base_url() . 'uploads/pdf-icons/' . $icon_img . '_icon.svg';
                                    $content .= '<td>';
                                    $content .= '<table class="name_tab">';
                                    $content .= '<tbody><tr>';
                                    $content .= '<td style="padding: 0; vertical-align: middle;">';
                                    $content .= '<img src="' . $profile_image_path . '" class="team_player_profile">';
                                    $content .= '<img src="' . $status_icon . '" class="team_player_profile_status">';
                                    $content .= '</td>';
                                    $content .= '<td style="vertical-align: middle;">' . $playerName . '</td>';
                                    $content .= '</tr>';
                                    $content .= '</tbody></table>';
                                    $content .= '</td>';
                                    ### End Player Name ###
                                    ### Join Date ###
                                    $content .= '<td style="line-height: 50px;">' . $join_date . '</td>';
                                    ### End Join Date ###
                                    ### Exit Date ###
                                    $content .= '<td style="line-height: 50px;">' . $end_date . '</td>';
                                    ### End Exit Date ###
                                    ### Player Location ###
                                    $content .= '<td style="line-height: 50px;">' . $location . '</td>';
                                    ### End Player Location ###
                                    $content .= '</tr>';
                                }
                                $avoid_same[] = $player['id'];
                            }
                        }
                    }
                    $content .= '</tbody>';
                    $content .= '</table>';
                    $content .= '</div>';
                }
            }
            /* ##### ~~ END CLUB PDF ~~ ##### */
            /* ##### ~~ Common Footer ~~ ##### */
            $content .= ' <table width="100%" border="0" cellspacing="0" cellpadding="0" class="footer_table">';
            $content .= '<tr>';
            $content .= '<td style="text-align: center;font-weight: bold;">Succer You Sports AG | www.socceryou.ch </td>';
            $content .= '</tr>';
            $content .= ' </table>';
            $content .= '</div>';
            $content .= '</div>';
            $content .= '</body>';
            $content .= '</html>';
            /* ##### ~~ End Common Footer ~~ ##### */
            $userPdfPath = WRITEPATH  . 'uploads/exports/';
            $pdf_name = trim($fullName);
            $pdf_name = str_replace(' ', '_', $pdf_name);
            $pdf_name = $pdf_name . '_' . $role_name . '_user_';
            $pdf_name = replace_special_chars($pdf_name);
            if (empty($pdf_name) || $pdf_name == '_') {
                $pdf_name = date('Y_M_D') . '_' . rand();
            }
            $filename = trim(strtolower($pdf_name)) . base64_encode($userId) . '.pdf';
            $folder_nd_file = 'uploads/exports/' . $filename;
            // echo 'downloaded [USER_NAME_'.$userId.'] Profile PDF ('.$filename.')';
            $activity = lang('App.downloaded_profile_pdf', ['userId' => $userId, 'imageName' => $filename]);
            try {
                // $mpdf->SetMargins(10, 10, 10); // Set the page margins: left, right, top

                $mpdf->WriteHTML($content);
                $this->response->setHeader('Content-Type', 'application/pdf');
                //  $mpdf->SetFooter('Succer You Sports AG | www.socceryou.ch');
                $mpdf->Output($userPdfPath . $filename, \Mpdf\Output\Destination::FILE);
                // $activity_data = [
                //     'user_id'               => 1, // default_admin
                //     'activity_type_id'      => 10,        //download
                //     'activity'              => $activity,
                //     'ip'                    => $this->request->getIPAddress()
                // ];
                // createActivityLog($activity_data);
                $activity_data = array();
                $languageService = \Config\Services::language();
                $languages = ['en', 'de', 'fr', 'it', 'es', 'pt', 'da', 'sv'];
                $activity_data = [
                    'user_id'               => auth()->id(),
                    'activity_type_id'      => 10,      // updated
                    'ip'                    => $this->request->getIPAddress()
                ];
                foreach ($languages as $lang) {
                    $languageService->setLocale($lang);
                    $translated_message = lang('App.downloaded_profile_pdf', ['userId' => $userId, 'imageName' => $filename]);
                    $activity_data['activity_' . $lang] = $translated_message;
                }
                createActivityLog($activity_data);
            } catch (\Mpdf\MpdfException $e) {
                $errors = [$e->getMessage(), $pdf_error];
                $userPdfPath = '';
            } catch (\Exception $e) {
                $errors['genral_error'] = [$e->getMessage(), $pdf_error];
                $userPdfPath = '';
            }
            // test_by_amrit
            if (file_exists($userPdfPath)) {
                // PDF exists, return the file path for download
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
                    "message"   => lang('App.noDataFound'), //lang('App.pdfDownloadFailed'), //
                    "data"      => ['error' => $errors]
                ];
            }
        } else {
            $response = [
                "status"    => false,
                "message"   => 'User not Found.', //lang('App.pdfDownloadFailed'), //
                "data"      => []
            ];
        }
        // echo '<pre>'; print_r($response); die;
        return $this->respondCreated($response);
    }

    public function searchBar()
    {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $url =  isset($_SERVER['HTTPS']) &&
            $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
        $url .= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $parse_url = parse_url($url);
        $uri = new \CodeIgniter\HTTP\URI($url);

        $queryString =  $uri->getQuery();
        parse_str($queryString, $searchParams);

        $whereClause = [];
        $metaQuery = [];
        $orderBy =  $order = $search = '';
        $noLimit =  $countOnly = FALSE;

        // pr(auth()->user());

        if (isset($searchParams['search']) && !empty($searchParams['search'])) {
            $search = $searchParams['search'];
        }

        if ($search) {
            $imagePath = base_url() . 'uploads/';

            $builder = $this->db->table('users u');
            $builder->select(
                'u.*,
                user_roles.role_name as role_name,
                languages.language as language,
                domains.domain_name as domain_name,
                domains.location as user_location,
                auth.secret as email,
                CONCAT("' . $imagePath . '", um.meta_value) as profile_image
                ',
            );

            $builder->join('(SELECT user_id, secret FROM auth_identities WHERE type = "email_password") auth', 'auth.user_id = u.id', 'LEFT');
            $builder->join('user_roles', 'user_roles.id = u.role', 'INNER');
            $builder->join('languages', 'languages.id = u.lang', 'INNER');
            $builder->join('domains', 'domains.id = u.user_domain', 'INNER');
            $builder->join('user_meta um', 'um.user_id = u.id AND um.meta_key = "profile_image"', 'LEFT');

            $builder->where('u.deleted_at', NULL);
            $builder->where("u.status IS NOT NULL");
            $builder->whereNotIn("u.role", [5]);
            if (auth()->id()) {
                $builder->whereNotIn("u.id", [auth()->id()]);
            }

            $builder->groupStart();
            $builder->like('u.first_name', $search);
            $builder->orLike('u.last_name', $search);
            $builder->orLike('u.username', $search);
            $builder->orLike('auth.secret', $search);
            $builder->groupEnd();


            // Count the total number of results
            $countBuilder   = clone $builder;
            $countQuery     = $countBuilder->get();
            $totalCount     =  $countQuery->getNumRows();

            $builder->orderBy('u.id', 'DESC');

            // Add pagination
            if (isset($searchParams['limit'])) {
                $builder->limit($searchParams['limit'], $searchParams['offset']);
            }

            $query = $builder->get();
            $users = $query->getResultArray();

            if ($users) {
                $response = [
                    "status"    => true,
                    "message"   => lang('App.dataFound'),
                    "data"      => [
                        'totalCount'    => $totalCount,
                        'userData'      => $users
                    ]
                ];
            } else {
                $response = [
                    "status"    => false,
                    "message"   => lang('App.noDataFound'),
                    "data"      => []
                ];
            }
        } else {
            $response = [
                "status"    => false,
                "message"   => 'Please enter text to search',
                "data"      => []
            ];
        }
        return $this->respondCreated($response);
    }
}
