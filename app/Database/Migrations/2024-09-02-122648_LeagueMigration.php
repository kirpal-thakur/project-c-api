<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class LeagueMigration extends Migration
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
            'league_name' => [
                'type'          => 'VARCHAR',
                'constraint'    => 100,
                'null'          => false,
            ],
            'league_logo' => [
                'type'          => 'VARCHAR',
                'constraint'    => 50,
            ],
            'country_id' => [
                'type'          => 'INT',
                'constraint'    => 11,
                'unsigned'      => true,
            ],
            
            'updated_at DATETIME default current_timestamp on update current_timestamp',
            'created_at DATETIME default current_timestamp',
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('country_id', 'countries', 'id');
        $this->forge->createTable('leagues');
    }

    public function down()
    {
        $this->forge->dropTable('leagues');
    }
}
