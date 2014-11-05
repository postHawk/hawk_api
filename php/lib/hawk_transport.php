<?php
namespace hawk_api;

class hawk_transport
{
	private static $transport = null;
	protected static $host = null;
	protected static $port = null;
	protected static $url = null;

	/**
	 * конструктор
	 */
	public function __construct()
	{
		;
	}

	public static final function get_transport()
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
				throw new Exception('Невозможно создать транспорт, пожалуйста включите curl или sockets расширения');
			}
			
			$class = 'hawk_api\\hawk_transport_' . $transport;
			if(!class_exists($class))
			{
				require 'hawk_transport_' . $transport . '.php';
			}
			
			self::$transport = new $class();
		}
		
		return self::$transport;
	}

	public static function set_url($url)
	{
		$parts = [];
		if(!preg_match('/^http[s]{0,1}\:\/\/([a-z0-9.\-]+)\:([\d]{2,})$/', $url, $parts))
		{
			throw new Exception('Неверный формат адреса');
		}
		self::$url = $url;
		self::$host = $parts[1];
		self::$port = $parts[2];
	}
}
