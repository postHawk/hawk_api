<?php
namespace Hawk\Api\Transport;

/**
 * Интерфейс , который необходимо реализовать 
 * для создания новых типов транспорта
 * 
 * @author Maxim Barulin <mbarulin@gmail.com>
 */
interface IHawkTransport
{
	/**
	 * отправка сообщения
	 * @param array $data сообщение
	 * @param string $type тип сообщения
	 * @return mixed
	 */
	public function send($data, $type);
}
