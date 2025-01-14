<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAttachmentsFieldToBlogs extends Migration
{
    public function up()
    {
        // Add the attachments column to the blogs table
        $this->forge->addColumn('blogs', [
            'attachments' => [
                'type' => 'TEXT',   // Or use 'VARCHAR(255)' if it's just a list of file paths
                'null' => true,     // Allow NULL values in case there are no attachments
            ],
        ]);
    }

    public function down()
    {
        // Drop the attachments column if we rollback the migration
        $this->forge->dropColumn('blogs', 'attachments');
    }
}
