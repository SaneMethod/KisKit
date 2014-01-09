<?php
/**
 * Copyright (c) Christopher Keefer, 2013. All Rights Reserved.
 *
 * Model for storing/retrieving user registrations.
 */
use sanemethod\kiskit\system\core\Model;

class MemberModel extends Model{

    protected $table = 'member';
    protected $fieldWhiteList = array(
        'first_name',
        'last_name',
        'email_address',
        'password',
        'invite_code',
        'confirmation_code',
        'confirmed_on',
        'updated_on',
        'active',
        'deleted'
    );
    protected $CONSTRAINTS = array(
        'MIN_PASSWORD_LENGTH'=>7,
        'EMAIL_NOT_VALID'=>11,
        'PASSWORD_LENGTH'=>12,
        'PASSWORD_NO_DIGIT'=>13,
        'CONFIRM_NOT_MATCH'=>14
    );

    function __construct()
    {
        parent::__construct();
        $this->dbh = $this->dbConnect();
    }

    /**
     * Create the registration table.
     * @return bool false if failed to create table, else true
     */
    function createTable()
    {
        $ct = $this->dbh->prepare(
            "CREATE TABLE {$this->table} (
              user_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
              first_name VARCHAR(50) NOT NULL,
              last_name VARCHAR(50) NOT NULL,
              email_address VARCHAR(255) NOT NULL UNIQUE,
              password VARCHAR(255) NOT NULL,
              invite_code VARCHAR(255) NOT NULL,
              confirmation_code VARCHAR(32) NOT NULL,
              confirmed_on DATETIME,
              updated_on TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              active BIT NOT NULL DEFAULT 0,
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

    /**
     * Validate the record and assign values where necessary.
     * @param $record
     * @throws Exception
     */
    function validateRecord(&$record)
    {
        if (!isset($record['email_address']) ||
            filter_var($record['email_address'], FILTER_VALIDATE_EMAIL) === FALSE)
        {
            throw new Exception("Email address failed to validate.",
                $this->CONSTRAINTS['EMAIL_NOT_VALID']);
        }
        if (isset($record['password']) && strlen($record['password']) >=
            $this->CONSTRAINTS['MIN_PASSWORD_LENGTH'])
        {
            if (preg_match('/\d/', $record['password'])) // We have at least one digit
            {
                // Salt+hash using blowfish algorithm
                $record['password'] = crypt($record['password'],
                    '$2a$07$' . substr(md5(uniqid(rand(), true)), 0, 22));
            }
            else
            {
                throw new Exception('Password does not contain any digits - must contain at least one.',
                    $this->CONSTRAINTS['PASSWORD_NO_DIGIT']);
            }
        }
        else
        {
            throw new Exception("Password length less than minimum character length
            {$this->CONSTRAINTS['MIN_PASSWORD_LENGTH']}.",
                $this->CONSTRAINTS['PASSWORD_LENGTH']);
        }
        $record['confirmation_code'] = md5(uniqid(rand(), true));
    }

    /**
     * Compare plaintext password to the hash stored in the db, searching on field with value to find the record to
     * compare on.
     * @param $field
     * @param $value
     * @param $plaintext
     * @return bool true if salted and hashed plaintext matches stored hash password, false otherwise.
     */
    function comparePassword($field, $value, $plaintext)
    {
        $sel = $this->selectOne(['fields'=>'password', 'where'=>[$field=>$value]]);
        if ($sel['password'] === crypt($plaintext, substr($sel['password'], 0, 29)))
        {
            return true;
        }
        $this->lastError = new Exception('Passwords do not match.');
        return false;
    }

    /**
     * Set the user as active if the confirmation code matches.
     * @param $userID
     * @param $confirmCode
     * @return bool
     */
    function processConfirmation($userID, $confirmCode)
    {
        $sel = $this->selectOne(['fields'=>'confirmation_code', 'where'=>['user_id'=>$userID]]);
        if ($sel['confirmation_code'] === $confirmCode)
        {
            return $this->update(array('user_id'=>$userID, 'active'=>1,
                'confirmed_on'=>date("Y-m-d H:i:s")));
        }
        $this->lastError = new Exception('Confirmation Code does not match code provided.',
            $this->CONSTRAINTS['CONFIRM_NOT_MATCH']);
        return false;
    }

    function __destruct()
    {
        $this->dbh = null; // Make sure we release this connection
    }
}