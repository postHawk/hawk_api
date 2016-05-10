<?php
namespace Hawk\Api\Encryption;

/**
 * Интерфейс, который необходимо реализовать 
 * для создания новых типов щифрования
 * @author Maxim Barulin <mbarulin@gmail.com>
 */
interface ICrypt
{
	/**
	 * Шифрование
	 * @param string $text текст для шифрования
	 * @return string
	 */
	public function encrypt($text);

	/**
	 * Дешифрование
	 * @param string $jsonString текст для расшифровки
	 * @return string
	 */
	public function decrypt($jsonString);
}
