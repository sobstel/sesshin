<?php
namespace League\Sesshin\FingerprintGenerator;

class UserAgent implements FingerprintGeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generate()
    {
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        return sha1($user_agent);
    }
}
