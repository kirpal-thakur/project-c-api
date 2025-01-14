<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class PageMigration extends Migration
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
                'constraint'    => 500,
                'null'          => false,
            ],
            'content' => [
                'type'          => 'TEXT',
                'null'          => true,
            ],
            'featured_image' => [
                'type'          => 'VARCHAR',
                'constraint'    => 50,
                'null'          => true,
            ],
            'language' => [
                'type'          => 'INT',
                'constraint'    => 11,
                'unsigned'      => true, 
            ],
            'status' => [
                'type'          => 'ENUM',
                'constraint'    => ['draft', 'published'],
                'default'       => 'draft',
            ],
            'updated_at DATETIME default current_timestamp on update current_timestamp',
            'created_at DATETIME default current_timestamp',
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('language', 'languages', 'id', '', 'CASCADE');
        $this->forge->createTable('pages');
    }

    public function down()
    {
        $this->forge->dropTable('pages');
    }
}
