<?php

namespace HasanAlyazidi\Sendables\OTP\Providers;

use Exception;
use GuzzleHttp;
use SendablesHelpers;
use Throwable;

class FirebaseOtpProvider extends OtpProvider
{
    protected $mobile;

    public function __construct(string $mobile) {
        $this->mobile = SendablesHelpers::addLeadingPlus($mobile);
    }

    public function getClientType() : string
    {
        return 'fcm';
    }

    public function getCodeDigitsCount() : int
    {
        return 6;
    }

    public function send() : bool
    {
        throw new Exception('FCM is sent from the client');
    }

    public function confirm(string $code) : bool
    {
        throw new Exception('FCM is confirmed from the client');
    }

    public function isAuthenticated(string $otpUserId, string $otpUserToken) : bool
    {
        $firebaseApiKey = config('sendables.otp.providers.firebase.apiKey');

        $data['json']['idToken'] = $otpUserToken;

        try {
            $client = new GuzzleHttp\Client();
            $request = $client->post("https://identitytoolkit.googleapis.com/v1/accounts:lookup?key=$firebaseApiKey", $data);

            if ($request->getStatusCode() == 200) {
                $response = $request->getBody()->getContents();
                $info = GuzzleHttp\Utils::jsonDecode($response, true);

                // we may add another security layer by checking if 'lastLoginAt' is within last 5 mins
                foreach ($info['users'] as $user) {
                    if ($user['phoneNumber'] === $this->mobile && $user['localId'] === $otpUserId) {
                        return true;
                    }
                }
            }
        } catch (Throwable $e) {
            // firebase or php error
        }

        return false;
    }
}
