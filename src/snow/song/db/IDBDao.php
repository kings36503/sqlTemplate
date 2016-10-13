<?php
namespace snow\song\db;

/**
 * Interface of the dbdao, all kinds of SQL based Databases (Mysql.
 * Sqlite. Oracle ) can implement it.
 *
 * @author kings36503
 *        
 */
interface IDBDao
{

    function setUp();

    function query($sql, array $param = NULL);

    function execute($sql, array $param = NULL);

    function tearDown();

    function beginTranscation();

    function endTranscation();

    function rollBack();
}

?>