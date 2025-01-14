<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ActivityEventsMigration extends Migration
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
            'activity_event' => [
                'type'          => 'VARCHAR',
                'constraint'    => '100',
            ],
            'activity_icon' => [
                'type'          => 'VARCHAR',
                'constraint'    => '50',
            ],
            'user_message_en' => [
                'type'          => 'VARCHAR',
                'constraint'    => '500',
                'null'          => true,
            ],
            'user_message_de' => [
                'type'          => 'VARCHAR',
                'constraint'    => '500',
                'null'          => true,
            ],
            'user_message_it' => [
                'type'          => 'VARCHAR',
                'constraint'    => '500',
                'null'          => true,
            ],
            'user_message_fr' => [
                'type'          => 'VARCHAR',
                'constraint'    => '500',
                'null'          => true,
            ],
            'user_message_es' => [
                'type'          => 'VARCHAR',
                'constraint'    => '500',
                'null'          => true,
            ],
            'user_message_pt' => [
                'type'          => 'VARCHAR',
                'constraint'    => '500',
                'null'          => true,
            ],
            'user_message_da' => [
                'type'          => 'VARCHAR',
                'constraint'    => '500',
                'null'          => true,
            ],
            'user_message_sv' => [
                'type'          => 'VARCHAR',
                'constraint'    => '500',
                'null'          => true,
            ],
            
            'admin_message_en' => [
                'type'          => 'VARCHAR',
                'constraint'    => '500',
                'null'          => true,
            ],
            'admin_message_de' => [
                'type'          => 'VARCHAR',
                'constraint'    => '500',
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
        $this->forge->createTable('activity_events');
    }

    public function down()
    {
        $this->forge->dropTable('activity_events');
    }
}
