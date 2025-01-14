<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ActivityMigration extends Migration
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
                'null'          => true,
            ],
            'activity_on_id' => [
                'type'          => 'INT',
                'constraint'    => 11,
                'unsigned'      => true, 
            ],
            'activity_type_id' => [
                'type'          => 'INT',
                'constraint'    => 11,
                'unsigned'      => true, 
            ],
            'activity' => [
                'type'          => 'VARCHAR',
                'constraint'    => '500',
            ],
            'activity_en' => [
                'type'          => 'VARCHAR',
                'constraint'    => '500',
                'null'          => true,
            ],
            'activity_de' => [
                'type'          => 'VARCHAR',
                'constraint'    => '500',
                'null'          => true,
            ],
            'activity_it' => [
                'type'          => 'VARCHAR',
                'constraint'    => '500',
                'null'          => true,
            ],
            'activity_fr' => [
                'type'          => 'VARCHAR',
                'constraint'    => '500',
                'null'          => true,
            ],
            'activity_es' => [
                'type'          => 'VARCHAR',
                'constraint'    => '500',
                'null'          => true,
            ],
            'activity_pt' => [
                'type'          => 'VARCHAR',
                'constraint'    => '500',
                'null'          => true,
            ],
            'activity_da' => [
                'type'          => 'VARCHAR',
                'constraint'    => '500',
                'null'          => true,
            ],
            'activity_sv' => [
                'type'          => 'VARCHAR',
                'constraint'    => '500',
                'null'          => true,
            ],
            'old_data' => [
                'type'          => 'TEXT',
                'null'          => true,
            ],
            'new_data' => [
                'type'          => 'TEXT',
                'null'          => true,
            ],
            'ip' => [
                'type'          => 'VARCHAR',
                'constraint'    => '20',
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
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE');
        $this->forge->addForeignKey('activity_type_id', 'activity_types', 'id', 'CASCADE');
        $this->forge->createTable('activities');
    }

    public function down()
    {
        $this->forge->dropTable('activities');
    }
}
