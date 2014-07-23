<?php
namespace hawk_api;

interface i_hawk_transport
{
	/**
	 * отправка сообщения
	 * @param array $data сообщение
	 * @param string $type тип сообщения
	 * @return type
	 */
	public function send($data, $type);
}
