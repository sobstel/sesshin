<?php
namespace Sesshin\Store;

interface StoreInterface
{
    /**
     * @param string $id
     * @return bool|mixed
     */
    public function fetch($id);

    /**
     * @param string $id
     * @param mixed $data
     * @param int $lifeTime
     */
    public function save($id, $data, $lifeTime);

    /**
     * @param string $id
     * @return boolean TRUE if successfully deleted.
     */
    public function delete($id);
}
