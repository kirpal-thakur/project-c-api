<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class BoosterStatisticMigration extends Migration
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
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
            ],
            'profile_viewed' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
            ],
            'action' => [
                'type'           => 'ENUM',
                'constraint'     => ['view', 'click'],
            ],
            'ip' => [
                'type'          => 'VARCHAR',
                'constraint'    => '50',
            ],
            'updated_at DATETIME default current_timestamp on update current_timestamp',
            'created_at DATETIME default current_timestamp',
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('profile_viewed', 'users', 'id', '', 'CASCADE');
        $this->forge->createTable('booster_statistics');
    }

    public function down()
    {
        $this->forge->dropTable('booster_statistics');
    }
}
