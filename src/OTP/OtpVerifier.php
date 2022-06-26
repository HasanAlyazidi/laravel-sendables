<?php

namespace HasanAlyazidi\Sendables\OTP;

use HasanAlyazidi\Sendables\OTP\Providers\OtpProvider;
use Illuminate\Support\Str;
use SendablesHelpers;

class OtpVerifier
{
    private $mobile;

    private $selectedProvider;
    private $defaultProvider;

    private $isSent      = false;
    private $isConfirmed = false;

    public function __construct(string $mobile) {
        $this->mobile = SendablesHelpers::removeLeadingPlus($mobile);

        $this->defaultProvider = config('sendables.otp.providers.default');

        $this->setProvider();
    }

    public function getProvider() : OtpProvider
    {
        return $this->selectedProvider;
    }

    public function isAuthenticated(string $otpUserId, string $otpUserToken) : bool
    {
        return $this->getProvider()->isAuthenticated($otpUserId, $otpUserToken);
    }

    public function send() : void
    {
        try {
            $this->isSent = $this->getProvider()->send();
        } catch (\Throwable $th) {
            $this->isSent = false;
        }
    }

    public function confirm(string $code) : void
    {
        try {
            $this->isConfirmed = $this->getProvider()->confirm($code);
        } catch (\Throwable $th) {
            $this->isConfirmed = false;
        }
    }

    public function isSent() : bool
    {
        return $this->isSent;
    }

    public function isConfirmed() : bool
    {
        return $this->isConfirmed;
    }

    public function getOtpUserId() : string
    {
        return $this->getProvider()->getOtpUserId();
    }

    public function getOtpUserToken() : string
    {
        return $this->getProvider()->getOtpUserToken();
    }

    public function getError() : string
    {
        $errorCode = $this->getProvider()->getError();
        return __("sendables::otp.errors.$errorCode");
    }

    private function setProvider() : void
    {
        $testers = config('sendables.otp.testers');

        if (array_key_exists($this->mobile, $testers)) {
            $this->setTesterProvider($testers);
            return;
        }

        $customProviders = config('sendables.otp.customProviders');

        foreach ($customProviders as $code => $customProvider) {
            if (Str::startsWith($this->mobile, $code)) {
                $this->selectedProvider = new $customProvider($this->mobile);
                return;
            }
        }

        $this->selectedProvider = new $this->defaultProvider($this->mobile);
    }

    private function setTesterProvider(array $testers) : void
    {
        $testerProvider = config('sendables.otp.providers.tester.provider');
        $testerCode = $testers[$this->mobile];

        $this->selectedProvider = new $testerProvider($this->mobile, $testerCode);
    }
}
