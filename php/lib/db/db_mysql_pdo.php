<?php

	/**
	 * Класс базы данных mysql.
	 *
	 * реализован через pdo
	 *
	 * @abstract
	 * @author Автор: Барулин Максим
	 * @version 1.0
	 * @copyright: Барулин Максим
	 */
	class db_mysql_pdo extends db
	{
		/**
		 * объект pdo
		 * @var pdo
		 */
		public $pdoDb;

		/**
		 * Конструктор
		 */
		public function __construct()
		{
			parent::__construct();

			try
			{
				$this -> pdoDb = new \PDO( 'mysql:host=' . db::$hostBD . ';dbname=' . db::$nameBD . ';port=' . db::$portBD, db::$loginDB, db::$passwordDB, array( \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES UTF8" ) );
				$this -> pdoDb -> setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
				$this -> pdoDb -> setAttribute( \PDO::ATTR_CASE, \PDO::CASE_UPPER );
				$this -> pdoDb -> setAttribute( \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, TRUE);
			}
			catch( Exception $exc )
			{
				echo $exc -> getTraceAsString();
				echo $exc -> getMessage();
				exit();
			}

		}

		/**
		 * ф-я возвращает текущий объект класса
		 * @return \CRsys\db_mysql_pdo
		 */
		public function & getObject()
		{
			return $this;
		}

		/**
		 * ф-я выполяет запрос к бд
		 * @param string $sql запрос
		 * @param array $params параметры запроса
		 * @param string $res_type тип возвращаемого значения
		 * @param int $for_navigation флаг подсчета количества строк
		 * @return mixed
		 */
		public function to_query($sql, $params = array(), $res_type = 'assoc', $for_navigation = false)
		{
			$res = $this ->connectDbPDO( $sql, $params, $res_type, $for_navigation);
			return $res;
		}

		/**
		 * Выполнение запрос через pdo
		 * @param string $command запрос
		 * @param array $pdoParam параметры запроса
		 * @param string $type_res тип возвращаемого значения
		 * @param int $for_navigation флаг подсчета количества строк
		 * @return mixed
		 */
		private function connectDbPDO( $command, $pdoParam, $type_res, $for_navigation )
		{
			if( is_array( $pdoParam ) )
			{
				$is_select = false;
				if( preg_match( '/^select/ui', $command ) || preg_match('/^\n[\s|select\r]*select/ui', $command) || preg_match( '/^show/ui', $command ) || preg_match( '/^describe/ui', $command ) )
				{
					if($for_navigation)
					{
						$command = preg_replace('/^select(.*)/', 'select SQL_CALC_FOUND_ROWS $1', $command);
					}

					$is_select = true;
				}

				$sth = $this -> pdoDb -> prepare( $command );
				try
				{
					$sth -> execute( $pdoParam );
				}
				catch( Exception $e )
				{
					echo 'Исключение при работе с бд: ';
					echo $e -> getMessage();
					exit();
				}

				if($is_select)
				{
					switch( $type_res )
					{
						case 'assoc':
							$res = $sth -> fetchAll( \PDO::FETCH_ASSOC );
							break;
						case 'num':
							$res = $sth -> fetchAll( \PDO::FETCH_NUM );
							break;
						default :
							$res = $sth -> fetchAll();
							break;
					}
					if($for_navigation)
					{
						parent::$count_record = $this->getCountRecord( );
					}
				}
				else
				{
					$res = true;
				}

				return $res;
			}
		}

		/**
		 * ф-я возвращает последнее значение автоинкриментального счетчика
		 * @param string $seq имя секвенции
		 */
		public function lastInsertId($seq = false)
		{
			return $this->pdoDb->lastInsertId();
		}

		/**
		 * ф-я заполняет число строк в результате последнего запроса
		 * @return type
		 */
		public function getCountRecord()
		{
			$count = $this -> pdoDb -> query( 'SELECT FOUND_ROWS()' )-> fetchAll( \PDO::FETCH_NUM );
			return $count[0][0];
		}
	}


