<?php
namespace Sesshin\User;

use Sesshin\Session as BaseSession;

class Session extends BaseSession
{
    private $userIdKey = '_user_id';

    /**
     * @param string $userIdKey
     */
    public function setUserIdKey($userIdKey)
    {
        $this->userIdKey = $userIdKey;
    }

    /**
     * @return string
     */
    public function getUserIdKey()
    {
        return $this->userIdKey;
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->getValue($this->getUserIdKey());
    }

    /**
     * @param string $userId
     */
    public function login($userId)
    {
        $this->setValue($this->getUserIdKey(), $userId);
    }

    /**
     * @return bool
     */
    public function isLogged()
    {
        return !is_null($this->getUserId());
    }

    /**
     */
    public function logout()
    {
        $this->unsetValue($this->getUserIdKey());
    }
}
