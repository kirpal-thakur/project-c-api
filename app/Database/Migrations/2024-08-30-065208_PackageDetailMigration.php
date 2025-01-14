<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class PackageDetailMigration extends Migration
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
            'package_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
            ],
            'description' => [
                'type'          => 'TEXT',
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
            'interval' => [
                'type'          => 'ENUM',
                'constraint'    => ['daily', 'weekly', 'monthly','yearly'],
            ],
            'stripe_plan_id' => [
                'type'          => 'VARCHAR',
                'constraint'    => 50,
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
        $this->forge->addForeignKey('package_id', 'packages', 'id');
        $this->forge->createTable('package_details');
    }

    public function down()
    {
        $this->forge->dropTable('package_details');
    }
}
