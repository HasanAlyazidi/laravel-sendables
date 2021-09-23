<?php

namespace HasanAlyazidi\Sendables\OTP\Providers;

abstract class OtpProvider {
    const ERROR_WRONG_CODE     = 'wrong-code';
    const ERROR_CONFIRM_UNSENT = 'unsent';
    const ERROR_EXPIRED        = 'expired';
    const ERROR_UNKNOWN        = 'unknown';

    protected $mobile;
    protected $error;

    protected $otpUserId;
    protected $otpUserToken;

    abstract public function getClientType() : string;
    abstract public function getCodeDigitsCount() : int;
    abstract public function send() : bool;
    abstract public function confirm(string $code) : bool;
    abstract public function isAuthenticated(string $otpUserId, string $otpUserToken) : bool;

    public function getError() : string
    {
        return $this->error ?? OtpProvider::ERROR_UNKNOWN;
    }

    public function getOtpUserId() : string
    {
        return $this->otpUserId;
    }

    public function getOtpUserToken() : string
    {
        return $this->otpUserToken;
    }

    protected function setError(string $error) : void
    {
        $this->error = $error;
    }

    protected function setOtpAuthenticationParams(string $otpUserId, string $otpUserToken) : void
    {
        $this->otpUserId    = $otpUserId;
        $this->otpUserToken = $otpUserToken;
    }

    protected function generateCode() : string
    {
        $length = $this->getCodeDigitsCount();

        $str = rand(0, pow(10, $length) - 1);
        return str_pad($str, $length, '0', STR_PAD_LEFT);
    }
}
