<?php

# Exceptions required
require_once("exceptions.php");
require_once("databaseQuery.php");

/**
 * @abstract Advanced database functions which may be used in other classes
 * Use class for specified database to access databases
 */
abstract class advancedDatabase {

	/**
	 * Constants
	 */
    const DATE_SQL 		= "Y-m-d";
    const DATETIME_SQL 	= "Y-m-d H:i:s";

	/**
	 * Available join types
	 * @var array
	 */
	protected $joinTypes		= array("left", "right", "inner");

	/**
	 * Parameters types
	 * @var array
	 */
	protected $parametersTypes	= array("group"  => "multi", 
										"order"  => "multi", 
										"join"	 => "multi", 
										"records"=> "multi",
										"offset" => "int", 
										"limit"  => "int");

	/**
	 * Creates where condition based on parameters
	 * @param array $conditions Conditions parameters
	 * @param bool  $mainTable	Holds table for which query is performed
	 * @return string
	 */
	protected function makeWhere($conditions, $mainTable = false) {
	
		# If no conditions
		if(!$conditions)
			return "";
	
		
		# Compiled expression
		$expression	= "WHERE ".$this->makeWhereGroup($conditions, $mainTable);
		
		
		# Return
		return $expression;
		
	}

	/**
	 * Creates conditions from group of conditions data
	 * @param Array       $conditions   group data
	 * @param bool|string $mainTable    Holds table for which query is performed
	 * @return string Condition string
	 */
	protected function makeWhereGroup($conditions, $mainTable) {
	
		
		# Result
		$result = "";
		
		
		# Go through conditions
		foreach($conditions as $condition) {
	
			
			# Correction
			$condition = $this->correctCondition($condition, $mainTable);
			
			
			# Useless logic
			if($result === "")
				$condition['logic'] = '';
			
			
			# Compile string
			if($condition["type"] === "group") {
				
				# If empty
				if(empty($condition['conditions']))
					continue;
				
				# Make expression
				$result .= " ".$condition['logic']." (".$this->makeWhereGroup($condition['conditions'], $mainTable).")";
				
			}
			else
				$result .= " ".$condition['logic']." ".$condition['name']." ".$condition['compare'] ." ".$condition['value'];
			
			
		}
		
		
		# Return
		return $result;
		
	}
	
	/**
	 * Adds special corrections to condition
	 * @param Array $condition Condition data
	 * @param bool|string $mainTable    Holds table for which query is performed
	 * @return string
	 */
	protected function correctCondition($condition, $mainTable = false) {


		# Make big
		if(isset($condition['logic']))
			$condition['logic'] = strtoupper($condition['logic']);
		
		
		# Group
		if(($condition["type"]) === "group")
			return $condition;
		
		
		# Special functions
		if(!empty($condition['function'])) {
			switch($condition['function']) { 
				case "noQuotes": break;
				case "iNetAtoN": {
					$condition['value'] = "INET_ATON(".$this->addDashes($condition['value']).")";
					break;
				}
				case "now": {
					$condition['value'] = "NOW()";
					break; 
				}
				default: $condition['value'] = $condition['function']."(".$this->addDashes($condition['value']).")";
			}
			$condition['function'] = "noQuotes";
		}
		
		
		# Dashes
		$condition['name'] = $this->addBackDashes($condition['name'], $mainTable);
		if(!is_array($condition['value']) && (empty($condition['function']) || $condition['function'] != "noQuotes"))
			$condition['value'] = $this->addDashes($condition['value']);


		# Correction
		if($condition['type'] !== "set") {
			if	  (is_null($condition['value']) && $condition['compare'] === "=" )	$condition['compare'] = "IS";
			elseif(is_null($condition['value']) && $condition['compare'] === "!=")	$condition['compare'] = "IS NOT";
			elseif(is_array($condition['value'])) {
				$condition['compare'] = "IN";
				foreach($condition['value'] as $key => $value) $condition['value'][$key] = $this->addDashes($value);
				$condition['value']	  = "(".  implode(", ", $condition['value']).")";
			}
		}


		# Null convert
		if(is_null($condition['value']))
			$condition['value'] = 'NULL';


		# Return
		return $condition;
		
	}

	/**
	 * Gets query string parts from parameters
	 * @param String $name  Part name
	 * @param Mixed  $value Value for expression generation
	 * @param Mixed   $options Additional options
	 * @throws databaseException
	 * @return string
	 */
	protected function getQueryPart($name, $value, $options = false) {
		
		
		# Check type
		if(is_array($value) && $this->parametersTypes[$name] !== "multi") 
			throw new databaseException("This parameter can't be array: ".$name);
		
		
		# Prepare expression
		switch($name) {
			case "group":	$expression = "GROUP BY ";	break;
			case "order":	$expression = "ORDER BY ";	break;
			case "limit":	$expression = "LIMIT ";		break;
			case "offset":	$expression = "OFFSET ";	break;
			default:		$expression = "";
		}
		
		
		# Empty parameters
		if(empty($value)) return "";
		
		
		# If array
		if(is_array($value)) {
			switch($name) {
				case "join": {
					
					
					# Retranslate single join to array structure
					if(!isset($value[0])) $value = array($value);

					
					# Multiple joins
					foreach($value as $join) {

						# If join is a string we just put it
						if(is_string($join)) {
							$expression .= $join;
							continue;
						}
						
						
						# Check if table set
						if(empty($join["table"])) 
							throw new databaseException("Table not set in join data");
						
						
						# Add dashes
						$join["table"] = $this->addBackDashes($join["table"]);
						
						
						# Adds space for second join
						if(!empty($expression)) $expression .= " ";
							
						
						# Adds join type
						if(!isset($join["type"]) || !in_array($join["type"], $this->joinTypes)) $expression .= "LEFT JOIN ".$join["table"]." ";
						else $expression .= $join["type"]." JOIN ".$join["table"];

						
						# If we have ON clause in join
						if(isset($join["on"])) {
							

							# Simple string on expression
							if(is_string($join["on"])) {
								$expression .= " ON ".$join["on"];
								continue;
							}


							# Check type
							if(!is_array($join["on"])) 
								throw new databaseException("Wrong join on value:.".$join["on"]);


							# Go  through params
							foreach($join["on"] as $key => $compare) {

								# Adds on of not added yet
								if($key === 0) $expression .= " ON ";			
								
							
								# If this is string with compare
								if(is_string($compare)) {
									if($key !== 0) $expression .= " AND ";			
									$expression .= $compare;
									continue;
								}
								
								
								# Check value
								if(!is_array($compare) || sizeof($compare) < 2)
									throw new databaseException("Wrong ON value for JOIN in array");

								
								# Add logick for this compare
								if($key && sizeof($compare) < 4) $expression .= " AND ";	
								elseif($key) $expression .= " ".$compare[3]." ";
								
								
								# Make statement
								$this->correctCondition($compare);
								if(!isset($compare[2])) $compare[2] = "=";
								$expression .= $compare[0]." ". $compare[2] ." ".$compare[1];
								
							}

							# Go to next join
							continue;

						} 


						# If we haven't USING instead of ON
						if(empty($join["using"])) continue;


						# If using simple string we just put it
						if(is_string($join["using"])) {
							$expression .= "USING({$join["using"]})";
							continue;
						}


						# If array we will make string with , as separator
						if(is_array($join["using"])) 
							$expression .= "USING(". $this->implodeNames($join["using"]) .")";

					}
					break;
				}
				case "order": {
					
					foreach($value as $field => $order) { 
						
						
						# Add comma
						if($expression !== "ORDER BY ") $expression .= ", ";

						
						# If simple string
						if(is_numeric($field)) {
							if(!strstr($order, " ASC") && !strstr($order, " DESC")) $order = $order." ASC";
							$expression .= " ".$order;
							continue;
						}
						
						
						# Add dashes
						if(!strstr($field, "(")) $this->addDashes($field); 
						
						
						# Make order
						$expression .= " ".$field." ".strtoupper($order);

					}
					break;
				}
				case "records": $expression .= $this->implodeNames($value, $options); break;
				default: $expression .= $this->implodeNames($value); break;
			}
			return $expression;
		}
		
		
		# Corrections and checks
		if($name == "offset" && $value < 1) return "";
		if($name == "order" && !strstr($value, " ASC") && !strstr($value, " DESC")) $value = $value." ASC";

		
		# If $value not array
		return $expression . $value;
		
	}
	
	/**
	 * Separates values with dashes
	 * @param mixed $value Value
	 * @return mixed
	 */
	protected function addDashes($value) {
		
		# Arrays
		if(is_array($value)) {
			foreach($value as $val)
				$value = $this->addDashes($val);
		}
		
		# Nulls
		if(is_null($value) || is_numeric($value))
			return $value;
		
		# Return
		return "'$value'";
			
	}

	/**
	 * Add back dashes to names
	 * @param string      $key   Name of field or condition
	 * @param bool|string $table Table name to add to field name
	 * @return string
	 */
	protected function addBackDashes($key, $table = false) {
		
		
		# Trim
		$key = trim($key);
		
		
		# If we can't get name
		if(strpos($key, " ") !== false || strpos($key, "(") !== false)
			return $key;


		# Add table
		if($table && mb_strpos($key, '.') === false)
			$key = $table . '.' . $key;


		# Add dashes
		$key = str_replace('`', '', $key);		# Replace old if set
	    $key = str_replace('.', '`.`', $key);	# Dot separate
	    $key = "`$key`";						# Separate all
	    $key = str_replace('`*`', '*', $key);	# Star match
		
		
		# Return
		return $key;
		
	}

	/**
	 * Implodes name list and add dashes
	 * @param Array $names Column names
	 * @param bool|string  $table Table name to add to non table set values
	 * @throws databaseException
	 * @return string
	 */
	protected function implodeNames($names, $table = false) {
		
		$result = "";
		
		# Check
		if(!is_array($names))
			throw new databaseException("Not an array");
			
		# Go through
		foreach($names as $name) {
			
			if($result !== "") $result .= ", ";
			$result .= $this->addBackDashes($name, $table);
			
		}
		return $result;
		
	}
	
}

/**
 * This class will provide main abstraction level to
 * work with databases 
 */
class DB2 extends advancedDatabase {
	
	protected
	    $queryCount,		# Number of queries performed in this page
        $databaseType,      # Database type
        $databaseHost,      # MySQL host address
        $databaseName,      # Database name
        $databaseUser,      # User name
        $databasePassword,  # Password

		/**
		 * PDO connection object
		 * @var PDO
		 */
		$databaseLink,

		/**
		 * Available return types
		 * @var array
		 */
		$returnTypes = array("all", 	# All data in one array of arrays
							 "single", 	# Array of first row
							 "cursor", 	# PDOStatement
							 "query", 	# Query string, no query performed
							 "updated", # Number of updated/deleted rows
							 "id", 		# Last inserted id
							 "none", 	# True
							 "value"),	# First value of first row
		
		$traceTypes	= array(true, "time", "safe");
	
	/**
	 * Creates new database connector instance, works through PDO
	 * @param String $address	Database url or ip
	 * @param String $database	Database name
	 * @param String $user		User name to access
	 * @param String $password	Password to access
	 * @param String $type		Type of database, all types which are imported in to your PDO class
	 */
    public function __construct($address, $database, $user, $password, $type = "mysql") {
        $this->databaseHost     = $address;
        $this->databaseName     = $database;
        $this->databaseUser     = $user;
        $this->databasePassword = $password;
        $this->databaseType     = $type;
		$this->queryCount		= 0;
    } 	
	
	/**
	 * 
	 * Prepares query object
	 * @param array|string|boolean $tables Table or list of table
	 * @return \databaseQuery
	 */
	public function make($tables = false) {

		# Get new query builder
		return new databaseQuery($this, $tables);
		
	}

	/**
	 * Return database connection object
	 * @return PDO
	 * @throws databaseException
	 */
	private function connect() {

        # Checks connection parameters
        if (!isset($this->databaseHost) || !isset($this->databaseName) || !isset($this->databaseUser) || !isset($this->databasePassword))
            throw new databaseException("Connection parameters doesn't initialized");

        # Checks if link exists
        if (isset($this->databaseLink)) 
			return $this->databaseLink;

        try {

            $link = @new PDO($this->databaseType.":dbname=".
							 $this->databaseName.";host=".
                             $this->databaseHost, 
                             $this->databaseUser, 
                             $this->databasePassword, 
                             array(PDO::ATTR_PERSISTENT => true));
                            
            $data = $link->query('SET NAMES UTF8');
            $data->closeCursor();
        
        } catch(PDOException $e) {
            throw new databaseException("Database connection error: " . $e->getMessage());
        }

        # If all ok
        return $this->databaseLink = $link;  # Save link to DB in global variable
        
    }

	/**
	 * Performs query
	 * @param String      $query  Query string to be performed
	 * @param bool|String $return Return data type
	 * @param bool|String $trace  Tracing query type
	 * @throws databaseException
	 * @return bool|String
	 */
	public function query($query, $return = false, $trace = false) {
		
		
		# Check return type
		if($return && !in_array($return, $this->returnTypes))
			throw new databaseException("Wrong return type: ".$return);
		
		
		# Check trace type
		if($trace && !in_array($trace, $this->traceTypes))
			throw new databaseException("Wrong trace type: ".$trace);
		
		
		# Connect if needed
        $link = $this->connect();

		
        try {
			
			# Tracing output
			$beginTime = microtime(true);
			if($trace  === true)	echo "Query was: $query";
			if($trace  === "safe")	{ echo "Query was: $query"; return false; }
			if($return === "query") return $query;
        
			
			# Prepare request
			$statement = $link->prepare($query);

			
			# Is statement not prepared
	        if($statement === false) {
	            $error = $link->errorInfo();
	            throw new databaseException("Database query error: " . $error[2] . "\nQuery was: ".$query);
	        }
	        
			
	        # Executes query
	        $error = $statement->execute();
	        
			
			# If error occupied
	        if($error === false) {
	            $error = $statement->errorInfo();
	            throw new databaseException("Database query error: " . $error[2] . "\nQuery was: ".$query);
	        }
			
			
			# Query count incrementation
			$this->queryCount++;
			
			
			# Cursor return
			if($trace  === "time")	  echo "Query \"$query\" <br/>execution time: " . round((microtime(true) - $beginTime), 4) . "sec";
			if($return === "cursor")  return $statement;
			if($return === "none")	  return true;
			
			
			# Get result data
			if($return === "value")		 $data = $statement->fetch(PDO::FETCH_NUM);  
			elseif($return === "single") $data = $statement->fetch(PDO::FETCH_ASSOC);
			else						 $data = $statement->fetchAll(PDO::FETCH_ASSOC);
			
			
			# Get updated
			if($return == "updated") return $statement->rowCount();

			
			# Clear cursor
			$statement->closeCursor();
			$statement = null;

			
			# Additional returns
			if($return === "id") return $link->lastInsertId();
			if($return === "value" && $data) $data = $data[0];
			
			
			# Return
			return $data;
	        
       	} catch(PDOException $e) {
            throw new databaseException("Database connection error: " . $e->getMessage());
        }
	}
	
	/**
     * Returns pointer for current connection
     * @return PDO connection resource or FALSE if no connection initialized
     */
    public function getLink() {
        return $this->databaseLink;
    } 
	
}