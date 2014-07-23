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

		throw new \Exception('Неверный формат идентификатора');
	}

	/**
	 * деструктор
	 */
	public function __destruct()
	{
		;
	}
}