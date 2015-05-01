<?php
namespace Sesshin\FingerprintGenerator;

class UserAgent implements FingerprintGeneratorInterface
{
	/**
     * @return string
     */
    public function generate()
    {
        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        return sha1($userAgent);
    }
}
