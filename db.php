<?php

class db
{
    private $dbhost = '';
    private $dbname = '';
    private $dbpw = '';
    private $dbuser = '';

    public function __construct($dbname, $dbuser, $dbpass, $dbhost = 'localhost', $mode = 'dev')
    {
        $this->dbhost = $dbhost;
        $this->dbname = $dbname;
        $this->dbpass = $dbpass;
        $this->dbuser = $dbuser;

        $this->mode = $mode;
    }

    public function conn()
    {
        try
        {
            $dbh = new PDO("mysql:host={$this->dbhost};dbname={$this->dbname}", $this->dbuser, $this->dbpw);
            $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // for debugging
            return $dbh;
        }
        catch (PDOException $exc)
        {
            $this->handleException($exc);
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
    public function safeExecute(PDOStatement &$stmt)
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
     * Note: this function opens and closes a db connection for every query.
     * 
     * @param string $sql
     * @return mixed PDOStatement on success, boolean false on error.
     */
    public function safeQuery($sql)
    {
        $conn = $this->conn();
        try
        {
            $stmt = $conn->query($sql);
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
        $conn = null;
    }

    private function handleException($exc)
    {
        if ($this->mode = 'dev')
        {
            echo $exc->getMessage();
        }
        else if ($this->mode = 'prod')
        {
            error_log($exc->getMessage());
        }
    }

    private function handlePdoError($stmt)
    {
        if ($this->mode = 'dev')
        {
            print_r($stmt->errorInfo());
        }
        else if ($this->mode = 'prod')
        {
            error_log(implode(' - ', $stmt->errorInfo()));
        }
    }
}