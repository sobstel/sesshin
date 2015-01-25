<?php
namespace Sesshin;

use Sesshin\Session;
use League\Event\Event as BaseEvent;

class Event extends BaseEvent
{
    const NO_DATA_OR_EXPIRED = 'session.no_data_or_expired';
    const EXPIRED = 'session.expired';
    const INVALID_FINGERPRINT = 'session.invalid_fingerprint';

    /*** @var Session */
    protected $session;

    /**
     * @param Session
     * @param string
     */
    public function __construct($name, Session $session)
    {
        $this->session = $session;
        parent::__construct($name);
    }

    public function getSession()
    {
        return $this->session;
    }
}
