<?php

namespace HasanAlyazidi\Sendables\SMS\Providers;

class OurSMSProvider implements ISMSProvider
{
    private $message;
    private $mobileNumbers;

    private $output;
    private $rawOutput;

    /**
     * Our SMS Provider
     *
     * @param string $message
     * @param array|string $mobileNumbers
     */
    public function __construct(string $message, $mobileNumbers) {
        $this->message       = $message;
        $this->mobileNumbers = is_array($mobileNumbers) ? $mobileNumbers : [$mobileNumbers];
    }

    public function send() : void
    {
        $numbers = implode(',', $this->mobileNumbers);
        $this->sendSMS($numbers);
    }

    public function isSent() : bool
    {
        if (is_null($this->output)) {
            return false;
        }

        return $this->output->Code == 100;
    }

    private function sendSMS(string $mobileNumbers)
    {
        $username   = config('sendables.sms.providers.oursms.username');
        $password   = config('sendables.sms.providers.oursms.password');
        $senderName = config('sendables.sms.providers.oursms.sender');

        $to   = $mobileNumbers;
        $text = urlencode($this->message);

        $url = "http://www.oursms.net/api/sendsms.php?username=$username&password=$password&numbers=$to&message=$text&sender=$senderName&unicode=E&return=json";

        $this->rawOutput = file_get_contents($url);

        $this->output = json_decode($this->rawOutput);
    }
}
