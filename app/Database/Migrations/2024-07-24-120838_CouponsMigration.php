<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CouponsMigration extends Migration
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
                'type'          => 'INT',
                'constraint'    => 11,
                'unsigned'      => true, 
            ],
            'title' => [
                'type'          => 'VARCHAR',
                'constraint'    => 256,
                'null'          => false,
            ],
            'description' => [
                'type'          => 'TEXT',
                'null'          => true,
            ],
            'coupon_code' => [
                'type'          => 'VARCHAR',
                'constraint'    => 50,
                'null'          => false,
            ],
            'discount' => [
                'type'          => 'INT',
                'constraint'    => 11,
                'unsigned'      => true, 
                'null'          => true,
            ],
            // 'discount_type' => [
            //     'type'          => 'TINYINT',
            //     'constraint'    => 1,
            //     'unsigned'      => true, 
            // ],
            'discount_type' => [
                'type'          => 'ENUM',
                'constraint'    => ['amount_off','percent_off'],
            ],
            'duration' => [
                'type'          => 'ENUM',
                'constraint'    => ['forever','once', 'repeating'],
                'default'       => 'once',
            ],
            'duration_in_months' => [
                'type'          => 'INT',
                'constraint'    => 11,
                'unsigned'      => true, 
            ],

            'valid_from' => [
                'type'          => 'DATE',
                'null'          => true,
            ],
            'valid_to' => [
                'type'          => 'DATE',
                'null'          => true,
            ],
            'no_validity' => [
                'type'          => 'TINYINT',
                'constraint'    => 1,
                'unsigned'      => true, 
                'null'          => true,
            ],
            'is_limit' => [
                'type'          => 'TINYINT',
                'constraint'    => 1,
                'unsigned'      => true, 
                'null'          => true,
            ],
            'limit' => [
                'type'          => 'INT',
                'constraint'    => 11,
                'unsigned'      => true, 
            ],
            'limit_per_user' => [
                'type'          => 'INT',
                'constraint'    => 11,
                'unsigned'      => true, 
            ],
            // 'status' => [
            //     'type'          => 'TINYINT',
            //     'constraint'    => 1,
            //     'unsigned'      => true, 
            //     'default'       => 1,
            // ],

            'status' => [
                'type'          => 'ENUM',
                'constraint'    => ['draft','published','expired'],
                'default'       => 'draft',
            ],
            'is_deleted' => [
                'type'          => 'DATETIME',
                'null'          => true,
            ],
            'updated_at DATETIME default current_timestamp on update current_timestamp',
            'created_at DATETIME default current_timestamp',
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', '');
        $this->forge->createTable('coupons');
    }

    public function down()
    {
        $this->forge->dropTable('coupons');
    }
}
