<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddNewFieldsToContacts extends Migration
{
    public function up()
    {
        $this->forge->addColumn('contacts', [
            'phone' => [
                'type' => 'VARCHAR',
                'constraint' => '15', 
                'null' => true,      
            ],
            'domain' => [
                'type' => 'VARCHAR',
                'constraint' => '255', 
                'null' => true,      
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('contacts', ['phone', 'domain']);
    }
}
