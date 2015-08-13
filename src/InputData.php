<?php namespace UACapabilities;

class InputData {

    public $uaString;

    public $userAgent;

    public $os;

    public $device;

    public function __construct($uaString = null, $userAgent = null, $os = null, $device = null)
    {
        $this->uaString = $uaString;

        $this->userAgent = $userAgent;

        $this->os = $os;

        $this->device = $device;
    }
}