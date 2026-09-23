<?php

namespace HasanAlyazidi\Sendables\OTP\Providers;

abstract class OtpProvider {
    const ERROR_WRONG_CODE        = 'wrong-code';
    const ERROR_CONFIRM_UNSENT    = 'unsent';
    const ERROR_EXPIRED           = 'expired';
    const ERROR_UNKNOWN           = 'unknown';
    const ERROR_TOO_MANY_ATTEMPTS = 'too-many-attempts';
    const ERROR_TOO_MANY_REQUESTS = 'too-many-requests';

    /**
     * Used when `sendables.otp.limits` has no value for a provider
     *
     * `resendAfterSeconds` is the wait after the 1st, 2nd, 3rd... code
     * of the last hour; the last value repeats. A single number means
     * the same wait every time.
     */
    const DEFAULT_LIMITS = [
        'resendAfterSeconds' => [60, 120, 300],
        'maxSendsPerHour'    => 10,
        'maxWrongAttempts'   => 5,
        'verifiedForMinutes' => 60,
    ];

    protected $mobile;
    protected $error;

    protected $otpUserId;
    protected $otpUserToken;

    abstract public function getClientType() : string;
    abstract public function getCodeDigitsCount() : int;
    abstract public function send() : bool;
    abstract public function confirm(string $code) : bool;
    abstract public function isAuthenticated(string $otpUserId, string $otpUserToken) : bool;

    /**
     * Seconds to wait before another code can be requested
     */
    public function getResendAfter() : int
    {
        $waits = $this->resendWaits();

        return $waits[0];
    }

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

        $str = random_int(0, pow(10, $length) - 1);
        return str_pad($str, $length, '0', STR_PAD_LEFT);
    }

    /**
     * This provider's limit, then the default one, then DEFAULT_LIMITS
     */
    protected function limit(string $name)
    {
        $providerLimits = config('sendables.otp.limits.providers.'.static::class, []);

        if (array_key_exists($name, $providerLimits)) {
            return $providerLimits[$name];
        }

        return config('sendables.otp.limits.default.'.$name, self::DEFAULT_LIMITS[$name]);
    }

    protected function resendWaits() : array
    {
        $waits = $this->limit('resendAfterSeconds');
        $waits = is_array($waits) ? array_values($waits) : [$waits];

        if (count($waits) === 0) {
            $waits = self::DEFAULT_LIMITS['resendAfterSeconds'];
        }

        return array_map('intval', $waits);
    }
}
