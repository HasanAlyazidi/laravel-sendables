<?php

namespace HasanAlyazidi\Sendables\OTP\Providers;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use SendablesHelpers;
use Throwable;

class PasswordProvider extends OtpProvider
{
    protected $mobile;

    public function __construct(string $mobile) {
        $this->mobile = SendablesHelpers::removeLeadingPlus($mobile);
    }

    public function getClientType() : string
    {
        return 'password';
    }

    public function getCodeDigitsCount() : int
    {
        return 6;
    }

    public function send() : bool
    {
        return true;
    }

    public function confirm(string $password) : bool
    {
        $userModel = SendablesHelpers::getPasswordModel();

        $users = $userModel::where('mobile', $this->mobile)->get();

        $user = $users->first();

        if ($user === null || $users->count() > 1) {
            $this->setError(self::ERROR_CONFIRM_UNSENT);
            return false;
        }

        $isCorrectPassword = Hash::check($password, $user->password);

        if ($isCorrectPassword) {
            $userToken = $user->generateAccessToken();

            $encryptedOtpUserId    = Crypt::encryptString($user->id);
            $encryptedOtpUserToken = Crypt::encryptString($userToken);

            $this->setOtpAuthenticationParams($encryptedOtpUserId, $encryptedOtpUserToken);

            return true;
        }

        $this->setError(self::ERROR_WRONG_CODE);

        return false;
    }

    public function isAuthenticated(string $otpUserId, string $otpUserToken) : bool
    {
        try {
            $decryptedOtpUserId    = Crypt::decryptString($otpUserId);
            $decryptedOtpUserToken = Crypt::decryptString($otpUserToken);

            $userModel = SendablesHelpers::getPasswordModel();

            $user = $userModel::where('id', $decryptedOtpUserId)
                ->where('mobile', $this->mobile)
                ->first();

            if ($user === null) {
                return false;
            }

            $accessTokenId = $this->extractAccessTokenIdFromBearerToken($decryptedOtpUserToken);

            $isValidToken = $user->tokens()->firstWhere('id', $accessTokenId) !== null;

            return $isValidToken;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function extractAccessTokenIdFromBearerToken(string $bearerToken): string
    {
        $authHeader = explode(' ', $bearerToken);
        $authHeaderTokenPart = $authHeader[0];

        $tokenParts = explode('.', $authHeaderTokenPart);
        $tokenHeader = $tokenParts[1];

        $tokenHeaderJson = json_decode(base64_decode($tokenHeader), true);
        $accessTokenId = $tokenHeaderJson['jti'];

        return $accessTokenId;
    }
}
