<?php

namespace HasanAlyazidi\Sendables\OTP\Providers;

use Carbon\Carbon;
use HasanAlyazidi\Sendables\SMS\Providers\ISMSProvider;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use SendablesHelpers;

abstract class SystemOtpProvider extends OtpProvider
{
    const ATTEMPTS_CACHE_KEY = 'sendables-otp-attempts:';

    protected $mobile;

    public function __construct(string $mobile) {
        $this->mobile = SendablesHelpers::removeLeadingPlus($mobile);
    }

    abstract public function smsProvider(string $message, string $code) : ISMSProvider;

    public function getClientType() : string
    {
        return 'system';
    }

    public function getCodeDigitsCount() : int
    {
        return 4;
    }

    public function send() : bool
    {
        if ($this->isSendLimited()) {
            $this->setError(self::ERROR_TOO_MANY_REQUESTS);
            return false;
        }

        DB::beginTransaction();

        try {
            $code = $this->generateCode();

            $this->saveOtp($code);

            $message = __('sendables::otp.messages.code', ['code' => $code]);

            $sms = $this->smsProvider($message, $code);
            $sms->send();

            DB::commit();

            return $sms->isSent();
        } catch (\Throwable $th) {
            DB::rollback();
            return false;
        }
    }

    public function confirm(string $code) : bool
    {
        $otpModel = SendablesHelpers::getOtpModel();

        $otp = $otpModel::where('mobile', $this->mobile)
                  ->whereNull('verified_at')
                  ->latest('created_at')
                  ->first();

        if ($otp === null) {
            $this->setError(self::ERROR_CONFIRM_UNSENT);
            return false;
        }

        if ($otp->isExpired()) {
            $this->setError(self::ERROR_EXPIRED);
            return false;
        }

        $limiter     = app(RateLimiter::class);
        $attemptsKey = self::ATTEMPTS_CACHE_KEY.$otp->id;

        if ($limiter->tooManyAttempts($attemptsKey, (int) $this->limit('maxWrongAttempts'))) {
            $this->setError(self::ERROR_TOO_MANY_ATTEMPTS);
            return false;
        }

        if ($otp->isValid() && Hash::check($code, $otp->code)) {
            $otpUserId    = Crypt::encryptString($otp->id);
            $otpUserToken = Crypt::encryptString($this->generateCode());

            $this->setOtpAuthenticationParams($otpUserId, $otpUserToken);

            $otp->update([
                'verified_at'    => Carbon::now(),
                'otp_user_id'    => $otpUserId,
                'otp_user_token' => $otpUserToken,
            ]);

            $limiter->clear($attemptsKey);

            return true;
        }

        $limiter->hit($attemptsKey, $otp::EXPIRE_AFTER);

        $this->setError(self::ERROR_WRONG_CODE);

        return false;
    }

    /**
     * A verified code proves the mobile for a limited time only
     */
    public function isAuthenticated(string $otpUserId, string $otpUserToken) : bool
    {
        $otpModel = SendablesHelpers::getOtpModel();

        $verifiedAfter = Carbon::now()->subMinutes((int) $this->limit('verifiedForMinutes'));

        return $otpModel::where('mobile', $this->mobile)
                  ->where('verified_at', '>=', $verifiedAfter)
                  ->where('otp_user_id', $otpUserId)
                  ->where('otp_user_token', $otpUserToken)
                  ->exists();
    }

    public function getResendAfter() : int
    {
        $sends = $this->sendsOfLastHour();
        $count = count($sends);

        if ($count === 0) {
            return parent::getResendAfter();
        }

        $nextSendAt = $sends[0]->getTimestamp() + $this->waitAfterSend($count);

        if ($count >= (int) $this->limit('maxSendsPerHour')) {
            $windowFreeAt = $sends[$count - 1]->copy()->addHour()->getTimestamp();
            $nextSendAt   = max($nextSendAt, $windowFreeAt);
        }

        return max(0, $nextSendAt - Carbon::now()->getTimestamp());
    }

    private function isSendLimited() : bool
    {
        $sends = $this->sendsOfLastHour();
        $count = count($sends);

        if ($count === 0) {
            return false;
        }

        if ($count >= (int) $this->limit('maxSendsPerHour')) {
            return true;
        }

        return $sends[0]->getTimestamp() + $this->waitAfterSend($count) > Carbon::now()->getTimestamp();
    }

    /**
     * Sent codes of the last hour, newest first
     *
     * @return \Carbon\Carbon[]
     */
    private function sendsOfLastHour() : array
    {
        $otpModel = SendablesHelpers::getOtpModel();

        $dates = $otpModel::where('mobile', $this->mobile)
                    ->where('created_at', '>', Carbon::now()->subHour())
                    ->orderByDesc('created_at')
                    ->pluck('created_at')
                    ->all();

        return array_map(function ($date) {
            return Carbon::parse($date);
        }, $dates);
    }

    private function waitAfterSend(int $sendsCount) : int
    {
        $waits = $this->resendWaits();

        return $waits[min($sendsCount, count($waits)) - 1];
    }

    private function saveOtp($code)
    {
        $otpModel = SendablesHelpers::getOtpModel();

        $newOtp = new $otpModel;

        $newOtp->mobile = $this->mobile;
        $newOtp->code   = Hash::make($code);

        $newOtp->save();
    }
}
