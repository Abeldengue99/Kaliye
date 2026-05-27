<?php
// config/payments.php
// Configuration for Angolan Payment Gateways (Multicaixa/Referência)

return [
    'gateway' => 'simulated', // Options: 'simulated', 'paypay', 'upay', 'proxypay'
    
    'entity' => '00123', // Your official MCX Entity
    
    'keys' => [
        'test' => [
            'api_key' => 'TEST_KEY_XXXXX',
            'token' => 'TEST_TOKEN_XXXXX'
        ],
        'live' => [
            'api_key' => 'LIVE_KEY_XXXXX',
            'token' => 'LIVE_TOKEN_XXXXX'
        ]
    ],
    
    'environment' => 'test', // Change to 'live' in production
    // Feature flag: disable real monetary flows for v1. Set to true in v2 to enable payments.
    'payments_enabled' => false,
];
