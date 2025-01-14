<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSiteMetaTable extends Migration
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
            'page_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'meta_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'meta_value' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'language_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,  
                'null'       => false,
            ],
            'created_at' => [
                'type'    => 'TIMESTAMP',
                'null'    => true,
                'default' => null,
            ],
            'updated_at' => [
                'type'    => 'TIMESTAMP',
                'null'    => true,
                'default' => null,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('page_id', 'pages', 'id', '', 'CASCADE');
        $this->forge->createTable('site_meta');
    }

    public function down()
    {
        $this->forge->dropTable('site_meta');
    }
}
