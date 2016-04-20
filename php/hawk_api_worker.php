<?php
namespace hawk_api;

require_once 'lib/transport/hawk_transport.php';
require_once 'lib/encryption/crypt.php';
/**
 * Класс выполняющий непосредственные запросы к апи
 *
 * @author Maximilian
 */
class hawk_api_worker
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


	public function register_user($id)
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

	public function unregister_user($id)
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

	public function add_user_to_group($id, array $groups, array $on_domains = array())
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

	public function remove_user_from_group($id, array $groups, array $on_domains = array())
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

	public function get_user_by_group(array $groups, array $on_domains = array())
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

	public function get_user_groups($id, $acc, $on_domains)
	{
		if (!count($on_domains))
		{
			$on_domains[] = $_SERVER['HTTP_HOST'];
		}

		if ($this->check_group($groups) && $this->check_domains($on_domains))
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

	public function send_message($from, $to, $text, array $on_domains = array())
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

	public function seng_group_message($from, $text, array $groups, array $on_domains = array())
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

	public function add_groups(array $groups, array $on_domains = array())
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

	public function remove_groups(array $groups, array $on_domains = array())
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

	public function get_group_list($type = self::ACCESS_ALL, array $on_domains = array())
	{
		if (!count($on_domains))
		{
			$on_domains[] = $_SERVER['HTTP_HOST'];
		}

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

	public function get_token($id, $salt, $on_domains = array())
	{
		if (!count($on_domains))
		{
			$on_domains[] = $_SERVER['HTTP_HOST'];
		}

		if ($this->check_domains($on_domains) && $this->check_id($id))
		{
			return $this->transport->send(array(
				'key'		 => $this->key,
				'login'	 => $id,
				'salt'	 => $salt,
				'domains'	 => $on_domains,
			), 'get_token');
		}
	}

	/**
	 * проверка идентификатора пользователя
	 * @param string $id идентификатор
	 * @return boolean
	 */
	private function check_id($id)
	{
		if (preg_match('/^[a-zA-Z\d\_]{3,64}$/u', $id))
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
	 * @param array $groups названия групп
	 * @return boolean
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
	 * @param array $domains домены
	 * @return boolean
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
	 * @param string $type тип доступа
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
	 * Включает/выключает шифрование
	 * @param boolean $use использовать ли шифрование
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
	 * @param string $type тип шифрования. Пока поддерживается только AES256
	 * @return \hawk_api\hawk_api5
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
	 * @param type $salt соль
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
}
