<?php

namespace HasanAlyazidi\Sendables\OTP\Providers;

use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Facades\Crypt;
use SendablesHelpers;
use Throwable;

class TesterOtpProvider extends OtpProvider
{
    const OTP_USER_ID    = '53UAXKKQJGCYE6X';
    const OTP_USER_TOKEN = 'A0GSI5SIUO2HZOY';

    protected $mobile;
    protected $code;

    public function __construct(string $mobile, string $code) {
        $this->mobile = SendablesHelpers::addLeadingPlus($mobile);
        $this->code = $code;
    }

    public function getClientType() : string
    {
        return 'system';
    }

    public function getCodeDigitsCount() : int
    {
        return 6;
    }

    public function send() : bool
    {
        return true;
    }

    public function canResend() : bool
    {
        return false;
    }

    /**
     * The code never changes, so wrong guesses are counted per number for a
     * code's lifetime instead of per code.
     */
    public function confirm(string $code) : bool
    {
        $limiter     = app(RateLimiter::class);
        $attemptsKey = self::ATTEMPTS_CACHE_KEY.'tester:'.$this->mobile;

        if ($limiter->tooManyAttempts($attemptsKey, (int) $this->limit('maxWrongAttempts'))) {
            $this->setError(self::ERROR_TOO_MANY_ATTEMPTS);
            return false;
        }

        $isCorrectCode = $code === $this->code;

        if ($isCorrectCode) {
            $encryptedOtpUserId    = Crypt::encryptString(self::OTP_USER_ID);
            $encryptedOtpUserToken = Crypt::encryptString(self::OTP_USER_TOKEN);

            $this->setOtpAuthenticationParams($encryptedOtpUserId, $encryptedOtpUserToken);

            $limiter->clear($attemptsKey);

            return true;
        }

        $otpModel = SendablesHelpers::getOtpModel();

        $limiter->hit($attemptsKey, $otpModel::EXPIRE_AFTER);

        $this->setError(self::ERROR_WRONG_CODE);

        return false;
    }

    public function isAuthenticated(string $otpUserId, string $otpUserToken) : bool
    {
        try {
            $decryptedOtpUserId    = Crypt::decryptString($otpUserId);
            $decryptedOtpUserToken = Crypt::decryptString($otpUserToken);

            return $decryptedOtpUserId === self::OTP_USER_ID
                && $decryptedOtpUserToken === self::OTP_USER_TOKEN;
        } catch (Throwable $e) {
            return false;
        }
    }
}
