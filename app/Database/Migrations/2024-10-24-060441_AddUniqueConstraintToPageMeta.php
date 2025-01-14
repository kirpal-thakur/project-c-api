<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUniqueConstraintToPageMeta extends Migration
{
    public function up()
    {
        $this->forge->addUniqueKey('page_meta', 'unique_meta_key', ['meta_key', 'lang_code', 'page_id']);
    }

    public function down()
    {
        $this->forge->dropUniqueKey('page_meta', 'unique_meta_key');
    }
}
