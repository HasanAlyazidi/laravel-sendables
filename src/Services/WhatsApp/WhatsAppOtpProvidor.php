<?php

namespace HasanAlyazidi\Sendables\Services\WhatsApp;

use HasanAlyazidi\Sendables\OTP\Providers\SystemOtpProvider;
use HasanAlyazidi\Sendables\SMS\Providers\ISMSProvider;

class WhatsAppOtpProvidor extends SystemOtpProvider
{
    public function smsProvider(string $message, string $code): ISMSProvider
    {
        return new WhatsAppOtpTemplateProvider($message, $code, $this->mobile);
    }

    public function getClientType(): string
    {
        return 'whatsapp';
    }

    public function getCodeDigitsCount(): int
    {
        return 6;
    }
}
