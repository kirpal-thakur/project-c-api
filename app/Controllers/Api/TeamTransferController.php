<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use App\Models\TeamTransferModel;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class TeamTransferController extends ResourceController
{
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);

        $this->db      = \Config\Database::connect();
        $this->session    = \Config\Services::session();
    }


    public function addTeamTransfer()
    {
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            $rules = [
                "team_from"             => "required",
                "team_to "              => "required",
                "session"               => "required",
                "date_of_transfer"      => "required",
            ];

            if (!$this->validate($rules)) {
                $response = [
                    "status"    => false,
                    "message"   => 'Error',
                    "data"      => ['error' => $this->validator->getErrors()]
                ];
            } else {

                $teamTransferModel = new TeamTransferModel();
                //$userId = auth()->id();

                $save_data = [
                    'user_id'           => auth()->id(),
                    'team_from'         => $this->request->getVar("team_from"),
                    'team_to'           => $this->request->getVar("team_to"),
                    'session'           => $this->request->getVar("session"),
                    'date_of_transfer'  => $this->request->getVar("date_of_transfer")
                ];

                $res = $teamTransferModel->save($save_data);

                if ($res) {

                    // create Activity log
                    $activity_data = [
                        'user_id'               => auth()->id(),
                        'activity_type_id'      => 1,      // added 
                        'activity'              => 'added team transfer detail',
                        'old_data'              => serialize($this->request->getVar()),
                        'ip'                    => $this->request->getIPAddress()
                    ];
                    createActivityLog($activity_data);

                    $response = [
                        "status"    => true,
                        "message"   => lang('App.teamTransferAdded'),
                        "data"      => []
                    ];
                } else {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.teamTransferAddFailed'),
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

    public function editTeamTransfer($id = null)
    {

        $teamTransferModel = new TeamTransferModel();

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP) || hasAccess(auth()->user(), EDITOR_ACCESS)) {

            $isRecordExist = $teamTransferModel->where('id', $id)->first();

            if (empty($isRecordExist)) {
                $response = [
                    "status"    => false,
                    "message"   => lang('App.recordNotExist'),
                    "data"      => []
                ];
            } else {

                $rules = [
                    "team_from"             => "required",
                    "team_to "              => "required",
                    "session"               => "required",
                    "date_of_transfer"      => "required",
                ];

                if (!$this->validate($rules)) {
                    $errors = $this->validator->getErrors();

                    $response = [
                        "status"    => false,
                        "message"   => lang('App.provideValidData'),
                        "data"      => ['errors' => $errors]
                    ];
                } else {

                    $save_data = [
                        'id'                => $id,
                        'user_id'           => auth()->id(),
                        'team_from'         => $this->request->getVar("team_from"),
                        'team_to'           => $this->request->getVar("team_to"),
                        'session'           => $this->request->getVar("session"),
                        'date_of_transfer'  => $this->request->getVar("date_of_transfer")
                    ];

                    // save data
                    $res = $teamTransferModel->save($save_data);

                    if ($res) {
                        $response = [
                            "status"    => true,
                            "message"   => lang('App.teamTransferUpdated'),
                            "data"      => []
                        ];
                    } else {
                        $response = [
                            "status"    => false,
                            "message"   => lang('App.teamTransferUpdateFailed'),
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

    public function getTeamTransferbackp($userId = null)
    {
        $teamTransferModel = new TeamTransferModel();

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $userId = $userId ?? auth()->id();
        $logoPath = base_url() . 'uploads/logos/';
        $profilePath = base_url() . 'uploads/';

        $builder = $teamTransferModel->builder();
        $builder->select('
                    team_transfers.*, 

                    cb1.club_name as team_name_from, 
                    t1.team_type as team_type_from, 
                    cb1.club_logo as team_logo_from, 
                    CONCAT("'.$profilePath.'", cb1.club_logo ) AS team_logo_path_from,
                    c1.country_name as country_name_from,
                    c1.country_flag as country_flag_from,
                    CONCAT( "'.$logoPath.'", c1.country_flag) AS country_flag_path_from,

                    cb1.club_name as team_name_to, 
                    t1.team_type as team_type_to, 
                    cb1.club_logo as team_logo_to, 
                    CONCAT("'.$profilePath.'", cb1.club_logo ) AS team_logo_path_to,
                    c1.country_name as country_name_to,
                    c1.country_flag as country_flag_to,
                    CONCAT( "'.$logoPath.'", c1.country_flag) AS country_flag_path_to,

                ');

                // um1.meta_value as team_name_from, 
                //     t1.team_type as team_type_from, 
                //     pi1.meta_value as team_logo_from,
                //     CONCAT( "' . $profilePath . '", pi1.meta_value) AS team_logo_path_from,
                //     c1.country_name as country_name_from,
                //     CONCAT( "' . $logoPath . '", c1.country_flag) AS country_flag_path_from,

                // um2.meta_value as team_name_to, 
                //     t2.team_type as team_type_to, 
                //     pi2.meta_value as team_logo_to,
                //     CONCAT( "' . $profilePath . '", pi2.meta_value) AS team_logo_path_to,
                //     c2.country_name as country_name_to,
                //     CONCAT( "' . $logoPath . '", c2.country_flag) AS country_flag_path_to


        $builder->join('teams t1', 't1.id = team_transfers.team_from', 'LEFT');
        $builder->join('clubs cb1', 'cb1.id = t1.club_id', 'LEFT');
        $builder->join('countries c1', 'c1.id = t1.country_id', 'LEFT');
        // $builder->join('user_meta um1', 'um1.user_id = t1.club_id AND um1.meta_key = "club_name"', 'LEFT');
        // $builder->join('user_meta pi1', 'pi1.user_id = t1.club_id AND pi1.meta_key = "profile_image"', 'LEFT');
        // $builder->join('user_nationalities un1', 'un1.user_id = t1.club_id', 'LEFT');
        // $builder->join('countries c1', 'c1.id = un1.country_id', 'LEFT');

        $builder->join('teams t2', 't2.id = team_transfers.team_to', 'LEFT');
        $builder->join('clubs cb2', 'cb2.id = t2.club_id', 'LEFT');
        $builder->join('countries c2', 'c2.id = t2.country_id', 'LEFT');
        // $builder->join('user_meta um2', 'um2.user_id = t2.club_id AND um2.meta_key = "club_name"', 'LEFT');
        // $builder->join('user_meta pi2', 'pi2.user_id = t2.club_id AND pi2.meta_key = "profile_image"', 'LEFT');
        // $builder->join('user_nationalities un2', 'un2.user_id = t2.club_id', 'LEFT');
        // $builder->join('countries c2', 'c2.id = un2.country_id', 'LEFT');

        $builder->where('team_transfers.user_id', $userId);
        $builder->orderBy('team_transfers.id', 'DESC');
        $transferQuery = $builder->get();
        $transferDetail = $transferQuery->getResultArray();
        //echo '>>>>>>>>>>>>>>>> '. $this->db->getLastQuery(); exit();

        if ($transferDetail) {
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => [
                    'logo_path'         => $profilePath,
                    'flag_path'         => $logoPath,
                    'transferDetail'    => $transferDetail
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

    public function getTeamTransfer($userId = null)
    {
        $teamTransferModel = new TeamTransferModel();

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $userId = $userId ?? auth()->id();
        $logoPath = base_url() . 'uploads/logos/';
        $profilePath = base_url() . 'uploads/';

        $builder = $teamTransferModel->builder();
        $builder->select('
        team_transfers.*, 
    
        cb1.club_name as team_name_from, 
        t1.team_type as team_type_from, 
        cb1.club_logo as team_logo_from, 
        CONCAT("'.$profilePath.'", cb1.club_logo) AS team_logo_path_from,
        c1.country_name as country_name_from,
        c1.country_flag as country_flag_from,
        CONCAT("'.$logoPath.'", c1.country_flag) AS country_flag_path_from,
    
        cb2.club_name as team_name_to, 
        t2.team_type as team_type_to, 
        cb2.club_logo as team_logo_to, 
        CONCAT("'.$profilePath.'", cb2.club_logo) AS team_logo_path_to,
        c2.country_name as country_name_to,
        c2.country_flag as country_flag_to,
        CONCAT("'.$logoPath.'", c2.country_flag) AS country_flag_path_to
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
        // echo '<pre>'; print_r($transferDetail); die(' transferDetail');
        //echo '>>>>>>>>>>>>>>>> '. $this->db->getLastQuery(); exit();

        if ($transferDetail) {
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => [
                    'logo_path'         => $profilePath,
                    'flag_path'         => $logoPath,
                    'transferDetail'    => $transferDetail
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

    public function deleteTeamTransfer($id = null)
    {

        $teamTransferModel = new TeamTransferModel();

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        // if (auth()->user()->inGroup('superadmin', 'superadmin-representator') && (auth()->user()->can('admin.access') || auth()->user()->can('transfer.ownership'))) {
            
        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            $del_data = $teamTransferModel->where('id', $id)->first();
            $del_res = $teamTransferModel->delete($id, true);

            if ($del_res == true) {

                // create Activity log
                $activity_data = [
                    'user_id'               => auth()->id(),
                    'activity_type_id'      => 3,      // deleted 
                    'activity'              => 'deleted team transfer detail',
                    'old_data'              =>  serialize($del_data),
                    'ip'                    => $this->request->getIPAddress()
                ];
                createActivityLog($activity_data);

                // Return a success message as a JSON response
                $response = [
                    "status"    => true,
                    "message"   => lang('App.teamTransferDeleted'),
                    "data"      => []
                ];
            } else {
                $response = [
                    "status"    => false,
                    "message"   => lang('App.teamTransferDeleteFailed'),
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


    //////////////////////////// ADMIN APIs /////////////////////////////////

    // Get teams transfer details of player by admin
    // GET API

    /* public function getTeamTransferAdmin($userId = null)
    {

        $teamTransferModel = new TeamTransferModel();
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

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
        //echo '>>>>>>>>>>>>>>>> '. $this->db->getLastQuery(); exit();

        if ($transferDetail) {
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => ['transferDetail' => $transferDetail]
            ];
        } else {
            $response = [
                "status"    => false,
                "message"   => lang('App.noDataFound'),
                "data"      => []
            ];
        }

        return $this->respondCreated($response);
    } */

    // Edit Team Transfer detail of player by admin
    // POST API
    public function editTeamTransferAdmin($id = null)
    {

        $teamTransferModel = new TeamTransferModel();

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        // if (auth()->user()->inGroup('superadmin', 'superadmin-representator') && (auth()->user()->can('admin.access') || auth()->user()->can('admin.edit') || auth()->user()->can('transfer.ownership'))) {

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP) || hasAccess(auth()->user(), EDITOR_ACCESS)) {
            
            $isRecordExist = $teamTransferModel->where('id', $id)->first();

            if (empty($isRecordExist)) {
                $response = [
                    "status"    => false,
                    "message"   => lang('App.recordNotExist'),
                    "data"      => []
                ];
            } else {

                $rules = [
                    "team_from"             => "required",
                    "team_to "              => "required",
                    "session"               => "required",
                    "date_of_transfer"      => "required",
                ];

                if (!$this->validate($rules)) {
                    $errors = $this->validator->getErrors();

                    $response = [
                        "status"    => false,
                        "message"   => lang('App.provideValidData'),
                        "data"      => ['errors' => $errors]
                    ];
                } else {

                    $save_data = [
                        'id'                => $id,
                        'team_from'         => $this->request->getVar("team_from"),
                        'team_to'           => $this->request->getVar("team_to"),
                        'session'           => $this->request->getVar("session"),
                        'date_of_transfer'  => $this->request->getVar("date_of_transfer")
                    ];

                    // save data
                    // $res = $teamTransferModel->save($save_data);

                    if ($teamTransferModel->save($save_data)) {
                        $response = [
                            "status"    => true,
                            "message"   => lang('App.teamTransferUpdated'),
                            "data"      => []
                        ];
                    } else {
                        $response = [
                            "status"    => false,
                            "message"   => lang('App.teamTransferUpdateFailed'),
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

    public function exportTeamTransferAdmin($userId = null)
    {
        $teamTransferModel = new TeamTransferModel();
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

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
                    CONCAT( "' . $logoPath . '", c2.country_flag) AS country_flag_path_to,
                    usr.first_name,usr.last_name
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
        $builder->join('users usr', 'usr.id = team_transfers.user_id', 'LEFT');

        $builder->where('team_transfers.user_id', $userId);
        $builder->orderBy('team_transfers.id', 'DESC');
        $transferQuery = $builder->get();
        $transferDetail = $transferQuery->getResultArray();
        # echo '<pre>'; print_r($transferDetail); die;
        if (isset($transferDetail) && !empty($transferDetail)) {
            /* ##### excel code By Amrit ##### */
            // Create a new Spreadsheet object
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            // Set the headers
            $sheet->setCellValue('A1', 'First Name');
            $sheet->setCellValue('B1', 'Last Name');
            $sheet->setCellValue('C1', 'Season');
            $sheet->setCellValue('D1', 'Date');
            $sheet->setCellValue('E1', ' Moving From Team Logo');
            $sheet->setCellValue('F1', ' Moving From Name');
            $sheet->setCellValue('G1', ' Moving From Country Logo');
            $sheet->setCellValue('H1', ' Moving To Team Logo');
            $sheet->setCellValue('I1', ' Moving To Name');
            $sheet->setCellValue('J1', ' Moving To Country Logo');
            $row = 2; // Values start from second column
            foreach ($transferDetail as $transfer) {
                $sheet->setCellValue('A' . $row, htmlspecialchars($transfer['first_name']));
                $sheet->setCellValue('B' . $row, htmlspecialchars($transfer['last_name']));
                $sheet->setCellValue('C' . $row, htmlspecialchars($transfer['session']));
                $sheet->setCellValue('D' . $row, htmlspecialchars($transfer['date_of_transfer']));
                $sheet->setCellValue('E' . $row, htmlspecialchars($transfer['team_logo_path_from']));
                $sheet->setCellValue('F' . $row, htmlspecialchars($transfer['team_name_from']));
                $sheet->setCellValue('G' . $row, htmlspecialchars($transfer['country_flag_path_from']));
                $sheet->setCellValue('H' . $row, htmlspecialchars($transfer['team_logo_path_to']));
                $sheet->setCellValue('I' . $row, htmlspecialchars($transfer['team_name_to']));
                $sheet->setCellValue('J' . $row, htmlspecialchars($transfer['country_flag_path_to']));
                $row++;
            }
            $filename = 'transfers_export_' . date('Y-m-d_H-i-s') . '.xlsx';
            $folder_nd_file = 'uploads/exports/'.$filename;
            $saveDirectory = WRITEPATH  . 'uploads/exports/';

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
            $activity_data = [
                'user_id'               => auth()->id(),
                'activity_type_id'      => 9,      // viewed
                'activity'              => 'Csv Downloded by user',
                'ip'                    => $this->request->getIPAddress()
            ];
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
        } else {
            $response = [
                'status' => false,
                'message' => lang('App.noDataFound'),
                'data' => []
            ];
        }
        return $this->respondCreated($response);
    }

    // by amrit 
    public function adminGetTeamTransfersbckp($userId = null)
    {

        $teamTransferModel = new TeamTransferModel();
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

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
        //echo '>>>>>>>>>>>>>>>> '. $this->db->getLastQuery(); exit();

        if ($transferDetail) {
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => ['transferDetail' => $transferDetail]
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
    public function adminGetTeamTransfers($userId = null){
        $teamTransferModel = new TeamTransferModel();
        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale(getLanguageCode($currentLang));

        $logoPath = base_url() . 'uploads/logos/';
        $profilePath = base_url() . 'uploads/';

        $builder = $teamTransferModel->builder();
        $builder->select('
        team_transfers.*, 
    
        cb1.club_name as team_name_from, 
        t1.team_type as team_type_from, 
        cb1.club_logo as team_logo_from, 
        CONCAT("'.$profilePath.'", cb1.club_logo) AS team_logo_path_from,
        c1.country_name as country_name_from,
        c1.country_flag as country_flag_from,
        CONCAT("'.$logoPath.'", c1.country_flag) AS country_flag_path_from,
    
        cb2.club_name as team_name_to, 
        t2.team_type as team_type_to, 
        cb2.club_logo as team_logo_to, 
        CONCAT("'.$profilePath.'", cb2.club_logo) AS team_logo_path_to,
        c2.country_name as country_name_to,
        c2.country_flag as country_flag_to,
        CONCAT("'.$logoPath.'", c2.country_flag) AS country_flag_path_to
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
        if ($transferDetail) {
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => ['transferDetail' => $transferDetail]
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
