<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPageTypeToPages extends Migration
{
    public function up()
    {
        $fields = [
            'page_type' => [
                'type'       => 'ENUM',
                'constraint' => [
                    'home',
                    'talent',
                    'clubs_and_scouts',
                    'about_us',
                    'pricing',
                    'news',
                    'contact',
                    'imprint',
                    'privacy_policy',
                    'terms_and_conditions',
                    'cookie_policy',
                    'faq',
                    'content_page'
                ],
                'default'    => 'content_page',
                'null'       => false,
            ],
        ];

        $this->forge->addColumn('pages', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('pages', 'page_type');
    }
}
