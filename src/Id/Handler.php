<?php
namespace Sesshin\Id;

use Sesshin\EntropyGenerator\EntropyGeneratorInterface;
use Sesshin\Id\Store\StoreInterface;
use Sesshin\Id\Store\Cookie as CookieStore;

/**
 * Session id handler (store, entropy generator, hash algo...).
 */
class Handler
{
    /** @var StoreInterface */
    private $idStore;

    /** @var EntropyGeneratorInterface */
    private $entropyGenerator;

    /** @var string Hash algo used to generate session ID (it hashes entropy). */
    private $hashAlgo = 'sha1';

    /**
     * @param StoreInterface $idStore
     */
    public function setIdStore(StoreInterface $idStore)
    {
        $this->idStore = $idStore;
    }

    /**
     * @return StoreInterface
     */
    public function getIdStore()
    {
        if (!$this->idStore) {
            $this->idStore = new CookieStore();
        }

        return $this->idStore;
    }

    /**
     * Sets entropy that is used to generate session id.
     *
     * @param EntropyGeneratorInterface $entropyGenerator
     */
    public function setEntropyGenerator(EntropyGeneratorInterface $entropyGenerator)
    {
        $this->entropyGenerator = $entropyGenerator;
    }

    /**
     * @return EntropyGeneratorInterface
     */
    public function getEntropyGenerator()
    {
        if (!$this->entropyGenerator) {
            $this->entropyGenerator = new EntropyGenerator\Uniq();
        }

        return $this->entropyGenerator;
    }

    /**
     * @param string Hash algorith accepted by hash extension.
     */
    public function setHashAlgo($algo)
    {
        if (in_array($algo, hash_algos())) {
            $this->hashAlgo = $algo;
        } else {
            throw new Exception('Provided algo is not valid (not on hash_algos() list)');
        }
    }

    /**
     * @return string
     */
    public function getHashAlgo()
    {
        return $this->hashAlgo;
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
        $this->getIdStore()->unsetId();
    }
}
