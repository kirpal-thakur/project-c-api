<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class PlayerPositionMigration extends Migration
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
            'user_id' => [
                'type'          => 'INT',
                'constraint'    => 11,
                'unsigned'      => true,
            ],
            'position_id' => [
                'type'          => 'INT',
                'constraint'    => 11,
                'unsigned'      => true, 
            ],
            'is_main' => [
                'type'          => 'TINYINT',
                'constraint'    => 1,
                'unsigned'      => true, 
                'null'          => true,
            ],
            'added_by' => [
                'type'          => 'INT',
                'constraint'    => 11,
                'unsigned'      => true, 
                'null'          => true,
            ],
            'updated_at DATETIME default current_timestamp on update current_timestamp',
            'created_at DATETIME default current_timestamp',
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('position_id', 'positions', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('added_by', 'users', 'id');
        $this->forge->createTable('player_positions');
    }

    public function down()
    {
        $this->forge->dropTable('player_positions');
    }
}
