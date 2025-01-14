<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ScoutPlayerMigration extends Migration
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
            'scout_id' => [
                'type'          => 'INT',
                'constraint'    => 11,
                'unsigned'      => true,
            ],
            'player_id' => [
                'type'          => 'INT',
                'constraint'    => 11,
                'unsigned'      => true, 
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
        $this->forge->addForeignKey('scout_id', 'users', 'id');
        $this->forge->addForeignKey('player_id', 'users', 'id');
        $this->forge->addForeignKey('added_by', 'users', 'id');
        $this->forge->createTable('scout_players');
    }

    public function down()
    {
        $this->forge->dropTable('scout_players');
    }
}
