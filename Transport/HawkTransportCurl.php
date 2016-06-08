<?php
namespace Hawk\Api\Transport;

/**
 * Класс реалзизующий отправку сообщений путём http запросов
 * 
 * @author Maxim Barulin <mbarulin@gmail.com>
 */
class HawkTransportCurl extends HawkTransport implements IHawkTransport
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
	 * @return array
	 * @throws \Exception
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
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);

		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			('Origin: ' . $_SERVER['HTTP_HOST']),
			'Transport: curl',
		]);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($ch);       
		
		if($output === false)
		{
			return [
				'error' => 'Ошибка curl: ' . curl_error($ch)
			];
		}

		curl_close($ch);
		
		return json_decode($output, true);
	}
}
