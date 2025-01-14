<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use App\Models\PerformanceReportModel;
use CodeIgniter\Files\File;
use ZipArchive;

class PerformanceReportController extends ResourceController
{
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);
        $this->db = \Config\Database::connect();
    }

    public function addPerformanceReport()
    {

        $performanceReportModel = new PerformanceReportModel();

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale($currentLang);

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

        $userId = auth()->id();

            $rules = [
                'document_title' => 'required|max_length[30]',
            ];

            $validationRule = [
                'report' => [
                    'label' => 'Upload Report',
                    'rules' => [
                        'uploaded[report]',
                        'ext_in[report,jpg,jpeg,png,pdf]',
                        'max_size[report,200000]',
                    ],
                    'errors' => [
                        'uploaded'  => lang('App.uploaded', ['file' => 'jpg, jpeg, png, pdf']),
                        'ext_in'   => lang('App.ext_in', ['file' => 'jpg, jpeg, png, pdf']),
                        'max_size'  => lang('App.max_size', ['size' => '200000']),
                    ],
                ],
            ];

            if ((! $this->validateData([], $validationRule)) || !$this->validate($rules)) {
                $errors = $this->validator->getErrors();

                $response = [
                    "status"    => false,
                    "message"   => lang('App.provideValidData'),
                    "data"      => ['errors' => $errors]
                ];
            } else {
                $report = $this->request->getFile('report');

                if (! $report->hasMoved()) {
                    $filepath = WRITEPATH . 'uploads/' . $report->store('');
                    $ext = $report->getClientExtension();

                    $save_data = [
                        'user_id'           => $userId,
                        'document_title'    => $this->request->getVar("document_title"),
                        'file_name'         => $report->getName(),
                        'file_type'         => $ext,
                        'status'            => 1,
                    ];
                    // save data
                    $res = $performanceReportModel->save($save_data);

                    $data = ['uploaded_fileinfo' => $report->getName()];

                    // create Activity log
                    $new_data = 'document title is ' . $this->request->getVar("document_title") . ' file name is ' . $report->getName();
                    $activity_data = [
                        'user_id'               => auth()->id(),
                        'activity_type_id'      => 1,      // added 
                        'activity'              => 'added performance report',
                        'new_data'              =>  $new_data,
                        'ip'                    => $this->request->getIPAddress()
                    ];
                    createActivityLog($activity_data);

                    $response = [
                        "status"    => true,
                        "message"   => lang('App.performanceReportUploaded'),
                        "data"      => $data
                    ];
                } else {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.performanceReportUploadFailed'),
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

    public function editPerformanceReport($id = null)
    {

        $performanceReportModel = new PerformanceReportModel();

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale($currentLang);

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP) || hasAccess(auth()->user(), EDITOR_ACCESS)) {

            $userId = auth()->id();

            if (empty($id)) {
                $response = [
                    "status"    => false,
                    "message"   => lang('App.provideValidData'),
                    "data"      => []
                ];
            } else {

                $rules = [
                    'document_title' => 'required|max_length[30]',
                ];

                $validationRule = [
                    'report' => [
                        'label' => 'Upload Report',
                        'rules' => [
                            'uploaded[report]',
                            'ext_in[report,jpg,jpeg,png,pdf]',
                            'max_size[report,200000]',
                        ],
                        'errors' => [
                            //'uploaded'  => lang('App.uploaded'),
                            'mime_in'   => lang('App.mime_in'),
                            'max_size'  => lang('App.max_size'),
                        ],
                    ],
                ];

                if ((! $this->validateData([], $validationRule)) || !$this->validate($rules)) {
                    $errors = $this->validator->getErrors();

                    $response = [
                        "status"    => false,
                        "message"   => lang('App.provideValidData'),
                        "data"      => ['errors' => $errors]
                    ];
                } else {
                    $report = $this->request->getFile('report');

                    if (! $report->hasMoved()) {
                        $filepath = WRITEPATH . 'uploads/' . $report->store('');
                        $ext = $report->getClientExtension();

                        $save_data = [
                            'user_id'           => $userId,
                            'document_title'    => $this->request->getVar("document_title"),
                            'file_name'         => $report->getName(),
                            'file_type'         => $ext,
                            'status'            => 1,
                        ];
                        // save data
                        $res = $performanceReportModel->save($save_data);

                        $data = ['uploaded_fileinfo' => $report->getName()];

                        $response = [
                            "status"    => true,
                            "message"   => lang('App.performanceReportUploaded'),
                            "data"      => $data
                        ];
                    } else {
                        $response = [
                            "status"    => false,
                            "message"   => lang('App.performanceReportUploadFailed'),
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

    public function getPerformanceReports($userId = null)
    {

        $performanceReportModel = new PerformanceReportModel();

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale($currentLang);

        //$userId = auth()->id();
        $userId = $userId ?? auth()->id();

        $reports = $performanceReportModel->where('user_id', $userId)->findAll();
        // echo '>>>>>> '. $this->db->getLastQuery();

        if ($reports) {
            $response = [
                "status"    => true,
                "message"   => lang('App.dataFound'),
                "data"      => ['reports' => $reports, 'uploads_path' => base_url() . 'uploads/']
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

    public function deletePerformanceReport()
    {
        $performanceReportModel = new PerformanceReportModel();

        $currentLang = $this->request->getVar("lang") ?? DEFAULT_LANGUAGE;
        $this->request->setLocale($currentLang);

        if (hasAccess(auth()->user(), ADMIN_ACCESS) || hasAccess(auth()->user(), TRANSFER_OWNERSHIP)) {

            $id = $this->request->getVar("id");

            if (!empty($id)) {

                $reports = $performanceReportModel->whereIn('id', $id)->findAll();

                if ($reports && count($reports) > 0) {

                    foreach ($reports as $report) {
                        if (file_exists(WRITEPATH . 'uploads/' . $report['file_name'])) {
                            unlink(WRITEPATH . 'uploads/' . $report['file_name']);
                            $del_res = $performanceReportModel->delete($report['id']);
                        }

                        // create Activity log
                        $old_data = 'document title is ' . $report['document_title'] . ' file name is ' . $report['file_name'];
                        $activity_data = [
                            'user_id'               => auth()->id(),
                            'activity_type_id'      => 3,      // added 
                            'activity'              => 'deleted performance report',
                            'old_data'              =>  $old_data,
                            'ip'                    => $this->request->getIPAddress()
                        ];
                        createActivityLog($activity_data);
                    }

                    if ($del_res == true) {
                        $response = [
                            "status"    => true,
                            "message"   => lang('App.performanceReportDeleted'),
                            "data"      => []
                        ];
                    } else {
                        $response = [
                            "status"    => false,
                            "message"   => lang('App.performanceReportDeleteFailed'),
                            "data"      => []
                        ];
                    }
                } else {
                    $response = [
                        "status"    => false,
                        "message"   => lang('App.noDataFound'),
                        "data"      => ['report_data' => $del_res]
                    ];
                }
            } else {
                $response = [
                    "status"    => false,
                    "message"   => lang('App.provideDeleteData'),
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

    public function downloadPerformanceReports()
    {
        $performanceReportModel = new PerformanceReportModel();
        $userId = auth()->id();
        $userId = '612';
        if(!empty($this->request->getVar("id"))){
            $ids = $this->request->getVar("id");
            $reports = $performanceReportModel->whereIn('id', $ids)->findAll();
        }else{
            $reports = $performanceReportModel->where('user_id', $userId)->findAll();
        }
        $zipDir = FCPATH . '/user_documents/'; // Adjust this path
        $user = getUserByID($userId);
        if (isset($user) && !empty($user)) {
            $zipFileName = $user->first_name . '' . $user->last_name . '_docs_' . date('ymdhis') . '.zip';
        } else {
            $zipFileName = $userId . '_docs_' . date('ymdhis') . '.zip';
        }

        $zipFilePath = $zipDir . $zipFileName;

        // Create the directory if it doesn't exist
        if (!is_dir($zipDir)) {
            mkdir($zipDir, 0777, true);
        }

        // Create a new zip archive
        $zip = new ZipArchive();

        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            foreach ($reports as $document) {
                // Get the content from the URL
                $full_path = base_url() . 'uploads/' . $document['file_name'];
                // $fileContent = file_get_contents($full_path);
                ob_start();
                $fileContent = file_get_contents($full_path);
                ob_end_flush();
                if ($fileContent !== false) {
                    // Create a unique file name based on the document title
                    $fileName = $document['document_title'] . '.' . $document['file_type'];
                    // Add the file content to the zip archive
                    $zip->addFromString($fileName, $fileContent);
                }
            }

            // Close the zip archive
            $zip->close();
            // echo "Zip file created successfully: " . $zipFilePath . "\n";
            $response = [
                "status" => true,
                "message" => "ZIP file created successfully.",
                "data" => [
                    "zip_name" => $zipFileName,
                    "zip_path" => base_url() . 'user_documents/' . $zipFileName // Use base_url to return a downloadable URL
                    // "zip_path" => $zipFilePath // Use base_url to return a downloadable URL
                ]
            ];
        } else {
            $response = [
                "status" => false,
                "message" => "Could not create ZIP file."
            ];
        }
        /* ##### code BY AMRIT ##### */
        return $this->respondCreated($response);
    }
}
