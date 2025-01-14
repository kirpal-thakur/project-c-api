<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class PaymentMethodMigration extends Migration
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
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
            ],
            'stripe_payment_method_id' => [
                'type'          => 'VARCHAR',
                'constraint'    => 50,
                'null'          => false,
            ],
            'type' => [
                'type'          => 'VARCHAR',
                'constraint'    => 50,
                'null'          => false,
            ],
            'brand' => [
                'type'          => 'VARCHAR',
                'constraint'    => 50,
                'null'          => false,
            ],
            'country' => [
                'type'          => 'VARCHAR',
                'constraint'    => 50,
                'null'          => false,
            ],
            'display_brand' => [
                'type'          => 'VARCHAR',
                'constraint'    => 50,
                'null'          => false,
            ],
           'exp_month' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
            ],
            'exp_year' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
            ],
            'last4' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
            ],
            'funding' => [                                              // credit or debit
                'type'          => 'VARCHAR',
                'constraint'    => 50,
                'null'          => false,
            ],
            'event_id' => [
                'type'          => 'VARCHAR',
                'constraint'    => 50,
                'null'          => true,
            ],
            'is_default' => [
                'type'          => 'INT',
                'constraint'    => 1,
                'default'       => 0,
            ],
            'stripe_created_at' => [
                'type'          => 'DATETIME',
                'null'          => false,
            ],
            'updated_at DATETIME default current_timestamp on update current_timestamp',
            'created_at DATETIME default current_timestamp',
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->createTable('payment_methods');
    }

    public function down()
    {
        $this->forge->dropTable('payment_methods');
    }
}
