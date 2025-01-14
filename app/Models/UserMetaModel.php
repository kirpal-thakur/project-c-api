<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Shield\Models\UserModel as ShieldUserModel;
##use CodeIgniter\Shield\Models\UserModel;

class UserMetaModel extends ShieldUserModel
##class UserMetaModel extends UserModel
{
    protected function initialize(): void
    {
        parent::initialize();

        $this->allowedFields = [
            ...$this->allowedFields,
            'first_name',
            'last_name',
            'role',
            'lang',
            'newsletter',
            'user_domain',
            'oauth_id',
            'parent_id',
            'stripe_customer_id',
            'is_email_verified',
            'added_by',
            'show_tour',
        ];
    }
}
