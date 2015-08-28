<?php

	/**
	 * Класс базы данных.
	 *
	 * @abstract
	 * @author Автор: Барулин Максим
	 * @version 1.0
	 * @copyright: Барулин Максим
	 */
	abstract class db
	{
		/**
		 * Логин для бд
		 * @var string
		 */
		protected static $loginDB = 'test';

		/**
		 * Пароль бд
		 * @var string
		 */
		protected static $passwordDB = 'test';

		/**
		 * Имя бд
		 * @var type
		 */
		protected static $nameBD = 'test';

		/**
		 * Хост базы данных
		 * @var string
		 */
		protected static $hostBD = 'localhost';

		/**
		 * Порт базы данных
		 * @var int
		 */
		protected static $portBD = '3306';

		/**
		 * Содержит чесло записей, которые вернет запрос без использования limit
		 * @var type
		 */
		public static $count_record;

		/**
		 * Поле для объекта pdo
		 * @var pdo
		 */
		protected static $db_mysql_pdo;

		/**
		 * Конструктор
		 */
		public function __construct()
		{
			
		}

		private function __clone()
		{
		}

		private function __wakeup()
		{
		}

		/**
		 * Возвращает текущий объект бд
		 * @return db
		 */
		public static function getInstance($bd = 'mysql_pdo')
		{
			$name = 'db_' . $bd;
			$class = '\\'. $name;

			if( is_null( self::$$name ) )
			{
				if(!class_exists($class))
				{
					require_once $name . '.php';
				}
				self::$$name = new $class;
			}

			return self::$$name->getObject();
		}
		
		/**
		 * ф-я возвращает объект класса потомка
		 * @abstract
		 */
		abstract public function getObject();

		/**
		 * ф-я выполняющая запрос к бд
		 * @abstract
		 * @param string $sql запрос
		 * @param array $params параметры для запроса
		 * @param string $res_type тип возвращаемого значения
		 */
		abstract public function to_query($sql, $params = array(), $res_type = '');

		/**
		 * ф-я возвращает последнее значение автоинкриментального счетчика
		 * @abstract
		 * @param string $seq имя секвенции
		 */
		abstract public function lastInsertId($seq = false);
	}
