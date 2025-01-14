<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UserMetaMigration extends Migration
{
    public function up()
    {
        $fields = [
            'first_name' => [
                'type'          => 'VARCHAR', 
                'constraint'    => '50', 
                'null'          => true
            ],
            'last_name' => [
                'type'          => 'VARCHAR', 
                'constraint'    => '50', 
                'null'          => true
            ],
            'role' => [
                'type'          => 'INT', 
                'constraint'    => '5', 
                'unsigned'       => true,
                'null'          => false
            ],  
            'lang' => [
                'type'          => 'INT', 
                'constraint'    => '5', 
                'unsigned'       => true,
                'null'          => false
            ],
            'newsletter' => [
                'type'          => 'INT', 
                'constraint'    => 5,
                'null'          => false
            ],
            'user_domain' => [
                'type'          => 'INT', 
                'constraint'    => 5,
                'unsigned'       => true,
                'null'          => false
            ],
            'parent_id' => [
                'type'          => 'INT', 
                'constraint'    => 11,
                'unsigned'      => true,
                'null'          => true
            ],
            'stripe_customer_id' => [
                'type'          => 'VARCHAR', 
                'constraint'    => '50', 
                'null'          => true
            ],
            'is_email_verified' => [
                'type'          => 'TINYINT', 
                'constraint'    => 1,
                'default'       => 0
            ],
            'added_by' => [
                'type'          => 'INT', 
                'constraint'    => 11,
                'unsigned'      => true,
                'null'          => true
            ],
        ];

        $forge->addForeignKey('lang', 'languages', 'id', '', 'CASCADE');
        $forge->addForeignKey('user_domain', 'domains', 'id', '', 'CASCADE');
        $forge->addForeignKey('role', 'user_roles', 'id', '', 'CASCADE');
        // gives CONSTRAINT `TABLENAME_users_foreign` FOREIGN KEY(`users_id`) REFERENCES `users`(`id`) ON DELETE CASCADE

        $this->forge->addColumn('users', $fields);
    }

    public function down()
    {
        $fields = [
            'first_name',
            'last_name',
            'role',
            'lang',
            'newsletter',
            'user_domain',
        ];
        $this->forge->dropColumn('users', $fields);
    }
}
