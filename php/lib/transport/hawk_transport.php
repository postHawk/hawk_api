<?php
namespace hawk_api;

/**
 * Базовый класс транспортов. Отвечает за 
 * отправку сообщений в сервис
 * 
 * @author Maxim Barulin <mbarulin@gmail.com>
 */
class hawk_transport
{
	/**
	 *
	 * @var i_hawk_transport текущий транспорт
	 */
	private static $transport = null;
	/**
	 *
	 * @var string хост
	 */
	protected static $host = null;
	/**
	 *
	 * @var string порт
	 */
	protected static $port = null;
	/**
	 *
	 * @var string адрес
	 */
	protected static $url = null;

	/**
	 * конструктор
	 */
	public function __construct()
	{
		;
	}

	/**
	 * Снглтон для получения текущего объекта транспорта
	 * @return i_hawk_transport
	 * @throws \Exception
	 */
	public static final function getTransport()
	{
		if(is_null(self::$transport))
		{
			$transport = null;
			if(extension_loaded('curl'))
			{
				$transport = 'curl';
			}
			elseif(extension_loaded('sockets'))
			{
				$transport = 'socket';
			}
			else
			{
				throw new \Exception('Невозможно создать транспорт, пожалуйста включите curl или sockets расширения');
			}
			
			$class = 'hawk_api\\hawk_transport_' . $transport;
			if(!class_exists($class))
			{
				require 'hawk_transport_' . $transport . '.php';
			}

			self::$transport = new $class();
			
			if(!(self::$transport instanceof i_hawk_transport))
			{
				throw new \Exception('Класс транспорта должен реализовывать интерфейс i_hawk_transport');
			}
		}
		
		return self::$transport;
	}

	/**
	 * Устанавливает текущийй адрес сервиса
	 * @param string $url адрес сервиса
	 * @throws \Exception
	 */
	public static function setUrl($url)
	{
		$parts = [];
		if(!preg_match('/^http[s]?:\/\/([a-z0-9.\-]+)(?::([\d]{2,}))?$/', $url, $parts))
		{
			throw new \Exception('Не верный формат адреса');
		}
		self::$url = $url;
		self::$host = $parts[1];
		if(isset($parts[2]))
		{
			self::$port = $parts[2];
		}
	}
}
