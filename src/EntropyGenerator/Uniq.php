<?php
namespace Sesshin\EntropyGenerator;

class Uniq implements EntropyGeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generate()
    {
        return uniqid(mt_rand(), true);
    }
}
