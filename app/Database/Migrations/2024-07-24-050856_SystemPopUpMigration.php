<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SystemPopUpMigration extends Migration
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
                'type'          => 'INT',
                'constraint'    => 11,
                'unsigned'      => true, 
            ],
            'title' => [
                'type'          => 'VARCHAR',
                'constraint'    => 256,
                'null'          => false,
            ],
            'description' => [
                'type'          => 'TEXT',
                'null'          => false,
            ],
            'popup_for' => [
                'type'          => 'TINYINT',
                'constraint'    => 1,
                'unsigned'      => true, 
            ],
            'language' => [
                'type'          => 'TINYINT',
                'constraint'    => 1,
                'unsigned'      => true, 
            ],
            'location' => [
                'type'          => 'TINYINT',
                'constraint'    => 1,
                'unsigned'      => true, 
            ],
            'frequency' => [
                'type'          => 'ENUM',
                'constraint'    => ['Once a day','Once a week','Once 2 Hrs','Twice a day','Once a month','One time only'],
                'unsigned'      => true, 
            ],
            'start_date' => [
                'type'          => 'DATE',
                'null'          => true,
            ],
            'end_date' => [
                'type'          => 'DATE',
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
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', '');
        $this->forge->createTable('system_popups');
    }

    public function down()
    {
        $this->forge->dropTable('system_popups');
    }
}
