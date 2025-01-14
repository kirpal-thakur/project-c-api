<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ActivityTypeMigration extends Migration
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
            'activity_type' => [
                'type'          => 'VARCHAR',
                'constraint'    => '50',
            ],
            'activity_icon' => [
                'type'          => 'VARCHAR',
                'constraint'    => '50',
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
        $this->forge->createTable('activity_types');
    }

    public function down()
    {
        $this->forge->dropTable('activity_types');
    }
}
