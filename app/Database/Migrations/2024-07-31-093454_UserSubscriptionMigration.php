<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UserSubscriptionMigration extends Migration
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
            'package_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
            ],
            'payment_method' => [
                'type'          => 'ENUM',
                'constraint'    => ['stripe'],
                'default'       => 'stripe',
            ],
            'payment_method_id' => [
                'type'          => 'VARCHAR',
                'constraint'    => 50,
                'null'          => true,
            ],
            'stripe_subscription_id' => [
                'type'          => 'VARCHAR',
                'constraint'    => 50,
                'null'          => false,
            ],
            'stripe_customer_id' => [
                'type'          => 'VARCHAR',
                'constraint'    => 50,
                'null'          => false,
            ],
            'stripe_plan_id' => [
                'type'          => 'VARCHAR',
                'constraint'    => 50,
                'null'          => false,
            ],
            'sub_item_id' => [
                'type'          => 'VARCHAR',
                'constraint'    => 50,
                'null'          => false,
            ],
            'invoice_number' => [
                'type'          => 'VARCHAR',
                'constraint'    => 50,
                'null'          => false,
            ],
            'plan_amount' => [
                'type'          => 'FLOAT',
                'constraint'    => '10,2',
                'unsigned'      => true, 
            ],
            'plan_amount_currency' => [
                'type'          => 'VARCHAR',
                'constraint'    => 10,
                'null'          => false,
            ],
            'plan_interval_count' => [
                'type'          => 'TINYINT',
                'constraint'    => 2,
                'null'          => false,
            ],
            'plan_interval' => [
                'type'          => 'VARCHAR',
                'constraint'    => 20,
                'null'          => false,
            ],
            'plan_period_start' => [
                'type'          => 'DATETIME',
                'null'          => false,
            ],
            'plan_period_end' => [
                'type'          => 'DATETIME',
                'null'          => false,
            ],
            'amount_paid' => [
                'type'          => 'FLOAT',
                'constraint'    => '10,2',
                'unsigned'      => true, 
                'null'          => true,
            ],
            'amount_paid_currency' => [
                'type'          => 'VARCHAR',
                'constraint'    => 10,
                'null'          => true,
            ],
            'payer_email' => [
                'type'          => 'VARCHAR',
                'constraint'    => 100,
                'null'          => false,
            ],
            'event_id' => [
                'type'          => 'VARCHAR',
                'constraint'    => 50,
                'null'          => true,
            ],
            'coupon_used' => [
                'type'          => 'VARCHAR',
                'constraint'    => 50,
                'null'          => true,
            ],
            'status' => [
                'type'          => 'VARCHAR',
                'constraint'    => 20,
                'null'          => false,
            ],
            
            'latest_invoice' => [
                'type'          => 'VARCHAR',
                'constraint'    => 50,
                'null'          => true,
            ],
            'invoice_status' => [
                'type'          => 'VARCHAR',
                'constraint'    => 20,
                'null'          => true,
            ],
            'invoice_file' => [
                'type'          => 'VARCHAR',
                'constraint'    => 50,
                'null'          => true,
            ],
            'previous_package_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'null'           => true,
            ],
            'upgrade_downgrade_status' => [
                'type'          => 'ENUM',
                'constraint'    => ['none','upgraded','downgraded','purchased','renewed'],
                'default'       => 'none',
            ],
            'proration_amount' => [
                'type'          => 'FLOAT',
                'constraint'    => '10,2',
                'null'          => true,
            ],
            'proration_currency' => [
                'type'          => 'VARCHAR',
                'constraint'    => 10,
                'null'          => true,
            ],
            'proration_description' => [
                'type'          => 'VARCHAR',
                'constraint'    => 250,
                'null'          => true,
            ],
            'stripe_cancel_at' => [
                'type'          => 'DATETIME',
                'null'          => true,
            ],
            'stripe_canceled_at' => [
                'type'          => 'DATETIME',
                'null'          => true,
            ],
            'canceled_at' => [
                'type'          => 'DATETIME',
                'null'          => true,
            ],
            'cancellation_reason' => [
                'type'          => 'VARCHAR',
                'constraint'    => 300,
                'null'          => true,
            ],
            'subscription_created_at' => [
                'type'          => 'DATETIME',
                'null'          => false,
            ],
            'updated_at DATETIME default current_timestamp on update current_timestamp',
            'created_at DATETIME default current_timestamp',
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id');
        $this->forge->createTable('user_subscriptions');
    }

    public function down()
    {
        $this->forge->dropTable('user_subscriptions');
    }
}
