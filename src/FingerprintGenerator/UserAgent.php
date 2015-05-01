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
        if($userAgent) {
            $srv = array_merge(array('HTTP_USER_AGENT' => ''), $_SERVER);
            $userAgent = (string) $srv['HTTP_USER_AGENT'];
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
