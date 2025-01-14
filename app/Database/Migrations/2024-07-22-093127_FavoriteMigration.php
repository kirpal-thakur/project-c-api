<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class FavoriteMigration extends Migration
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
            'favorite_id' => [
                'type'          => 'INT',
                'constraint'    => 11,
                'unsigned'      => true, 
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
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', '');
        $this->forge->addForeignKey('favorite_id', 'users', 'id', 'CASCADE', '');
        // $this->forge->addForeignKey('team_from', 'teams', 'id', 'CASCADE', 'fk_team_from_team_id');
        // $this->forge->addForeignKey('team_to', 'teams', 'id', 'CASCADE', 'fk_team_to_team_id');
        $this->forge->createTable('favorites');
    }

    public function down()
    {
        $this->forge->dropTable('favorites');
    }
}
