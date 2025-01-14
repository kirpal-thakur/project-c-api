<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ClubMigration extends Migration
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
            'club_name' => [
                'type'           => 'VARCHAR',
                'constraint'     => 100,
                'null'           => false,
            ],
            'club_logo' => [
                'type'           => 'VARCHAR',
                'constraint'     => 50,
                'null'           => true,
            ],
            'country_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
            ],
            'is_taken' => [
                'type'           => 'ENUM',
                'constraint'     => ['yes', 'no'],
                'default'        => 'no',
            ],
            'taken_by' => [                         // pass id column from users table
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'null'           => true,
            ],
            'status' => [
                'type'           => 'ENUM',
                'constraint'     => ['active', 'inactive'],
                'default'        => 'active',
            ],
            'updated_at DATETIME default current_timestamp on update current_timestamp',
            'created_at DATETIME default current_timestamp',
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('country_id', 'countries', 'id');
        $this->forge->createTable('clubs');
    }

    public function down()
    {
        $this->forge->dropTable('clubs');
    }
}
