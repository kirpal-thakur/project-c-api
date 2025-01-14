<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class GalleryMigration extends Migration
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
                'unsigned'      => true, // Change this to FALSE
            ],
            'file_name' => [
                'type'       => 'VARCHAR',
                'constraint' => '200',
            ],
            'file_type' => [
                'type'       => 'VARCHAR',
                'constraint' => '10',
            ],
            'is_featured' => [
                'type'          => 'TINYINT',
                'constraint'    => 1,
                'unsigned'      => true, // Change this to FALSE
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
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('gallery');
    }
    
    public function down()
    {
        $this->forge->dropTable('gallery');
    }
}
