<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RepresentatorRoleMigration extends Migration
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
            'role_name' => [
                'type'          => 'VARCHAR',
                'constraint'    => '100',
            ],
            'role_description' => [
                'type'          => 'TEXT',
                'null'          => true,
            ],
            'role_slug'  => [
                'type'          => 'VARCHAR',
                'constraint'    => '100',
            ],
            'status' => [
                'type'          => 'ENUM',
                'constraint'    => ['active', 'inactive'],
                'default'       => 'active',
            ],
            'created_at datetime default current_timestamp',
            'updated_at datetime default current_timestamp on update current_timestamp',
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('representator_roles');
    }

    public function down()
    {
        $this->forge->dropTable('representator_roles');
    }
}
