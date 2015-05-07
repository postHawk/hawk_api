<?php

namespace hawk_api;

require_once 'lib/hawk_transport.php';
require_once 'lib/crypt.php';

class hawk_api
{

	const ACCESS_ALL	 = 'all';
	const ACCESS_PUBLIC	 = 'public';
	const ACCESS_PRIVATE = 'private';

	/**
	 * ключ для шифрования запросов
	 * @var string
	 */
	private $key = null;

	/**
	 *
	 * @var object hawk_transport_socket | hawk_transport_curl
	 */
	private $transport;

	/**
	 *
	 * @var object crypt_aes256
	 */
	private $encryptor = null;

	/**
	 * состояние шифрования
	 * @var boolean
	 */
	private $encryption = false;

	/**
	 * тип шифрования
	 * @var string
	 */
	private $encryption_type = crypt::TYPE_AES256;

	/**
	 * спиоск возможных типов групп
	 * @var array
	 */
	private $accesses = [
		self::ACCESS_PUBLIC,
		self::ACCESS_PRIVATE,
		self::ACCESS_ALL,
	];

	/**
	 * текущий стек задач
	 * @var array
	 */
	private $stack		 = [];

	/**
	 * текущие ошибки
	 * @var array
	 */
	private $errors		 = [];

	/**
	 * Последняя возникшая ошибка
	 * @var array
	 */
	private $last_error	 = '';

	/**
	 * Массив результатов
	 * @var array
	 */
	private $results = [];


	/**
	 * конструктор
	 * @param string $key API ключ
	 * @param string $url адрес сервиса в формате http://url:port
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

		if($this->get_encryption() && !extension_loaded('openssl'))
		{
			throw new \Exception('Для использования функции шифрования сообщений необходимо активировать поддрежку openssl');
		}

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
	 * Отпрвка сообщения конкретному пользователю
	 * @param string $from от кого
	 * @param string $to кому
	 * @param mixed $text данные
	 * @param array $on_domains на какие домены
	 * @return string|boolean
	 */
	private function _send_message($from, $to, $text, array $on_domains = array())
	{
		if (!count($on_domains))
		{
			$on_domains[] = $_SERVER['HTTP_HOST'];
		}

		if ($this->check_id($to) && $this->check_id($from) && $this->check_domains($on_domains))
		{
			if($this->get_encryption())
			{
				$text = $this->get_encryptor()->encrypt($text);
			}

			return $this->transport->send(array(
					'key'		 => $this->key,
					'from'		 => $from,
					'to'		 => $to,
					'text'		 => $text,
					'domains'	 => $on_domains,
					), 'send_message');
		}

		return false;
	}

	/**
	 * отправка сообщения пользователям группы / групп
	 * @param string $from id пользователя от которого происходит рассылка
	 * @param string $text текст сообщения
	 * @param array $groups группы куда послать сообщения
	 * @return string|boolean
	 */
	private function _seng_group_message($from, $text, array $groups, array $on_domains = array())
	{
		if (!count($on_domains))
		{
			$on_domains[] = $_SERVER['HTTP_HOST'];
		}

		if ($this->check_id($from) && $this->check_group($groups) && $this->check_domains($on_domains))
		{

			if($this->get_encryption())
			{
				$text = $this->get_encryptor()->encrypt($text);
			}

			return $this->transport->send(array(
					'key'		 => $this->key,
					'from'		 => $from,
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

	/**
	 * Проверка допустимости типа группы
	 * @param string $type
	 * @return boolean
	 */
	private function check_type($type)
	{
		if (!in_array($type, $this->accesses))
		{
			$this->last_error = 'Не верный параметр доступа: ' . $type;
			return false;
		}

		return true;
	}

	/**
	 * Проверка наличия ошибок выполнения
	 * @return boolean
	 */
	public function has_errors()
	{
		return !empty($this->errors);
	}

	/**
	 * Получение ошибок выполнения
	 * @return array
	 */
	public function get_errors()
	{
		return $this->errors;
	}

	/**
	 * Добавить ошибку
	 * @param string $method метод, сгенерировавший ошибку
	 * @param string $text тексе ошибки
	 */
	private function set_error($method, $text)
	{
		$this->errors = [$method => $text];
	}

	/**
	 * Возвращает результат выполнения запросов
	 * @return array
	 */
	public function get_results()
	{
		return $this->results;
	}

	/**
	 * Возвращает результат для одного метода
	 * @param string $method название метода
	 * @return array
	 */
	public function get_result($method)
	{
		$result = [];
		foreach ($this->results as $call)
		{
			$m = key($call);
			if ($m == $method)
			{
				$result[] = $call[$method];
			}
		}
		return $result;
	}

	/**
	 * Включает/выключает шифрование
	 * @param boolean $use
	 * @return \hawk_api\hawk_api
	 */
	public function set_encryption($use)
	{
		$this->encryption = $use;
		return $this;
	}

	/**
	 * Возвращает текущее состояние шифрования
	 * @return boolean
	 */
	public function get_encryption()
	{
		return $this->encryption;
	}

	/**
	 * Устанавливает тип шифрования
	 * @param string $type
	 * @return \hawk_api\hawk_api
	 */
	public function set_encryption_type($type)
	{
		$this->encryption_type = $type;
		return $this;
	}

	/**
	 * DВозвращает тип шифрования
	 * @return type
	 */
	public function get_encryption_type()
	{
		return $this->encryption_type;
	}

	/**
	 * устанавливает соль для шифрования
	 * @param type $salt
	 * @return \hawk_api\hawk_api
	 */
	public function set_salt($salt)
	{
		$this->get_encryptor()->set_crypt_key($salt);
		return $this;
	}

	/**
	 * возвращает объект-шифровальщик
	 * @return object crypt_aes256
	 */
	private function get_encryptor()
	{
		if(is_null($this->encryptor))
		{
			$this->encryptor = crypt::get_encryptor($this->get_encryption_type());
		}

		return $this->encryptor;
	}

	/**
	 * очищает текущее состояние
	 */
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
		
	}

}
