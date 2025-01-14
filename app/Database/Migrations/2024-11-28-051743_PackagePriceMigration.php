<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class PackagePriceMigration extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'package_detail_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
            ],
            'price' => [
                'type'          => 'FLOAT',
                'constraint'    => '10,2',
                'unsigned'      => true, 
            ],
            'currency' => [
                'type'          => 'VARCHAR',
                'constraint'    => 10,
                'null'          => false,
            ],
            'status' => [
                'type'          => 'ENUM',
                'constraint'    => ['active', 'inactive'],
                'default'       => 'active',
            ],
            'updated_at DATETIME default current_timestamp on update current_timestamp',
            'created_at DATETIME default current_timestamp',
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('package_detail_id', 'package_details', 'id');
        $this->forge->createTable('package_prices');
    }

    public function down()
    {
        $this->forge->dropTable('package_prices');
    }
}
