<?php
/**
 * Copyright (c) 2013 Art & Logic, Inc., All Rights Reserved.
 *
 * Model for storing/retrieving team interests.
 */
use sanemethod\kiskit\system\core\Model;

class MemberteaminterestsModel extends Model{

    protected $table = 'member_team_interests';
    protected $fieldWhiteList = array(
        'user_id',
        'interest_id',
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
              id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
              user_id INT NOT NULL,
              interest_id INT NOT NULL,
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
        return true;
    }

    function __destruct()
    {
        $this->dbh = null; // Make sure we release this connection
    }
}