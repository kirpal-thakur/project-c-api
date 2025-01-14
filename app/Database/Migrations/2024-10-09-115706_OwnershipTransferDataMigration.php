<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class OwnershipTransferDataMigration extends Migration
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
            'from_user_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
            ],
            'from_email' => [
                'type'          => 'VARCHAR',
                'constraint'    => 255,
            ],
            'to_user_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
            ],
            'to_email' => [
                'type'          => 'VARCHAR',
                'constraint'    => 255,
            ],
            'status' => [
                'type'          => 'ENUM',
                'constraint'    => ['pending', 'confrimed', 'rejected'],
                'default'       => 'pending',
            ],
            'updated_at DATETIME default current_timestamp on update current_timestamp',
            'created_at DATETIME default current_timestamp',
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('ownership_transfer_data');
    }

    public function down()
    {
        $this->forge->dropTable('ownership_transfer_data');
    }
}
