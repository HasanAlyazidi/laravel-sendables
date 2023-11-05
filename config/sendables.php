<?php

use HasanAlyazidi\Sendables\Models\Otp;
use HasanAlyazidi\Sendables\OTP\Providers\TesterOtpProvider;
use HasanAlyazidi\Sendables\OTP\Providers\FirebaseOtpProvider;
use HasanAlyazidi\Sendables\SMS\Providers\OurSMSV2Provider;

return [

    /**
     * SMS configuration
     */
    'sms' => [
        'options' => [
            'enabled' => true,
        ],

        'providers' => [
            /**
             * Default SMS Provider
             */
            'default' => OurSMSV2Provider::class,
        ],
    ],

    /**
     * OTP configuration
     */
    'otp' => [
        'db' => [
            'model' => Otp::class,
            'table' => 'otps',
        ],

        'providers' => [
            /**
             * Default OTP Provider
             */
            'default' => FirebaseOtpProvider::class,

            /**
             * Tester
             */
            'tester' => [
                'provider' => TesterOtpProvider::class,
            ],
        ],

        /**
         * Custom providers
         *
         * Examples:
         * '20' => FirebaseOtpProvider::class
         * '966' => OurSMSOtpProvider::class
         * '966540000000' => FirebaseOtpProvider::class
         */
        'customProviders' => [],

        /**
         * Mobile numbers of testers
         *
         * Examples:
         * '966540000000' => '666666'
         *
         * 'mobile number' => 'otp code',
         */
        'testers' => [],
    ],

    /**
     * Services configuration
     */
    'services' => [
        /**
         * Our SMS
         *
         * URL: https://oursms.com
         */
        'oursms' => [
            'username' => '',
            'password' => '',
            'sender'   => '',
        ],

        /**
         * Our SMS Version 2
         *
         * URL: https://oursms.com
         */
        'oursms-v2' => [
            'username' => '',
            'password' => '',
            'sender'   => '',
            'apiKey'   => '',
        ],

        /**
         * Google Firebase Phone Authentication
         */
        'firebase' => [
            'apiKey' => '',
        ],
    ]
];
