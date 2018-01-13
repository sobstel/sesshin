<?php
    /**
     * Created by PhpStorm.
     * User: yabafinet
     * Date: 10/01/18
     * Time: 09:24 AM
     */

    namespace Sesshin;


    class SessionFlash
    {
        /**
         * Singleton of this class
         *
         * @var $this
         */
        public static $singleton;

        /**
         * Object manager of sessions.
         *
         * @var Session
         */
        private $session;

        /**
         * Unique ID in each transaction request.
         *
         * @var int
         */
        private $msg_id;


        /**
         * key name to save the flash dat
         *
         * @var string
         */
        protected $key_name = '_flash';

        /**
         * SessionFlash constructor.
         *
         * @param Session $session
         */
        public function __construct(Session $session)
        {
            $this->session = $session;
        }

        /**
         * Add a value in the flash session.
         *
         * @param $value
         * @param string $type
         */
        public function add($value, $type = 'n')
        {
            $this->session->put($type, $value);
            $this->session->push($this->key_name.'.new', $type);
            $this->removeFromOldFlashData([$type]);
        }


        /**
         * Add a value in the flash session.
         *
         * @param $key
         * @param $value
         */
        public function set($key, $value)
        {
            $this->session->put($key, $value);
            $this->session->push($this->key_name.'.new', $key);
            $this->removeFromOldFlashData([$key]);
        }

        /**
         * Get a value in the flash session.
         *
         * @param $key
         * @return mixed
         */
        public function get($key)
        {
            return $this->session->getValue($key);
        }

        /**
         * Determine if exist key in flash data.
         *
         * @param $key
         * @return bool
         */
        public function has($key)
        {
            $current_data = $this->session->getValue($key);

            return isset($current_data);
        }

        /**
         * Get all the data or data of a type.
         *
         * @return mixed
         */
        public function getCurrentData()
        {
            $current_data = $this->session->getValue($this->key_name);

            return isset($current_data) ? $current_data : $current_data = array();
        }

        /**
         * Reflash all of the session flash data.
         *
         * @return void
         */
        public function reflash()
        {
            $this->mergeNewFlashes($this->session->getValue($this->key_name.'.old'));
            $this->session->setValue($this->key_name.'.old', []);
        }


        /**
         * Reflash a subset of the current flash data.
         *
         * @param  array|mixed  $keys
         * @return void
         */
        public function keep($keys = null)
        {
            $this->mergeNewFlashes($keys = is_array($keys) ? $keys : func_get_args());
            $this->removeFromOldFlashData($keys);
        }


        /**
         * Merge new flash keys into the new flash array.
         *
         * @param  array  $keys
         * @return void
         */
        protected function mergeNewFlashes(array $keys)
        {
            $values = array_unique(array_merge($this->session->getValue($this->key_name.'.new'), $keys));
            $this->session->setValue($this->key_name.'.new', $values);
        }

        /**
         * Remove the given keys from the old flash data.
         *
         * @param  array  $keys
         * @return void
         */
        protected function removeFromOldFlashData(array $keys)
        {
            $old_data = $this->session->getValue($this->key_name.'.old');

            if (! is_array($old_data)) {
                $old_data = array();
            }
            $this->session->setValue($this->key_name.'.old', array_diff($old_data, $keys));
        }

        /**
         * Age the flash data for the session.
         *
         * @return void
         */
        public function ageFlashData()
        {
            $old_data = $this->session->getValue($this->key_name.'.old');
            if (! is_array($old_data)) {
                $old_data = array();
            }
            $this->session->forget($old_data);
            $this->session->put($this->key_name.'.old', $this->session->getValue($this->key_name.'.new'));
            $this->session->put($this->key_name.'.new', []);
        }


        /**
         * Clear all data flash.
         *
         */
        public function clear()
        {
            $this->session->unsetValue($this->key_name);
        }


        /**
         * Calling this class in a singleton way.
         *
         * @param Session|null $session
         * @return SessionFlash
         */
        static function singleton(Session $session = null)
        {
            if (self::$singleton == null) {
                self::$singleton = new SessionFlash($session);
            }

            return self::$singleton;
        }
    }