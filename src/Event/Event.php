<?php
namespace Sesshin\Event;

use Sesshin\Session;
use League\Event\Event as BaseEvent;

class Event extends BaseEvent
{
    /*** @var Session */
    protected $session;

    /**
     * @param string $name
     * @param Session $session
     */
    public function __construct($name, Session $session)
    {
        parent::__construct($name);
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
