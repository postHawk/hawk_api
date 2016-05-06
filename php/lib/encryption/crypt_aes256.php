<?php
namespace hawk_api;

require_once __DIR__ . '/../interface/i_crypt.php';

/**
 * Класс реализующий aes256 шифрование
 * 
 * @author Maxim Barulin <mbarulin@gmail.com>
 */
class crypt_aes256 extends crypt implements i_crypt
{
	/**
	 * тип шифрования
	 */
	const AES256 = 'aes-256-cbc';

	/**
	 * Шифрование
	 * @param string $text текст для шифрования
	 * @return string
	 */
	public function encrypt($text)
	{
		if (!$text)
		{
			return false;
		}

		$u_salt = $this->getCryptKey();

		try
		{
			$salt	 = openssl_random_pseudo_bytes(8);
			$salted	 = '';
			$dx		 = '';
			while (strlen($salted) < 48)
			{
				$dx = md5($dx . $u_salt . $salt, true);
				$salted .= $dx;
			}

			$key			 = substr($salted, 0, 32);
			$iv				 = substr($salted, 32, 16);
			$encrypted_data	 = openssl_encrypt(json_encode($text), self::AES256, $key, true, $iv);
			$data			 = array("ct" => base64_encode($encrypted_data), "iv" => bin2hex($iv), "s" => bin2hex($salt));
		}
		catch (\Exception $exc)
		{
			echo $exc->getMessage();
			echo $exc->getTraceAsString();
			exit();
		}

		return json_encode($data);
	}

	/**
	 * Дешифрование
	 * @param string $jsonString текст для расшифровки
	 * @return string
	 */
	public function decrypt($jsonString)
	{
		if (!$jsonString)
		{
			return false;
		}

		$u_salt = $this->getCryptKey();

		$jsondata	 = json_decode($jsonString, true);
		$salt		 = hex2bin($jsondata["s"]);
		$ct			 = base64_decode($jsondata["ct"]);
		$iv			 = hex2bin($jsondata["iv"]);

		$concatedPassphrase = $u_salt . $salt;

		$md5	 = array();
		$md5[0]	 = md5($concatedPassphrase, true);
		$result	 = $md5[0];
		for ($i = 1; $i < 3; $i++)
		{
			$md5[$i] = md5($md5[$i - 1] . $concatedPassphrase, true);
			$result .= $md5[$i];
		}

		$key	 = substr($result, 0, 32);
		$data	 = openssl_decrypt($ct, self::AES256, $key, true, $iv);

		return json_decode($data, true);
	}
}
