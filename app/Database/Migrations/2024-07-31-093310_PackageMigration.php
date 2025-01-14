<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class PackageMigration extends Migration
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
            'title' => [
                'type'          => 'VARCHAR',
                'constraint'    => 100,
                null            => false,
            ],
            // 'description' => [
            //     'type'          => 'TEXT',
            // ],
            // 'price' => [
            //     'type'          => 'FLOAT',
            //     'constraint'    => '10,2',
            //     'unsigned'      => true, 
            // ],
            // 'currency' => [
            //     'type'          => 'VARCHAR',
            //     'constraint'    => 10,
            //     'null'          => false,
            // ],
            // 'interval' => [
            //     'type'          => 'ENUM',
            //     'constraint'    => ['Month','Year'],
            // ],
            // 'stripe_plan_id' => [
            //     'type'          => 'VARCHAR',
            //     'constraint'    => 50,
            // ],
            'status' => [
                'type'          => 'ENUM',
                'constraint'    => ['active', 'inactive'],
                'default'       => 'active',
            ],
            'updated_at DATETIME default current_timestamp on update current_timestamp',
            'created_at DATETIME default current_timestamp',
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('packages');
    }

    public function down()
    {
        $this->forge->dropTable('packages');
    }
}
