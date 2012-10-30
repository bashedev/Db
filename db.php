<?php

abstract class db
{
    private $dbhost = '';
    private $dbname = '';
    private $dbpass = '';
    private $dbuser = '';
    protected $pdo = null;

    public function __construct($dbname, $dbuser, $dbpass, $dbhost = 'localhost', $mode = 'dev')
    {
        $this->dbhost = $dbhost;
        $this->dbname = $dbname;
        $this->dbpass = $dbpass;
        $this->dbuser = $dbuser;

        $this->mode = $mode;

        $this->pdo = $this->conn();
        
        if (!$this->pdo)
        {
            throw new Exception('Could not connect to DB');
        }
    }

    public function __destruct()
    {
        $this->pdo = null;
    }

    protected function conn()
    {
        try
        {
            $dbh = null;
            if ($this->dbname === 'root')
            {
                $dbh = new PDO("mysql:host={$this->dbhost};", $this->dbuser, $this->dbpass);
            }
            else
            {
                $dbh = new PDO("mysql:host={$this->dbhost};dbname={$this->dbname}", $this->dbuser, $this->dbpass);
            }
            $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // for debugging
            return $dbh;
        }
        catch (PDOException $exc)
        {
            $this->handleException($exc);
        }
        return false;
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
     * Note: this function opens and closes a db connection for every query.
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
        if ($this->mode = 'dev')
        {
            echo $exc->getMessage() . "\n";
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