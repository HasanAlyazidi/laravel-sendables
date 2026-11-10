<?php

namespace HasanAlyazidi\Sendables\Tests\Feature;

use Carbon\Carbon;
use HasanAlyazidi\Sendables\OTP\OtpVerifier;
use HasanAlyazidi\Sendables\OTP\Providers\FirebaseOtpProvider;
use HasanAlyazidi\Sendables\OTP\Providers\OtpProvider;
use HasanAlyazidi\Sendables\OTP\Providers\PasswordProvider;
use HasanAlyazidi\Sendables\OTP\Providers\TesterOtpProvider;
use HasanAlyazidi\Sendables\OTP\Providers\SystemOtpProvider;
use HasanAlyazidi\Sendables\SMS\Providers\ISMSProvider;
use HasanAlyazidi\Sendables\Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use SendablesHelpers;

class OtpLimitsTest extends TestCase
{
    const MOBILE = '966500000001';

    public function testTheSecondCodeHasToWait()
    {
        Carbon::setTestNow(Carbon::now());

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

    public function testNothingSentMeansNoWait()
    {
        $this->assertSame(0, $this->provider()->getResendAfter());
    }

    public function testAFullHourOpensWhenEnoughCodesLeaveIt()
    {
        config(['sendables.otp.limits.default.resendAfterSeconds' => [0]]);

        $start = Carbon::now();

        foreach ([0, 10, 20] as $minute) {
            Carbon::setTestNow($start->copy()->addMinutes($minute));
            $this->assertTrue($this->provider()->send());
        }

        // Three codes in the hour and the cap lowered to two: two of them must
        // leave the hour, the second one (minute 10) at minute 70.
        config(['sendables.otp.limits.default.maxSendsPerHour' => 2]);

        $this->assertSame(50 * 60, $this->provider()->getResendAfter());

        Carbon::setTestNow($start->copy()->addMinutes(60)->addSecond());
        $this->assertFalse($this->provider()->send());

        Carbon::setTestNow($start->copy()->addMinutes(70)->addSecond());
        $this->assertTrue($this->provider()->send());
    }

    public function testTestersNeverWaitToResend()
    {
        config(['sendables.otp.testers' => [self::MOBILE => '666666']]);

        $otp = new OtpVerifier(self::MOBILE);
        $otp->send();

        $this->assertTrue($otp->isSent());
        $this->assertSame(0, $otp->getResendAfter());
    }

    public function testOnlyProvidersThatCanSendAnotherCodeOfferResending()
    {
        config(['sendables.otp.testers' => [self::MOBILE => '666666']]);

        $this->assertFalse((new OtpVerifier(self::MOBILE))->canResend());
        $this->assertFalse((new PasswordProvider(self::MOBILE))->canResend());

        $this->assertTrue($this->provider()->canResend());
        $this->assertTrue((new FirebaseOtpProvider(self::MOBILE))->canResend());
    }

    public function testResendLimitsSkipProvidersThatSendNothing()
    {
        config([
            'sendables.otp.testers' => [self::MOBILE => '666666'],
            'sendables.otp.limits.default.resendAfterSeconds' => [90],
            'sendables.otp.limits.providers.'.TesterOtpProvider::class => ['resendAfterSeconds' => [30]],
        ]);

        $this->assertSame(0, (new OtpVerifier(self::MOBILE))->getResendAfter());
        $this->assertSame(0, (new FirebaseOtpProvider(self::MOBILE))->getResendAfter());
        $this->assertSame(0, (new PasswordProvider(self::MOBILE))->getResendAfter());
    }

    public function testTesterWrongGuessesAreCapped()
    {
        config(['sendables.otp.testers' => [self::MOBILE => '666666']]);
        Carbon::setTestNow(Carbon::now());

        for ($try = 1; $try <= 5; $try++) {
            $this->assertFalse($this->tester()->confirm('000000'));
        }

        $capped = $this->tester();

        $this->assertFalse($capped->confirm('666666'));
        $this->assertSame(OtpProvider::ERROR_TOO_MANY_ATTEMPTS, $capped->getError());

        $otpModel = SendablesHelpers::getOtpModel();

        Carbon::setTestNow(Carbon::now()->addSeconds($otpModel::EXPIRE_AFTER + 1));

        $this->assertTrue($this->tester()->confirm('666666'));
    }

    public function testLimitsFallBackToTheDefaultsWhenTheConfigHasNone()
    {
        config(['sendables.otp.limits' => null]);
        Carbon::setTestNow(Carbon::now());

        $this->assertTrue($this->provider()->send());

        $this->assertSame(
            OtpProvider::DEFAULT_LIMITS['resendAfterSeconds'][0],
            $this->provider()->getResendAfter()
        );
    }

    public function testASendWhileAnotherRunsIsRefused()
    {
        $running = Cache::lock(SystemOtpProvider::SEND_LOCK_KEY.self::MOBILE, 30);
        $this->assertTrue($running->get());

        $sender = $this->provider();
        $sender::$sentCode = null;

        $this->assertFalse($sender->send());
        $this->assertSame(OtpProvider::ERROR_TOO_MANY_REQUESTS, $sender->getError());
        $this->assertNull($sender::$sentCode);

        $otpModel = SendablesHelpers::getOtpModel();

        $this->assertSame(0, $otpModel::where('mobile', self::MOBILE)->count());
    }

    public function testASendReleasesItsLock()
    {
        config(['sendables.otp.limits.default.resendAfterSeconds' => [0]]);

        $this->assertTrue($this->provider()->send());
        $this->assertTrue($this->provider()->send());

        $this->assertTrue(Cache::lock(SystemOtpProvider::SEND_LOCK_KEY.self::MOBILE, 30)->get());
    }

    public function testConfirmPicksTheNewestCodeWhenTwoShareASecond()
    {
        config(['sendables.otp.limits.default.resendAfterSeconds' => [0]]);
        Carbon::setTestNow(Carbon::now()->startOfSecond());

        $this->provider()->send();

        $newest = $this->provider();
        $newest->send();

        $otpModel = SendablesHelpers::getOtpModel();
        $codes    = $otpModel::where('mobile', self::MOBILE);

        $this->assertCount(1, $codes->pluck('created_at')->unique());

        $this->assertTrue($this->provider()->confirm($newest::$sentCode));

        $verified = $otpModel::where('mobile', self::MOBILE)->whereNotNull('verified_at')->pluck('id')->all();

        $this->assertSame([$codes->max('id')], $verified);
    }

    private function tester() : OtpProvider
    {
        return (new OtpVerifier(self::MOBILE))->getProvider();
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
