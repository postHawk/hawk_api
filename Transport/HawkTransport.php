<?php
namespace Hawk\Api\Transport;

/**
 * Базовый класс транспортов. Отвечает за 
 * отправку сообщений в сервис
 * 
 * @author Maxim Barulin <mbarulin@gmail.com>
 */
class HawkTransport
{
	/**
	 *
	 * @var HawkTransport текущий транспорт
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
	 * @return HawkTransport
	 * @throws \Exception
	 */
	public static final function getTransport()
	{
		if(is_null(self::$transport))
		{
			$transport = null;
			if(extension_loaded('curl'))
			{
				$transport = 'Curl';
			}
			elseif(extension_loaded('sockets'))
			{
				$transport = 'Socket';
			}
			else
			{
				throw new \Exception('Невозможно создать транспорт, пожалуйста включите curl или sockets расширения');
			}
			
			$class = __NAMESPACE__ . '\\HawkTransport' . $transport;

			self::$transport = new $class();
			
			if(!(self::$transport instanceof IHawkTransport))
			{
				throw new \Exception('Класс транспорта должен реализовывать интерфейс IHawkTransport');
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
