<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateContactsTableMigration extends Migration
{
    public function up()
    {
        // Define the fields for the contacts table
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'      => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'message' => [
                'type'       => 'TEXT',
            ],
            'created_at' => [
                'type'      => 'DATETIME',
                'null'      => true,
            ],
            'updated_at' => [
                'type'      => 'DATETIME',
                'null'      => true,
            ],
        ]);

        // Add the primary key
        $this->forge->addPrimaryKey('id');

        // Create the table
        $this->forge->createTable('contacts');
    }

    public function down()
    {
        $this->forge->dropTable('contacts');
    }
}
