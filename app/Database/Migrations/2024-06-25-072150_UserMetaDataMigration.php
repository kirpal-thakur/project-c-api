<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UserMetaDataMigration extends Migration
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
                'unsigned'      => true, // Change this to FALSE
            ],
            'meta_key' => [
                'type'       => 'VARCHAR',
                'constraint' => '200',
            ],
            'meta_value' => [
                'type' => 'mediumtext',
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE', 'fk_user_meta_users_id');
        $this->forge->createTable('user_meta');
    }

    public function down()
    {
        $this->forge->dropTable('user_meta');
    }
}
