<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CountryMigration extends Migration
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
            'country_name' => [
                'type'          => 'VARCHAR',
                'constraint'    => '100',
                'null'          => false,
            ],
            'country_code' => [
                'type'          => 'VARCHAR',
                'constraint'    => '10',
                'null'          => false,
            ],
            'country_flag' => [
                'type'          => 'VARCHAR',
                'constraint'    => '100',
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
        $this->forge->createTable('countries');
    }

    public function down()
    {
        $this->forge->dropTable('countries');
    }
}
