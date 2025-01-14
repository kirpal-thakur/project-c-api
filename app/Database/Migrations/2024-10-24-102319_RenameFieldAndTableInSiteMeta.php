<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RenameFieldAndTableInSiteMeta extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('page_meta', [
            'lang_code' => [
                'name' => 'lang_id', 
                'type' => 'VARCHAR', 
                'constraint' => 2,   
            ],
        ]);
    }

    public function down()
    {
        $this->forge->modifyColumn('page_meta', [
            'lang_id' => [
                'name' => 'lang_code', 
                'type' => 'VARCHAR',   
                'constraint' => 2,     
            ],
        ]);
    }
}
