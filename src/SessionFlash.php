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
         * Property with flash data
         *
         * @var array
         */
        private $flash_data = [];

        /**
         * key name to save the flash dat
         *
         * @var string
         */
        protected $key_name = 'flash_data';

        /**
         * SessionFlash constructor.
         *
         * @param Session $session
         */
        function __construct(Session $session)
        {
            $this->session = $session;
        }

        /**
         * Add a value in the flash session.
         *
         * @param $value
         * @param string $type
         */
        function add($value, $type = 'n')
        {
            $current_save_data = $this->getCurrentData($type);

            $current_save_data[$type] = $value;

            $this->session->setValue($this->key_name, $current_save_data);
        }

        /**
         * Get a value in the flash session.
         *
         * @param string $type
         * @return mixed
         */
        function get($type = 'n')
        {
            // get the current data.
            $current_data = $this->getCurrentData($type);

            // eliminate after obtaining the data.
            //$this->session->unsetValue($this->key_name);

            return $current_data;
        }

        /**
         * Determine if there is data of a type.
         *
         * @param $type
         * @return bool
         */
        function has($type)
        {
            return is_null($this->getCurrentData($type)) ? false : true;
        }

        /**
         * Get all the data or data of a type.
         *
         * @param string $type
         * @return mixed
         */
        function getCurrentData($type = 'n')
        {
            $current_data = $this->session->getValue($this->key_name);

            return isset($current_data[$type]) ? $current_data[$type] : $current_data[$type] = array();
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