<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CouponDiscountAmountMigration extends Migration
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
            'coupon_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
            ],
            'currency' => [
                'type'          => 'VARCHAR',
                'constraint'    => 256,
                'null'          => false,
            ],
            'discount_amount' => [
                'type'          => 'INT',
                'constraint'    => 11,
                'unsigned'      => true, 
            ],
            'status' => [
                'type'          => 'ENUM',
                'constraint'    => ['draft','published','expired'],
                'default'       => 'published',
            ],
            'updated_at DATETIME default current_timestamp on update current_timestamp',
            'created_at DATETIME default current_timestamp',
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('coupon_id', 'coupons', 'id', '', 'CASCADE');
        $this->forge->createTable('coupon_discount_amounts');
    }

    public function down()
    {
        $this->forge->dropTable('coupon_discount_amounts');
    }
}
