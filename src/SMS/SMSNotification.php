<?php

namespace HasanAlyazidi\Sendables\SMS;

class SMSNotification
{
    private $message;
    private $mobileNumbers;

    private $provider;

    /**
     * SMS Notification
     *
     * @param string $message
     * @param string|array $mobileNumbers
     */
    public function __construct(string $message, $mobileNumbers) {
        $this->message       = $message;
        $this->mobileNumbers = is_array($mobileNumbers) ? $mobileNumbers : [$mobileNumbers];

        $defaultProvider = config('sendables.sms.providers.default');

        $this->provider = new $defaultProvider($this->message, $this->mobileNumbers);
    }

    public function send()
    {
        $this->provider->send();
    }

    public function isSent() : bool
    {
        return $this->provider->isSent();
    }
}
