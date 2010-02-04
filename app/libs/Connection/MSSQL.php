<?php
PhpBURN::load('Connection.IConnection');
/**
 * This class is responsable for connect the application to the database and perform the queries in a driver-level
 * Than translate the results and resultSets to the application trought Dialect
 * 
 * 
 * This class has been adapted from Lumine 1.0
 * The original idea is from Hugo Ferreira da Silva in Lumine
 * 
 * @package PhpBURN
 * @subpackage Connection
 * 
 * It has been modified and implemented into PhpBURN by:
 * 
 * @author Klederson Bueno Bezerra da Silva
 *
 */
class PhpBURN_Connection_MSSQL implements IConnection
{

	const CLOSED							= 100201;
	const OPEN								= 100202;

	const SERVER_VERSION				= 10;
	const CLIENT_VERSION				= 11;
	const HOST_INFO						= 12;
	const PROTOCOL_VERSION			= 13;
	const RANDOM_FUNCTION			= 'rand()';
	
	const ESCAPE_CHAR					= '\\';
	
	protected $_event_types = array(
		'preExecute','posExecute','preConnect','onConnectSucess','preClose','posClose',
		'onExecuteError','onConnectError'
	);
	
	private $conn_id;
	private $database;
	private $user;
	private $password;
	private $port;
	private $host;
	private $options;
	private $state;
	
	public $mode = MSSQL_ASSOC;
	
	private static $instance = null;
	
	static public function getInstance()
	{
		if(self::$instance == null)
		{
			self::$instance = new PhpBURN_Connection_MSSQL();
		}
		
		return self::$instance;
	}
	
	public function connect()
	{
		
		if($this->conn_id && $this->state == self::OPEN)
		{
			mssql_select_db($this->getDatabase(), $this->conn_id);
			return true;
		}
		
		//TODO preConnect actions should be called from here
		
		$hostString = $this->getHost();
		if($this->getPort() != '') 
		{
			$hostString .=  ',' . $this->getPort();
		}
		if(isset($this->options['socket']) && $this->options['socket'] != '')
		{
			$hostString .= ',' . $this->options['socket'];
		}
		$flags = isset($this->options['flags']) ? $this->options['flags'] : null;
					
		if(isset($this->options['persistent']) && $this->options['persistent'] == true)
		{
			$this->conn_id = @mssql_pconnect($hostString, $this->getUser(), $this->getPassword(), $flags);
		} else {
			$this->conn_id = @mssql_connect($hostString, $this->getUser(), $this->getPassword(), $flags);
		}
		
		if( !$this->conn_id )
		{
			$this->state = self::CLOSED;
			$msg = '[!Database connection error!]: ' . $this->getDatabase().' - '.$this->getErrorMsg();
			
			PhpBURN_Message::output($msg, PhpBURN_Message::ERROR);			
			return false;
		}
		
		//Selecting database
		mssql_select_db($this->getDatabase(), $this->conn_id);
		$this->state = self::OPEN;
		
		//TODO onConnectSucess actions should be called from here
		
		return true;
	}
	
	public function close()
	{
		//$this->dispatchEvent('preClose', $this);
		if($this->conn_id && $this->state != self::CLOSED)
		{
			$this->state = self::CLOSED;
			mssql_close($this->conn_id);
		}
		//$this->dispatchEvent('posClose', $this);
	}
	
	public function getState()
	{
		return $this->state;
	}
	
	public function setDatabase($database)
	{
		$this->database = $database;
	}
	
	public function getDatabase()
	{
		return $this->database;
	}
	
	public function setUser($user)
	{
		$this->user = $user;
	}
	public function getUser()
	{
		return $this->user;
	}

	public function setPassword($password)
	{
		$this->password = $password;
	}
	public function getPassword()
	{
		return $this->password;
	}

	public function setPort($port)
	{
		$this->port = $port;
	}
	public function getPort()
	{
		return $this->port;
	}
	
	public function setHost($host)
	{
		$this->host = $host;
	}
	public function getHost()
	{
		return $this->host;
	}
	
	public function setOptions($options)
	{
		$this->options = $options;
	}
	
	public function getOptions()
	{
		return $this->options;
	}
	
	public function setOption($name, $val)
	{
		$this->options[ $name ] = $val;
	}
	
	public function getOption($name)
	{
		if(empty($this->options[$name]))
		{
			return null;
		}
		return $this->options[$name];
	}

	public function getErrorMsg()
	{
		$msg = '';
		if($this->conn_id) 
		{
//			$msg = mssql_error($this->conn_id);
			$msg = mssql_get_last_message();
		} else {
//			$msg = mssql_error();
			$msg = mssql_get_last_message();
		}
		return $msg;
	}
	
	public function getTables()
	{
		if( ! $this->connect() )
		{
			return false;
		}
		
		$rs = $this->executeSQL("show tables");
		
		$list = array();
		
		while($row = mssql_fetch_row($rs))
		{
			$list[] = $row[0];
		}
		return $list;
	}
	
	public function getForeignKeys($tablename)
	{
		PhpBURN_Message::output("[!This feature isn't avaliable for this database driver yet. Please be invited to help us to implement it. http://www.phpburn.com!]");
		return null;
	}
	
	public function getServerInfo($type = null)
	{
		if($this->conn_id && $this->state == self::OPEN)
		{
			PhpBURN_Message::output("[!This feature isn't avaliable for this database driver yet. Please be invited to help us to implement it. http://www.phpburn.com!]");
//			switch($type)
//			{
//				case self::CLIENT_VERSION:
//					return null;
//					break;
//				case self::HOST_INFO:
//					return null;
//					break;
//				case self::PROTOCOL_VERSION:
//					return null;
//					break;
//				case self::SERVER_VERSION:
//				default:
//					return null;
//					break;
//			}
			return null;
		} 
	}
	
	public function describe($tablename)
	{
		PhpBURN_Message::output("[!This feature isn't avaliable for this database driver yet. Please be invited to help us to implement it. http://www.phpburn.com!]");
		return null;
	}
	
	public function executeSQL($sql)
	{
		//$this->dispatchEvent('preExecute', $this, $sql);
		$this->connect();
		$sql = stripslashes(sprintf("USE [%s]; %s", $this->getDatabase(), $sql));		
//		PhpBURN_Message::output($sql);
		$rs = @mssql_query($sql, $this->conn_id);
		if( ! $rs )
		{	
			$msg = "[!Database error:!] " . $this->getErrorMsg();
			PhpBURN_Message::output($msg, PhpBURN_Message::ERROR);
			return false;
			//$this->dispatchEvent('onExecuteError', $this, $sql, $msg);
		} 
		//$this->close();
		//$this->dispatchEvent('posExecute', $this, $sql);
		return $rs;
	}
	
	public function escape($str) 
	{
		if($this->state == self::OPEN)
		{
			return $str;
		} else {
			return $str;
		}
	}
	
	public function escapeBlob($blob)
	{
		return $this->escape( $blob );
	}
	
	public function escapeBFile($bfile) {
		
	}
	
	public function affected_rows()
	{
		if($this->state == self::OPEN)
		{
			return mssql_rows_affected($this->conn_id);
		}
		//throw new PhpBURN_Exception() TODO CREATE EXCETION CLASS AND INPUT AN EXCEPTION HERE;
	}
	
	public function num_rows($rs)
	{
		return mssql_num_rows($rs);
	}
	
	public function random()
	{
		return self::RANDOM_FUNCTION;
	}
	
	public function getEscapeChar()
	{
		return self::ESCAPE_CHAR;
	}
	
	public function fetch($rs) {
		return mssql_fetch_array($rs, $this->mode);
	}
	
	//Transactions
	public function begin($transactionID=null)
	{
		$this->executeSQL("BEGIN");
	}
	public function commit($transactionID=null)
	{
		$this->executeSQL("COMMIT");
	}
	public function rollback($transactionID=null)
	{
		$this->executeSQL("ROLLBACK");
	}
	
	//Utils
	public function last_id() {
//		$query = sprintf("SELECT IDENT_CURRENT('%s') AS last_id", $tablename);
		$query = "SELECT SCOPE_IDENTITY()  AS last_id";

		$rs = $this->executeSQL($query);
		$result = $this->fetch($rs);
		unset($rs);
		
		return $result['last_id']; unset($result);
	}
	
	public function __destruct() {
		self::close();
	}
}


?>
