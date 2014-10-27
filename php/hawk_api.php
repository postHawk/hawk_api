<?php

namespace hawk_api;

require_once 'lib/hawk_transport.php';

class hawk_api
{
	private $key = null;
	/**
	 *
	 * @var object hawk_transport_socket | hawk_transport_curl
	 */
	private $transport;
	/**
	 * конструктор
	 * @param string $key API ключ
	 */
	public function __construct($key) 
	{
		$this->key = $key;
		$this->transport = hawk_transport::get_transport();
	}

	/**
	 * регистрация пользователя в системе
	 * @param string $id идентификатор пользователя
	 * @return string
	 */
	public function register_user($id)
	{
		if($this->check_id($id))
		{
			return $this->transport->send(array(
				'key' => $this->key,
				'id' => $id,
			), 'register_user');
		}
	}

	/**
	 * удаление пользователя из системы
	 * @param string $id идентификатор пользователя
	 * @return string
	 */
	public function unregister_user($id)
	{
		if($this->check_id($id))
		{
			return $this->transport->send(array(
				'key' => $this->key,
				'id' => $id,
			), 'unregister_user');
		}
	}

	/**
	 * добавление пользователя в группы
	 * создание новых групп происходит автоматически
	 * @param array $groups группы
	 * @return string
	 */
	public function add_user_to_group($id, array $groups)
	{
		if($this->check_id($id) && $this->check_group($groups))
		{
			return $this->transport->send(array(
				'key' => $this->key,
				'id' => $id,
				'groups' => $groups,
			), 'add_in_groups');
		}
	}

	/**
	 * удаление пользователя из группы
	 * @param array $groups группы
	 * @return string
	 */
	public function remove_user_from_group($id, array $groups)
	{
		if($this->check_id($id) && $this->check_group($groups))
		{
			return $this->transport->send(array(
				'key' => $this->key,
				'id' => $id,
				'groups' => $groups,
			), 'remove_from_groups');
		}
	}

	/**
	 * получение списка пользователей в группе или группах
	 * @param array $groups
	 * @return string JSON
	 */
	public function get_user_by_group(array $groups)
	{
		if($this->check_group($groups))
		{
			return $this->transport->send(array(
				'key' => $this->key,
				'groups' => $groups,
			), 'get_by_group');
		}
	}

	/**
	 * отправка сообщения пользователям группы / групп
	 * @param string $from id пользователя от которого происходит рассылка
	 * @param string $text текст сообщения
	 * @param array $groups группы куда послать сообщения
	 * @param mixed $time время в любом формате
	 * @return string
	 */
	public function seng_group_message($from, $text, array $groups, $time = false)
	{
		if($this->check_id($from) && $this->check_group($groups))
		{
			return $this->transport->send(array(
				'key' => $this->key,
				'from' => $from,
				'time' => $time,
				'text' => $text,
				'groups' => $groups,
			), 'send_group_message');
		}
	}

	/**
	 * проверка идентификатора пользователя
	 * @param string $id идентификатор
	 * @return boolean
	 */
	protected function check_id($id)
	{
		if (preg_match('/^[a-zA-Z\d]{3,64}$/u', $id))
		{
			return true;
		}

		throw new \Exception('Неверный формат идентификатора пользователя');
	}

	private function check_group($groups)
	{
		foreach ($groups as $group)
		{
			if(!$this->check_id($group))
			{
				throw new \Exception('Неверный формат идентификатора группы');
			}
		}

		return true;
	}

	/**
	 * деструктор
	 */
	public function __destruct()
	{
		;
	}
}