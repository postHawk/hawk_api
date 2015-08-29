<?php
namespace hawk_api;

/**
 * Интерфейс, который необходимо реализовать 
 * для создания новых типов щифрования
 * @author Maxim Barulin <mbarulin@gmail.com>
 */
interface i_crypt
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
