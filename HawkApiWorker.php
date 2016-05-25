<?php
namespace Hawk\Api;
use Hawk\Api\Encryption\Crypt;
use Hawk\Api\Transport\HawkTransportCurl;
use Hawk\Api\Transport\HawTransportSocket;

/**
 * Класс выполняющий непосредственные запросы к апи
 *
 * @author Maximilian
 */
class HawkApiWorker
{
	const ACCESS_ALL	 = 'all';
	const ACCESS_PUBLIC	 = 'public';
	const ACCESS_PRIVATE = 'private';

	/**
	 * ключ апи
	 * @var string
	 */
	private $key = null;

	/**
	 *
	 * @var HawkTransportCurl | HawTransportSocket
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
	private $encryption_type = Crypt::TYPE_AES256;

	/**
	 * Последняя возникшая ошибка
	 * @var array
	 */
	private $last_error	 = '';

	/**
	 * спиоск возможных типов групп
	 * @var array
	 */
	private $accesses = [
		self::ACCESS_PUBLIC,
		self::ACCESS_PRIVATE,
		self::ACCESS_ALL,
	];


	public function __construct($transport, $key)
	{
		$this->setTransport($transport);
		$this->setKey($key);
	}

	function getTransport()
	{
		return $this->transport;
	}

	function setTransport($transport)
	{
		$this->transport = $transport;
		return $this;
	}

	function getKey()
	{
		return $this->key;
	}

	function setKey($key)
	{
		$this->key = $key;
		return $this;
	}


	public function registerUser($id)
	{
		if ($this->checkId($id))
		{
			return $this->transport->send(array(
				'key'	 => $this->key,
				'id'	 => $id,
			), 'register_user');
		}

		return false;
	}

	public function unregisterUser($id)
	{
		if ($this->checkId($id))
		{
			return $this->transport->send(array(
				'key'	 => $this->key,
				'id'	 => $id,
			), 'unregister_user');
		}

		return false;
	}

	public function addUserToGroup($id, array $groups, array $on_domains = array())
	{
		if (!count($on_domains))
		{
			$on_domains[] = $_SERVER['HTTP_HOST'];
		}

		if ($this->checkId($id) && $this->checkGroup($groups) && $this->checkDomains($on_domains))
		{
			return $this->transport->send(array(
					'key'		 => $this->key,
					'id'		 => $id,
					'groups'	 => array_values($groups),
					'domains'	 => $on_domains,
					), 'add_in_groups');
		}

		return false;
	}

	public function removeUserFromGroup($id, array $groups, array $on_domains = array())
	{
		if (!count($on_domains))
		{
			$on_domains[] = $_SERVER['HTTP_HOST'];
		}

		if ($this->checkId($id) && $this->checkGroup($groups) && $this->checkDomains($on_domains))
		{
			return $this->transport->send(array(
				'key'		 => $this->key,
				'id'		 => $id,
				'groups'	 => array_values($groups),
				'domains'	 => $on_domains,
			), 'remove_from_groups');
		}

		return false;
	}

	public function getUserByGroup(array $groups, array $on_domains = array())
	{
		if (!count($on_domains))
		{
			$on_domains[] = $_SERVER['HTTP_HOST'];
		}

		if ($this->checkGroup($groups) && $this->checkDomains($on_domains))
		{
			return $this->transport->send(array(
				'key'		 => $this->key,
				'groups'	 => array_values($groups),
				'domains'	 => $on_domains,
			), 'get_by_group');
		}

		return false;
	}

	public function getUserGroups($id, $acc, $on_domains)
	{
		if (!count($on_domains))
		{
			$on_domains[] = $_SERVER['HTTP_HOST'];
		}

		if ($this->checkId($id) && $this->checkDomains($on_domains))
		{
			return $this->transport->send(array(
					'key'		 => $this->key,
					'login'	 => $id,
					'access' => $acc,
					'domains'	 => $on_domains,
					), 'get_group_by_simple_user');
		}

		return false;
	}

	public function sendMessage($from, $to, $text, $event, array $on_domains = array())
	{
		if (!count($on_domains))
		{
			$on_domains[] = $_SERVER['HTTP_HOST'];
		}

		if ($this->checkId($to) && $this->checkId($from) && $this->checkDomains($on_domains))
		{
			if($this->getEncryption())
			{
				$text = $this->getEncryptor()->encrypt($text);
			}

			return $this->transport->send(array(
				'key'		 => $this->key,
				'from'		 => $from,
				'to'		 => $to,
				'text'		 => $text,
				'event'		 => $event,
				'domains'	 => $on_domains,
			), 'send_message');
		}

		return false;
	}

	public function sendGroupMessage($from, $text, array $groups, $event, array $on_domains = array())
	{
		if (!count($on_domains))
		{
			$on_domains[] = $_SERVER['HTTP_HOST'];
		}

		if ($this->checkId($from) && $this->checkGroup($groups) && $this->checkDomains($on_domains))
		{

			if($this->getEncryption())
			{
				$text = $this->getEncryptor()->encrypt($text);
			}

			return $this->transport->send(array(
				'key'		 => $this->key,
				'from'		 => $from,
				'text'		 => $text,
				'groups'	 => array_values($groups),
				'event'		 => $event,
				'domains'	 => $on_domains,
			), 'send_group_message');
		}

		return false;
	}

	public function addGroups(array $groups, array $on_domains = array())
	{
		if (!count($on_domains))
		{
			$on_domains[] = $_SERVER['HTTP_HOST'];
		}

		if ($this->checkGroupAcc($groups) && $this->checkDomains($on_domains))
		{
			return $this->transport->send(array(
				'key'		 => $this->key,
				'groups'	 => array_values($groups),
				'domains'	 => $on_domains,
			), 'add_groups');
		}

		return false;
	}

	public function removeGroups(array $groups, array $on_domains = array())
	{
		if (!count($on_domains))
		{
			$on_domains[] = $_SERVER['HTTP_HOST'];
		}

		if ($this->checkGroup($groups) && $this->checkDomains($on_domains))
		{
			return $this->transport->send(array(
				'key'		 => $this->key,
				'groups'	 => array_values($groups),
				'domains'	 => $on_domains,
			), 'remove_groups');
		}

		return false;
	}

	public function getGroupList($type = self::ACCESS_ALL, array $on_domains = array())
	{
		if (!count($on_domains))
		{
			$on_domains[] = $_SERVER['HTTP_HOST'];
		}

		if ($this->checkDomains($on_domains) && $this->checkType($type))
		{
			return $this->transport->send(array(
				'key'		 => $this->key,
				'access'	 => $type,
				'domains'	 => $on_domains,
			), 'get_group_list');
		}

		return false;
	}

	public function getToken($id, $salt, $on_domains = array())
	{
		if (!count($on_domains))
		{
			$on_domains[] = $_SERVER['HTTP_HOST'];
		}

		if ($this->checkDomains($on_domains) && $this->checkId($id))
		{
			return $this->transport->send(array(
				'key'		 => $this->key,
				'login'	 => $id,
				'salt'	 => $salt,
				'domains'	 => $on_domains,
			), 'get_token');
		}

		return false;
	}

	public function isOnline($id, array $on_domains = array())
	{
		if (!count($on_domains))
		{
			$on_domains[] = $_SERVER['HTTP_HOST'];
		}

		if ($this->checkDomains($on_domains) && $this->checkId($id))
		{
			return $this->transport->send(array(
				'key'		 => $this->key,
				'id'	 => $id,
				'domains'	 => $on_domains,
			), 'is_online');
		}

		return false;
	}

	/**
	 * проверка идентификатора пользователя
	 * @param string $id идентификатор
	 * @return boolean
	 */
	private function checkId($id)
	{
		if (preg_match('/^[a-zA-Z\d_]{3,64}$/u', $id))
		{
			return true;
		}

		$this->last_error = 'Неверный формат идентификатора';
		return false;
	}

	/**
	 * Проверка группы
	 * @param array $groups названия групп
	 * @return boolean
	 */
	private function checkGroup($groups)
	{
		foreach ($groups as $group)
		{
			if (!$this->checkId($group))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Проверка группы с правами
	 * @param array $groups названия групп
	 * @return boolean
	 */
	private function checkGroupAcc($groups)
	{
		foreach ($groups as $group)
		{
			if (!$this->checkId($group['name']) || !$this->checkType($group['access']))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Проверка доменов
	 * @param array $domains домены
	 * @return boolean
	 */
	private function checkDomains($domains)
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
	 * @param string $type тип доступа
	 * @return boolean
	 */
	private function checkType($type)
	{
		if (!in_array($type, $this->accesses))
		{
			$this->last_error = 'Не верный параметр доступа: ' . $type;
			return false;
		}

		return true;
	}

	/**
	 * Включает/выключает шифрование
	 * @param boolean $use использовать ли шифрование
	 * @return HawkApiWorker
	 */
	public function setEncryption($use)
	{
		$this->encryption = $use;
		return $this;
	}

	/**
	 * Возвращает текущее состояние шифрования
	 * @return boolean
	 */
	public function getEncryption()
	{
		return $this->encryption;
	}

	/**
	 * Устанавливает тип шифрования
	 * @param string $type тип шифрования. Пока поддерживается только AES256
	 * @return HawkApiWorker
	 */
	public function setEncryptionType($type)
	{
		$this->encryption_type = $type;
		return $this;
	}

	/**
	 * Возвращает тип шифрования
	 * @return string
	 */
	public function getEncryptionType()
	{
		return $this->encryption_type;
	}

	/**
	 * устанавливает соль для шифрования
	 * @param string $salt соль
	 * @return HawkApiWorker
	 */
	public function setSalt($salt)
	{
		$this->getEncryptor()->setCryptKey($salt);
		return $this;
	}

	/**
	 * возвращает объект-шифровальщик
	 * @return object crypt_aes256
	 */
	private function getEncryptor()
	{
		if(is_null($this->encryptor))
		{
			$this->encryptor = Crypt::getEncryptor($this->getEncryptionType());
		}

		return $this->encryptor;
	}
}
