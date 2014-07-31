<?php

namespace hawk_api;

require_once 'i_hawk_transport.php';

class hawk_transport_socket extends hawk_transport implements i_hawk_transport
{
	/**
	 * конструктор
	 */
	public function __construct()
	{
		;
	}

	/**
	 * отправка сообщения
	 * @param array $data сообщение
	 * @param string $type тип сообщения
	 * @return string or false;
	 */
	public function send($data, $type)
	{
		$socket	 = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

		if(socket_connect($socket, $this->host, $this->port) === false)
		{
			return false;
		}

		$in	 = "POST / HTTP/1.1\r\n";
		$in .= "Host: {$this->host}\r\n";
		$in .= "Origin: http://{$_SERVER['HTTP_HOST']}\r\n";
		$in .= "Connection: keep-alive\r\n";
		$in .= "Transport: sokets\r\n\r\n";
		$in .= '{' . $type . '}' . json_encode($data);
		$out = '';

		if(socket_write($socket, $in, strlen($in)) === false)
		{
			return false;
		}
		while ($out = socket_read($socket, 2048))
		{
			echo $out;
		}

		socket_close($socket);
	}

}
