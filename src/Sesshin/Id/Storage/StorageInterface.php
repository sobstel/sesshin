<?php
namespace Sesshin\Id\Storage;

interface StorageInterface
{
    /**
     * @param int $id
     */
    public function setId($id);

    /**
     * @return string
     */
    public function getId();

    /**
     * @return bool
     */
    public function issetId();

    public function unsetId();
}
