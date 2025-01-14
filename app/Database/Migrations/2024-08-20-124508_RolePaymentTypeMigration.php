<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RolePaymentTypeMigration extends Migration
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
            'user_role_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
            ],
            'payment_type' => [
                'type'          => 'ENUM',
                'constraint'    => ['Free', 'Paid'],
            ],
            'updated_at DATETIME default current_timestamp on update current_timestamp',
            'created_at DATETIME default current_timestamp',
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_role_id', 'users', 'id', '', 'CASCADE');
        $this->forge->createTable('role_payment_types');
    }

    public function down()
    {
        $this->forge->dropTable('role_payment_types');
    }
}
