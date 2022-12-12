<?php

namespace HasanAlyazidi\Sendables\SMS\Providers;

use GuzzleHttp;
use Throwable;

class OurSMSV2Provider implements ISMSProvider
{
    private $message;
    private $mobileNumbers;

    private $output;
    private $rawOutput;

    private $username;
    private $password;
    private $apiKey;
    private $src;
    private $priority = 0;
    private $delay    = 0;
    private $validity = 0;
    private $maxParts = 0;
    private $dlr      = 0;
    private $prevDups = 0;
    private $msgClass = '';

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

        return $this->output['accepted'] >= 1;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function setApiKey(string $apiKey): self
    {
        $this->apiKey = $apiKey;
        return $this;
    }

    public function setSrc(string $src): self
    {
        $this->src = $src;
        return $this;
    }

    public function setPriority(int $priority): self
    {
        $this->priority = $priority;
        return $this;
    }

    public function setDelay(int $delay): self
    {
        $this->delay = $delay;
        return $this;
    }

    public function setValidity(int $validity): self
    {
        $this->validity = $validity;
        return $this;
    }

    public function setMaxParts(int $maxParts): self
    {
        $this->maxParts = $maxParts;
        return $this;
    }

    public function setDlr(int $dlr): self
    {
        $this->dlr = $dlr;
        return $this;
    }

    public function setPrevDups(int $prevDups): self
    {
        $this->prevDups = $prevDups;
        return $this;
    }

    public function setMsgClass(string $msgClass): self
    {
        $this->msgClass = $msgClass;
        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username ?? $this->getConfigValue('username');
    }

    public function getPassword(): ?string
    {
        return $this->password ?? $this->getConfigValue('password');
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey ?? $this->getConfigValue('apiKey');
    }

    public function getSrc(): ?string
    {
        return $this->src ?? $this->getConfigValue('sender');
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function getDelay(): int
    {
        return $this->delay;
    }

    public function getValidity(): int
    {
        return $this->validity;
    }

    public function getMaxParts(): int
    {
        return $this->maxParts;
    }

    public function getDlr(): int
    {
        return $this->dlr;
    }

    public function getPrevDups(): int
    {
        return $this->prevDups;
    }

    public function getMsgClass(): string
    {
        return $this->msgClass;
    }

    protected function sendSMS(string $mobileNumbers)
    {
        $text = urlencode($this->message);

        $url = 'https://api.oursms.com/api-a/msgs'.
                    '?username=' . $this->getUsername().
                    '&token='    . $this->getApiKey().
                    '&src='      . $this->getSrc().
                    '&dests='    . $mobileNumbers.
                    '&body='     . $text.
                    '&priority=' . $this->getPriority().
                    '&delay='    . $this->getDelay().
                    '&validity=' . $this->getValidity().
                    '&maxParts=' . $this->getMaxParts().
                    '&dlr='      . $this->getDlr().
                    '&prevDups=' . $this->getPrevDups().
                    '&msgClass=' . $this->getMsgClass();

        try {
            $client = new GuzzleHttp\Client();
            $request = $client->get($url);

            if ($request->getStatusCode() == 200) {
                $this->rawOutput = $request->getBody()->getContents();
                $this->output    = GuzzleHttp\Utils::jsonDecode($this->rawOutput, true);
            }
        } catch (Throwable $ex) {}
    }

    private function getConfigValue(string $key): ?string
    {
        return config("sendables.sms.providers.oursms-v2.$key");
    }
}
