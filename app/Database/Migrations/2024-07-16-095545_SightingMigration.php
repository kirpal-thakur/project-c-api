<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SightingMigration extends Migration
{
    public function up()
    {
        //sightings
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'club_id' => [
                'type'          => 'INT',
                'constraint'    => 11,
                'unsigned'      => true,
            ],
            'event_name' => [
                'type'          => 'VARCHAR',
                'constraint'    => 256,
                'null'          => true,
            ],
            'manager_name' => [
                'type'          => 'VARCHAR',
                'constraint'    => 256,
                'null'          => true,
            ],
            'event_date' => [
                'type'          => 'DATE',
                'null'          => true,
            ],
            'event_time' => [
                'type'          => 'VARCHAR',
                'constraint'    => 10,
                'null'          => true,
            ],
            'banner' => [
                'type'          => 'VARCHAR',
                'constraint'    => 50,
                'null'          => true,
            ],
            'address' => [
                'type'          => 'VARCHAR',
                'constraint'    => 256,
                'null'          => true,
            ],
            'zipcode' => [
                'type'          => 'VARCHAR',
                'constraint'    => 10,
                'null'          => true,
            ],
            'city' => [
                'type'          => 'VARCHAR',
                'constraint'    => 100,
                'null'          => true,
            ],
            'region' => [
                'type'          => 'VARCHAR',
                'constraint'    => 100,
                'null'          => true,
            ],
            'document_title' => [
                'type'          => 'VARCHAR',
                'constraint'    => 100,
                'null'          => true,
            ],
            'document_file' => [
                'type'          => 'VARCHAR',
                'constraint'    => 100,
                'null'          => true,
            ],
            'about_event' => [
                'type'          => 'TEXT',
                'constraint'    => 100,
                'null'          => true,
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
        // $this->forge->addForeignKey('club_id', 'users', 'id', 'CASCADE', 'CASCADE');
        // $this->forge->addForeignKey('player_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('added_by', 'users', 'id');
        $this->forge->createTable('sightings');
    }

    public function down()
    {
        $this->forge->dropTable('sightings');
    }
}
