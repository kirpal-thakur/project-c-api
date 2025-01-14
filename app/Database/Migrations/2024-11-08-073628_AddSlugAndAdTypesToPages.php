<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSlugAndAdTypesToPages extends Migration
{
    public function up()
    {
        $this->forge->addColumn('pages', [
            'slug' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,  // Allow null values
                // 'unique'     => true,  // Ensure the value is unique
            ],
            'ad_types' => [
                'type' => 'JSON',
                'null' => true,  // Allow null values
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('pages', ['slug', 'ad_types']);
    }
}
