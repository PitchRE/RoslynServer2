<?php

declare(strict_types=1);

use App\Services\SiaIpDc09\Enums\SiaToken;

return [

    /*
    |--------------------------------------------------------------------------
    | Supported SIA DC-09 Protocol ID Tokens & Handlers
    |--------------------------------------------------------------------------
    |
    | Define which SIA protocol tokens your application will actively process.
    | For each supported token (the base token string, e.g., "ADM-CID"),
    | you can specify:
    |   - 'name': A friendly name for display or logging.
    |   - 'handler_class': The fully qualified class name of the action or service
    |     responsible for interpreting the 'message_data' for this token type.
    |     This handler should typically implement an `AlarmDataInterpreter` contract.
    |
    | Tokens not listed here might be rejected with a DUH response if
    | `reject_unsupported_known_tokens` is true, or if they are entirely unknown
    | (not defined in the SiaToken Enum).
    |
    */
    'supported_tokens' => [
        SiaToken::ADM_CID->value => [
            'name' => 'Ademco Contact ID',
            'handler_class' => \App\Services\AlarmDataFormats\AdemcoContactId\Actions\InterpretAdemcoContactIdData::class,
        ],
        SiaToken::NULL->value => [
            'name' => 'Null (Link Test)',
            'handler_class' => \App\Services\NullHandler\Actions\NullHandler::class,
        ],
        // Add other tokens from SiaToken enum as you support them
    ],

    /*
    |--------------------------------------------------------------------------
    | Behavior for Unsupported Tokens
    |--------------------------------------------------------------------------
    |
    | - reject_unsupported_configured_tokens: If true, tokens listed in
    |   `supported_tokens` but with a null/missing `handler_class` will
    |   result in a DUH. If false, they might be ACKed but not further processed.
    |   It's generally safer to set this to true to avoid acknowledging data
    |   you can't fully understand.
    |
    | - reject_unknown_tokens: If true, any token string received that is not
    |   even defined in your `SiaToken` enum will result in a DUH.
    |   This is highly recommended.
    |
    */
    'behavior' => [
        'reject_unsupported_configured_tokens' => true,
        'reject_unknown_tokens' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Encryption Settings
    |--------------------------------------------------------------------------
    |
    | Settings for AES encryption/decryption as per SIA DC-09.
    |
    */
    'encryption' => [
        // Default cipher. SIA DC-09 specifies AES.
        // Common OpenSSL formats: 'aes-128-cbc', 'aes-192-cbc', 'aes-256-cbc'.
        // CSRs must support 128, 192, and 256-bit keys.
        'default_cipher' => env('SIA_DEFAULT_CIPHER', 'aes-128-cbc'),

        // Default encryption key.
        // IMPORTANT: This key should be a raw binary string or hex-encoded binary.
        // For security, store this in your .env file and DO NOT commit to version control.
        // Example for .env: SIA_DEFAULT_KEY="your_hex_encoded_16_byte_key_for_aes128"
        // The application should convert this hex key to binary before use if stored as hex.
        'default_key_hex' => env('SIA_DEFAULT_KEY_HEX', bin2hex('1234567890123456')), // Store as hex in .env

        // Future: You might implement a key_provider_class for dynamic key lookups
        // 'key_provider_class' => null, // e.g., App\Services\SiaIpDc09\KeyManagement\DatabaseKeyProvider::class
        // 'keys_per_account' => [
        //    'PANEL_ACCOUNT_123' => env('SIA_KEY_ACCOUNT_123_HEX'),
        // ],

        // Padding block size for AES (typically 16 bytes).
        'block_size' => 16,
    ],

    /*
    |--------------------------------------------------------------------------
    | Timestamp Validation Tolerance
    |--------------------------------------------------------------------------
    |
    | Define the acceptable window (in seconds) for timestamps on
    | ENCRYPTED messages, relative to the receiver's GMT/UTC.
    | SIA DC-09 (Sec 5.5.1.9) suggests +20/-40 seconds.
    |
    */
    'timestamp_tolerance' => [
        'future_seconds' => env('SIA_TIMESTAMP_TOLERANCE_FUTURE', 20),  // Max seconds message can be ahead
        'past_seconds' => env('SIA_TIMESTAMP_TOLERANCE_PAST', 40),    // Max seconds message can be behind
    ],

    /*
    |--------------------------------------------------------------------------
    | Supervision (Link Test) Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for CSR (Central Station Receiver) behavior regarding
    | supervision messages (NULL messages) from PEs.
    |
    */
    'supervision' => [
        // If true, the CSR will expect periodic NULL messages for accounts configured for supervision.
        'enabled_by_default_for_receiver' => env('SIA_SUPERVISION_ENABLED', false),

        // Default interval (in seconds) the CSR expects a message (NULL or event) from a supervised account.
        // SIA DC-09 (Sec 5.5.2) suggests a configurable range.
        // This is the receiver's expectation. PE has its own sending interval.
        'max_interval_seconds' => env('SIA_SUPERVISION_MAX_INTERVAL', 90), // Example: 90 seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Network Listener Settings (Example - if you build a listener)
    |--------------------------------------------------------------------------
    |
    | These would be used if you implement a TCP/UDP listener directly in PHP.
    |
    */
    // 'listener' => [
    //     'udp_port' => env('SIA_UDP_PORT', 50001), // Common port, but can be anything
    //     'tcp_port' => env('SIA_TCP_PORT', 50002),
    //     'ip_bind' => env('SIA_IP_BIND', '0.0.0.0'), // Bind to all interfaces
    //     'max_packet_size_bytes' => 2048, // Max UDP packet size to accept
    //     'tcp_connection_timeout_seconds' => 30,
    // ],

    /*
    |--------------------------------------------------------------------------
    | Retry Logic for PE (Premises Equipment) - Informational for CSR Design
    |--------------------------------------------------------------------------
    |
    | SIA DC-09 (Sec 5.6.1.1) specifies PE retry behavior.
    | This section is informational for how your CSR should expect PEs to behave.
    | Your CSR doesn't *implement* PE retries, but its timeout for ACK should consider this.
    |
    */
    'pe_retry_behavior' => [
        'recommended_timeout_seconds' => 20,
        'recommended_attempts' => 3,
    ],

];
