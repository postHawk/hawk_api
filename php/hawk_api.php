<?php

namespace hawk_api;

require_once 'lib/hawk_transport.php';

class hawk_api
{

	const ACCESS_ALL		 = 'all';
	const ACCESS_PUBLIC	 = 'public';
	const ACCESS_PRIVATE	 = 'private';

	private $key = null;

	/**
	 *
	 * @var object hawk_transport_socket | hawk_transport_curl
	 */
	private $transport;
	private $accesses = [
		self::ACCESS_PUBLIC,
		self::ACCESS_PRIVATE,
		self::ACCESS_ALL,
	];
	private $stack		 = [];
	private $errors		 = [];
	private $last_error	 = '';
	private $results = [];

	/**
	 * конструктор
	 * @param string $key API ключ
	 */
	public function __construct($key, $url)
	{
		$this->key		 = $key;
		hawk_transport::set_url($url);
		$this->transport = hawk_transport::get_transport();
	}

	public function __call($name, $args)
	{
		$this->addStack($name, $args);
		return $this;
	}

	/**
	 * Запускает выполнение методов
	 */
	public function execute()
	{
		$this->clear();
		foreach ($this->stack as $call)
		{

			$method = key($call);
			$parmas = current($call);
			if (!$this->has_errors())
			{
				$result = call_user_func_array([$this, ("_" . $method)], $parmas);
				if ($result === false)
				{
					$this->set_error($method, $this->last_error);
				}
				else
				{
					$this->results[] = [$method => $result];
				}
			}

			unset($this->stack[$method]);
		}
	}

	/**
	 * регистрация пользователя в системе
	 * @param string $id идентификатор пользователя
	 * @return string
	 */
	private function _register_user($id)
	{
		if ($this->check_id($id))
		{
			return $this->transport->send(array(
					'key'	 => $this->key,
					'id'	 => $id,
					), 'register_user');
		}

		return false;
	}

	/**
	 * удаление пользователя из системы
	 * @param string $id идентификатор пользователя
	 * @return string
	 */
	private function _unregister_user($id)
	{
		if ($this->check_id($id))
		{
			return $this->transport->send(array(
					'key'	 => $this->key,
					'id'	 => $id,
					), 'unregister_user');
		}

		return false;
	}

	/**
	 * Добавление пользователя в группы
	 * создание новых групп происходит автоматически.
	 * Группа создаётся с публичным доступом
	 * @param array $groups группы
	 * @return string
	 */
	private function _add_user_to_group($id, array $groups, array $on_domains = array())
	{
		if (!count($on_domains))
		{
			$on_domains[] = $_SERVER['HTTP_HOST'];
		}

		if ($this->check_id($id) && $this->check_group($groups) && $this->check_domains($on_domains))
		{
			return $this->transport->send(array(
					'key'		 => $this->key,
					'id'		 => $id,
					'groups'	 => $groups,
					'domains'	 => $on_domains,
					), 'add_in_groups');
		}

		return false;
	}

	/**
	 * Удаление пользователя из группы.
	 * Пустые группы удаляются автоматически.
	 * @param array $groups группы
	 * @return string
	 */
	private function _remove_user_from_group($id, array $groups, array $on_domains = array())
	{
		if (!count($on_domains))
		{
			$on_domains[] = $_SERVER['HTTP_HOST'];
		}

		if ($this->check_id($id) && $this->check_group($groups) && $this->check_domains($on_domains))
		{
			return $this->transport->send(array(
					'key'		 => $this->key,
					'id'		 => $id,
					'groups'	 => $groups,
					'domains'	 => $on_domains,
					), 'remove_from_groups');
		}

		return false;
	}

	/**
	 * получение списка пользователей в группе или группах
	 * @param array $groups
	 * @return string JSON
	 */
	private function _get_user_by_group(array $groups, array $on_domains = array())
	{
		if (!count($on_domains))
		{
			$on_domains[] = $_SERVER['HTTP_HOST'];
		}

		if ($this->check_group($groups) && $this->check_domains($on_domains))
		{
			return $this->transport->send(array(
					'key'		 => $this->key,
					'groups'	 => $groups,
					'domains'	 => $on_domains,
					), 'get_by_group');
		}

		return false;
	}

	/**
	 * отправка сообщения пользователям группы / групп
	 * @param string $from id пользователя от которого происходит рассылка
	 * @param string $text текст сообщения
	 * @param array $groups группы куда послать сообщения
	 * @param mixed $time время в любом формате
	 * @return string
	 */
	private function _seng_group_message($from, $text, array $groups, array $on_domains = array(), $time = false)
	{
		if (!count($on_domains))
		{
			$on_domains[] = $_SERVER['HTTP_HOST'];
		}

		if ($this->check_id($from) && $this->check_group($groups) && $this->check_domains($on_domains))
		{
			return $this->transport->send(array(
					'key'		 => $this->key,
					'from'		 => $from,
					'time'		 => $time,
					'text'		 => $text,
					'groups'	 => $groups,
					'domains'	 => $on_domains,
					), 'send_group_message');
		}

		return false;
	}

	/**
	 * Добавляет группу
	 * @param array $groups
	 * @param array $on_domains
	 * @return string
	 */
	private function _add_groups(array $groups, array $on_domains = array())
	{
		if (!count($on_domains))
		{
			$on_domains[] = $_SERVER['HTTP_HOST'];
		}

		if ($this->check_group_acc($groups) && $this->check_domains($on_domains))
		{
			return $this->transport->send(array(
					'key'		 => $this->key,
					'groups'	 => $groups,
					'domains'	 => $on_domains,
					), 'add_groups');
		}

		return false;
	}

	/**
	 * Удаляет группу
	 * @param array $groups
	 * @param array $on_domains
	 * @return string
	 */
	private function _remove_groups(array $groups, array $on_domains = array())
	{
		if (!count($on_domains))
		{
			$on_domains[] = $_SERVER['HTTP_HOST'];
		}

		if ($this->check_group($groups) && $this->check_domains($on_domains))
		{
			return $this->transport->send(array(
					'key'		 => $this->key,
					'groups'	 => $groups,
					'domains'	 => $on_domains,
					), 'remove_groups');
		}
	}

	/**
	 * Получение списка групп
	 * @param type $type
	 * @param array $on_domains
	 * @return JSON
	 * @throws \Exception
	 */
	private function _get_group_list($type = self::ACCESS_ALL, array $on_domains = array())
	{
		if (!count($on_domains))
		{
			$on_domains[] = $_SERVER['HTTP_HOST'];
		}

		$this->check_domains($on_domains);
		

		if ($this->check_domains($on_domains) && $this->check_type($type))
		{
			return $this->transport->send(array(
					'key'		 => $this->key,
					'access'	 => $type,
					'domains'	 => $on_domains,
					), 'get_group_list');
		}

		return false;
	}

	/**
	 * Добавляет метод в очередь выполнения
	 * @param type $method
	 * @param type $params
	 */
	private function addStack($method, $params)
	{
		$this->stack[] = [$method => $params];
	}

	/**
	 * проверка идентификатора пользователя
	 * @param string $id идентификатор
	 * @return boolean
	 */
	private function check_id($id)
	{
		if (preg_match('/^[a-zA-Z\d]{3,64}$/u', $id))
		{
			return true;
		}

		$this->last_error = 'Неверный формат идентификатора';
		return false;
	}

	/**
	 * Проверка группы
	 * @param array $groups
	 * @return boolean
	 */
	private function check_group($groups)
	{
		foreach ($groups as $group)
		{
			if (!$this->check_id($group))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Проверка группы с правами
	 * @param array $groups
	 * @throws \Exception
	 */
	private function check_group_acc($groups)
	{
		foreach ($groups as $group)
		{
			if (!$this->check_id($group['name']) || !$this->check_type($group['access']))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Проверка доменов
	 * @param type $domains
	 * @return boolean
	 * @throws \Exception
	 */
	private function check_domains($domains)
	{
		foreach ($domains as $domain)
		{
			if (!preg_match('/^([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,6}$/', $domain))
			{
				$this->last_error = 'Неверный формат домена';
				return false;
			}
		}

		return true;
	}

	private function check_type($type)
	{
		if (!in_array($type, $this->accesses))
		{
			$this->last_error = 'Не верный параметр доступа: ' . $type;
			return false;
		}

		return true;
	}

	public function has_errors()
	{
		return !empty($this->errors);
	}

	public function get_errors()
	{
		return $this->errors;
	}

	private function set_error($method, $text)
	{
		$this->errors = [$method => $text];
	}

	public function get_results()
	{
		return $this->results;
	}

	public function get_result($method)
	{
		$result = [];
		foreach ($this->results as $call)
		{
			$m = key($call);
			if ($m == $method)
			{
				$result[] = $call;
			}
		}
		return $result;
	}

	private function clear()
	{
		$this->errors		 = [];
		$this->last_error	 = '';
		$this->results		 = [];
	}

	/**
	 * деструктор
	 */
	public function __destruct()
	{
		;
	}

}
