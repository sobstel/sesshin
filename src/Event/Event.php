<?php
namespace League\Sesshin\Event;

use League\Sesshin\Session;
use League\Event\Event as BaseEvent;

class Event extends BaseEvent
{
    /*** @var Session */
    protected $session;

    /**
     * @param Session
     * @param string
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * @return Session
     */
    public function getSession()
    {
        return $this->session;
    }
}
