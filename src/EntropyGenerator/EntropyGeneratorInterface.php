<?php
namespace Sesshin\EntropyGenerator;

interface EntropyGeneratorInterface
{
    /**
     * @return string
     */
    public function generate();
}
