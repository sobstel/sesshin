<?php
namespace League\Sesshin\EntropyGenerator;

interface EntropyGeneratorInterface
{
    /**
     * @return string
     */
    public function generate();
}
