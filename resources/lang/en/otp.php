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
    ],

];
