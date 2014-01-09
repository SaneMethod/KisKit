<?php
/**
 * Copyright (c) Christopher Keefer, 2013. All Rights Reserved.
 *
 * Defines some basic behaviours for all table models, such as selecting, updating and deleting rows.
 */

namespace sanemethod\kiskit\system\core;

use \PDO;
use \Exception;

class Model {
    /**
     * Array of config properties for the database.
     * @var null|array
     */
    protected $db = null;
    /**
     * PDO Database Handle.
     * @var null|PDO
     */
    protected $dbh = null;
    /**
     * Table Name.
     * @var string
     */
    protected $table = '';
    /**
     * White list of fields on table to allow for various statements.
     * @var array
     */
    protected $fieldWhiteList = array();
    /**
     * Stores the last Exception that was thrown from a predictable source in the model.
     * @var null|Exception
     */
    public $lastError = null;


    function __construct()
    {
        require(SERVER_ROOT . 'config/dbconfig.php');
        if (isset($db) && isset($active_group)) $this->db = $db[$active_group];
        if ($this->db === null)
        {
            $this->lastError = new Exception("Failed to retrieve database configuration.");
            return false;
        }
        return true;
    }

    /**
     * Create a connection to the db, specifying options (as per PDO) if desired. Returns null if we failed to
     * obtain a database handle.
     *
     * @param $options
     * @return null|PDO
     */
    function dbConnect($options = array()){
        $options = array_merge(array(PDO::ATTR_PERSISTENT => true,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION),
            $options);

        try{
            $dbhandle = new PDO(
                "{$this->db['driver']}:host={$this->db['hostname']};".(($this->db['port']) ?
                    'port='.$this->db['port'].';' : '')."dbname={$this->db['database']};", $this->db['username'],
                $this->db['password'], $options
            );
        }catch(Exception $e){
            $this->lastError = $e;
            return null;
        }

        return $dbhandle;
    }

    /**
     * Determine whether a table exists (returning true if so, false otherwise). Optionally create a table
     * if the table does not exist, with $define either indicating an array to call as a user function with the
     * elements being: ($this, 'user_func_string_name'), or a string to create the table with.
     *
     * @param bool $create
     * @param array|string $define
     * @return bool
     */
    function tableExists($create = false, $define = null)
    {
        try
        {
            $prep = $this->dbh->prepare("SELECT COUNT(1) FROM {$this->table}");
            if (!$prep->execute()) throw new Exception("Table Not Defined: {$this->table}.");
        }
        catch(Exception $e)
        {
            // Table doesn't exist - if $create is true and $define is a string or array, create the table
            if ($create)
            {
                return $this->genTable($define);
            }
            else { return false; }
        }

        return true;
    }

    /**
     * Select any number of rows from this table based on the $where array, where each key should be the field we
     * want to select on in the db, and the array value the value of said field: array(id=>5, name=>'bob').
     * Select all fields (default) or specify fields as an array (id, name, date) or as a string "id, name, date".
     * Returns an associative (potentially multi-dimensional) array if successful, false otherwise.
     *
     * @param array $options{
     *     @type string|array $fields
     *     @type array $where
     *     @type int $fetchMode
     *     @type bool $fetchOne Whether to limit this fetch to a single result.
     * }
     * @return bool|array
     */
    function select($options = array()){
        $fields = isset($options['fields']) ? $options['fields'] : '*';
        $where = isset($options['where']) ? $options['where'] : array();
        $fetchMode = isset($options['fetchMode']) ? $options['fetchMode'] : PDO::FETCH_ASSOC;
        $fetchOne = isset($options['fetchOne']) ? $options['fetchOne'] : false;

        $statement = $this->prepareSelectStatement($where, $fields);
        if ($statement === false) return false;

        if ($fetchOne) $statement .= ' LIMIT 1';

        $ct = $this->dbh->prepare($statement);
        $ct->setFetchMode($fetchMode);
        $sel = false;

        try
        {
            $ct->execute($where);
            $sel = ($fetchOne) ? $ct->fetch() : $ct->fetchAll();
        }
        catch(Exception $e)
        {
            $this->lastError = $e;
            return false;
        }

        return $sel;
    }

    /**
     * As Model::select, but returning only the first row in the result, regardless of how many would
     * otherwise be returned.
     *
     * @see Model::select
     */
    function selectOne($options = array()){
        $options = array_merge($options, array('fetchOne' => true));
        return $this->select($options);
    }

    /**
     * Alias for Model::select() that selects and returns all rows from the table.
     *
     * @see Model::select
     */
    function selectAll(){
        return $this->select();
    }

    /**
     * Insert a single record into the table. Returns true on success, false otherwise.
     *
     * @see Model::prepareInsertStatement
     * @param array $record
     * @return bool
     */
    function insert($record){
        $statement = $this->prepareInsertStatement($record);
        if ($statement === false) return false;

        $ct = $this->dbh->prepare($statement);
        try{
            $ct->execute($record);
        }
        catch(Exception $e)
        {
            $this->lastError = $e;
            return false; // Indicate insert failed
        }
        return true;
    }

    /**
     * Insert multiple records into the table. Returns true if all records successfully inserted, false
     * otherwise.
     * Transactional.
     *
     * @see Model::prepareInsertStatement
     * @param array[] $records
     * @return bool
     */
    function insertMany($records){
        if (!is_array($records) || count($records) < 1)
        {
            $this->lastError = new Exception("Records must be an array. Insertion failed.");
            return false;
        }
        $statement = $this->prepareInsertStatement($records);
        if ($statement === false) return false;

        $ct = $this->dbh->prepare($statement);
        $this->dbh->beginTransaction();

        foreach($records as $record)
        {
            try
            {
                $ct->execute($record);
            }
            catch(Exception $e)
            {
                $this->lastError = $e;
                $this->dbh->rollBack();
                return false;
            }
        }
        $this->dbh->commit();

        return true;
    }

    /**
     * Update a single row based on the $record and $where arrays. The $where array should contain key(s) that
     * exist within our $record array, and indicate which $keys should be used for the WHERE statement.
     * Example: $record = array(name=>'bob', id=>5), $FIELD_WHITE_LIST = array('name'), $where = array('id').
     *
     * @see Model::prepareUpdateStatement
     * @param array $record
     * @param array $where
     * @return bool
     */
    function update($record, $where = array('id')){
        $statement = $this->prepareUpdateStatement($record, $where);
        if ($statement === false) return false;

        $ct = $this->dbh->prepare($statement);
        try
        {
            $ct->execute($record);
        }
        catch(Exception $e)
        {
            $this->lastError = $e;
            return false;
        }
        return true;
    }

    /**
     * Update multiple rows based on the $records and $where arrays. The $where array should contain key(s) that
     * exist within each of our $records arrays, and indicate which $keys should be used for the WHERE statement.
     * Example: $record = array(name=>'bob', id=>5), $FIELD_WHITE_LIST = array('name'), $where = array('id').
     * Transactional.
     *
     * @see Model::prepareUpdateStatement
     * @param array[] $records
     * @param array $where
     * @return bool
     */
    function updateMany($records, $where = array('id')){
        if (!is_array($records) || count($records) < 1)
        {
            $this->lastError = new Exception("Records must be an array and must not be empty. Update failed.");
            return false;
        }

        $statement = $this->prepareUpdateStatement($records, $where);
        if ($statement === false) return false;

        $ct = $this->dbh->prepare($statement);
        $this->dbh->beginTransaction();

        foreach($records as $record)
        {
            try
            {
                $ct->execute($record);
            }
            catch(Exception $e)
            {
                $this->lastError = $e;
                $this->dbh->rollBack();
                return false;
            }
        }
        $this->dbh->commit();

        return true;
    }

    /**
     * Delete row(s) based on the $where array keys and values, where each key should be the field we want to
     * limit on in the db, and the value the value of said field. Returns true on success, false otherwise.
     * Transactional.
     *
     * @param array $where
     * @return bool
     */
    function delete($where){
        $statement = $this->prepareDeleteStatement($where);
        if ($statement === false) return false;

        $ct = $this->dbh->prepare($statement);
        $this->dbh->beginTransaction();

        try
        {
            $ct->execute($where);
        }
        catch(Exception $e)
        {
            $this->lastError = $e;
            $this->dbh->rollBack();
            return false;
        }
        $this->dbh->commit();

        return true;
    }

    /**
     * Stub to be overridden by sub-classes, to validate records before insertion/update.
     */
    function validate(){
        return true;
    }

    /**
     * Prepare select statement based on the $where array and the $fields string|array. Returns the statement
     * string if $where is an array, false otherwise.
     *
     * @param array $where
     * @param string|array $fields
     * @return bool|string
     */
    private function prepareSelectStatement($where, $fields){
        if (is_array($fields)) $fields = join(', ', $fields);
        $statement = "SELECT {$fields} FROM {$this->table} WHERE ";

        if (!is_array($where))
        {
            $this->lastError = new Exception("Where must be an array. Select failed.");
            return false;
        }

        foreach($where as $wkey => $wval)
        {
            $statement .= "{$wkey} =:{$wkey} AND ";
        }

        if (count($where) > 0)
        {
            // Remove last 5 characters (extra AND)
            $statement = substr($statement, 0, -5);
        }
        else
        {
            // Remove last 7 characters (removing WHERE clause)
            $statement = substr($statement, 0, -7);
        }

        return $statement;
    }

    /**
     * Prepare an insert statement based on the $record passed and the field white list for this model.
     * Returns string of insert statement on success, false otherwise.
     *
     * @param array $record
     * @return bool|string
     */
    private function prepareInsertStatement(&$record){
        $statement = "INSERT INTO {$this->table} ";

        if (!is_array($record))
        {
            $this->lastError = new Exception("Record must be an array. Insertion failed.");
            return false;
        }

        if (isset($record[0]) && is_array($record[0]))
        {
            // We've been passed a two-dimensional array - save the 2d array, and set the $record as the
            // first element for generating our statement - we'll set it back after stripping out all non
            // white-listed elements from each afterwards.
            $allRecords = $record;
            $record = $allRecords[0];
        }

        // Intersect the keys in $record with the keys in our table white list - those set in both will have
        // their values inserted.
        $keys = array_intersect(array_keys($record), $this->fieldWhiteList);
        if (count($keys) < 1)
        {
            $this->lastError = new Exception("No fields were present in record that existed in field white list - ".
                "unable to insert record.");
            return false;
        }
        // Looks like (field1, field2) VALUES (:field1, :field2)
        $statement .= '('.join(', ', $keys).') VALUES (:'.join(', :', $keys).')';

        // Remove elements from $record that aren't present in the white list to prevent throwing errors
        // from PDO::Execute
        $record = $this->stripBlackFields((isset($allRecords) ? $allRecords : $record), (array_flip($keys)));

        return $statement;
    }

    /**
     * Prepare the update statement to include whatever fields are set within our record,
     * so long as they also occur within our field white list. The $where array should contain key(s) that
     * exist within our $record array, and indicate which $keys should be used for the WHERE statement.
     * Example: $record = array(name=>'bob', id=>5), $FIELD_WHITE_LIST = array('name'), $where = array('id').
     *
     * @param $record
     * @param $where
     * @return string
     */
    private function prepareUpdateStatement(&$record, $where){
        $statement = "UPDATE {$this->table} SET ";

        if (!is_array($record)){
            $this->lastError = new Exception("Record must be an array. Update failed.");
            return false;
        }

        if (is_array($record[0]))
        {
            // We've been passed a two-dimensional array - save the 2d array, and set the $record as the
            // first element for generating our statement - we'll set it back after stripping out all non
            // white-listed elements from each afterwards.
            $allRecords = $record;
            $record = $allRecords[0];
        }

        $keys = array_intersect(array_keys($record), $this->fieldWhiteList);
        if (count($keys) < 1)
        {
            $this->lastError = new Exception("No fields were present in record that existed in field white list - ".
                "unable to update record.");
            return false;
        }
        for ($i=0; $i < count($keys); $i++)
        {
            $statement .= "{$keys[$i]} =:{$keys[$i]} ";
        }
        $statement .= "WHERE ";

        if (is_string($where)) $where = (array)$where;
        $wkeys = array_intersect(array_keys($record), $where);
        for ($i=0; $i < count($wkeys); $i++)
        {
            $statement .= "{$wkeys[$i]} =:{$wkeys[$i]} AND ";
        }
        $statement = substr($statement, 0, -5);

        // Remove elements from $record that aren't present in the white list or where statement to
        // prevent throwing errors from PDO::Execute
        $record = $this->stripBlackFields((isset($allRecords) ? $allRecords : $record),
            (array_flip($keys) + array_flip($wkeys)));

        return $statement;
    }

    /**
     * Prepare a Delete statement, based on the keys in the $where array. When executing, the values of the
     * $where array will be used to limit rows for deletion. Returns statement string if $where is an array,
     * and not empty, false otherwise.
     *
     * @param array $where
     * @return bool|string
     */
    private function prepareDeleteStatement($where){
        $statement = "DELETE FROM {$this->table} WHERE ";

        if (!is_array($where) || count($where) < 1){
            if (is_string($where)){
                $where = (array)$where;
            }else{
                $this->lastError = new Exception("Where must be an array. Delete failed.");
                return false;
            }
        }

        foreach($where as $wkey => $wval)
        {
            $statement .= "{$wkey} =:{$wkey} AND ";
        }
        $statement = substr($statement, 0, -5);

        return $statement;
    }

    /**
     * Strip out fields included in the $records array (or 2d array) that are not included in the whitelist,
     * by intersecting the keys of the records with the whitelist keys.
     *
     * @param $records
     * @param $keys
     * @return array
     */
    private function stripBlackFields($records, $keys){
        if (isset($record[0]) && is_array($records[0])){
            foreach($records as &$record){
                $record = array_intersect_key($record, $keys);
            }
            return $records;
        }else{
            return array_intersect_key($records, $keys);
        }
    }

    /**
     * Create a table based on the table name and the define array or string.
     *
     * @param array|string $define
     * @return bool
     */
    private function genTable($define){
        $ct = null;

        if (is_array($define))
        {
            // Call passed object ($this) and create table function name - we expect it will take no args.
            return call_user_func($define);
        }

        $ct = $this->dbh->prepare($define);

        try{
            $ct->execute();
        }
        catch(Exception $e)
        {
            $this->lastError = $e;
            return false;
        }
        return true;
    }

}
