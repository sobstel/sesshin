<?php
/*
 * This file is part of Sesshin library.
 *
 * (c) Przemek Sobstel <http://sobstel.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sesshin\Listener;

class Listener {

  /** trigger(): return last returned value */
  const RETURN_VALUE = 1;

  /** trigger(): stop execution when callbacks returns (loosely-typed) true */ 
  const STOP_ON_TRUE = 2;

  /** @var array of SplPriorityQueue */
  private $queues;
  
  /**
   * Get queue for given event.
   * 
   * @param string $event
   * @return SplPriorityQueue
   */
  public function getQueue($event) {
    if (!isset($this->queues[$event])) {
      $this->queues[$event] = new SplPriorityQueue();
    }
    return $this->queues[$event];
  }

  /**
   * Bind (connect) callback to a given event.
   *
   * @param string $event Event name
   * @param callable $callback Listener callback
   */
  public function bind($event, $callback, $priority = 10) {
    $this->getQueue($event)->insert($callback, $priority);
  }

  /**
   * Call (notify) all callbacks for a given event.
   *
   * @param string $event Event name
   * @param array $args Aruments passed to callbacks
   * @param int $flags Flags
   * @return mixed Depends on flags setup (true if any callback invoked, false otherwise)
   */
  public function trigger($event, array $args = array(), $flags = 0) {
    $queue = $this->getQueue($event);

    if (!empty($queue)) {
      array_unshift($args, $event);

      $result = 0;

      foreach ($queue as $callback) {
        $result = call_user_func_array($callback, $args);
        if ($result && ($flags & self::STOP_ON_TRUE)) {
          break;
        }
      }

      return (($flags & self::RETURN_VALUE) ? $result : true);
    }

    return false;
  }

}
