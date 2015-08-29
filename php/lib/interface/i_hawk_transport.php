<?php
namespace hawk_api;

/**
 * Интерфейс , который необходимо реализовать 
 * для создания новых типов транспорта
 * 
 * @author Maxim Barulin <mbarulin@gmail.com>
 */
interface i_hawk_transport
{
	/**
	 * отправка сообщения
	 * @param array $data сообщение
	 * @param string $type тип сообщения
	 * @return mixed
	 */
	public function send($data, $type);
}
