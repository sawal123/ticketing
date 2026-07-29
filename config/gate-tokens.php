<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Dedicated gate-token encryption key
    |--------------------------------------------------------------------------
    |
    | Generate this after the compromised server has been cleaned:
    | php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
    |
    */
    'key' => env('GATE_TOKEN_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Events enabled for automatic issuance and execute-mode commands
    |--------------------------------------------------------------------------
    |
    | Supply an explicit comma-separated UID allowlist. Existing events are
    | never selected automatically.
    |
    */
    'active_event_uids' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('GATE_TOKEN_EVENT_UIDS', ''))
    ))),
];
