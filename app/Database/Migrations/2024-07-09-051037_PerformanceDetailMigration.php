<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class PerformanceDetailMigration extends Migration
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
                'unsigned'      => true, 
            ],
            'team_id' => [
                'type'          => 'INT',
                'constraint'    => 11,
                'unsigned'      => true, 
            ],
            'matches' => [
                'type'          => 'INT',
                'constraint'    => 11,
                'unsigned'      => true, 
            ],
            'goals' => [
                'type'          => 'INT',
                'constraint'    => 11,
                'unsigned'      => true, 
            ],
            'session' => [
                'type'          => 'VARCHAR',
                'constraint'    => '10',
                'null'          => false,
            ],
            'coach' => [
                'type'          => 'VARCHAR',
                'constraint'    => '100',
                'null'          => false,
            ],
            'player_age' => [
                'type'          => 'INT',
                'constraint'    => 11,
                'unsigned'      => true, 
            ],
            'from_date' => [
                'type'          => 'DATE',
            ],
            'to_date' => [
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
        $this->forge->addForeignKey('team_id', 'teams', 'id', 'CASCADE');
        $this->forge->createTable('performance_details');
    }

    public function down()
    {
        $this->forge->dropTable('performance_details');
    }
}
