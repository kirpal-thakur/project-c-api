<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SystemPopUpUserRoleMigration extends Migration
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
            'popup_id' => [
                'type'          => 'INT',
                'constraint'    => 11,
                'unsigned'      => true, 
            ],
            'user_role' => [
                'type'          => 'INT',
                'constraint'    => 11,
                'unsigned'      => true, 
            ],
            'updated_at DATETIME default current_timestamp on update current_timestamp',
            'created_at DATETIME default current_timestamp',
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('popup_id', 'system_popups', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('user_role', 'role_payment_types', 'id', '', 'CASCADE');
        $this->forge->createTable('system_popup_user_roles');
    }

    public function down()
    {
        $this->forge->dropTable('system_popup_user_roles');
    }
}
