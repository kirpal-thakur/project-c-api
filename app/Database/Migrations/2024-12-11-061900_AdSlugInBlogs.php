<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AdSlugInBlogs extends Migration
{
    public function up()
    {
        $this->forge->addColumn('blogs', [
            'slug' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'default'    => '1',
                'null'       => false,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('users', 'slug');
    }
}
