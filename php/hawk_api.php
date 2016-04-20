<?php

namespace hawk_api;

require_once 'lib/transport/hawk_transport.php';
require_once 'lib/encryption/crypt.php';
require_once 'hawk_api_worker.php';

use hawk_api\hawk_api_worker;

/**
 * Класс предоставляющий api для обращения к
 * сервису Post Hawk
 *
 * @author Maxim Barulin <mbarulin@gmail.com>
 */
class hawk_api
{

	const ACCESS_ALL	 = 'all';
	const ACCESS_PUBLIC	 = 'public';
	const ACCESS_PRIVATE = 'private';

	/**
	 *
	 * @var \hawk_api\hawk_api_worker
	 */
	private $worker = null;

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
	public function __construct($key, $url = 'https://post-hawk.com:2222')
	{
		$this->key		 = $key;
		hawk_transport::set_url($url);
		$this->worker = new hawk_api_worker(
			hawk_transport::get_transport(),
			$key
		);

		$this->session_start();
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

		if($this->worker->get_encryption() && !extension_loaded('openssl'))
		{
			throw new \Exception('Для использования функции шифрования сообщений необходимо активировать поддрежку openssl');
		}

		foreach ($this->stack as $call)
		{
			$method = key($call);
			$parmas = current($call);
			if (!$this->has_errors())
			{
				$result = call_user_func_array([$this->worker, $method], $parmas);
				if ($result === false)
				{
					$this->set_error($method, $this->last_error);
				}
				else
				{
					if(!isset($result['error']) || $result['error'] === false)
					{
						$this->results[] = [$method => $result];
					}
					else
					{
						$this->last_error = $result['error'];
						$this->set_error($method, $result['error']);
					}
				}
			}

			unset($this->stack[$method]);
		}

		return $this;
	}

	/**
	 * регистрация пользователя в системе
	 * @param string $id идентификатор пользователя
	 * @return string
	 */
	public function register_user($id)
	{
		$this->addStack(__FUNCTION__, func_get_args());
		return $this;
	}

	/**
	 * удаление пользователя из системы
	 * @param string $id идентификатор пользователя
	 * @return string
	 */
	public function unregister_user($id)
	{
		$this->addStack(__FUNCTION__, func_get_args());
		return $this;
	}

	/**
	 * Добавление пользователя в группы
	 * создание новых групп происходит автоматически.
	 * Группа создаётся с публичным доступом
	 * @param string $id id пользователя
	 * @param array $groups группы
	 * @param array $on_domains домены
	 * @return string
	 */
	public function add_user_to_group($id, array $groups, array $on_domains = array())
	{
		$this->addStack(__FUNCTION__, func_get_args());
		return $this;
	}

	/**
	 * Удаление пользователя из группы.
	 * Пустые группы удаляются автоматически.
	 * @param string $id id пользователя
	 * @param array $groups группы
	 * @param array $on_domains домены
	 * @return string
	 */
	public function remove_user_from_group($id, array $groups, array $on_domains = array())
	{
		$this->addStack(__FUNCTION__, func_get_args());
		return $this;
	}

	/**
	 * получение списка пользователей в группе или группах
	 * @param array $groups группы
	 * @param array $on_domains домены
	 * @return string JSON
	 */
	public function get_user_by_group(array $groups, array $on_domains = array())
	{
		$this->addStack(__FUNCTION__, func_get_args());
		return $this;
	}

	/**
	 * Получение списка групп зарегистрированного пользователя
	 * @param string $id пользователь
	 * @param string $acc уровень доступа
	 * @param array $on_domains домены
	 * @return string JSON
	 */
	public function get_user_groups($id, $acc = self::ACCESS_ALL, array $on_domains = array())
	{
		$this->addStack(__FUNCTION__, func_get_args());
		return $this;
	}

	/**
	 * Отпрвка сообщения конкретному пользователю
	 * @param string $from от кого
	 * @param string $to кому
	 * @param mixed $text данные
	 * @param array $on_domains на какие домены
	 * @return string|boolean
	 */
	public function send_message($from, $to, $text, array $on_domains = array())
	{
		$this->addStack(__FUNCTION__, func_get_args());
		return $this;
	}

	/**
	 * отправка сообщения пользователям группы / групп
	 * @param string $from id пользователя от которого происходит рассылка
	 * @param string $text текст сообщения
	 * @param array $groups группы куда послать сообщения
	 * @param array $on_domains на какие домены
	 * @return string|boolean
	 */
	public function seng_group_message($from, $text, array $groups, array $on_domains = array())
	{
		$this->addStack(__FUNCTION__, func_get_args());
		return $this;
	}

	/**
	 * Добавляет группу
	 * @param array $groups массив названий групп
	 * @param array $on_domains на какие домены
	 * @return string
	 */
	public function add_groups(array $groups, array $on_domains = array())
	{
		$this->addStack(__FUNCTION__, func_get_args());
		return $this;
	}

	/**
	 * Удаляет группу
	 * @param array $groups массив названий групп
	 * @param array $on_domains на какие домены
	 * @return string
	 */
	public function remove_groups(array $groups, array $on_domains = array())
	{
		$this->addStack(__FUNCTION__, func_get_args());
		return $this;
	}

	/**
	 * Получение списка групп
	 * @param type $type тип группы (все, открытая или закрытая)
	 * @param array $on_domains на какие домены
	 * @return JSON
	 * @throws \Exception
	 */
	public function get_group_list($type = self::ACCESS_ALL, array $on_domains = array())
	{
		$this->addStack(__FUNCTION__, func_get_args());
		return $this;
	}

	/**
	 * Возвращает токен для авторизации пользователя
	 *
	 * @param type $id
	 * @param type $salt
	 * @param type $on_domains
	 * @return \hawk_api\hawk_api
	 */
	public function get_token($id, $salt, $on_domains = array())
	{
		$this->addStack(__FUNCTION__, func_get_args());
		return $this;
	}

	/**
	 * Добавляет метод в очередь выполнения
	 * @param type $method название метода
	 * @param type $params параметры
	 */
	private function addStack($method, $params)
	{
		$this->stack[] = [$method => $params];
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
	 * очищает текущее состояние
	 */
	private function clear()
	{
		$this->errors		 = [];
		$this->last_error	 = '';
		$this->results		 = [];
	}

	/*
	 * Запускает сессию
	 */
	private function session_start()
	{
		if(session_status() !== PHP_SESSION_ACTIVE)
		{
			session_start();
		}
	}

	/**
	 * деструктор
	 */
	public function __destruct()
	{

	}

}
