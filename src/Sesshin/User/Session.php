<?php
namespace Sesshin\User;

/*
 * This file is part of Sesshin library.
 *
 * (c) Przemek Sobstel <http://sobstel.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

class Session extends \Sesshin\Session\Session {

	private $user_id_key = '_user_id';

  public function setUserIdKey($user_id_key) {
    $this->user_id_key = $user_id_key;
  }

  public function getUserIdKey() {
    return $this->user_id_key;
  }

  public function getUserId() {
    return $this->getValue($this->getUserIdKey());
  }

  public function login($user_id) {
    $this->setValue($this->getUserIdKey(), $user_id);
  }

  public function isLogged() {
    return !is_null($this->getUserId());
  }

  public function logout() {
    $this->unsetValue($this->getUserIdKey());
  }

}
