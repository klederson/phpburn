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
class PhpBURN_Connection_Mongo implements IConnection
{

	const CLOSED					= 100201;
	const OPEN					= 100202;

	const SERVER_VERSION				= 10;
	const CLIENT_VERSION				= 11;
	const HOST_INFO					= 12;
	const PROTOCOL_VERSION                          = 13;
	const RANDOM_FUNCTION                           = 'rand()';
	
	const ESCAPE_CHAR				= '\\';
	
	protected $_event_types = array(
		'preExecute','posExecute','preConnect','onConnectSucess','preClose','posClose',
		'onExecuteError','onConnectError'
	);
	
	private $conn_id;
	private $database;
	private $user;
	private $password;
	private $port = 27017;
	private $host = "locahost";
	private $options = array();
	private $state;
	
	public $mode = NULL;
	
	private static $instance = null;
	
	static public function getInstance()
	{
		if(self::$instance == null)
		{
			self::$instance = new PhpBURN_Connection_Mongo();
		}
		
		return self::$instance;
	}

        private function getConnPrefix() {
            if(isset($this->user) && isset($this->password))
                return sprintf("%s:%s@",$this->user, $this->password);
        }

        private function getConnSulfix() {
            if(isset($this->port) && isset($this->database)) {
                return sprintf(":%s/%s", $this->port, $this->database);
            } else if(isset($this->database)) {
                return sprintf("/%s", $this->database);
            }
        }
	
	public function connect()
	{
		
		if($this->conn_id && $this->state == self::OPEN) {
                    $this->conn_id->selectDB($this->getDatabase());
                    return true;
		}
		
		//TODO preConnect actions should be called from here		

                //Build the Connection String Prefix ( username:password@ )
                $connPrefix = $this->getConnPrefix();

                //Build the Connection String Host
                $hostString = $this->getHost();

                //Build the Connection String Sulfix ( :port/database or /database ) and select database
                $connSulfix = $this->getConnSulfix();

                $connString = sprintf("mongodb://%s%s%s",$connPrefix,$hostString,$connSulfix);

                $this->conn_id = new Mongo($connString);
		
		if( !$this->conn_id )
		{
                    $this->state = self::CLOSED;
                    $msg = '[!Database connection error!]: ' . $this->getDatabase().' - '.$this->getErrorMsg();

                    PhpBURN_Message::output($msg, PhpBURN_Message::ERROR);
                    return false;
		} else {
                    $this->state = self::OPEN;
                }
		
		//TODO onConnectSucess actions should be called from here
		
		return true;
	}
	
	public function close()
	{
//		@TODO preCloseEvents
		if($this->conn_id && $this->state != self::CLOSED) {
			$this->state = self::CLOSED;
			$this->conn_id->close();
		}
//		@TODO postCloseEvents
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
		
		return $this->conn_id->__toString();
	}
	
	public function getTables()
	{
		if( !$this->connect() )	{
                    return false;
		}
		
		$rs = $this->conn_id->listCollections();
		
		$list = array();
		
		foreach($rs as $collection) {
                    $list[] = $collection->getName();
                }

                unset($rs);                
		return $list;
	}

        /**
        * Get Current References ( From ActiveMongo by @crodas )
        *
        * Inspect the current document trying to get any references,
        * if any.
        *
        * @param array $document Current document
        * @param array &$refs References found in the document.
        * @param array $parent_key Parent key
        *
        * @return void
        */
        final protected function getDocumentReferences($document, &$refs, $parent_key=NULL) {
            foreach ($document as $key => $value) {
               if (is_array($value)) {
                   if (MongoDBRef::isRef($value)) {
                       $pkey = $parent_key;
                       $pkey[] = $key;
                       $refs[] = array('ref' => $value, 'key' => $pkey);
                   } else {
                       $parent_key1 = $parent_key;
                       $parent_key1[] = $key;
                       $this->getDocumentReferences($value, $refs, $parent_key1);
                   }
               }
            }
        }
	
	public function getForeignKeys($tablename) {
            if( ! $this->connect() ) {
                return false;
            }

            foreach($this->conn_id->listCollections() as $document) {
                
            }



//
//
//		$fks = array();
//                $sql = sprintf("SHOW CREATE TABLE `%s`",$tablename);
//		$rs = $this->executeSQL($sql);
//		$result = mysql_fetch_row($rs);
//		$result[0] = preg_replace("(\r|\n)",'\n', $result[0]);
//
//                $matches = array();
//
//		preg_match_all('@FOREIGN KEY \(`([a-z,A-Z,0-9,_]+)`\) REFERENCES (`([a-z,A-Z,0-9,_]+)`\.)?`([a-z,A-Z,0-9\.,_]+)` \(`([a-z,A-Z,0-9,_]+)`\)(.*?)(\r|\n|\,)@i', $result[1], $matches);
//
//                for($i=0; $i<count($matches[0]); $i++)
//		{
//                    $name = $matches[4][$i];
//                    if(isset($fks[ $name ]))
//                    {
//                            $name = $name . '_' . $matches[4][$i];
//                    }
//
//                    $fks[ $name ]['thisColumn'] = $matches[1][$i];
//                    $fks[ $name ]['thisTable'] = $tablename;
//                    $fks[ $name ]['toDatabase'] = !empty($matches[2][$i]) ? preg_replace("([`\.])", '', $matches[2][$i]) : $this->database;
//                    $fks[ $name ]['references'] = !empty($matches[3][$i]) ? $matches[3][$i].'.'.$matches[4][$i] : $matches[4][$i];
//                    $fks[ $name ]['referencedTable'] = $matches[4][$i];
//                    $fks[ $name ]['referencedColumn'] = $matches[5][$i];
//
//
//                    $reg = array();
//                    if(preg_match('@(.*?)ON UPDATE (RESTRICT|CASCADE)@i', $matches[6][$i], $reg))
//                    {
//                            $fks[ $name ]['onUpdate'] = strtoupper($reg[2]);
//                    } else {
//                            $fks[ $name ]['onUpdate'] = 'RESTRICT';
//                    }
//                    if(preg_match('@(.*?)ON DELETE (RESTRICT|CASCADE)@i', $matches[6][$i], $reg))
//                    {
//                            $fks[ $name ]['onDelete'] = strtoupper($reg[2]);
//                    } else {
//                            $fks[ $name ]['onDelete'] = 'RESTRICT';
//                    }
//
//		}
//		return $fks;
	}
	
	public function getServerInfo($type = null)
	{
		if($this->conn_id && $this->state == self::OPEN)
		{
			switch($type)
			{
				case self::CLIENT_VERSION:
					return mysql_get_client_info();
					break;
				case self::HOST_INFO:
					return mysql_get_host_info($this->conn_id);
					break;
				case self::PROTOCOL_VERSION:
					return mysql_get_proto_info($this->conn_id);
					break;
				case self::SERVER_VERSION:
				default:
					return mysql_get_server_info($this->conn_id);
					break;
			}
			return '';
			
		} 
		//throw new PhPBURN_Exception() TODO CREATE EXCETION CLASS AND INPUT AN EXCEPTION HERE;
	}
	
	public function describe($tablename)
	{
            $sql = sprintf("DESCRIBE `%s`", $tablename);
            $rs = $this->executeSQL( $sql );

            $data = array();
            while($row = mysql_fetch_row($rs))
            {
                $name           = $row[0];
                $type_native    = $row[1];
                if(preg_match('@(\w+)\((\d+)\)@', $row[1], $r))
                {
                    $type       = $r[1];
                    $length     = $r[2];
                } else {
                    $type       = $row[1];
                    $length     = null;
                }

                switch( strtolower($type) )
                {
                    case 'tinyblob': $length = 255; break;
                    case 'tinytext': $length = 255; break;
                    case 'blob': $length = 65535; break;
                    case 'text': $length = 65535; break;
                    case 'mediumblob': $length = 16777215; break;
                    case 'mediumtext': $length = 16777215; break;
                    case 'longblob': $length = 4294967295; break;
                    case 'longtext': $length = 4294967295; break;
                    case 'enum': $length = 65535; break;
                }

                $notnull        = $row[2] == 'YES' ? false : true;
                $primary        = $row[3] == 'PRI' ? true : false;
                $default        = $row[4] == 'NULL' ? null : $row[4];
                $autoincrement  = $row[5] == 'auto_increment' ? true : false;

                $data[] = array($name, $type_native, $type, $length, $primary, $notnull, $default, $autoincrement);
            }
            return $data;
	}
	
	public function executeSQL($sql)
	{
		//$this->dispatchEvent('preExecute', $this, $sql);
		$this->connect();
//		$rs = @mysql_query($sql, $this->conn_id);
//		if( ! $rs )
//		{
//			$msg = "[!Database error:!] " . $this->getErrorMsg();
//			PhpBURN_Message::output($msg, PhpBURN_Message::ERROR);
//			return false;
//			//$this->dispatchEvent('onExecuteError', $this, $sql, $msg);
//		}
//		//$this->close();
//		//$this->dispatchEvent('posExecute', $this, $sql);
//		return $rs;
	}
	
	public function escape($str) 
	{
		if($this->state == self::OPEN)
		{
			return mysql_real_escape_string($str, $this->conn_id);
		} else {
			return mysql_escape_string($str);
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
			return true;
		}
		//throw new PhpBURN_Exception() TODO CREATE EXCETION CLASS AND INPUT AN EXCEPTION HERE;
	}
	
	public function num_rows($rs)
	{
		return mysql_num_rows($rs);
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
		return mysql_fetch_array($rs, $this->mode);
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
//		return mysql_insert_id($this->conn_id);
	}
	
	public function __destruct() {
		self::close();
	}
}


?>
