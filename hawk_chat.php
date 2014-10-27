<?php
require_once 'php/hawk_api.php';
require_once 'php/lib/db/db.php';


/**
 * Description of hawk_chat
 *
 * @author maximilian
 */
class hawk_chat extends hawk_api\hawk_api
{
	private $user = array();
	private $group_id = null;
	private $result = array(
		'result' => null,
		'error' => null,
	);

	const ERROR_ACTION = 'invalid_action';
	const ERROR_LOGIN = 'invalid_login';


	public function __construct($key)
	{
		parent::__construct($key);
		$this->group_id = md5('hawk_chat');
		((isset($_SESSION['hawk_user']['setted'])) ? $this->get_user() : $this->set_user());
	}
	
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
	
	private function set_user()
	{
		$id = md5(session_id());
		$_SESSION['hawk_user']['setted'] = 1;
		$_SESSION['hawk_user']['id'] = $id;
		$_SESSION['hawk_user']['login'] = '';
		$this->user = $_SESSION['hawk_user'];
		$res = $this->register_user($id);
		if($res != 'ok')
		{
			session_destroy();
			throw new Exception('Ошибка регистрации пользователя');
		}
		$res = $this->add_user_to_group($id, array($this->group_id));
		
		if($res != 'ok')
		{
			session_destroy();
			throw new Exception('Ошибка добавления пользователя в группу');
		}
		
	}
	
	public function get_user()
	{
		if(!$this->user)
		{
			$this->_get_user();
		}
		
		return $this->user;
	}
	
	private function _get_user()
	{
		$this->user = $_SESSION['hawk_user'];
	}
	
	public function get_online()
	{
		$list = json_decode($this->get_user_by_group(array($this->group_id)));
		$db_list = db::getInstance()->to_query('select * from user_to_login');

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
	
	public static function session_start()
	{
		if(session_status() != PHP_SESSION_ACTIVE)
		{
			session_start();
		}
	}
	
	public function get_group_id()
	{
		return $this->group_id;
	}
	
	private function check_login($login)
	{
		if(!preg_match('/^[a-zA-Z0-9\_\-]{1,32}$/iu', $login))
		{
			return false;
		}
		
		return true;
	}

}