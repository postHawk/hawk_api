<?php
namespace Hawk\Api\Encryption;

/**
 * Корневой класс для создания объектов шифрования
 *
 * @author Maxim Barulin <mbarulin@gmail.com>
 */
class Crypt
{
	/**
	 *
	 * @var string ключ для шифрования 
	 */
	private $crypt_key = 'a3453fsdf564l546asdff6mas,.fma.S<Dfm';

	/**
	 *
	 * @var ICrypt массив шифраторов
	 */
	private static $encryptor = [];

	/**
	 * Тип шифрования AES256
	 */
	const TYPE_AES256 = 'Aes256';

	/**
	 * возвращает текущий ключ шифрования
	 * @return string
	 */
	public function getCryptKey()
	{
		return $this->crypt_key;
	}

	/**
	 * устанавливает текущий ключ шифрования
	 * @param string $key
	 */
	public function setCryptKey($key)
	{
		$this->crypt_key = $key;
	}

	/**
	 * Возвращает объект шифровальщик заданного типа
	 * @param string $type тип шифрования
	 * @return object 
	 * @throws \Exception
	 */
	public final static function getEncryptor($type)
	{
		if(empty(self::$encryptor[$type]))
		{
			$class = 'Crypt' . $type;
			if(!class_exists($class))
			{
				throw new \Exception('Невозможно создать объект класса шифрования');
			}

			self::$encryptor[$type] = new $class();
			
			if(!(self::$encryptor[$type] instanceof ICrypt))
			{
				throw new \Exception('Класс шифрования должен реализовывать интерфейс ICrypt');
			}
		}

		return self::$encryptor[$type];
	}
}
