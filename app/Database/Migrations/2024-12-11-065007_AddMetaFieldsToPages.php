<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMetaFieldsToPages extends Migration
{
    public function up()
    {
        $fields = [
            'meta_title' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'title',
            ],
            'meta_description' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'meta_title',
            ],
        ];

        $this->forge->addColumn('pages', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('pages', 'meta_title');
        $this->forge->dropColumn('pages', 'meta_description');
    }
}
