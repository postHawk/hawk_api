<?php
namespace hawk_api;

/**
 * Корневой класс для создания объектов шифрования
 *
 * @author Maxim Barulin <mbarulin@gmail.com>
 */
class crypt
{
	/**
	 *
	 * @var string ключ для шифрования 
	 */
	private $crypt_key = 'a3453fsdf564l546asdff6mas,.fma.S<Dfm';

	/**
	 *
	 * @var i_crypt массив шифраторов
	 */
	private static $encryptor = [];

	/**
	 * Тип шифрования AES256
	 */
	const TYPE_AES256 = 'aes256';

	/**
	 * возвращает текущий ключ шифрования
	 * @return string
	 */
	public function get_crypt_key()
	{
		return $this->crypt_key;
	}

	/**
	 * устанавливает текущий ключ шифрования
	 * @param type $key
	 */
	public function set_crypt_key($key)
	{
		$this->crypt_key = $key;
	}

	/**
	 * Возвращает объект шифровальщик заданного типа
	 * @param type $type тип шифрования
	 * @return object 
	 * @throws \Exception
	 */
	public final static function get_encryptor($type)
	{
		if(empty(self::$encryptor[$type]))
		{
			$file = __DIR__ . '/crypt_' . $type . '.php';

			if(!file_exists($file))
			{
				throw new \Exception('Невозможно создать объект класса шифрования');
			}

			$class = 'hawk_api\\crypt_' . $type;
			if(!class_exists($class))
			{
				require $file;
			}
			
			self::$encryptor[$type] = new $class();
			
			if(!(self::$encryptor[$type] instanceof i_crypt))
			{
				throw new \Exception('Класс шифрования должен реализовывать интерфейс i_crypt');
			}
		}

		return self::$encryptor[$type];
	}
}
