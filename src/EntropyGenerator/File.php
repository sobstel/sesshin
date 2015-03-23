<?php
namespace Sesshin\EntropyGenerator;

use Sesshin\Exception;

class File implements EntropyGeneratorInterface
{
    private $file;
    private $length;

    public function __construct($file = '/dev/urandom', $length = 512)
    {
        $this->file = $file;
        $this->length = $length;
    }

    /**
     * {@inheritdoc}
     */
    public function generate()
    {
        $entropy = file_get_contents($this->file, false, null, 0, $this->length);
        if (empty($entropy)) {
            throw new Exception('Entropy file is empty.');
        }

        return $entropy;
    }
}
