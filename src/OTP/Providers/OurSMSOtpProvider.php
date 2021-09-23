<?php

namespace HasanAlyazidi\Sendables\OTP\Providers;

use HasanAlyazidi\Sendables\SMS\Providers\ISMSProvider;
use HasanAlyazidi\Sendables\SMS\Providers\OurSMSProvider;

class OurSMSOtpProvider extends SystemOtpProvider
{
    public function smsProvider(string $message) : ISMSProvider
    {
        return new OurSMSProvider($message, $this->mobile);
    }
}
