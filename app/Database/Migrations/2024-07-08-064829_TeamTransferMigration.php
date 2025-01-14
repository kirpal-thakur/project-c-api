<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class TeamTransferMigration extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 5,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'          => 'INT',
                'constraint'    => 11,
                'unsigned'      => true, // Change this to FALSE
            ],
            'team_from' => [
                'type'          => 'INT',
                'constraint'    => 11,
                'unsigned'      => true, // Change this to FALSE
            ],
            'team_to' => [
                'type'          => 'INT',
                'constraint'    => 11,
                'unsigned'      => true, // Change this to FALSE
            ],
            'session' => [
                'type'          => 'VARCHAR',
                'constraint'    => '10',
                'null'          => false,
            ],
            'date_of_transfer' => [
                'type'          => 'DATE',
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
        $this->forge->addForeignKey('team_from', 'teams', 'id', 'CASCADE', 'fk_team_from_team_id');
        $this->forge->addForeignKey('team_to', 'teams', 'id', 'CASCADE', 'fk_team_to_team_id');
        //$this->forge->addForeignKey(['team_from', 'team_to'], 'teams', ['id', 'id'], 'CASCADE', '','fk_team_id_team_transfer');
        $this->forge->createTable('team_transfers');
    }

    public function down()
    {
        $this->forge->dropTable('team_transfers');
    }
}
