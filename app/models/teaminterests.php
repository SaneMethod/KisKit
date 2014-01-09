<?php
/**
 * Copyright (c) 2013 Art & Logic, Inc., All Rights Reserved.
 *
 * Model for storing/retrieving team interests.
 */
use sanemethod\kiskit\system\core\Model;

class TeaminterestsModel extends Model{

    protected $table = 'team_interests';
    protected $fieldWhiteList = array(
        'description',
        'active',
        'deleted'
    );

    function __construct()
    {
        parent::__construct();
        $this->dbh = $this->dbConnect();
    }

    /**
     * Create the table.
     * @return bool false if failed to create table, else true
     */
    function createTable()
    {
        $ct = $this->dbh->prepare(
            "CREATE TABLE {$this->table} (
              interest_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
              description TEXT NOT NULL,
              active BIT NOT NULL DEFAULT 1,
              deleted BIT NOT NULL DEFAULT 0
            )"
        );
        try{
            $ct->execute();
        }
        catch(Exception $e)
        {
            $this->lastError = $e;
            return false;
        }
        return $this->initialValues();
    }

    /**
     * Insert initial values for this table.
     */
    function initialValues(){
        $success = true;
        if (!$this->selectOne())
        {
            $success = $this->insertMany(array(array('description'=>'Motorsports'), array('description'=>'Movies'),
                array('description'=>'Music')));
        }
        return $success;
    }

    function __destruct()
    {
        $this->dbh = null; // Make sure we release this connection
    }
}