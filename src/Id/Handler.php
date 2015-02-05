<?php
namespace League\Sesshin\Id;

use Sesshin\League\EntropyGenerator;
use Sesshin\Id\Store\StoreInterface;
use Sesshin\Id\Store\Cookie as CookieStore;

/**
 * Session id handler (store, entropy generator, hash algo...).
 */
class Handler
{
    /** @var StoreInterface */
    private $id_store;

    /** @var \Sesshin\EntropyGenerator\EntropyGeneratorInterface */
    private $entropy_generator;

    /** @var string Hash algo used to generate session ID (it hashes entropy). */
    private $hash_algo = 'sha1';

    /**
     * @param StoreInterface $id_store
     */
    public function setIdStore(StoreInterface $id_store)
    {
        $this->id_store = $id_store;
    }

    /**
     * @return StoreInterfaceStoreInterface
     */
    public function getIdStore()
    {
        if (!$this->id_store) {
            $this->id_store = new CookieStore();
        }

        return $this->id_store;
    }

    /**
     * Sets entropy that is used to generate session id.
     *
     * @param \Sesshin\EntropyGenerator\EntropyGeneratorInterface $entropy_generator
     */
    public function setEntropyGenerator(EntropyGenerator\EntropyGeneratorInterface $entropy_generator)
    {
        $this->entropy_generator = $entropy_generator;
    }

    /**
     * @return \Sesshin\EntropyGenerator\EntropyGeneratorInterface
     */
    public function getEntropyGenerator()
    {
        if (!$this->entropy_generator) {
            $this->entropy_generator = new EntropyGenerator\Uniq();
        }

        return $this->entropy_generator;
    }

    /**
     * @param string Hash algorith accepted by hash extension.
     */
    public function setHashAlgo($algo)
    {
        if (in_array($algo, hash_algos())) {
            $this->hash_algo = $algo;
        } else {
            throw new Exception('Provided algo is not valid (not on hash_algos() list)');
        }
    }

    /**
     * @return string
     */
    public function getHashAlgo()
    {
        return $this->hash_algo;
    }

    /**
     * @return string
     */
    public function generateId()
    {
        $id = hash($this->getHashAlgo(), $this->getEntropyGenerator()->generate());
        $this->setId($id);

        return $this->getId();
    }

    public function setId($id)
    {
        $this->getIdStore()->setId($id);
    }

    public function getId()
    {
        return $this->getIdStore()->getId();
    }

    public function issetId()
    {
        return $this->getIdStore()->issetId();
    }

    public function unsetId()
    {
        return $this->getIdStore()->unsetId();
    }
}
