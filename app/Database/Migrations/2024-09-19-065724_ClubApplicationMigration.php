<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ClubApplicationMigration extends Migration
{
    public function up()
    {
        $fields = [
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'username' => [
                'type'          => 'VARCHAR', 
                'constraint'    => '255', 
                'null'          => false
            ],
            'email' => [
                'type'          => 'VARCHAR', 
                'constraint'    => '255', 
                'null'          => false
            ],
            'password' => [
                'type'          => 'VARCHAR', 
                'constraint'    => '255', 
                'null'          => false
            ],
            'first_name' => [
                'type'          => 'VARCHAR', 
                'constraint'    => '50', 
                'null'          => false
            ],
            'last_name' => [
                'type'          => 'VARCHAR', 
                'constraint'    => '50', 
                'null'          => false
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
                'null'          => true
            ],
            'user_domain' => [
                'type'          => 'INT', 
                'constraint'    => 5,
                'unsigned'       => true,
                'null'          => false
            ],
            'club_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
            ],
            'is_email_verified' => [
                'type'          => 'TINYINT', 
                'constraint'    => 1,
                'default'       => 0
            ],
            'status' => [
                'type'           => 'ENUM',
                'constraint'     => ['Pending','Verified', 'Rejected'],
                'default'        => 'Pending'
            ],
            'updated_at DATETIME default current_timestamp on update current_timestamp',
            'created_at DATETIME default current_timestamp',
        ];
        $this->forge->addField($fields);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('lang', 'languages', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('user_domain', 'domains', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('role', 'user_roles', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('club_id', 'users', 'id', '', 'CASCADE');
        // gives CONSTRAINT `TABLENAME_users_foreign` FOREIGN KEY(`users_id`) REFERENCES `users`(`id`) ON DELETE CASCADE

        $this->forge->createTable('club_applications');
    }


    public function down()
    {
        $this->forge->dropTable('club_applications');
    }
}
