<?php
namespace Sesshin\FingerprintGenerator;

class UserAgent implements FingerprintGeneratorInterface
{
    /** @var string */
    private $userAgent;

    /**
     * @param string $userAgent
     */
    public function __construct($userAgent = null)
    {
        if ($userAgent !== null) {
            $userAgent = (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');
        }

        $this->userAgent = $userAgent;
    }

    /**
     * @return string
     */
    public function generate()
    {
        return sha1($this->userAgent);
    }
}
