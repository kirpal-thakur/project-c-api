<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdatePageTypeEnum extends Migration
{
    public function up()
    {
        // Add the new enum value 'abc_test' to the 'page_type' column
        $db = \Config\Database::connect();
        $db->query("ALTER TABLE pages CHANGE COLUMN page_type page_type ENUM('home','talent','clubs_and_scouts','about_us','pricing','news','contact','imprint','privacy_policy','terms_and_conditions','cookie_policy','faq','content_page','abc_test') DEFAULT 'content_page';");
    }

    public function down()
    {
        // Revert the changes and remove 'abc_test' from the enum
        $db = \Config\Database::connect();
        $db->query("ALTER TABLE pages CHANGE COLUMN page_type page_type ENUM('home','talent','clubs_and_scouts','about_us','pricing','news','contact','imprint','privacy_policy','terms_and_conditions','cookie_policy','faq','content_page') DEFAULT 'content_page';");
    }
}
