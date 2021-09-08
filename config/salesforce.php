<?php

return [
    /**
     * Default Salesforce base api
     */
    'base_api_url' => env('SALESFORCE_BASE_API_URL', 'https://force-bridge-stagining-7lcyopg5cq-ue.a.run.app/'),

    /**
     * Auth
     */
    'api_auth_email' => env('SALESFORCE_API_AUTH_EMAIL'),
    'api_auth_password' => env('SALESFORCE_API_AUTH_PASSWORD'),

    /**
     * Token time to live in seconds. Default to 1 week.
     * Check agains Salesforce documentation
     */
    'token_ttl' => env('SALESFORCE_TOKEN_TTL', 60 * 60 * 24 * 7),
];
