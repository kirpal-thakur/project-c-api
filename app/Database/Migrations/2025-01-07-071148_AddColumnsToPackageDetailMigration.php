<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColumnsToPackageDetailMigration extends Migration
{
    public function up()
    {
        $fields = [
            'description_en' => [
                'type'          => 'text',
                'after'         => 'stripe_plan_id',
            ],
            'interval_en' => [
                'type'          => 'VARCHAR',
                'constraint'    => 100,
                'after'         => 'description_en',
            ],

            'description_de' => [
                'type'          => 'text',
                'after'         => 'interval_en',
            ],
            'interval_de' => [
                'type'          => 'VARCHAR',
                'constraint'    => 100,
                'after'         => 'description_de',
            ],

            'description_it' => [
                'type'          => 'text',
                'after'         => 'interval_de',
            ],
            'interval_it' => [
                'type'          => 'VARCHAR',
                'constraint'    => 100,
                'after'         => 'description_it',
            ],

            'description_fr' => [
                'type'          => 'text',
                'after'         => 'interval_it',
            ],
            'interval_fr' => [
                'type'          => 'VARCHAR',
                'constraint'    => 100,
                'after'         => 'description_fr',
            ],

            'description_es' => [
                'type'          => 'text',
                'after'         => 'interval_fr',
            ],
            'interval_es' => [
                'type'          => 'VARCHAR',
                'constraint'    => 100,
                'after'         => 'description_es',
            ],

            'description_pt' => [
                'type'          => 'text',
                'after'         => 'interval_es',
            ],
            'interval_pt' => [
                'type'          => 'VARCHAR',
                'constraint'    => 100,
                'after'         => 'description_pt',
            ],

            'description_da' => [
                'type'          => 'text',
                'after'         => 'interval_pt',
            ],
            'interval_da' => [
                'type'          => 'VARCHAR',
                'constraint'    => 100,
                'after'         => 'description_da',
            ],

            'description_sv' => [
                'type'          => 'text',
                'after'         => 'interval_da',
            ],
            'interval_sv' => [
                'type'          => 'VARCHAR',
                'constraint'    => 100,
                'after'         => 'description_sv',
            ],
        ];

        $this->forge->addColumn('package_details', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('package_details', ['description_en', 'interval_en', 'description_de', 'interval_de', 'description_it', 'interval_it', 'description_fr', 'interval_fr', 'description_es', 'interval_es', 'description_pt', 'interval_pt', 'description_da', 'interval_da', 'description_sv', 'interval_sv']);
    }
}
