<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use App\Models\ClubPlayerModel;
use App\Models\EmailTemplateModel;
use CodeIgniter\Shield\Models\UserModel;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use DateTime;
class ClubPlayerController extends ResourceController
{
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);
        $this->db = \Config\Database::connect();
        $this->clubPlayerModel = new ClubPlayerModel();
    }


    // Players - Add players under club 
    public function addClubPlayer()
    {
        $response = [];
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            $rules = [
                'players.*.player_id'       => 'required|is_natural',
                'players.*.team_id'         => 'required|is_natural',
                'players.*.join_date'       => 'required|valid_date',
            ];

            if (!$this->validate($rules)) {
                $errors = $this->validator->getErrors();

                $response = [
                    "status"    => false,
                    "message"   => lang('App.provideValidData'),
                    "data"      => ['errors' => $errors]
                ];
            } else {

                $playerExist = $playerAdded = [];
                foreach ($this->request->getVar('players') as $key => $player) {

                    $isExist = $this->clubPlayerModel
                        ->select('club_players.*, users.first_name, users.last_name')
                        ->join('users', 'users.id = club_players.player_id', 'LEFT')
                        ->where('club_players.team_id', $player["team_id"])
                        ->where('club_players.player_id', $player["player_id"])
                        ->where('club_players.status', 'active')
                        ->first();

                    if ($isExist) {
                        $existMessage = $isExist["first_name"] . ' ' . $isExist["last_name"] . ' ' . lang('App.playerExistInClub');
                        array_push($playerExist, $existMessage);
                    } else {

                        // update status to inactive for previous records
                        // $this->clubPlayerModel->where('player_id', $player["player_id"])
                        //     ->set(['status' => 2])      //2 = inactive
                        //     ->update();

                        // save new data
                        $save_data = [
                            'team_id'       => $player["team_id"],
                            'player_id'     => $player["player_id"],
                            'join_date'     => $player["join_date"],
                            'end_date'      => $player["end_date"],
                            'no_end_date'   => $player["no_end_date"] ?? 0,
                        ];
                        if (auth()->user()->role == 1 || in_array(auth()->user()->role, REPRESENTATORS_ROLES)) {
                            $save_data['added_by'] = auth()->id();
                        }

                        // save data
                        if ($this->clubPlayerModel->save($save_data)) {

                            $users = auth()->getProvider();
                            $playerInfo = $users->findById($player["player_id"]);

                            // get current club name
                            $currentClubName = '';
                            $clubBuilder = $this->db->table('clubs');
                            $clubQuery = $clubBuilder->select('club_name')->where('taken_by', auth()->id())->get();
                            $cClubInfo = $clubQuery->getRow();
                            if($cClubInfo){
                                $currentClubName = $cClubInfo->club_name;
                            }

                            // get team name
                            $teamName = getTeamNameByID($player["team_id"]);

                            $emailType = 'Club Adds Talent';

                            // email to player
                            $getEmailTemplate = getEmailTemplate($emailType, $playerInfo->lang, PLAYER_ROLE );

                            if($getEmailTemplate){

                                $subjectReplacements = [
                                    '{clubName}' => $currentClubName,
                                    '{teamName}' => $teamName,
                                ];

                                $replacements = [
                                    '{firstName}'   => $playerInfo->first_name,
                                    '{clubName}'    => $currentClubName,
                                    '{teamName}'    => $teamName,
                                    '{link}'        => '<a href="#">link</a>',
                                    '{helpEmail}'   => '<a href="mailto:'. HELP_EMAIL .'">'. HELP_EMAIL .'</a>',
                                ]; 

                                $subject = strtr($getEmailTemplate['subject'], $subjectReplacements);

                                // Replace placeholders with actual values in the email body
                                $content = strtr($getEmailTemplate['content'], $replacements);
                                $message = view('emailTemplateView' , ['message' => $content, 'disclaimerText' => getEmailDisclaimer($playerInfo->lang), 'footer' => getEmailFooter($playerInfo->lang)]);

                                $toEmail = $playerInfo->email;

                                $emailData = [
                                    'fromEmail'     => FROM_EMAIL,
                                    'fromName'      => FROM_NAME,
                                    'toEmail'       => $toEmail,
                                    'subject'       => $subject,
                                    'message'       => $message,
                                ];
                                sendEmail($emailData);
                            }

                            // email to logged-in club
                            $getClubEmailTemplate = getEmailTemplate($emailType, auth()->user()->lang, CLUB_ROLE );

                            if($getClubEmailTemplate){

                                $subjectReplacements = [
                                    '{talentName}'  => $playerInfo->first_name,
                                    '{teamName}'    => $teamName,
                                ];

                                $replacements = [
                                    '{clubName}'    => $currentClubName,
                                    '{talentName}'  => $playerInfo->first_name,
                                    '{link}'        => '<a href="'.getPageInUserLanguage(auth()->user()->user_domain, auth()->user()->lang).'">'.lang('Email.login').'</a>',
                                    '{faqLink}'     => '<a href="'.getPageInUserLanguage(auth()->user()->user_domain, auth()->user()->lang, "faq").'">faq</a>',
                                    '{helpEmail}'   => '<a href="mailto:'. HELP_EMAIL .'">'. HELP_EMAIL .'</a>',
                                ]; 

                                $subject = strtr($getClubEmailTemplate['subject'], $subjectReplacements);

                                // Replace placeholders with actual values in the email body
                                $content = strtr($getClubEmailTemplate['content'], $replacements);
                                $message = view('emailTemplateView' , ['message' => $content, 'disclaimerText' => getEmailDisclaimer(auth()->user()->lang), 'footer' => getEmailFooter(auth()->user()->lang)]);

                                $toEmail = auth()->user()->email;
                                $emailData = [
                                    'fromEmail'     => FROM_EMAIL,
                                    'fromName'      => FROM_NAME,
                                    'toEmail'       => $toEmail,
                                    'subject'       => $subject,
                                    'message'       => $message,
                                ];
                                sendEmail($emailData);
                            }
                            
                            $oldClubdetail = $this->clubPlayerModel
                                                ->select('club_players.*,t.club_id as pre_club_id, c.taken_by as club_id, c.club_name as club_name, u.first_name, u.last_name, u.lang, u.user_domain, auth.secret as email')
                                                ->join('teams t', 't.id = club_players.team_id', 'LEFT')
                                                ->join('clubs c', 'c.id = t.club_id', 'LEFT')
                                                ->join('users u', 'u.id = c.taken_by', 'LEFT')
                                                ->join('auth_identities auth', 'auth.user_id = c.taken_by AND type = "email_password"', 'LEFT')
                                                ->where('club_players.player_id', $player["player_id"])
                                                ->where('club_players.status', 'active')
                                                ->first();
                            
                            // remove old club data and send email to old club if club is different
                            if($oldClubdetail && $oldClubdetail['club_id'] != auth()->id()){

                                $teamBuilder = $this->db->table('teams');
                                $teamQuery = $teamBuilder->select('id')->where('club_id', $oldClubdetail['pre_club_id'])->get();
                                $teams = $teamQuery->getResult();

                                $teamsIDs = array_column($teams, 'id');

                                $this->clubPlayerModel->where('player_id', $player["player_id"])
                                                    ->whereIn('team_id', $teamsIDs)      
                                                    ->set(['status' => 2])      //2 = inactive
                                                    ->update();                    

                               // send email to talent when his club changes
                               $talentEmailType = 'When Talent moves from one team to another';
                               $getTalentEmailTemplate = getEmailTemplate($talentEmailType, $playerInfo->lang, PLAYER_ROLE );
                   
                               if($getTalentEmailTemplate){
       
                                   $replacements = [
                                       '{oldTeamName}'     => $oldClubdetail['club_name'],
                                       '{newTeamName}'     => $currentClubName,
                                       '{talentName}'      => $playerInfo->first_name,
                                       '{faqLink}'         => '<a href="'.getPageInUserLanguage($playerInfo->user_domain, $playerInfo->lang, "faq").'">faq</a>',
                                       '{helpEmail}'       => '<a href="mailto:'. HELP_EMAIL .'">'. HELP_EMAIL .'</a>',
                                   ]; 
       
                                   // Replace placeholders with actual values in the email body
                                   $subject = $getTalentEmailTemplate['subject'];
                                   $content = strtr($getTalentEmailTemplate['content'], $replacements);
                                   $message = view('emailTemplateView' , ['message' => $content, 'disclaimerText' => getEmailDisclaimer(auth()->user()->lang), 'footer' => getEmailFooter(auth()->user()->lang)]);
       
                                   $toEmail = $playerInfo->email;
                                   $emailData = [
                                       'fromEmail'     => FROM_EMAIL,
                                       'fromName'      => FROM_NAME,
                                       'toEmail'       => $toEmail,
                                       'subject'       => $subject,
                                       'message'       => $message,
                                   ];
                                   sendEmail($emailData);
                               }
                            }

                            $addMessge = $playerInfo->first_name . ' ' . $playerInfo->last_name . ' ' . lang('App.addedInClub');
                            array_push($playerAdded, $addMessge);

                            $activity_data = [
                                'user_id'               => auth()->id(),
                                'activity_on_id'        => $player["player_id"],
                                'activity_type_id'      => 1,      // created
                                'activity'              => 'added player in club',
                                'activity_en'           => 'added player in club',
                                'activity_de'           => 'neuer Spieler im Verein',
                                'activity_it'           => 'giocatore aggiunto nel club',
                                'activity_fr'           => 'ajout d\'un joueur dans le club',
                                'activity_es'           => 'jugador añadido en el club',
                                'activity_pt'           => 'mais um jogador no clube',
                                'activity_da'           => 'tilføjet spiller i klubben',
                                'activity_sv'           => 'lagt till spelare i klubben',
                                'ip'                    => $this->request->getIPAddress()
                            ];
                            createActivityLog($activity_data);
                        }
                    }

                    $response = [
                        "status"    => true,
                        "message"   => lang('App.playersAdded'),
                        "data"      => [
                            'playerExist' => $playerExist,
                            'playerAdded' => $playerAdded
                        ]
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

    // Players - Add players under club 
    public function EditClubPlayer($id = null)
    {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP) || hasAccess(auth()->user(), EDITOR_ACCESS)) {

            $rules = [
                'join_date'       => 'required|valid_date',
            ];

            if (!$this->validate($rules)) {
                $errors = $this->validator->getErrors();

                $response = [
                    "status"    => false,
                    "message"   => lang('App.provideValidData'),
                    "data"      => ['errors' => $errors]
                ];
            } else {
                $res = 0;

                $isExist = $this->clubPlayerModel->where('id', $id)->first();

                if ($isExist) {
                    $save_data = [
                        'id'            => $id,
                        'team_id'       => $this->request->getVar('team_id'),
                        'join_date'     => $this->request->getVar('join_date'),
                        'end_date'      => $this->request->getVar('end_date'),
                        'no_end_date'   => $this->request->getVar('no_end_date'),
                    ];

                    // save data
                    $res = $this->clubPlayerModel->save($save_data);
                } else {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.recordNotExist'),
                        "data"      => []
                    ];
                }

                if ($res > 0) {

                    // create Activity log
                    $activity_data = [
                        'user_id'               => auth()->id(),
                        'activity_on_id'        => $isExist['player_id'] ?? 0,
                        'activity_type_id'      => 2,      // updated
                        'activity'              => 'updated club player detail',
                        'activity_en'           => 'updated club player detail',
                        'activity_de'           => 'aktualisierte Vereinsspielerdetails',
                        'activity_it'           => 'Dettaglio giocatore di club aggiornato',
                        'activity_fr'           => 'mise à jour des informations sur le joueur du club',
                        'activity_es'           => 'detalles actualizados de los jugadores del club',
                        'activity_pt'           => 'dados actualizados do jogador do clube',
                        'activity_da'           => 'opdaterede detaljer om klubspillere',
                        'activity_sv'           => 'uppdaterade uppgifter om klubbspelare',
                        'ip'                    => $this->request->getIPAddress()
                    ];
                    createActivityLog($activity_data);

                    $response = [
                        "status"    => true,
                        "message"   => lang('App.playersUpdated'),
                        "data"      => []
                    ];
                } else {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.playersUpdatedFailed'),
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


    public function getClubPlayers($team_id = null)
    {
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $clubId = auth()->id();
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

        // Count the total number of results
        $countBuilder = clone $builder;
        $countQuery = $countBuilder->get();
        $totalCount =  $countQuery->getNumRows();

        $builder->orderBy('cp.id', 'DESC');
        //$builder->limit($limit, $offset);

        $query = $builder->get();
        $playersDetail = $query->getResultArray();

        $data = [
            'totalCount' => $totalCount,
            'players' => $playersDetail
        ];

        if ($playersDetail) {
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


    // Players - Get list of club players
    public function getClubPlayers_old($team_id = null)
    {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $clubId = auth()->id();
        $imagePath = base_url() . 'uploads/';

        $sql = "SELECT `cp`.*, 
                    `u`.`first_name`, 
                    `u`.`last_name`,
                    `u`.`status` as `user_status`,
                    `um2`.`profile_image` as `profile_image`,
                    CONCAT('" . $imagePath . "', `um2`.`profile_image`) AS `profile_image_path`,
                    `d`.`location` as `location`
                FROM 
                    `club_players` `cp`
                INNER JOIN 
                    `users` `u` 
                ON 
                    `u`.`id` = `cp`.`player_id`
                INNER JOIN 
                    `domains` `d` 
                ON 
                    `d`.`id` = `u`.`user_domain`
                LEFT JOIN 
                    ( SELECT `um`.`user_id` as `user_id`, `um`.`meta_value` as `profile_image` FROM `user_meta` `um` WHERE `meta_key` LIKE 'profile_image' ) `um2`  ON `um2`.`user_id` = `cp`.`player_id`
                WHERE 
                    `cp`.`team_id` = $team_id
                ORDER BY 
                    `cp`.`id` DESC;";

        $query = $this->db->query($sql);
        $playersDetail = $query->getResult();

        if ($playersDetail) {
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => ['players' => $playersDetail]
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

    // Players - Delete players under club 
    public function deleteClubPlayer($id = null)
    {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));
            
        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            $del_data   = $this->clubPlayerModel->where('id', $id)->first();
            $del_res    = $this->clubPlayerModel->delete($id, true);

            $userId = auth()->id();

            // check if representator
            if(in_array(auth()->user()->role, REPRESENTATORS_ROLES)){
                $authUser = auth()->user();
                $userId = $authUser->parent_id;
            }

            if ($del_res == true) {

                // create Activity log
                $activity_data = [
                    'user_id'               => $userId,
                    'activity_type_id'      => 3,      // deleted
                    'activity'              => 'deleted Club player',
                    'activity_en'           => 'deleted Club player',
                    'activity_de'           => 'gelöscht Clubspieler',
                    'activity_it'           => 'ha cancellato il giocatore del Club',
                    'activity_fr'           => 'supprimé Joueur de club',
                    'activity_es'           => 'eliminado Jugador del club',
                    'activity_pt'           => 'jogador do clube eliminado',
                    'activity_da'           => 'slettet Club-spiller',
                    'activity_sv'           => 'borttagen Club-spelare',
                    'old_data'              =>  serialize($del_data),
                    'ip'                    => $this->request->getIPAddress()
                ];
                createActivityLog($activity_data);

                // Return a success message as a JSON response
                $response = [
                    "status"    => true,
                    "message"   => lang('App.clubPlayerDeleted'),
                    "data"      => []
                ];
            } else {
                $response = [
                    "status"    => false,
                    "message"   => lang('App.clubPlayerDeleteFailed'),
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

    // ADMIN APIs
    // Admin Players - Get list of club players
    public function getClubPlayersAdmin($team_id = null)
    {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

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
        $builder->join('teams t', 't.id = cp.team_id', 'LEFT');
        $builder->join('user_meta um3', 'um3.user_id = t.club_id AND um3.meta_key = "club_name"', 'LEFT');
        $builder->join('user_meta um4', 'um4.user_id = t.club_id AND um4.meta_key = "profile_image"', 'LEFT');

        // to get club's league
        $builder->join('league_clubs lc', 'lc.club_id = t.club_id', 'LEFT');
        $builder->join('leagues l', 'l.id = lc.league_id', 'LEFT');

        $builder->join('user_meta um5', 'um5.user_id = cp.player_id AND um5.meta_key = "international_player"', 'LEFT');
        $builder->join('countries c1', 'c1.id = um5.meta_value', 'LEFT');

        $builder->where('cp.team_id', $team_id);

        // Count the total number of results
        $countBuilder = clone $builder;
        $countQuery = $countBuilder->get();
        $totalCount =  $countQuery->getNumRows();

        $builder->orderBy('cp.id', 'DESC');
        //$builder->limit($limit, $offset);

        $query = $builder->get();
        $playersDetail = $query->getResultArray();

        $data = [
            'totalCount' => $totalCount,
            'players' => $playersDetail
        ];

        if ($playersDetail) {
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


    // Admin Players - Add players under club 
    public function addClubPlayerAdmin($club_id = null)
    {
        $response = [];
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        // if (auth()->user()->inGroup('superadmin', 'superadmin-representator') && (auth()->user()->can('admin.access') || auth()->user()->can('transfer.ownership'))) {
            
        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            $rules = [
                'players.*.player_id'       => 'required|is_natural',
                'players.*.team_id'         => 'required|is_natural',
                'players.*.join_date'       => 'required|valid_date',
            ];

            if (!$this->validate($rules)) {
                $errors = $this->validator->getErrors();

                $response = [
                    "status"    => false,
                    "message"   => lang('App.provideValidData'),
                    "data"      => ['errors' => $errors]
                ];
            } else {
                $playerExist = $playerAdded = [];
                foreach ($this->request->getVar('players') as $key => $player) {
                    $isExist = $this->clubPlayerModel
                        ->select('club_players.*, users.first_name, users.last_name')
                        ->join('users', 'users.id = club_players.player_id', 'INNER')
                        //->where('team_id', $player["team_id"])
                        ->where('player_id', $player["player_id"])
                        ->first();
                    if ($isExist) {
                        $existMessage = $isExist["first_name"] . ' ' . $isExist["last_name"]  . ' ' . lang('App.playerExistInClub');
                        array_push($playerExist, $existMessage);
                    } else {
                        $save_data = [
                            'team_id'       => $player["team_id"],
                            'player_id'     => $player["player_id"],
                            'join_date'     => $player["join_date"],
                            'end_date'      => $player["end_date"],
                            'no_end_date'   => $player["no_end_date"] ?? 0,
                            'added_by'      => auth()->id(),
                        ];

                        // save data
                        if ($this->clubPlayerModel->save($save_data)) {

                            $userModel = new UserModel();
                            $userData = $userModel->where('id', $player["player_id"])->first();
                            $addMessge = $userData->first_name . ' ' . $userData->last_name . ' ' . lang('App.addedInClub');
                            array_push($playerAdded, $addMessge);

                            $activity_data = [
                                'user_id'               => auth()->id(),
                                'activity_type_id'      => 2,      // updated 
                                'activity'              => 'added player in club',
                                'activity_en'           => 'added player in club',
                                'activity_de'           => 'neuer Spieler im Verein',
                                'activity_it'           => 'giocatore aggiunto nel club',
                                'activity_fr'           => 'ajout d\'un joueur dans le club',
                                'activity_es'           => 'jugador añadido en el club',
                                'activity_pt'           => 'mais um jogador no clube',
                                'activity_da'           => 'tilføjet spiller i klubben',
                                'activity_sv'           => 'lagt till spelare i klubben',
                                'ip'                    => $this->request->getIPAddress()
                            ];
                            createActivityLog($activity_data);
                        }
                    }

                    $response = [
                        "status"    => true,
                        "message"   => lang('App.scoutPlayersAdded'),
                        "data"      => [
                            'playerExist' => $playerExist,
                            'playerAdded' => $playerAdded
                        ]
                    ];
                }
            }
        } else{
            $response = [
                "status"    => false,
                "message"   => lang('App.permissionDenied'),
                "data"      => []
            ];
        }
        return $this->respondCreated($response);
    }

    // Admin Players - Add players under club 
    // here $id is 'id' column from table club_players
    public function EditClubPlayerAdmin($id = null)
    {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        // if (auth()->user()->inGroup('superadmin', 'superadmin-representator') && (auth()->user()->can('admin.access') || auth()->user()->can('admin.edit') || auth()->user()->can('transfer.ownership'))) {
            
        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP) || hasAccess(auth()->user(), EDITOR_ACCESS)) {

            $rules = [
                'join_date'       => 'required|valid_date',
            ];

            if (!$this->validate($rules)) {
                $errors = $this->validator->getErrors();

                $response = [
                    "status"    => false,
                    "message"   => lang('App.provideValidData'),
                    "data"      => ['errors' => $errors]
                ];
            } else {
                $res = 0;

                $isExist = $this->clubPlayerModel->where('id', $id)->first();

                if ($isExist) {
                    $save_data = [
                        'id'            => $id,
                        'team_id'       => $this->request->getVar('team_id'),
                        'join_date'     => $this->request->getVar('join_date'),
                        'end_date'      => $this->request->getVar('end_date'),
                        'no_end_date'   => $this->request->getVar('no_end_date'),
                        'added_by'      => auth()->id(),
                    ];

                    // save data
                    $res = $this->clubPlayerModel->save($save_data);
                } else {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.recordNotExist'),
                        "data"      => []
                    ];
                }

                if ($res > 0) {

                    // create Activity log
                    $activity_data = [
                        'user_id'               => auth()->id(),
                        'activity_on_id'        => $isExist['club_id'] ?? 0,
                        'activity_type_id'      => 2,      // updatd 
                        'activity'              => 'updated club player detail',
                        'activity_en'           => 'updated club player detail',
                        'activity_de'           => 'aktualisierte Vereinsspielerdetails',
                        'activity_it'           => 'Dettaglio giocatore di club aggiornato',
                        'activity_fr'           => 'mise à jour des informations sur le joueur du club',
                        'activity_es'           => 'detalles actualizados de los jugadores del club',
                        'activity_pt'           => 'dados actualizados do jogador do clube',
                        'activity_da'           => 'opdaterede detaljer om klubspillere',
                        'activity_sv'           => 'uppdaterade uppgifter om klubbspelare',
                        'ip'                    => $this->request->getIPAddress()
                    ];
                    createActivityLog($activity_data);

                    $response = [
                        "status"    => true,
                        "message"   => lang('App.clubPlayersUpdated'),
                        "data"      => []
                    ];
                } else {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.clubPlayersUpdateFailed'),
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

    public function exportClubPlayersAdmin($team_id = null)
    {

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

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
        $builder->join('teams t', 't.id = cp.team_id', 'LEFT');
        $builder->join('user_meta um3', 'um3.user_id = t.club_id AND um3.meta_key = "club_name"', 'LEFT');
        $builder->join('user_meta um4', 'um4.user_id = t.club_id AND um4.meta_key = "profile_image"', 'LEFT');

        // to get club's league
        $builder->join('league_clubs lc', 'lc.club_id = t.club_id', 'LEFT');
        $builder->join('leagues l', 'l.id = lc.league_id', 'LEFT');

        $builder->join('user_meta um5', 'um5.user_id = cp.player_id AND um5.meta_key = "international_player"', 'LEFT');
        $builder->join('countries c1', 'c1.id = um5.meta_value', 'LEFT');

        $builder->where('cp.team_id', $team_id);

        // Count the total number of results
        $countBuilder = clone $builder;
        $countQuery = $countBuilder->get();
        $totalCount =  $countQuery->getNumRows();

        $builder->orderBy('cp.id', 'DESC');
        //$builder->limit($limit, $offset);

        $query = $builder->get();
        $playersDetail = $query->getResultArray();

        if (isset($playersDetail) && !empty($playersDetail)) {
            /* ##### excel code By Amrit ##### */
            // Create a new Spreadsheet object
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Set the headers
            $sheet->setCellValue('A1', 'First Name');
            $sheet->setCellValue('B1', 'Last Name');
            $sheet->setCellValue('C1', 'Team Type');
            $sheet->setCellValue('D1', 'Joining Date');
            $sheet->setCellValue('E1', 'Exit Date');
            $sheet->setCellValue('F1', 'Location');
            $row = 2; // Start from the second row
            foreach ($playersDetail as $player) {
                $sheet->setCellValue('A' . $row, htmlspecialchars($player['first_name']));
                $sheet->setCellValue('B' . $row, htmlspecialchars($player['last_name']));
                $sheet->setCellValue('C' . $row, htmlspecialchars($player['team_type']));
                // Create a DateTime object from the date
                $date = new DateTime($player['join_date']);
                // Format the date as 11.30.0002 (MM.DD.YYYY)
                $join_date = $date->format('m.d.Y');
                if (!empty($player['end_date'])) {
                    $date2 = new DateTime($player['end_date']);
                    $end_date = $date2->format('m.d.Y');
                } else {
                    $end_date = '-';
                }
                $sheet->setCellValue('D' . $row, htmlspecialchars($join_date));
                $sheet->setCellValue('E' . $row, htmlspecialchars($end_date));
                $sheet->setCellValue('F' . $row, htmlspecialchars($player['location']));
                $row++;
            } // 
            if(isset($player['team_type']) && !empty($player['team_type'])){
                $team_type = trim($player['team_type']);
            }else{
                $team_type = 'Team';
            }
            $filename = $team_type.'_export_' . date('Y-m-d_H-i-s') . '.xlsx';
            $folder_nd_file = 'uploads/exports/' . $filename;
            $saveDirectory = WRITEPATH  . 'uploads/exports/';

            // Full path of the file
            $filePath = $saveDirectory . $filename;
         
            if (!is_dir($saveDirectory)) {
                mkdir($saveDirectory, 0777, true); 
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save($filePath);  // Save the file
            $activity_data = [
                'user_id'               => auth()->id(),
                'activity_type_id'      => 1,      // viewed
                'activity'              => $team_type.' Csv Downloded by user',
                'activity_en'           => $team_type.' Csv Downloded by user',
                'activity_de'           => $team_type.' Csv Heruntergeladen durch Benutzer',
                'activity_it'           => $team_type.' Csv Downloded by user',
                'activity_fr'           => $team_type.' Csv Downlodé par l\'utilisateur',
                'activity_es'           => $team_type.' Csv Descodificado por el usuario',
                'activity_pt'           => $team_type.' Csv Descodificado pelo utilizador',
                'activity_da'           => $team_type.' Csv Downloded af bruger',
                'activity_sv'           => $team_type.' Csv Nedladdad av användaren',
                'ip'                    => $this->request->getIPAddress()
            ];
            createActivityLog($activity_data);
            // Return the file details in the response
            $response = [
                "status"    => true,
                "message"   => lang('App.fileCreatedSuccess'),
                "data"      => [
                    "file_name" => $filename,
                    "file_path" => base_url() . $folder_nd_file
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
}
