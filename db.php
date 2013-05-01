<?php

/**
 *  Copyright Bashe Development
 *  2012
 *
 *  All rights reserved
 *
 */
abstract class db extends PDO
{

    private $mode;

    public function __construct($name, $user, $pass, $host = 'localhost', $mode = 'dev')
    {
        $this->mode = $mode;
        
        $dsn = ($name === 'root') ? "mysql:host=$host" : "mysql:dbname=$name;host=$host";
        parent::__construct($dsn, $user, $pass);
        
        $this->setAttribute(self::ATTR_ERRMODE, self::ERRMODE_EXCEPTION); // for debugging
    }

    protected function returnRow(PDOStatement $stmt)
    {
        if ($this->safeExecute($stmt) && ($result = $stmt->fetchAll(PDO::FETCH_OBJ)) && (count($result) === 1))
        {
            return $result[0];
        }
        return false;
    }

    /**
     *
     * safeExecute tries to execute the PDOStatement. If a false response is returned
     * from the database, the database error message is logged. If a PDOException is
     * caught, the PDOException error message is logged as well.
     *
     * @param PDOStatement $stmt Fully prepared PDOStatement to be executed.
     * @return <bool> True on success, false on query error or exception.
     */
    protected function safeExecute(PDOStatement &$stmt)
    {
        try
        {
            if ($stmt->execute())
            {
                return true;
            }
            else
            {
                $this->handlePdoError($stmt);
            }
        }
        catch (PDOException $exc)
        {
            $this->handleException($exc);
        }
        return false;
    }

    /**
     * safeQuery tries to query the database with arbitrary sql code. Error checking
     * similar to safeExecute is done. 
     * 
     * @param string $sql
     * @return mixed PDOStatement on success, boolean false on error.
     */
    protected function safeQuery($sql)
    {
        try
        {
            $stmt = $this->pdo->query($sql);
            if ($stmt)
            {
                return $stmt;
            }
            else
            {
                $this->handlePdoError($stmt);
            }
        }
        catch (PDOException $exc)
        {
            $this->handleException($exc);
        }
    }

    private function handleException($exc)
    {
        if ($this->mode == 'dev')
        {
            echo $exc->getMessage() . "\n";
        }
        else if ($this->mode == 'prod')
        {
            error_log($exc->getMessage());
        }
    }

    private function handlePdoError($stmt)
    {
        if ($this->mode == 'dev')
        {
            print_r($stmt->errorInfo());
        }
        else if ($this->mode == 'prod')
        {
            error_log(implode(' - ', $stmt->errorInfo()));
        }
    }

}