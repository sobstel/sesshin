<?php
namespace League\Sesshin\Store;

class FileStore implements StoreInterface
{
    /*** @var string */
    protected $dir;

    /**
     * @param string
     */
    public function __construct($dir)
    {
        $this->dir = $dir;
    }

    /**
     * {@inheritdoc}
     */
    public function fetch($id)
    {
        $fileName = $this->getFileName($id);

        if (file_exists($fileName)) {
            list($expirationTime, $content) = explode('|', file_get_contents($fileName));

            if ($expirationTime < time()) {
                $this->delete($id);

                return false;
            }

            return unserialize($content);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function save($id, $data, $lifeTime)
    {
        $fileName = $this->getFileName($id);

        $expirationTime = time() + $lifeTime;
        $content = $expirationTime.'|'.serialize($data);

        return file_put_contents($fileName, $content);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id)
    {
        return unlink($this->getFileName($id));
    }

    protected function getFileName($id)
    {
        return $this->dir.DIRECTORY_SEPARATOR.$id.'.sess';
    }
}
