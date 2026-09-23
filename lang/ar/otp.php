<?php

use HasanAlyazidi\Sendables\OTP\Providers\OtpProvider;

return [

    'messages' => [
        'code' => 'رمز التحقق هو :code',
    ],

    'errors' => [
        OtpProvider::ERROR_WRONG_CODE     => 'الرمز غير صحيح',
        OtpProvider::ERROR_CONFIRM_UNSENT => 'لا يمكن التحقق من الرمز , حاول مجدداً',
        OtpProvider::ERROR_EXPIRED        => 'انتهى الرمز',
        OtpProvider::ERROR_UNKNOWN        => 'حدث خطأ , حاول مجدداً لاحقاً',

        OtpProvider::ERROR_TOO_MANY_ATTEMPTS => 'محاولات خاطئة كثيرة، اطلب رمزاً جديداً',
        OtpProvider::ERROR_TOO_MANY_REQUESTS => 'طلبت رموزاً كثيرة، حاول مجدداً بعد قليل',
    ],

];
