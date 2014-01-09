<?php
/**
 * Copyright (c) 2013 Art & Logic, Inc., All Rights Reserved.
 *
 * Model for storing/retrieving invite codes.
 */
use sanemethod\kiskit\system\core\Model;

class InvitecodesModel extends Model{

    protected $table = 'invite_codes';
    protected $fieldWhiteList = array(
        'user_id',
        'invite_code',
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
              user_id INT NOT NULL,
              invite_code VARCHAR(255) NOT NULL,
              updated_on TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              active BIT NOT NULL DEFAULT 1,
              deleted BIT NOT NULL DEFAULT 0,
              PRIMARY KEY (user_id, invite_code)
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
     * Insert initial values into table.
     */
    function initialValues()
    {
        return $this->insert(array('user_id'=>0, 'invite_code'=>'indyracing2013'));
    }

    function __destruct()
    {
        $this->dbh = null; // Make sure we release this connection
    }

}