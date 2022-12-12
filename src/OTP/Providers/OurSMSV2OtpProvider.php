<?php

namespace HasanAlyazidi\Sendables\OTP\Providers;

use HasanAlyazidi\Sendables\SMS\Providers\ISMSProvider;
use HasanAlyazidi\Sendables\SMS\Providers\OurSMSV2Provider;

class OurSMSV2OtpProvider extends SystemOtpProvider
{
    public function smsProvider(string $message) : ISMSProvider
    {
        return new OurSMSV2Provider($message, $this->mobile);
    }
}
