<?php
namespace hawk_api;

class hawk_transport
{
	private static $transport = null;
	protected $host = '127.0.0.1';
	protected $port = '2222';
	protected $url = "http://127.0.0.1:2222";

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

}
