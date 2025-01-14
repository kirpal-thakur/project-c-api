<?php

namespace App\Controllers;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use CodeIgniter\Controller;
use App\Models\ClubModel;
use App\Models\TeamModel;


class ExcelController extends Controller
{
    // View to upload Excel
    public function importExcel()
    {
        return view('import_excel');
    }

    // Handle Excel Upload
    public function uploadExcel()
    {
        $file = $this->request->getFile('excel_file');

        $clubModel = new ClubModel();
        $teamModel = new TeamModel();

        if ($file->isValid() && !$file->hasMoved()) {
            // Move the file to the writable directory
            // $filePath = WRITEPATH . 'uploads/' . $file->getRandomName();
            // $file->move(WRITEPATH . 'uploads', $filePath);


            // $newName = $file->getRandomName();  // Generate a random name
            // $file->move(WRITEPATH . 'uploads', $newName);

            // Load PhpSpreadsheet and read the file
            // $spreadsheet = IOFactory::load($filePath);
            // $spreadsheet = IOFactory::load(WRITEPATH . 'uploads/'. $newName);

            $tempFilePath = $file->getTempName();

            // Load the spreadsheet file
            $spreadsheet = IOFactory::load($tempFilePath);

            $sheet = $spreadsheet->getActiveSheet();
            $data = $sheet->toArray();

            $importData = [];
            if($data){

                $counter = 0;
                foreach($data as $key => $clubData){
                    // echo '<pre>';
                    // print_r($clubData);
                    // echo '</pre>';;

                    if($key == 0){
                        continue;
                    }
                    $saveData = [
                        'club_name' => $clubData['0'],
                        'club_logo' => $clubData['1'],
                        'country_id' => $clubData['5'],
                    ];

                    $clubModel->save($saveData);

                    // Get the last inserted ID
                    $lastInsertID = $clubModel->insertID();

                    if($lastInsertID){
                        $teamData = [
                            [
                                'club_id'       =>  $lastInsertID,
                                'team_type'     =>  'A',
                                'country_id'    =>  $clubData['5'],
                            ],
                            [
                                'club_id'       =>  $lastInsertID,
                                'team_type'     =>  'B',
                                'country_id'    =>  $clubData['5'],
                            ],
                            [
                                'club_id'       =>  $lastInsertID,
                                'team_type'     =>  'U17',
                                'country_id'    =>  $clubData['5'],
                            ],
                            [
                                'club_id'       =>  $lastInsertID,
                                'team_type'     =>  'U19',
                                'country_id'    =>  $clubData['5'],
                            ],
                            [
                                'club_id'       =>  $lastInsertID,
                                'team_type'     =>  'U21',
                                'country_id'    =>  $clubData['5'],
                            ],
                            [
                                'club_id'       =>  $lastInsertID,
                                'team_type'     =>  'U23',
                                'country_id'    =>  $clubData['5'],
                            ]
                            ];
                            
                            $teamModel->insertBatch($teamData);
                    }

                }

                
            }

            
            // Process the data (example: display the array of data)
            // echo '>>>>>>>>>>>> newName >>>> ' . $newName;

            // echo '<pre>';
            // print_r($data);
            // echo '</pre>';

            // Optionally, write a modified Excel file after processing
            $newSpreadsheet = new Spreadsheet();
            $newSpreadsheet->getActiveSheet()->fromArray($data, NULL, 'A1');

            // Save the new Excel file
            $writer = new Xlsx($newSpreadsheet);
            $newFilePath = WRITEPATH . 'uploads/new_excel_file.xlsx';
            $writer->save($newFilePath);

            // Output the path to the saved file
            echo "New Excel file saved at: " . $newFilePath;
        } else {
            return redirect()->back()->with('error', 'File upload failed!');
        }
    }
}
