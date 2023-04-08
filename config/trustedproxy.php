<?php

use Symfony\Component\HttpFoundation\Request;

return [
    'proxies' => [
        // Local Proxy
        '127.0.0.1',
        '80.78.25.105',
    ],

    'headers' => Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO,
];
