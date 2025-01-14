<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColumnsToPackageMigration extends Migration
{
    public function up()
    {
        $fields = [
            'title_en' => [
                'type'          => 'VARCHAR',
                'constraint'    => 100,
                'after'         => 'domain_id',
            ],
            'title_de' => [
                'type'          => 'VARCHAR',
                'constraint'    => 100,
                'after'         => 'title_en',
            ],
            'title_it' => [
                'type'          => 'VARCHAR',
                'constraint'    => 100,
                'after'         => 'title_de',
            ],
            'title_fr' => [
                'type'          => 'VARCHAR',
                'constraint'    => 100,
                'after'         => 'title_it',
            ],
            'title_es' => [
                'type'          => 'VARCHAR',
                'constraint'    => 100,
                'after'         => 'title_fr',
            ],
            'title_pt' => [
                'type'          => 'VARCHAR',
                'constraint'    => 100,
                'after'         => 'title_es',
            ],
            'title_da' => [
                'type'          => 'VARCHAR',
                'constraint'    => 100,
                'after'         => 'title_pt',
            ],
            'title_sv' => [
                'type'          => 'VARCHAR',
                'constraint'    => 100,
                'after'         => 'title_da',
            ],
        ];

        $this->forge->addColumn('packages', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('packages', ['title_en', 'title_de', 'title_it', 'title_fr', 'title_es', 'title_pt', 'title_da', 'title_sv']);
    }
}
