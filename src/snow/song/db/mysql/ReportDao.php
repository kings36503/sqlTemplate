<?php
namespace snow\song\db\mysql;

/**
 * bean_id : report_dao_mysql
 *
 * @author kings36503
 *        
 */
class ReportDao extends MySqlBaseDao
{

    public function __construct()
    {
        /**
         * change host, username, password of your own mysql server
         */
        parent::__construct('192.168.43.12', 'root', 'hillstone', 'mysql');
    }

    function __destruct()
    {
        parent::__destruct();
    }
}

?>