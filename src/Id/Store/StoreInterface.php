<?php
namespace Sesshin\Id\Store;

interface StoreInterface
{
    /**
     * Set (store) session id in store
     *
     * @param string $id
     */
    public function setId($id);

    /**
     * Get session id from store
     *
     * @return string
     */
    public function getId();

    /**
     * @return bool
     */
    public function issetId();

    /**
     * @return void
     */
    public function unsetId();
}
