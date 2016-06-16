<?php

namespace Hawk\Api;

use Hawk\Api\Transport\HawkTransport;
use Hawk\Api\Transport\HawkTransportCurl;
use Hawk\Api\Transport\HawTransportSocket;

/**
 * Класс предоставляющий api для обращения к
 * сервису Post Hawk
 *
 * @author Maxim Barulin <mbarulin@gmail.com>
 */
class HawkApi
{
	const ACCESS_ALL	 = 'all';
	const ACCESS_PUBLIC	 = 'public';
	const ACCESS_PRIVATE = 'private';

	/**
	 *
	 * @var HawkApiWorker
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
	 * @var String
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
		HawkTransport::setUrl($url);
		$this->worker = new HawkApiWorker(
			HawkTransport::getTransport(),
			$key
		);

		$this->sessionStart();
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

		if($this->worker->getEncryption() && !extension_loaded('openssl'))
		{
			throw new \Exception('Для использования функции шифрования сообщений необходимо активировать поддрежку openssl');
		}

		foreach ($this->stack as $key => $call)
		{
			$method = key($call);
			$parmas = current($call);
			if (!$this->hasErrors())
			{
				$result = call_user_func_array([$this->worker, $method], $parmas);

				if ($result === false)
				{
					$this->setError($method, $this->worker->getLastError());
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
						$this->setError($method, $result['error']);
					}
				}
			}

			unset($this->stack[$key]);
		}

		return $this;
	}

	/**
	 * регистрация пользователя в системе
	 * @param string $id идентификатор пользователя
	 * @return HawkApi
	 */
	public function registerUser($id)
	{
		$this->addStack(__FUNCTION__, [$id]);
		return $this;
	}

	/**
	 * удаление пользователя из системы
	 * @param string $id идентификатор пользователя
	 * @return HawkApi
	 */
	public function unregisterUser($id)
	{
		$this->addStack(__FUNCTION__, [$id]);
		return $this;
	}

	/**
	 * Добавление пользователя в группы
	 * создание новых групп происходит автоматически.
	 * Группа создаётся с публичным доступом
	 * @param string $id id пользователя
	 * @param array $groups группы
	 * @param array $on_domains домены
	 * @return HawkApi
	 */
	public function addUserToGroup($id, array $groups, array $on_domains = array())
	{
		$this->addStack(__FUNCTION__, [$id, $groups, $on_domains]);
		return $this;
	}

	/**
	 * Удаление пользователя из группы.
	 * Пустые группы удаляются автоматически.
	 * @param string $id id пользователя
	 * @param array $groups группы
	 * @param array $on_domains домены
	 * @return HawkApi
	 */
	public function removeUserFromGroup($id, array $groups, array $on_domains = array())
	{
		$this->addStack(__FUNCTION__, [$id, $groups, $on_domains]);
		return $this;
	}

	/**
	 * получение списка пользователей в группе или группах
	 * @param array $groups группы
	 * @param array $on_domains домены
	 * @return HawkApi
	 */
	public function getUserByGroup(array $groups, array $on_domains = array())
	{
		$this->addStack(__FUNCTION__, [$groups, $on_domains]);
		return $this;
	}

	/**
	 * Получение списка групп зарегистрированного пользователя
	 * @param string $id пользователь
	 * @param string $acc уровень доступа
	 * @param array $on_domains домены
	 * @return HawkApi
	 */
	public function getUserGroups($id, $acc = self::ACCESS_ALL, array $on_domains = array())
	{
		$this->addStack(__FUNCTION__, [$id, $acc, $on_domains]);
		return $this;
	}

	/**
	 * Отпрвка сообщения конкретному пользователю
	 * @param string $from от кого
	 * @param string $to кому
	 * @param mixed $text данные
	 * @param string $event событие
	 * @param array $on_domains на какие домены
	 * @return HawkApi
	 */
	public function sendMessage($from, $to, $text, $event = 'sendMessage', array $on_domains = array())
	{
		$this->addStack(__FUNCTION__, [$from, $to, $text, $event, $on_domains]);
		return $this;
	}

	/**
	 * отправка сообщения пользователям группы / групп
	 * @param string $from id пользователя от которого происходит рассылка
	 * @param string $text текст сообщения
	 * @param array $groups группы куда послать сообщения
	 * @param string $event событие
	 * @param array $on_domains на какие домены
	 * @return HawkApi
	 */
	public function sendGroupMessage($from, $text, array $groups, $event = 'sendGroupMessage', array $on_domains = array())
	{
		$this->addStack(__FUNCTION__, [$from, $text, $groups, $event, $on_domains]);
		return $this;
	}

	/**
	 * Добавляет группу
	 * @param array $groups массив названий групп
	 * @param array $on_domains на какие домены
	 * @return HawkApi
	 */
	public function addGroups(array $groups, array $on_domains = array())
	{
		$this->addStack(__FUNCTION__, [$groups, $on_domains]);
		return $this;
	}

	/**
	 * Удаляет группу
	 * @param array $groups массив названий групп
	 * @param array $on_domains на какие домены
	 * @return HawkApi
	 */
	public function removeGroups(array $groups, array $on_domains = array())
	{
		$this->addStack(__FUNCTION__, [$groups, $on_domains]);
		return $this;
	}

	/**
	 * Получение списка групп
	 * @param string $type тип группы (все, открытая или закрытая)
	 * @param array $on_domains на какие домены
	 * @return HawkApi
	 * @throws \Exception
	 */
	public function getGroupList($type = self::ACCESS_ALL, array $on_domains = array())
	{
		$this->addStack(__FUNCTION__, [$type, $on_domains]);
		return $this;
	}

	/**
	 * Возвращает токен для авторизации пользователя
	 *
	 * @param string $id id пользователя
	 * @param string $salt соль для шифрования
	 * @param array $on_domains на какие домены
	 * @return HawkApi
	 */
	public function getToken($id, $salt, array $on_domains = array())
	{
		$this->addStack(__FUNCTION__, [$id, $salt, $on_domains]);
		return $this;
	}
	
	public function isOnline($id, array $on_domains = array())
	{
		$this->addStack(__FUNCTION__, [$id, $on_domains]);
		return $this;
	}

	/**
	 * Добавляет метод в очередь выполнения
	 * @param string $method название метода
	 * @param array $params параметры
	 */
	private function addStack($method, $params)
	{
		$this->stack[] = [$method => $params];
	}

	/**
	 * Проверка наличия ошибок выполнения
	 * @return boolean
	 */
	public function hasErrors()
	{
		return !empty($this->errors);
	}

	/**
	 * Получение ошибок выполнения
	 * @return array
	 */
	public function getErrors()
	{
		return $this->errors;
	}

	/**
	 * Добавить ошибку
	 * @param string $method метод, сгенерировавший ошибку
	 * @param string $text тексе ошибки
	 */
	private function setError($method, $text)
	{
		$this->errors = [$method => $text];
	}

	/**
	 * Возвращает результат выполнения запросов
	 * @return array
	 */
	public function getResults()
	{
		return $this->results;
	}

	/**
	 * Возвращает результат для одного метода
	 * @param string $method название метода
	 * @return array
	 */
	public function getResult($method)
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

	/**
	 * Запускает сессию
	 */
	private function sessionStart()
	{
		if(session_status() !== PHP_SESSION_ACTIVE)
		{
			session_start();
		}
	}

	/**
	 * @return String
	 */
	public function getLastError()
	{
		return $this->last_error;
	}

	/**
	 * деструктор
	 */
	public function __destruct()
	{

	}

}
