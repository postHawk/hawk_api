<?php

namespace Hawk\Api\Transport;

/**
 * Класс реалзизующий отправку сообщений путём эмуляции http запросов
 * 
 * @author Maxim Barulin <mbarulin@gmail.com>
 */
class HawkTransportSocket extends HawkTransport implements IHawkTransport
{
	/**
	 * конструктор
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * отправка сообщения
	 * @param array $data сообщение
	 * @param string $type тип сообщения
	 * @return string or false;
	 */
	public function send($data, $type)
	{
		if(!isset($data['hawk_action']))
		{
			$data['hawk_action'] = $type;
		}

		$socket	 = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

		if(socket_connect($socket, parent::$host, parent::$port) === false)
		{
			return false;
		}

		$json = json_encode($data);

		$in	 = "POST / HTTP/1.1\r\n";
		$in .= "Host: " . parent::$host . "\r\n";
		$in .= "Origin: http://{$_SERVER['HTTP_HOST']}\r\n";
		$in .= "Connection: keep-alive\r\n";
		$in .= "Transport: sokets\r\n\r\n";
		$in .= $json;

		if(socket_write($socket, $in, strlen($in)) === false)
		{
			return false;
		}
		$output = '';
		while ($out = socket_read($socket, 2048))
		{
			$output .= $out;
		}

		socket_close($socket);

		return json_decode($output, true);
	}

}
