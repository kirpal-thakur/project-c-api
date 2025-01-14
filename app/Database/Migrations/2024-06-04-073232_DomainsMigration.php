<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DomainsMigration extends Migration
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
            'domain_name' => [
                'type'          => 'VARCHAR',
                'constraint'    => '100',
            ],
            'location' => [
                'type'          => 'VARCHAR',
                'constraint'    => '50',
            ],
            'domain_description' => [
                'type'          => 'TEXT',
                'null'          => true,
            ],
            'domain_flag' => [
                'type'          => 'VARCHAR',
                'constraint'    => '50',
                'null'          => true,
            ],
            'currency' => [
                'type'          => 'VARCHAR',
                'constraint'    => '10',
            ],
            'domain_country_code' => [
                'type'          => 'VARCHAR',
                'constraint'    => '10',
            ],
            'country_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
            ],
            'default_lang_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
            ],
            'status' => [
                'type'          => 'ENUM',
                'constraint'    => ['active', 'inactive'],
                'default'       => 'active',
            ],
            'created_at datetime default current_timestamp',
            'updated_at datetime default current_timestamp on update current_timestamp',
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('domains');
    }

    public function down()
    {
        $this->forge->dropTable('domains');
    }
}

// ALTER TABLE `domains` ADD `default_lang_id` INT NOT NULL AFTER `country_id`;
