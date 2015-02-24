<?php
namespace League\Sesshin\Store;

interface StoreInterface
{
    /**
     * @param string $id
     * @return mixed
     */
    public function fetch($id);

    /**
     * @param string $id
     * @param mixed  $data
     * @param int $lifeTime Lifetime (0 => infinite lifeTime).
     */
    public function save($id, $data, $lifeTime = 0);

    /**
     * @param string $id
     * @return boolean TRUE if successfully deleted.
     */
    public function delete($id);
}
