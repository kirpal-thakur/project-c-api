<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class TeamMigration extends Migration
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
            'club_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
            ],
            'team_type' => [
                'type'          => 'VARCHAR',
                'constraint'    => '10',
                'null'          => false,
            ],
            'country_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
            ],
            'team_logo' => [
                'type'          => 'VARCHAR',
                'constraint'    => '100',
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
        $this->forge->addForeignKey('club_id', 'users', 'id');
        $this->forge->addForeignKey('country_id', 'countries', 'id');
        $this->forge->createTable('teams');
    }

    public function down()
    {
        $this->forge->dropTable('teams');
    }
}
