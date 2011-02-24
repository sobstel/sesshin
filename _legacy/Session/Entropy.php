<?php
/**
 * Entropy - some predefined entropy generators
 * 
 * @package Composit Session
 * @version SVN: $Id: $
 * @author Przemek Sobstel http://sobstel.org
 * @link http://segfaultlabs.com/composit/
 */

class cSession_Entropy
{

    private function __construct()
    {
    }

	static public function file($file = '/dev/urandom', $length = 512)
	{
		$entropy = file_get_contents($file, false, null, 0, $length);
		
		if ($entropy == false)
		{
			throw new cSession_Exception('Entropy file is empty!');
		}
		
		return $entropy;
	}	
	
	/**
	 * mt_rand() is fast but pretty weak algorith for secure random number generation
	 */
	static public function mt_uniq()
	{
		return uniqid(mt_rand(), true);
	}	
	
}