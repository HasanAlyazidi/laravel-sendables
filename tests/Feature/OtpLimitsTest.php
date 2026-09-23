<?php

namespace HasanAlyazidi\Sendables\Tests\Feature;

use HasanAlyazidi\Sendables\OTP\Providers\OtpProvider;
use HasanAlyazidi\Sendables\OTP\Providers\SystemOtpProvider;
use HasanAlyazidi\Sendables\SMS\Providers\ISMSProvider;
use HasanAlyazidi\Sendables\Tests\TestCase;
use SendablesHelpers;

class OtpLimitsTest extends TestCase
{
    const MOBILE = '966500000001';

    public function testTheSecondCodeHasToWait()
    {
        $this->assertTrue($this->provider()->send());

        $second = $this->provider();

        $this->assertFalse($second->send());
        $this->assertSame(OtpProvider::ERROR_TOO_MANY_REQUESTS, $second->getError());
        $this->assertSame(60, $second->getResendAfter());
    }

    public function testWrongGuessesAreCapped()
    {
        $sender = $this->provider();
        $sender->send();

        $code  = $sender::$sentCode;
        $wrong = $code === '0000' ? '1111' : '0000';

        for ($try = 1; $try <= 5; $try++) {
            $guess = $this->provider();

            $this->assertFalse($guess->confirm($wrong));
            $this->assertSame(OtpProvider::ERROR_WRONG_CODE, $guess->getError());
        }

        $capped = $this->provider();

        $this->assertFalse($capped->confirm($wrong));
        $this->assertSame(OtpProvider::ERROR_TOO_MANY_ATTEMPTS, $capped->getError());

        $rightCode = $this->provider();

        $this->assertFalse($rightCode->confirm($code));
        $this->assertSame(OtpProvider::ERROR_TOO_MANY_ATTEMPTS, $rightCode->getError());
    }

    public function testAVerifiedCodeStopsProvingTheMobileAfterItsLifetime()
    {
        $sender = $this->provider();
        $sender->send();

        $confirmer = $this->provider();

        $this->assertTrue($confirmer->confirm($sender::$sentCode));

        $otpUserId    = $confirmer->getOtpUserId();
        $otpUserToken = $confirmer->getOtpUserToken();

        $this->assertTrue($this->provider()->isAuthenticated($otpUserId, $otpUserToken));

        $otpModel = SendablesHelpers::getOtpModel();

        $otpModel::where('mobile', self::MOBILE)->update([
            'verified_at' => now()->subMinutes(61),
        ]);

        $this->assertFalse($this->provider()->isAuthenticated($otpUserId, $otpUserToken));
    }

    public function testLimitsFallBackToTheDefaultsWhenTheConfigHasNone()
    {
        config(['sendables.otp.limits' => null]);

        $this->assertSame(
            OtpProvider::DEFAULT_LIMITS['resendAfterSeconds'][0],
            $this->provider()->getResendAfter()
        );
    }

    private function provider(string $mobile = self::MOBILE) : SystemOtpProvider
    {
        return new class($mobile) extends SystemOtpProvider {
            public static $sentCode;

            public function smsProvider(string $message, string $code) : ISMSProvider
            {
                self::$sentCode = $code;

                return new class implements ISMSProvider {
                    public function send() : void {}

                    public function isSent() : bool
                    {
                        return true;
                    }
                };
            }
        };
    }
}
