<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class BoosterAudiencMigration extends Migration
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
            'subscription_id' => [
                'type'           => 'VARCHAR',
                'constraint'     => 50,
                null             => false   
            ],
            'user_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
            ],
            'target_role' => [
                'type'           => 'INT',
                'constraint'     => 5,
                'unsigned'       => true,
            ],
            'updated_at DATETIME default current_timestamp on update current_timestamp',
            'created_at DATETIME default current_timestamp',
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        // $this->forge->addForeignKey('subscription_id', 'user_subscriptions', 'stripe_subscription_id', '', 'CASCADE'); // cannot be set as Foreign key
        $this->forge->addForeignKey('target_role', 'user_roles', 'id', '', 'CASCADE');
        $this->forge->createTable('booster_audiences');
    }

    public function down()
    {
        $this->forge->dropTable('booster_audiences');
    }
}
