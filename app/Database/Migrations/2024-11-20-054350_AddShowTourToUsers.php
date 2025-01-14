<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddShowTourToUsers extends Migration
{
    public function up()
    {
        // Modify the users table
        $this->forge->addColumn('users', [
            'show_tour' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'null'       => false,
            ],
        ]);
    }

    public function down()
    {
        // Rollback: Remove the column
        $this->forge->dropColumn('users', 'show_tour');
    }
}
