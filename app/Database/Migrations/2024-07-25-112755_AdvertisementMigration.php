<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AdvertisementMigration extends Migration
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
            'type' => [
                'type'          => 'ENUM',
                'constraint'    => [
                    '250 x 250 - Square',
                    '200 x 200 - Small Square',
                    '468 x 60 - Banner',
                    '728 x 90 - Leaderboard',
                    '300 x 250 - Inline Rectangle',
                    '336 x 280 - Large Rectangle',
                    '120 x 600 - Skyscraper',
                    '160 x 600 - Wide Skyscraper'
                ],
            ],
            'redirect_url' => [
                'type'          => 'VARCHAR',
                'constraint'    => 50,
                'null'          => false,
            ],
            'page_id' => [
                'type'          => 'INT',
                'constraint'    => 11,
                'unsigned'      => true, 
            ],
            'featured_image' => [
                'type'          => 'VARCHAR',
                'constraint'    => 50,
                'null'          => true,
            ],
            'valid_from' => [
                'type'          => 'DATE',
                'null'          => true,
            ],
            'valid_to' => [
                'type'          => 'DATE',
                'null'          => true,
            ],
            'no_validity' => [
                'type'          => 'TINYINT',
                'constraint'    => 1,
                'unsigned'      => true, 
            ],
            'views' => [
                'type'          => 'INT',
                'constraint'    => 11,
                'unsigned'      => true, 
            ],
            'clicks' => [
                'type'          => 'INT',
                'constraint'    => 11,
                'unsigned'      => true, 
            ],
            'status' => [
                'type'          => 'ENUM',
                'constraint'    => ['draft','published','expired'],
                'default'       => 'draft',
            ],
            'updated_at DATETIME default current_timestamp on update current_timestamp',
            'created_at DATETIME default current_timestamp',
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('page_id', 'pages', 'id', '', 'CASCADE');
        $this->forge->createTable('advertisements');
    }

    public function down()
    {
        $this->forge->dropTable('advertisements');
    }
}
