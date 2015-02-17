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
	public function __construct($key, $url)
	{
		$this->key = $key;
		hawk_transport::set_url($url);
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
	public function add_user_to_group($id, array $groups, array $on_domains = array())
	{
		if(!count($on_domains))
		{
			$on_domains[] = $_SERVER['HTTP_HOST'];
		}

		$this->check_id($id);
		$this->check_group($groups);
		$this->check_domains($on_domains);

		return $this->transport->send(array(
			'key' => $this->key,
			'id' => $id,
			'groups' => $groups,
			'domains' => $on_domains,
		), 'add_in_groups');
	}

	/**
	 * удаление пользователя из группы
	 * @param array $groups группы
	 * @return string
	 */
	public function remove_user_from_group($id, array $groups, array $on_domains = array())
	{
		if(!count($on_domains))
		{
			$on_domains[] = $_SERVER['HTTP_HOST'];
		}

		$this->check_id($id);
		$this->check_group($groups);
		$this->check_domains($on_domains);

		return $this->transport->send(array(
			'key' => $this->key,
			'id' => $id,
			'groups' => $groups,
			'domains' => $on_domains,
		), 'remove_from_groups');

	}

	/**
	 * получение списка пользователей в группе или группах
	 * @param array $groups
	 * @return string JSON
	 */
	public function get_user_by_group(array $groups, array $on_domains = array())
	{
		if(!count($on_domains))
		{
			$on_domains[] = $_SERVER['HTTP_HOST'];
		}

		$this->check_group($groups);
		$this->check_domains($on_domains);
		
		return $this->transport->send(array(
			'key' => $this->key,
			'groups' => $groups,
			'domains' => $on_domains,
		), 'get_by_group');
	}

	/**
	 * отправка сообщения пользователям группы / групп
	 * @param string $from id пользователя от которого происходит рассылка
	 * @param string $text текст сообщения
	 * @param array $groups группы куда послать сообщения
	 * @param mixed $time время в любом формате
	 * @return string
	 */
	public function seng_group_message($from, $text, array $groups, array $on_domains = array(), $time = false)
	{
		if(!count($on_domains))
		{
			$on_domains[] = $_SERVER['HTTP_HOST'];
		}

		$this->check_id($from);
		$this->check_group($groups);
		$this->check_domains($on_domains);

		return $this->transport->send(array(
			'key' => $this->key,
			'from' => $from,
			'time' => $time,
			'text' => $text,
			'groups' => $groups,
			'domains' => $on_domains,
		), 'send_group_message');
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
			$this->check_id($group);
		}

		return true;
	}
	
	private function check_domains($domains)
	{
		foreach ($domains as $domain)
		{
			if(!preg_match('/^([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,6}$/', $domain))
			{
				throw new \Exception('Неверный формат домена');
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