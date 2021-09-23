<?php

namespace HasanAlyazidi\Sendables\OTP\Providers;

use Carbon\Carbon;
use HasanAlyazidi\Sendables\SMS\Providers\ISMSProvider;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use SendablesHelpers;

abstract class SystemOtpProvider extends OtpProvider
{
    protected $mobile;

    public function __construct(string $mobile) {
        $this->mobile = SendablesHelpers::removeLeadingPlus($mobile);
    }

    abstract public function smsProvider(string $message) : ISMSProvider;

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
        DB::beginTransaction();

        try {
            $code = $this->generateCode();

            $this->saveOtp($code);

            $message = __('sendables::otp.messages.code', ['code' => $code]);

            $sms = $this->smsProvider($message);
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

        if ($otp->isValid() && Hash::check($code, $otp->code)) {
            $otpUserId    = Crypt::encryptString($otp->id);
            $otpUserToken = Crypt::encryptString($this->generateCode());

            $this->setOtpAuthenticationParams($otpUserId, $otpUserToken);

            $otp->update([
                'verified_at'    => Carbon::now(),
                'otp_user_id'    => $otpUserId,
                'otp_user_token' => $otpUserToken,
            ]);

            return true;
        }

        $this->setError(self::ERROR_WRONG_CODE);

        return false;
    }

    public function isAuthenticated(string $otpUserId, string $otpUserToken) : bool
    {
        $otpModel = SendablesHelpers::getOtpModel();

        return $otpModel::where('mobile', $this->mobile)
                  ->whereNotNull('verified_at')
                  ->where('otp_user_id', $otpUserId)
                  ->where('otp_user_token', $otpUserToken)
                  ->exists();
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
