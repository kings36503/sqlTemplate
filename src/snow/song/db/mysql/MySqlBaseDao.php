<?php
namespace snow\song\db\mysql;

use mysqli;
use Exception;
use snow\song\db\IDBDao;

/**
 * Implementation of MYSQL db type, use mysqli.
 */
class MySqlBaseDao implements IDBDao
{

    private $mysqli;

    private $host;

    private $user;

    private $pwd;

    private $db;
    
    private $port;

    function __construct($host = '127.0.0.1', $user = 'root', $pwd = 'password', $db = 'mysql', $port = '3306')
    {
        $this->host = $host;
        $this->user = $user;
        $this->pwd = $pwd;
        $this->db = $db;
        $this->port = $port;
        $this->realConnect();
    }

    private function realConnect()
    {
        if (! is_null($this->mysqli)) {
            $this->mysqli->close();
            $this->mysqli = NULL;
        }
        $this->mysqli = new mysqli($this->host, $this->user, $this->pwd, $this->db, $this->port);
        if ($this->mysqli->connect_error) {
            throw new Exception('Connectin Error (' . $this->mysqli->connect_errno . ') ' . $this->mysqli->connect_error);
        }
    }

    /**
     * Query datas from db
     *
     * @param string $sql            
     * @param array $param
     *            If it is not empty, first element must be a string, contains the type of prepared statment params type,
     *            other elements must params which fullfill prepared statment. see <a href="http://php.net/manual/en/mysqli-stmt.bind-param.php">http://php.net/manual/en/mysqli-stmt.bind-param.php</a>
     * @param string $mode            
     * @return sql result
     */
    function query($sql, array $param = NULL)
    {
        for ($i = 0; $i < 2; $i ++) {
            if ($stmt = $this->mysqli->prepare($sql)) {
                if (! empty($param)) {
                    call_user_func_array(array(
                        $stmt,
                        'bind_param'
                    ), $param);
                }
                $executeRst = $stmt->execute();
                // 执行成功
                if ($executeRst == true) {
                    // select查询 result为true
                    if ($result = $stmt->get_result()) {
                        $resultArray = $result->fetch_all(MYSQLI_ASSOC);
                        foreach ($result->fetch_fields() as $fieldCls) {
                            switch ($fieldCls->type) {
                                case MYSQLI_TYPE_NEWDECIMAL:
                                    $fields[] = $fieldCls->name;
                                    break;
                            }
                        }
                        if (! empty($fields)) {
                            foreach ($resultArray as &$item) {
                                foreach ($fields as $field) {
                                    $item[$field] = floatval($item[$field]);
                                }
                            }
                        }
                        $stmt->close();
                        $result->free();
                        return $resultArray;
                    } else {
                        $stmt->close();
                        if ($this->mysqli->errno === 0) {
                            return true;
                        }
                    }
                }
            } else {
                if ($this->mysqli->errno == 2006 || $this->mysqli->errno == 2013) {
                    $this->realConnect();
                    continue;
                } else {
                    throw new \Exception($this->mysqli->error . "\n" . ' error sql: ' . $sql . ";\n" . ' param: ' . json_encode($param));
                }
            }
        }
    }

    /**
     * Execute sql commands, such as update, delete, insert etc.
     *
     * @param string $sql            
     * @param array $param            
     */
    function execute($sql, array $param = NULL)
    {
        for ($i = 0; $i < 2; $i ++) {
            if (! empty($param)) {
                if ($stmt = $this->mysqli->prepare($sql)) {
                    call_user_func_array(array(
                        $stmt,
                        'bind_param'
                    ), $param);
                    $result = $stmt->execute();
                    $stmt->close();
                    return $result;
                } else {
                    if ($this->mysqli->errno == 2006 || $this->mysqli->errno == 2013) {
                        $this->realConnect();
                        continue;
                    } else {
                        throw new \Exception($this->mysqli->error . "\n" . ' error sql: ' . $sql . ";\n" . ' param: ' . json_encode($param));
                    }
                }
            } else {
                if ($result = $this->mysqli->query($sql)) {
                    return $result;
                } else {
                    if ($this->mysqli->errno == 2006 || $this->mysqli->errno == 2013) {
                        $this->realConnect();
                        continue;
                    } else {
                        throw new \Exception($this->mysqli->error . "\n" . ' error sql: ' . $sql . ";\n" . ' param: ' . json_encode($param));
                    }
                }
            }
        }
    }

    function __destruct()
    {
        $this->clearConnection();
    }

    /**
     * close connection
     */
    function clearConnection()
    {
        if ($this->mysqli) {
            @$this->mysqli->close();
        }
    }

    /*
     * (non-PHPdoc)
     * @see \db\basic\IDao::setUp()
     */
    public function setUp()
    {}

    /*
     * (non-PHPdoc)
     * @see \db\basic\IDao::tearDown()
     */
    public function tearDown()
    {
        return $this->clearConnection();
    }

    /*
     * (non-PHPdoc)
     * @see \dbdao\basic\IDao::beginTransacation()
     */
    public function beginTranscation()
    {
        return $this->mysqli->autocommit(false);
    }

    /*
     * (non-PHPdoc)
     * @see \dbdao\basic\IDao::endTransacation()
     */
    public function endTranscation()
    {
        try {
            return $this->mysqli->commit();
        } catch (\Exception $e) {} finally {
            $this->mysqli->autocommit(true);
        }
    }

    /*
     * (non-PHPdoc)
     * @see \dbdao\basic\IDao::rollBack()
     */
    public function rollBack()
    {
        try {
            return $this->mysqli->rollback();
        } catch (\Exception $e) {} finally {
            $this->mysqli->autocommit(true);
        }
    }
}

?>
