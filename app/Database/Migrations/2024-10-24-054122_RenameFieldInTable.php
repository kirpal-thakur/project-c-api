<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RenameFieldInTable extends Migration
{
    public function up()
    {
        // Rename the field
        $this->forge->modifyColumn('site_meta', [
            'language_code' => [
                'name' => 'lang_code',
                'type' => 'VARCHAR',
                'constraint' => 60, // Adjust as needed
            ],
        ]);

        // Rename the table
        $this->forge->renameTable('site_meta', 'page_meta');
    }

    public function down()
    {
        // Rename the table back
        $this->forge->renameTable('page_meta', 'site_meta');

        // Rename the field back
        $this->forge->modifyColumn('site_meta', [
            'lang_code' => [
                'name' => 'language_code',
                'type' => 'VARCHAR',
                'constraint' => 60, // Must match the original type
            ],
        ]);
    }
}
