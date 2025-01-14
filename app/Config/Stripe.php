<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Stripe extends BaseConfig
{
    public $secretKey = 'sk_test_jR7Bck6DdtXS7VsP3ZMJmPTl00731tpBFH'; // Replace with your Stripe secret key
    public $publishableKey = 'pk_test_S8FtoTkzjDTAP9qgfTOMXG2e00eSsjNTLD'; // Replace with your Stripe publishable key
}
