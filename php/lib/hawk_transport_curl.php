<?php
namespace hawk_api;

require_once __DIR__ . '/interface/i_hawk_transport.php';

class hawk_transport_curl extends hawk_transport implements i_hawk_transport
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
	 * @return type
	 */
	public function send($data, $type)
	{
		if(!isset($data['hawk_action']))
		{
			$data['hawk_action'] = $type;
		}
		$json = json_encode($data);
		$ch = curl_init(parent::$url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_POST, 1);

		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			('Origin: ' . $_SERVER['HTTP_HOST']),
			('Transport: curl'),
		]);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($ch);       
		
		if($output === false)
		{
		    throw new \Exception( 'Ошибка curl: ' . curl_error($ch));
		}

		curl_close($ch);
		
		return json_decode($output, true);
	}
}
