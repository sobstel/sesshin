<?php
namespace Sesshin\EntropyGenerator;

class Uniq implements EntropyGeneratorInterface
{
	/**
     * @return string
     */
    public function generate()
    {
        return uniqid(mt_rand(), true);
    }
}
