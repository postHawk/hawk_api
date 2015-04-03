<?php
namespace hawk_api;

/**
 * Description of crypt
 *
 * @author maximilian
 */
class crypt
{
	private $crypt_key = 'a3453fsdf564l546asdff6mas,.fma.S<Dfm';

	private static $encryptor = [];

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
	 * @param type $type
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
		}

		return self::$encryptor[$type];
	}
}
