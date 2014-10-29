<?php
require_once 'php/hawk_api.php';
require_once 'php/lib/db/db.php';


/**
 * Простейший класс чата
 *
 * @author mbarulin@gmail.com
 */
class hawk_chat extends hawk_api\hawk_api
{
	/**
	 * текущий юзер
	 * @var array
	 */
	private $user = array();
	/**
	 * id группы
	 * @var string
	 */
	private $group_id = null;
	/**
	 * результирующий массив для ответа
	 * @var array
	 */
	private $result = array(
		'result' => null,
		'error' => null,
	);

	const ERROR_ACTION = 'invalid_action';
	const ERROR_LOGIN = 'invalid_login';

	/**
	 * конструктор
	 * @param string $key ключ апи
	 */
	public function __construct($key)
	{
		parent::__construct($key);
		$this->set_group_id(md5('hawk_chat'));
		
		((isset($_SESSION['hawk_user']['setted'])) ? $this->get_user() : $this->set_user());
	}

	/**
	 * метод для работы с ajax запросами
	 * @param string $action
	 */
	public function ajax($action)
	{
		ob_clean();
		switch ($action)
		{
			case 'set_login':
				$this->set_login();
				break;
			case 'get_online':
				$this->get_online();
				break;

			default:
				$this->result['error'] = self::ERROR_ACTION;
				break;
		}

		echo json_encode($this->result);
		exit();
	}

	/**
	 * метод регистрации пользователя в сервисе
	 * @return boolean
	 * @throws Exception
	 */
	private function set_user()
	{
		$id = md5(session_id());

		//регистрируем пользователя в сервисе
		$res = $this->register_user($id);
		if($res != 'ok')
		{
			session_destroy();
			throw new Exception('Ошибка регистрации пользователя: ' . $res);
		}

		//добавляем пользователя в группу
		$res = $this->add_user_to_group($id, array($this->get_group_id()));
		if($res != 'ok')
		{
			session_destroy();
			throw new Exception('Ошибка добавления пользователя в группу: ' . $res);
		}

		$_SESSION['hawk_user']['setted'] = 1;
		$_SESSION['hawk_user']['id'] = $id;
		$_SESSION['hawk_user']['login'] = '';
		$this->_get_user();

		return true;
	}

	/**
	 * метод получения текущего пользователя
	 * @return array
	 */
	public function get_user()
	{
		if(!$this->user)
		{
			$this->_get_user();
		}
		
		return $this->user;
	}

	/**
	 * метод установки текущего пользователя
	 */
	private function _get_user()
	{
		$this->user = $_SESSION['hawk_user'];
	}

	/**
	 * метод получения пользователей по группе
	 * так как группа у нас одна, то параметров не имеет
	 */
	public function get_online()
	{
		//получаем пользователей из сервиса
		$list = json_decode($this->get_user_by_group(array($this->get_group_id())));
		$db_list = db::getInstance()->to_query('select * from user_to_login');

		//так как сервис вернёт нам id пользователей, то преобразуем их в логины
		//понятно, что для больших таблиц такой метод не подходит
		//сделано для демонстрации работы со списком пользователей из сервиса
		foreach ($list as $key => $value)
		{
			foreach ($db_list as $num => $rec)
			{
				if($rec['USER_ID'] == $value->user)
				{
					$list[$key]->login = $rec['LOGIN'];
					unset($db_list[$num]);
					break;
				}
			}
		}

		$this->result['result'] = $list;
	}

	/**
	 * фиксирует логин текущего пользователя чата
	 */
	private function set_login()
	{
		if($this->check_login($_POST['login']))
		{
			$_SESSION['hawk_user']['login'] = $_POST['login'];
			$this->result['result'] = 1;
			
			$sql = 'INSERT INTO user_to_login(user_id, login) VALUES (:user, :login) ON DUPLICATE KEY UPDATE login=VALUES(login)';
			db::getInstance()->to_query($sql, [
				'user'	 => $this->get_user()['id'],
				'login'	 => $_POST['login']
			]);
		}
		else
		{
			$this->result['error'] = self::ERROR_LOGIN;
		}
	}

	/**
	 * метод запуска сессии
	 */
	public static function session_start()
	{
		if(session_status() != PHP_SESSION_ACTIVE)
		{
			session_start();
		}
	}

	/**
	 * метод возвращает текущий id группы
	 * @return string
	 */
	public function get_group_id()
	{
		return $this->group_id;
	}

	/**
	 * метод устанавливает текущий id группы
	 * @param string $id id группы
	 */
	public function set_group_id($id)
	{
		$this->group_id = $id;
	}



	/**
	 * метод проверки логина пользователя
	 * @param string $login логин для проверки
	 * @return boolean
	 */
	private function check_login($login)
	{
		if(!preg_match('/^[a-zA-Z0-9\_\-]{1,32}$/iu', $login))
		{
			return false;
		}
		
		return true;
	}

}