<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AdvertisementLogMigration extends Migration
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
            'ad_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
            ],
            'user_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
            ],
            'ip_address' => [
                'type'           => 'VARCHAR',
                'constraint'     => '45',
                'null'           => false,
            ],
            'action_type' => [
                'type'           => 'ENUM',
                'constraint'     => ['view', 'click'],
                'null'           => false,
            ],
            'updated_at DATETIME default current_timestamp on update current_timestamp',
            'created_at DATETIME default current_timestamp',
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('ad_id', 'advertisements', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->createTable('advertisement_logs');
    }

    public function down()
    {
        $this->forge->dropTable('advertisement_logs');
    }
}
