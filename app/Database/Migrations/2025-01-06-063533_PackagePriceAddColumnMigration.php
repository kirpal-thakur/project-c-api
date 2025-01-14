<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class PackagePriceAddColumnMigration extends Migration
{
    public function up()
    {
            $fields = [
                'domain_id' => [
                    'type'          => 'INT',
                    'constraint'    => '5',
                    'unsigned'      => true,
                    'after'         => 'currency',
                ],
            ];

            $this->forge->addColumn('package_prices', $fields);
            $this->forge->addForeignKey('domain_id', 'domains', 'id');
    }

    public function down()
    {
        $this->forge->dropColumn('package_prices', 'domain_id'); // to drop one single column
    }
}
