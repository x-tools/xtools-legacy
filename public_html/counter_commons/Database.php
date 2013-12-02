<?php

/*
Soxred93's Edit Counter
Copyright (C) 2010 Soxred93

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <//www.gnu.org/licenses/>.
*/

class Database {
	/**
     * MySQL object
     * @var object
     */
	private $mConn;
	
	/**
     * Read-only mode
     * @var bool
     */
	private $mReadonly;
	
	private $mHost;
	private $mPort;
	private $mUser;
	private $mPass;
	private $mDb;
	
	/**
     * Construct function, front-end for mysql_connect.
     * @param string $host Server to connect to
     * @param string $port Port
     * @param string $user Username
     * @param string $pass Password
     * @param string $db Database
     * @param bool readonly Read-only mode. Default false
     * @return void
     */
	public function __construct( $host, $port, $user, $pass, $db, $readonly = false ) {
		$this->mHost = $host;
		$this->mPort = $port;
		$this->mUser = $user;
		$this->mPass = $pass;
		$this->mDb = $db;
		$this->mReadonly = $readonly;
		
		$this->connectToServer();
	}
	
	private function connectToServer( $force = false ) {
		$this->mConn = mysql_connect( $this->mHost.':'.$this->mPort, $this->mUser, $this->mPass, $force );
		mysql_select_db( $this->mDb, $this->mConn );
	} 
	
	/**
	 * Destruct function, front-end for mysql_close.
     * @return void
     */
	public function __destruct() {
		mysql_close( $this->mConn );
	}
	
	/**
     * Front-end for mysql_query. It's preferred to not use this function, 
     * and rather the other Database::select, update, insert, and delete functions.
     * @param string $sql Raw DB query
     * @return object|bool MySQL object, false if there's no result
     */
	public function doQuery( $sql ) {
		$sql = trim($sql);
		//echo "MySQL: $sql;\n";
		$result = mysql_query( $sql, $this->mConn );
		//var_dump($result);
		if( mysql_errno( $this->mConn ) == 2006 ) {
			$this->connectToServer( true );
			$result = mysql_query( $sql, $this->mConn );
		}
		
		if( !$result ) return $this->errorStr();
		return $result;
	}
	
	/**
     * Front-end for mysql_error
     * @return string|bool MySQL error string, null if no error
     */
	public function errorStr() {
		global $fnc, $phptemp;
		$result = mysql_error( $this->mConn );
		if( !$result ) return false;
		
		if( $result == 'Query execution was interrupted' ) {
			$fnc->toDie( $phptemp->getConf( 'interrupted', $result ) );
		}
		else {
			$fnc->toDie( $phptemp->getConf( 'mysqlerror', $result ) );
		}
	}
	
	/**
     * Front-end for mysql_real_escape_string
     * @param string $data Data to escape
     * @return string Escaped data
     */
	public function mysqlEscape( $data ) {
		return mysql_real_escape_string( $data, $this->mConn );
	}
	
	/**
     * Shortcut for converting a MySQL result object to a plain array
     * @param object $data MySQL result
     * @return array Converted result
     * @static
     */
	public static function mysql2array( $data ) {

		$return = array();
		while( $row = mysql_fetch_assoc( $data ) ) {
			$return[] = $row;
			unset($row);
		}

		return $return;
	}
	
	/**
     * SELECT frontend
     * @param array|string $table Table(s) to select from. If it is an array, the tables will be JOINed.
     * @param string|array $fields Columns to return
     * @param string|array $where Conditions for the WHERE part of the query. Default null.
     * @param array $options Options to add, can be GROUP BY, HAVING, and/or ORDER BY. Default an empty array.
     * @param array $join_on If selecting from more than one table, this adds an ON statement to the query. Defualt an empty array.
     * @return object MySQL object
     */
	public function select ( $table, $fields, $where = null, $options = array(), $join_on = array() ) {
		if( is_array( $fields ) ) {
            $fields = implode( ',', $fields );
        }
        
        if( !is_array( $options ) ) {
            $options = array( $options );
        }
        
		if( is_array( $table ) ) {
			if( count( $join_on ) == 0 ) {
				$from = 'FROM ' . implode( ',', $table );
				$on = null;
			}
			else {
				$tmp = array_shift( $table );
				$from = 'FROM ' . $tmp;
				$from .= ' JOIN ' . implode( ' JOIN ', $table );
				
				$tmp = array_keys( $join_on );
				$on = 'ON ' . $tmp[0] . ' = ' . $join_on[$tmp[0]];
			}
		}
		else {
			$from = 'FROM ' . $table;
			$on = null;
		}
		
		$newoptions = null;
		$slowok = null;
		if ( isset( $options['GROUP BY'] ) ) $newoptions .= "GROUP BY {$options['GROUP BY']}";
        if ( isset( $options['HAVING'] ) ) $newoptions .= "HAVING {$options['HAVING']}";
        if ( isset( $options['ORDER BY'] ) ) $newoptions .= "ORDER BY {$options['ORDER BY']}";
        if ( isset( $options['SLOW OK'] ) ) $slowok = "/* SLOW_OK */";
        if ( isset( $options['RUN_LIMIT'] ) ) $slowok .= " /* LIMIT:{$options['RUN_LIMIT']} */";
		
		if( !is_null( $where ) ) {
			if( is_array( $where ) ) {
				$where_tmp = array();
				foreach( $where as $wopt ) {
					$tmp = $this->mysqlEscape( $wopt[2] );
					if( $wopt[1] == 'LIKE' ) $tmp = $wopt[2];
					$where_tmp[] = '`' . $wopt[0] . '` ' . $wopt[1] . ' \'' . $tmp . '\'';					
				}
				$where = implode( ' AND ', $where_tmp );
			}
			$sql = "SELECT $slowok $fields $from $on WHERE $where $newoptions";
		}
		else {
			$sql = "SELECT $slowok $fields $from $on $newoptions";
		}
		
		if (isset($options['LIMIT'])) {
			$sql .= " LIMIT {$options['LIMIT']}";
		}
		        
        if (isset($options['EXPLAIN'])) {
            $sql = 'EXPLAIN ' . $sql;
        }
        
        //echo $sql;
        return $this->doQuery( $sql );
	}
	
	/**
     * INSERT frontend
     * @param string $table Table to insert into.
     * @param array $values Values to set.
     * @param array $options Options
     * @return object MySQL object
     */
	public function insert( $table, $values, $options = array() ) {
		echo "Running insert.";
		if( $this->mReadonly == true ) throw new Exception( "Write query called while under read-only mode" );
		if ( !count( $values ) ) {
            return true;
        }
        
        if ( !is_array( $options ) ) {
            $options = array( $options );
        }
        
        $cols = array();
        $vals = array();
        foreach( $values as $col => $value ) {
        	$cols[] = "`$col`";
        	$vals[] = "'" . $this->mysqlEscape( $value ) . "'";
        }
        
        $cols = implode( ',', $cols );
        $vals = implode( ',', $vals );
        
        $sql = "INSERT " . implode( ' ', $options ) . " INTO $table ($cols) VALUES ($vals)";
        echo $sql;
        return (bool)$this->doQuery( $sql );
	}
	
	/**
     * UPDATE frontend
     * @param string $table Table to update.
     * @param array $values Values to set.
     * @param array $conds Conditions to update. Default *, updates every entry.
     * @return object MySQL object
     */
	public function update( $table, $values, $conds = '*' ) { 
		if( $this->mReadonly == true ) throw new Exception( "Write query called while under read-only mode" );
        $vals = array();
        foreach( $values as $col => $val ) {
        	$vals[] = "`$col`" . "= '" . $this->mysqlEscape( $val ) . "'";
        }
        $vals = implode( ', ', $vals );
        
        $sql = "UPDATE $table SET " . $vals;
        if ( $conds != '*' ) {
        	$cnds = array();
		    foreach( $conds as $col => $val ) {
		    	$cnds[] = "`$col`" . "= '" . $this->mysqlEscape( $val ) . "'";
		    }
		    $cnds = implode( ', ', $cnds );
		    
            $sql .= " WHERE " . $cnds;
        }
        return $this->doQuery( $sql );
    }
	
	/**
     * DELETE frontend
     * @param string $table Table to delete from.
     * @param array $conds Conditions to delete. Default *, deletes every entry.
     * @return object MySQL object
     */
	public function delete( $table, $conds ) {
        if( $this->mReadonly == true ) throw new Exception( "Write query called while under read-only mode" );
        $sql = "DELETE FROM $table";
        if ( $conds != '*' ) {
        	$cnds = array();
		    foreach( $conds as $col => $val ) {
		    	$cnds[] = "`$col`" . "= '" . $this->mysqlEscape( $val ) . "'";
		    }
		    $cnds = implode( ' AND ', $cnds );
		    
            $sql .= " WHERE " . $cnds;
        }
        return $this->doQuery( $sql );
    }	
}
