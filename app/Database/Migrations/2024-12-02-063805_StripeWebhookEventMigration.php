<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class StripeWebhookEventMigration extends Migration
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
            'event_id' => [
                'type'          => 'VARCHAR',
                'constraint'    => 255,
                'unique'        => TRUE,
                'null'          => false,
            ],
            'event_type' => [
                'type'          => 'VARCHAR',
                'constraint'    => 255,
                'null'          => false,
            ],
            'payload' => [
                'type'          => 'JSON',
                'null'          => false,
            ],
            'processed_at' => [
                'type'          => 'TIMESTAMP',
                'null'          => TRUE,
                'default'       => null,
            ],
            'updated_at DATETIME default current_timestamp on update current_timestamp',
            'created_at DATETIME default current_timestamp',
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('stripe_webhook_events');
    }

    public function down()
    {
        $this->forge->dropTable('stripe_webhook_events');
    }
}
