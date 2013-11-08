<?php 

/**
 * Class to perform new syntax to queries
 */
class databaseQuery extends advancedDatabase {
	
	/**
	 * Database object
	 * @var DB2
	 */
	private $database = false;
	
	/**
	 * Tables list
	 * @var string
	 */
	private $tables = false;

	/**
	 * Holds tables list as it was set
	 * @var bool|string|array
	 */
	private $tablesRaw = false;

	/**
	 * Request parameters list
	 * @var array
	 */
	private $parameters = array();
	
	/**
	 * Conditions list
	 * @var array 
	 */
	private $conditions = false;
	
	/**
	 * Records to be updated/added
	 * @var array
	 */
	private $records = false;
	
	/**
	 * Request trace
	 * @var string|bool 
	 */
	private $trace = false;
	
	/**
	 * Available compares
	 * @var array
	 */
	private $compares = array("=", ">", "<", "!=", "<=", ">=", "<=>", "IN", "IS", "IS NOT", "LIKE");
	
	/**
	 * Available logic
	 * @var array
	 */
	private $logic = array("AND", "OR", "XOR");
	
	/**
	 * Group list
	 * @var array
	 */
	private $groups = array();


	/**
	 * Prepares object
	 * @param DB2 					$database	Database access object
	 * @param string|boolean|array 	$tables		Tables list
	 */
	function __construct($database, $tables = false) {
		
		# Save database
		$this->database = $database;


		# Set tables list
		if($tables)
			$this->setTables($tables);
		
	}

	/**
	 * Get conditions or count
	 * @param bool $count Should we return or count
	 * @return array|bool
	 */
	function getConditions($count = false) {
		return $count ? sizeof($this->conditions) : $this->conditions;
	}

	/**
	 * Sets tables list to use in FROM
	 * @param string|array $tables	Tables list
	 * @return $this
	 */
	public function setTables($tables) {


		# Tables convert
		if(is_array($tables)) {


			# Prepare
			foreach($tables as $i => $table)
				$tables[$i] = $this->addBackDashes($table);


			# Compile tables list
			$this->tables = implode(", ", $tables);


		} else
			$this->tables = $this->addBackDashes($tables);


		# Save not imploded
		$this->tablesRaw = $tables;


		# Return
		return $this;

	}

	/**
	 * Set offset and limit for page navigation request
	 * @param int $perPage Elements per page
	 * @param int $page	Page number
	 * @return databaseQuery
	 */
	public function setPage($perPage, $page = 1) {
		return $this->limit($perPage)->offset($perPage * ($page - 1));
	}

	/**
	 * Clones current query
	 * @param mixed $drop Elements to be dropped in new request
	 * @see databaseQuery::drop
	 * @return \databaseQuery
	 */
	public function same($drop = false) {

		# Make clone
		$clone = clone $this;

		# Drop
		if($drop)
			$clone->drop($drop);

		# Return
		return $clone;
	}

	/**
	 * Removes some request parameters
	 * @param string|array $what Element(s) to be dropped
	 * @param bool|string  $name Special name for some of drops
	 * @return \databaseQuery
	 */
	public function drop($what, $name = false) {

		# If array drop each
		if(is_array($what)) {
			foreach($what as $drop)
				$this->drop($drop);
		}

		# Preferences and conditions and etc
		if($what == "parameters") 	$this->parameters 	= array();
		if($what == "conditions")	$this->conditions 	= array();
		if($what == "records")		unset($this->parameters['records']);

		# Return
		return $this;

	}

	/**
	 * Sets records offset
	 * @param $offset
	 * @internal param int $limit
	 * @return \databaseQuery
	 */
	public function offset($offset) {

		# Return
		$this->parameters["offset"] = $offset;

		# Set
		return $this;

	}

	/**
	 * Sets max records limit
	 * @param int $limit Limit size
	 * @throws databaseException
	 * @return \databaseQuery
	 */
	public function limit($limit) {

		# Check
		if(!validator::value($limit, "positive"))
			throw new databaseException("Wrong request limit: ".$limit);

		# Return
		$this->parameters["limit"] = $limit;

		# Set
		return $this;

	}

	/**
	 * Sets order parameters
	 * @param string|array $field Field name to make order or array of fields
	 * @param string       $order Order direction
	 * @throws systemErrorException
	 * @return \databaseQuery
	 */
	public function order($field, $order = "DESC") {
		
		
		# If is array
		if(is_array($field)) {
			
			# Go each
			foreach($field as $key => $val) {
				if(is_int($key)) $this->order($val, $order);
				else			 $this->order($key, $val);
			}
				
			# Return
			return $this;
		}
		
		
		# Make array
		if(!isset($this->parameters['order']))
			$this->parameters['order'] = array();


		# Set to upper
		$order = strtoupper($order);


		# Check
		if(!in_array($order, array("ASC", "DESC")))
			throw new systemErrorException("Wrong order type: ".$order);


		# Add param
		$this->parameters['order'][$field] = $order;


		# Return
		return $this;

	}

	/**
	 * Sets records to be returned
	 * @param string|array $names 	 Fields to be gathered
	 * @return \databaseQuery
	 */
	public function records($names) {
		
		# Set
		if(!isset($this->parameters["records"]))
			$this->parameters["records"] = array();
		
		
		# Add
		if(is_array($names)) {
			foreach($names as $name)
				$this->records($name);
		} else 
			$this->parameters["records"][] = $names;
		
		
		# Return
		return $this;
		
	}

	/**
	 * Set group by fields
	 * @param array|string $names List of fields to group by
	 * @return \databaseQuery
	 */
	public function group($names) {


		# Set
		if(!isset($this->parameters["group"]))
			$this->parameters["group"] = array();


		# Add
		if(is_array($names)) {
			foreach($names as $name)
				$this->group($name);
		} else
			$this->parameters["group"][] = $names;


		# Return
		return $this;

	}

	/**
	 * Adds date condition for request by period name
	 * @param String $period   Name of period to add conditions
	 * @param String $name     Name of column
	 * @param bool   $withTime
	 * @param string $tableName
	 * @return $this
	 * @see utils::getDateConditions
	 */
	public function whereDates($period, $name = "date_short", $withTime = false, $tableName = "") {
		return $this->whereList(utils::getDateConditions($period, $name, $withTime, $tableName));
	}

	/**
	 * Sets list of conditions
	 * @param array       $conditions List of conditions
	 * @param string|bool $group      Group to add conditions to
	 * @return $this
	 * @throws systemErrorException
	 */
	public function whereList($conditions, $group = false) {


		# Array check
		if(!is_array($conditions))
			throw new systemErrorException("Conditions must be array, if no use 'where' instead");


		# Add conditions
		foreach($conditions as $name => $value) {

			# If value holds name or name not set
			if(is_numeric($name)) {

				# If value holds name in array
				if(is_array($value)) {


					# Check
					if(sizeof($value) < 2)
						throw new systemErrorException("Can't be less than 2 elements when you set array of arrays with numeric key");


					# Add condition
					$this->where(	$value[0],
									$value[1],
									isset($value[2]) ? $value[2] : false,
									isset($value[3]) ? $value[3] : false,
									isset($value[4]) ? $value[4] : $group);

				# If name not set we use default
				} else
					$this->where($value, false, $group);

			# If kwy is name
			} else {
				$this->where($name, $value, $group);
			}


		}


		return $this;

	}

	/**
	 * Adds condition
	 * @param string|array $name         Field name
	 * @param mixed        $value        Value to compare
	 * @param string       $compare      Comparison, like "=", or ">", "<", etc.
	 * @param string       $logic        Logic to add before
	 * @param bool|string  $group        Group to add condition to
	 * @throws databaseException
	 * @return \databaseQuery
	 */
	public function where($name, $value = false, $compare = "=", $logic = "AND", $group = false) {
		
		# Make array
		if(!$this->conditions) 
			$this->conditions = array();
		
		
		# If id given
		if(is_numeric($name) || (is_array($name) && is_numeric($name[0]))) {
			$value = $name;
			$name = 'id';
			if(!strpos($this->tables, " "))
				$name = $this->tables.'.id';
		}
		
		
		# Set function
		if(is_array($name) && sizeof($name) > 1) {
			$function = $name[1];
			$name = $name[0];
		} else $function = false;
		
		
		# Fixes
		if(!in_array($compare, $this->compares))		{ $group = $logic; $logic = $compare;	$compare = "="; }
		if(!in_array(strtoupper($logic), $this->logic))	{ $group = $logic; $logic = "AND";	}
		
		
		# Group check
		if($group && empty($this->groups[$group]))
			self::addGroup($group, $logic);


		# Prepare condition
		$condition = array(
				"type"		=> "where",
				"name"		=> $name, 
				"value"		=> $value, 
				"compare"	=> $compare, 
				"logic"		=> $logic, 
				"group"		=> $group,
				"function"	=> $function);


		# Array push
		if(!$group)	$this->conditions[] = $condition;
		else		$this->groups[$group]['conditions'][] = $condition;
		
		
		# Return
		return $this;
		
	}

	/**
	 * Sets records parameter
	 * @param string|array $name         Name of field
	 * @param mixed        $value        Value to insert
	 * @param bool|string  $function     Function to be operated on value
	 * @return \databaseQuery
	 */
	public function set($name, $value = false, $function = false) {



		# If array
		if(is_array($name)) {
			foreach($name as $key => $val) {

				# Global function if set
				$func = $function;

				# If have self function
				if(is_array($val)) {
					$func = $val[1];
					$val  = $val[0];
				}

				# Self call
				$this->set($key, $val, $func);

			}
			return $this;
		}


		# Set function
		if(is_array($value) && sizeof($value) > 1) {
			$function = $value[1];
			$value	  = $value[0];
		}
		
		
		# Save record
		$this->records[] = array(
			"type"		=> "set",
			"name"		=> $name,
			"value"		=> addslashes($value),
			"function"	=> $function
		);
		
		
		# Return
		return $this;
		
	}

	/**
	 * Adds group
	 * @param string      $name      Group name
	 * @param bool|string $parent    Parent group id
	 * @param string      $logic     Logic operator before group
	 * @throws databaseException
	 * @return \databaseQuery
	 */
	public function addGroup($name, $parent = false, $logic = "AND") {
		
		
		# Parameters shifting
		if(in_array($parent, $this->logic)) {
			$logic = $parent; 
			$parent = false; 
		}
		
			
		# Group check
		if($parent && empty($this->groups[$parent]))
			throw new databaseException("Try add group to not existing group");
		
		
		# Group
		$group = array(
			"type"		=> "group",
			"name"		=> $name,
			"logic"		=> $logic,
			"conditions"=> array(),
		);
		
		
		# Add to stacks
		if($parent) {
			$this->groups[$parent.'.'.$name] = $group;
			$this->groups[$parent]['conditions'][] = &$this->groups[$parent.'.'.$name];
		} else {
			$this->groups[$name] = $group;
			$this->conditions[] = &$this->groups[$name];
		}
		
		
		# Return
		return $this;
		
	}

	/**
	 * Sets trace option
	 * @param bool|string $type Type of tracing
	 * @return \databaseQuery
	 */
	public function trace($type = true) {
		
		# Set trace
		$this->trace = $type;
		
		# Return
		return $this;
		
	}

	public function index($name) {

		# Set index
		$this->parameters["index"] = is_array($name) ? implode(", ", $name) : "`$name`";

		# Self return
		return $this;

	}

	/**
	 * Adds join parameter
	 * @param string       $table        Join table
	 * @param string|array $on           Fields to be joined on
	 * @param string       $type         Join type
	 * @param bool         $prepend		 If true join would be pushed to begin of stack
	 * @return \databaseQuery
	 */
	public function join($table, $on, $type = "LEFT", $prepend = false) {
	
		# Set
		if(!isset($this->parameters["join"]))
			$this->parameters["join"] = array();

		if($prepend) {

			# Prepend join
			array_unshift($this->parameters["join"], array(
				"table" => $table,
				"on"	=> $on,
				"type"	=> $type
			));

		} else {

			# Add join
			$this->parameters["join"][] = array(
				"table" => $table,
				"on"	=> $on,
				"type"	=> $type
			);
		}
		
		# return
		return $this;
		
	}

	/**
	 * Same as select
	 * @see $this->select
	 * @param bool|string $return What should be returned, see database doc return types
	 * @return Mixed
	 */
	public function get($return = false) {
		return $this->select($return);
	}

	/**
	 * Performs select request
	 * @param bool|string $return What should be returned, see database doc return types
	 * @return Mixed
	 */
	public function select($return = false) {
	
		
		# Parameters generation
        $whereExpression	= $this->makeWhere($this->conditions, is_string($this->tablesRaw) ? $this->tablesRaw : false);
		$groupExpression	= "";
		$orderExpression	= "";
		$limitExpression	= "";
		$offsetExpression	= "";
        $joinExpression		= "";
		$recordsExpression  = "*";
		
		
		# Get each string parameter 
		if(is_array($this->parameters)) {
			foreach($this->parameters as $name => $value) {
					
				# Check name
				if(!in_array($name, array_keys($this->database->parametersTypes))) continue;

				${$name."Expression"} = $this->getQueryPart($name, $value, $name == "records" && is_string($this->tablesRaw) ? $this->tablesRaw : false);
				
			}
		}

		
		# Prepare statement
        $requestString = "SELECT $recordsExpression FROM " . $this->tables .
        				" $joinExpression" .
                        " $whereExpression" .
                        " $groupExpression" .
                        " $orderExpression" .
                        " $limitExpression" .
                        " $offsetExpression";


        # Performs query
		return $this->database->query($requestString, $return, $this->trace);
		
	}

	/**
	 * Performs update query
	 * @param string $return What should be returned, see database doc return types
	 * @throws databaseException
	 * @internal param array $records Array of record to update, key is field and value is value
	 * @return int number of updated records
	 */
	public function update($return = 'updated') {
		
		
		# Check to update
		if(!$this->records)
			throw new databaseException("No fields to update");
		
		
		# Prepare
		$records = array();

		
		# Make where
        $whereExpression = $this->makeWhere($this->conditions);

		
		# Compile
		foreach($this->records as $record) {

            # Expression generation
            $record = $this->correctCondition($record);
			
			# Record make
            $records[] = $record['name'] . "=" . $record['value'];
        
		}

		
		# Make string
        $requestString = "UPDATE ".$this->tables." SET ".implode(", ", $records)." $whereExpression";

		
        # Performs query
        return $this->database->query($requestString, $return, $this->trace); 
		
		
	}
	
	/**
	 * Performs delete from database
	 * @param string $return What should be returned, see database doc return types
	 * @return Int number of records
	 */
	public function delete($return = "updated") {
		
		# Making WHERE expression
        $whereExpression = $this->makeWhere($this->conditions);

		# Prepare query
        $requestString = "DELETE FROM ".$this->tables." $whereExpression";

        # Performs query
        return $this->database->query($requestString, $return, $this->trace);
		
	}
	
	/**
	 * Preforms INSERT
	 * @param bool		$replace Indicates that we should use REPLACE instead of REPLACE
	 * @param string	$return	 Type of return data
	 * @return Int Inserted record id
	 */
	public function insert($replace = false, $return = "id") {
		
		# Initialization
        $fieldsExpression = "";
        $valuesExpression = "";

		
        foreach($this->records as $record) {

            # Add " ," if needed
            if($fieldsExpression != "") $fieldsExpression .= ", ";
            if($valuesExpression != "") $valuesExpression .= ", ";

            # Reinterpret values
			$record = $this->correctCondition($record);
			
            # Add field to list
            $fieldsExpression .= $record['name'];

            # Add value to list
            $valuesExpression .= $record['value'];
        }

		
		# Prepare request
        $requestString = ($replace ? "REPLACE" : "INSERT") . " INTO ".$this->tables."($fieldsExpression) VALUES($valuesExpression)";

		
        # Performs query
        return $this->database->query($requestString, $return, $this->trace);
		
	}
	
}