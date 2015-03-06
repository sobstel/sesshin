<?php
namespace League\Sesshin\User;

use League\Sesshin\Session as BaseSession;

class Session extends BaseSession
{
    private $userIdKey = '_user_id';

    public function setUserIdKey($userIdKey)
    {
        $this->userIdKey = $userIdKey;
    }

    public function getUserIdKey()
    {
        return $this->userIdKey;
    }

    public function getUserId()
    {
        return $this->getValue($this->getUserIdKey());
    }

    public function login($userId)
    {
        $this->setValue($this->getUserIdKey(), $userId);
    }

    public function isLogged()
    {
        return !is_null($this->getUserId());
    }

    public function logout()
    {
        $this->unsetValue($this->getUserIdKey());
    }
}
