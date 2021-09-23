<?php

namespace HasanAlyazidi\Sendables\SMS\Providers;

interface ISMSProvider {
    public function send() : void;
    public function isSent() : bool;
}
