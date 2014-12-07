<?php
/**
 *  Copyright Bashe Development
 *  2014
 *
 *  All rights reserved
 *
 */

namespace bashedev\Db;

/**
 * db is a PDO wrapper class that includes try-catch statements and error handling.
 */
abstract class Db extends \PDO
{

    /**
     *
     * @var string 'dev' mode for debug info
     */
    protected $mode = '';

    /**
     * 
     * @param string $name
     * @param string $user
     * @param string $pass
     * @param string $host
     * @param string $mode
     */
    public function __construct($name, $user, $pass, $host = 'localhost', $mode = 'prod')
    {
        $this->mode = $mode;
        $dsn = ($name === 'root') ? "mysql:host=$host" : "mysql:dbname=$name;host=$host";
        parent::__construct($dsn, $user, $pass, null);
        $this->setAttribute(self::ATTR_ERRMODE, self::ERRMODE_EXCEPTION);
    }

    /**
     * Handles exception by either printing it to the screen with other useful information (dev mode)
     * or logging the exception.
     * 
     * @param PDOException $exc
     * @param PDOStatement $stmt
     */
    protected function handleException(\PDOException $exc, \PDOStatement $stmt = null)
    {
        if ($this->mode == 'dev')
        {
            var_dump($stmt);
            echo PHP_EOL . $exc->getMessage() . PHP_EOL;
        }
        else if ($this->mode == 'prod')
        {
            error_log($exc->getMessage());
        }
    }

    /**
     * Handles PDO error by either printing it to the screen with other useful information (dev mode)
     * or logging the error
     * 
     * @param PDOStatement $stmt
     */
    protected function handlePdoError(\PDOStatement $stmt)
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

    /**
     * Returns a single row as a stdClass object for unique results, false otherwise.
     *  
     * @param \PDOStatement $stmt
     * @return stdClass|boolean
     */
    protected function returnRow(\PDOStatement $stmt)
    {
        if (!$this->safeExecute($stmt))
        {
            return false;
        }
        $result = $stmt->fetchAll(\PDO::FETCH_OBJ);
        if ($result && count($result) === 1)
        {
            return $result[0];
        }
        return false;
    }

    /**
     * safeExecute tries to execute the PDOStatement. If a false response is returned from the 
     * database, the database error message is logged. If a PDOException is caught, the PDOException 
     * error message is logged as well.
     *
     * @param PDOStatement $stmt Fully prepared PDOStatement to be executed.
     * @return bool TRUE on success, FALSE on query error or exception.
     */
    protected function safeExecute(\PDOStatement &$stmt)
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
        catch (\PDOException $exc)
        {
            $this->handleException($exc, $stmt);
        }
        return false;
    }

    /**
     * safeQuery tries to query the database with arbitrary sql code. Error 
     * checking similar to safeExecute is done. 
     * 
     * @param string $sql
     * @return mixed PDOStatement on success, boolean FALSE on error.
     */
    protected function safeQuery($sql)
    {
        try
        {
            $stmt = $this->query($sql);
            if ($stmt)
            {
                return $stmt;
            }
            else
            {
                $this->handlePdoError($stmt);
            }
        }
        catch (\PDOException $exc)
        {
            $this->handleException($exc);
        }
    }

}