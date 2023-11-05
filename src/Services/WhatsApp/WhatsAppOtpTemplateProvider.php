<?php

namespace HasanAlyazidi\Sendables\Services\WhatsApp;

use Adrii\Whatsapp\Whatsapp;
use HasanAlyazidi\Sendables\SMS\Providers\ISMSProvider;
use SendablesHelpers;
use Throwable;

class WhatsAppOtpTemplateProvider implements ISMSProvider
{
    protected $message;
    protected $code;
    protected $mobileNumbers;

    protected $ws;
    protected $isMassageSent = false;

    /**
     * Our SMS Provider
     *
     * @param string $message
     * @param array|string $mobileNumbers
     */
    public function __construct(string $message, string $code, $mobileNumbers) {
        $this->message       = $message;
        $this->code          = $code;
        $this->mobileNumbers = is_array($mobileNumbers) ? $mobileNumbers : [$mobileNumbers];

        $this->initWhatsApp();
    }

    public function send(): void
    {
        foreach ($this->mobileNumbers as $mobileNumber) {
            $this->sendWhatsAppMessage($mobileNumber);
        }
    }

    public function isSent(): bool
    {
        return $this->isMassageSent;
    }

    protected function getPhoneNumberId(): string
    {
        return $this->getConfigValue('phoneNumberId');
    }

    protected function getAccessToken(): string
    {
        return $this->getConfigValue('accessToken');
    }

    protected function getGraphVersion(): string
    {
        return $this->getConfigValue('graphVersion');
    }

    protected function sendWhatsAppMessage(string $mobileNumber): void
    {
        try {
            $bodyComponent = [
                'type' => 'body',
                'parameters' => [
                    [
                        'type' => 'text',
                        'text' => $this->code,
                    ],
                ],
            ];

            $buttonComponent = [
                'type'       => 'button',
                'sub_type'   => 'url',
                'index'      => '0',
                'parameters' => [
                    [
                        'type' => 'text',
                        'text' => $this->code,
                    ],
                ],
            ];

            $this->ws->send_message()
                ->addComponent($bodyComponent, $buttonComponent);

            $response = $this->ws->send_message()
                ->template('otp_code', $mobileNumber, SendablesHelpers::getCurrentLocale());

            $this->isMassageSent = isset($response[2]) && $response[2] === 'OK';

            if (!$this->isMassageSent) {
                throw new \Exception('OTP not sent, response: ' . json_encode($response));
            }
        } catch (Throwable $ex) {
            $class = self::class;
            \Log::error("Error in `$class`: " . $ex->getMessage());
        }
    }

    protected function initWhatsApp(): void
    {
        $this->ws = new Whatsapp(
            $this->getPhoneNumberId(),
            $this->getAccessToken(),
            $this->getGraphVersion()
        );
    }

    protected function getConfigValue(string $key): ?string
    {
        return config("sendables.services.whatsapp.$key");
    }
}
