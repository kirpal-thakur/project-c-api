<?php

namespace App\Models;

use CodeIgniter\Model;

class UserSubscriptionModel extends Model
{
    protected $table            = 'user_subscriptions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'package_id',
        'payment_method',
        'payment_method_id',
        'stripe_subscription_id',
        'stripe_customer_id',
        'stripe_plan_id',
        'sub_item_id',
        'invoice_number',
        'plan_amount',
        'plan_amount_currency',
        'plan_interval_count',
        'plan_interval',
        'plan_period_start',
        'plan_period_end',
        'amount_paid',
        'amount_paid_currency',
        'payer_email',
        'event_id',
        'coupon_used',
        'status',
        'latest_invoice',
        'invoice_status',
        'invoice_file',
        'previous_package_id',
        'upgrade_downgrade_status',
        'proration_amount',
        'proration_currency',
        'proration_description',
        'stripe_cancel_at',
        'stripe_canceled_at',
        'canceled_at',
        'cancellation_reason',
        'subscription_created_at',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];
}
