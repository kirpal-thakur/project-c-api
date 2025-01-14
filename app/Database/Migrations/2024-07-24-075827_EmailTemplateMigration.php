<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class EmailTemplateMigration extends Migration
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
            'type' => [
                'type'          => 'VARCHAR',
                'constraint'    => 256,
                'null'          => false,
            ],
            'title' => [
                'type'          => 'VARCHAR',
                'constraint'    => 256,
                'null'          => false,
            ],
            'subject' => [
                'type'          => 'TEXT',
                'null'          => false,
            ],
            'content' => [
                'type'          => 'TEXT',
                'null'          => false,
            ],
            'email_for' => [
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
        $this->forge->createTable('email_templates');
    }

    public function down()
    {
        $this->forge->dropTable('email_templates');
    }
}
