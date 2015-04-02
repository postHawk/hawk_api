<?php
namespace hawk_api;

/**
 *
 * @author Maximilian
 */
interface i_crypt
{
	public function encrypt($text);

	public function decrypt($jsonString);
}
