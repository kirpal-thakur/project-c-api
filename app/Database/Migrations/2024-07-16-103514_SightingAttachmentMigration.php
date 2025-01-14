<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SightingAttachmentMigration extends Migration
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
            'event_id' => [
                'type'          => 'INT',
                'constraint'    => 11,
                'unsigned'      => true,
            ],
            'title' => [
                'type'          => 'VARCHAR',
                'constraint'    => 256,
            ],
            'attachment' => [
                'type'          => 'VARCHAR',
                'constraint'    => 50,
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
        $this->forge->addForeignKey('event_id', 'sightings', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('added_by', 'users', 'id');
        $this->forge->createTable('sighting_attachments');
    }

    public function down()
    {
        $this->forge->dropTable('sighting_attachments');
    }
}
