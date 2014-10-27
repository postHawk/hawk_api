<?php
namespace hawk_api;

require_once 'i_hawk_transport.php';

class hawk_transport_curl extends hawk_transport implements i_hawk_transport
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
	 * @return type
	 */
	public function send($data, $type)
	{
		$ch = curl_init($this->url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_POST, 1);

		curl_setopt($ch, CURLOPT_POSTFIELDS, array('{' . $type . '}' . json_encode($data)));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($ch);       
		
		if(curl_exec($ch) === false)
		{
		    throw new \Exception( 'Ошибка curl: ' . curl_error($ch));
		}

		curl_close($ch);
		
		return $output;
	}
}
