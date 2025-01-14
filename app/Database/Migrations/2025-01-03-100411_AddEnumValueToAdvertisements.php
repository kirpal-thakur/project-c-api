<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEnumValueToAdvertisements extends Migration
{
    public function up()
    {
        // Alter the 'type' field of the 'advertisements' table to add a new enum value
        $this->forge->modifyColumn('advertisements', [
            'type' => [
                'name'    => 'type',
                'type'    => 'ENUM',
                'constraint' => ['250 x 250 - Square','200 x 200 - Small Square','468 x 60 - Banner','728 x 90 - Leaderboard','300 x 250 - Inline Rectangle','336 x 280 - Large Rectangle','120 x 600 - Skyscraper','160 x 600 - Wide Skyscraper','730 x 90 - Large Leaderboard'],  // Add your new enum value here
                'null'    => false
            ]
        ]);
    }

    public function down()
    {
        // Rollback the change (optional)
        $this->forge->modifyColumn('advertisements', [
            'type' => [
                'name'    => 'type',
                'type'    => 'ENUM',
                'constraint' => ['250 x 250 - Square','200 x 200 - Small Square','468 x 60 - Banner','728 x 90 - Leaderboard','300 x 250 - Inline Rectangle','336 x 280 - Large Rectangle','120 x 600 - Skyscraper','160 x 600 - Wide Skyscraper','730 x 90 - Large Leaderboard'],
                'null'    => false
            ]
        ]);
    }
}
