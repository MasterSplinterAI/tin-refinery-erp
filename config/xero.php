<?php

use Webfox\Xero\Oauth2CredentialManagers\FileStore;

return [
    /*
    |--------------------------------------------------------------------------
    | Xero Application Settings
    |--------------------------------------------------------------------------
    |
    | These settings are used for connecting to the Xero API.
    |
    */
    'client_id' => env('XERO_CLIENT_ID'),
    'client_secret' => env('XERO_CLIENT_SECRET'),
    'redirect' => env('XERO_REDIRECT_URI', '/xero/callback'),
    'scope' => env('XERO_SCOPE', 'openid profile email accounting.transactions offline_access'),
    
    /*
    |--------------------------------------------------------------------------
    | Xero Account Codes
    |--------------------------------------------------------------------------
    |
    | These account codes are used for mapping transactions to the correct
    | accounts in Xero. You should update these to match your Chart of Accounts.
    |
    */
    'accounts' => [
        // Bank accounts
        'usd_bank' => env('XERO_USD_BANK_ACCOUNT', '1000'), // USD Bank Account
        'cop_bank' => env('XERO_COP_BANK_ACCOUNT', '1001'), // COP Bank Account
        'main_bank' => env('XERO_MAIN_BANK_ACCOUNT', '1000'), // Main Bank Account for transactions
        
        // Expense accounts
        'bank_fees' => env('XERO_BANK_FEES_ACCOUNT', '6000'), // Bank Fees Account
        
        // Inventory accounts
        'inventory_asset' => env('XERO_INVENTORY_ASSET_ACCOUNT', '1200'), // Inventory Asset Account
        'cogs' => env('XERO_COGS_ACCOUNT', '5000'), // Cost of Goods Sold Account
        'revenue' => env('XERO_REVENUE_ACCOUNT', '4000'), // Revenue Account
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Xero Tax Rates
    |--------------------------------------------------------------------------
    |
    | Tax rates for different transaction types.
    |
    */
    'tax_rates' => [
        'none' => 'NONE', // No tax
        'standard' => 'STANDARD', // Standard rate
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Xero Contact Information
    |--------------------------------------------------------------------------
    |
    | Default contact information for Xero transactions.
    |
    */
    'contacts' => [
        'default_bank' => env('XERO_DEFAULT_BANK_CONTACT', 'Bank'),
        'default_supplier' => env('XERO_DEFAULT_SUPPLIER_CONTACT', 'Tin Supplier'),
    ],

    'api_host' => 'https://api.xero.com/api.xro/2.0',

    /************************************************************************
     * Class used to store credentials.
     * Must implement OauthCredentialManager Interface
     ************************************************************************/
    'credential_store' => FileStore::class,

    /************************************************************************
     * Disk used to store credentials.
     ************************************************************************/
    'credential_disk' => env('XERO_CREDENTIAL_DISK'),

    'oauth' => [
        /************************************************************************
         * Webhook signing key provided by Xero when registering webhooks
         ************************************************************************/
        'webhook_signing_key' => env('XERO_WEBHOOK_KEY', ''),

        /************************************************************************
         * Then scopes you wish to request access to on your token
         * https://developer.xero.com/documentation/oauth2/scopes
         ************************************************************************/
        'scopes' => [
            'openid',
            'email',
            'profile',
            'offline_access',
            'accounting.settings',
        ],

        /************************************************************************
         * Url to redirect to upon success
         ************************************************************************/
        'redirect_on_success' => 'xero.auth.success',

        /************************************************************************
         * Url for Xero to redirect to upon granting access
         * Unless you wish to change the default behaviour you should not need to
         * change this
         ************************************************************************/
        'redirect_uri' => 'xero.auth.callback',

        /************************************************************************
         * If the 'redirect_uri' is not a route name, but rather a full url set
         * this to true and we won't wrap it in `route()`
         ************************************************************************/
        'redirect_full_url' => false,

        /************************************************************************
         * Urls for Xero's Oauth integration, you shouldn't need to change these
         ************************************************************************/
        'url_authorize' => 'https://login.xero.com/identity/connect/authorize',
        'url_access_token' => 'https://identity.xero.com/connect/token',
        'url_resource_owner_details' => 'https://api.xero.com/api.xro/2.0/Organisation',
    ],

];
