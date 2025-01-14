<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class TestMigration extends Migration
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
            'team_id' => [
                'type'          => 'INT',
                'constraint'    => 11,
                'unsigned'      => true,
            ],
            'player_id' => [
                'type'          => 'INT',
                'constraint'    => 11,
                'unsigned'      => true, 
            ],
            'join_date' => [
                'type'          => 'DATE',
                'null'          => false,
            ],
            'end_date' => [
                'type'          => 'DATE',
                'null'          => true,
            ],
            'no_end_date' => [
                'type'          => 'TINYINT',
                'constraint'    => 1,
                'unsigned'      => true,
                'default'       => 0,
            ],
            'added_by' => [
                'type'          => 'INT',
                'constraint'    => 11,
                'unsigned'      => true, 
                'null'          => true,
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
        $this->forge->addForeignKey('team_id', 'teams', 'id');
        $this->forge->addForeignKey('player_id', 'users', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('added_by', 'users', 'id');
        $this->forge->createTable('club_players_test');
    }

    public function down()
    {
        $this->forge->dropTable('club_players_test');
    }
}
