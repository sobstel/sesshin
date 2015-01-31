<?php
namespace League\Sesshin\Event;

class InvalidFingerprint extends Event
{
    protected $name = "sesshin.invalid_fingerprint";
}
