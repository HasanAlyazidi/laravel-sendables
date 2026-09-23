<?php

use HasanAlyazidi\Sendables\OTP\Providers\OtpProvider;

return [

    'messages' => [
        'code' => 'Your code is :code',
    ],

    'errors' => [
        OtpProvider::ERROR_WRONG_CODE     => 'Incorrect code',
        OtpProvider::ERROR_CONFIRM_UNSENT => 'Could not verify code, try again later',
        OtpProvider::ERROR_EXPIRED        => 'Code is expired',
        OtpProvider::ERROR_UNKNOWN        => 'An error occurred, try again later',

        OtpProvider::ERROR_TOO_MANY_ATTEMPTS => 'Too many wrong tries, request a new code',
        OtpProvider::ERROR_TOO_MANY_REQUESTS => 'Too many codes requested, try again shortly',
    ],

];
