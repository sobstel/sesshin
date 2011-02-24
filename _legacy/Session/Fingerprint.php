<?php
/**
 * Fingerprint - some predefined fingerprints
 * 
 * @package Composit Session
 * @version SVN: $Id: $
 * @author Przemek Sobstel http://sobstel.org
 * @link http://segfaultlabs.com/composit/
 */

class cSession_Fingerprint
{

    private function __construct()
    {
    }

	static public function userAgent()
	{
        return md5(cRegistry::getRequest()->getHeader('User-Agent'));
	}	
	
}