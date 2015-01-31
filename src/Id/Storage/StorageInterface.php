<?php
namespace League\Sesshin\Id\Storage;

interface StorageInterface
{
    /**
     * Set (store) session id in storage.
     *
     * @param string $id
     */
    public function setId($id);

    /**
     * Get session id from storage.
     *
     * @return string
     */
    public function getId();

    /**
     * @return bool
     */
    public function issetId();

    public function unsetId();
}
