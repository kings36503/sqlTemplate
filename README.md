# php-ibatis

ibatis-like php based library, it can be used as a sql template, support mysql now, and will support more db type later. PHP version 5.5.10 or later.

# Example Usage

- prepare a mysql/mariaDB server.
- open [src/snow/song/db/mysql/ReportDao.php](https://github.com/kings36503/sqlTemplate/blob/master/src/snow/song/db/mysql/ReportDao.php)
- go to line 15, change the host, username, password to your own mysql/mariaDB server.
- Run [example.php](https://github.com/kings36503/sqlTemplate/blob/master/example.php) in CLI mode: php example.php
~~~php
// load configuration file
$sqlComposer = new SqlComposer(__DIR__ . '/sqlmap/sqlmapACC.json', true);

// create some test data
$sqlComposer->execute('report.createDB');
$sqlComposer->execute('report.createTable1');
$sqlComposer->execute('report.createTable2');
$sqlComposer->execute('report.truncateTable1');
$sqlComposer->execute('report.truncateTable2');

// begin a transcation
try{
    $sqlComposer->beginTranscation('report');
    $sqlComposer->execute('report.addTable1Data', ['count' => [1,2,3,4,5,6,7,8,9,10]]);
    $sqlComposer->execute('report.addTable2Data', ['count' => [1,2,3,4,5,6,7,8,9,10]]);
    $sqlComposer->execute('report.useReport');
}catch(\Exception $e){
    // error accured, roll back.
    $sqlComposer->rollBack('report');
}

// commit
$sqlComposer->endTranscation('report');

// query data from database
$result = $sqlComposer->query('report.getReport', [
    'tableNames' => [
        'table_1',
        'table_2'
    ],
    'ip' => [
        'hasDstIp' => true,
        'srcIp' => 0,
        'dstIp' => [ 
            1,
            2,
            3,
            4,
            5
        ]
    ],
    'alertName' => '%alert name%',
    'limit' => [
        'one' => 0,
        'two' => 10
    ]
]);

print_r($result);
~~~

# Configuration detail

Configuration is JSON format, [schema.json](https://github.com/kings36503/sqlTemplate/blob/master/sqlmap/schema.json) is the json schema of the file. if you are familiar with 
ibatis sqlMap config, it will be easy for you to use. If you never heard of ibatis sqlMap config, 
that all right, you can see the comments as follows:

~~~javascript 
    {
    	/** 
         * MUST 
         * namespace of the config file , one configuration file prefer only one namespace. 
         */
        "report" : {
        /**
         * MUST
         * Class path of the dbdao, it will be initialized in a reflection way. this dao must 
         * implement interface db\IDBDAO. 
		 */
        "daoName" : "snow\\song\\db\\mysql\\ReportDao", 
         /** 
          * MUST ==== SQL ID which value can not be literal 'daoName' stand for a sql statement.  
		  * SQL ID consist of many elements, such as 'iterate', 'dynamic', 'isEqual' etc. 
		  */
		"getReport" : [
			/**
			 * String type element 
			 */
			"SELECT * FROM", 
			{	
				/** 
				 * MUST
				 * Type of the element, can be [iterate, dynamic, include, isEqual, isNotEqual,
				 * isGreaterThan, isGreaterEqual, isLessThan, isLessEqual, isPropertyAvailable, 
				 * isNotPropertyAvailable, isNull, isNotNull, isEmpty, isNotEmpty]. iterate 
				 * stand for a loop. 
				 */
				"type" : "iterate",
				/**
				 * MUST
				 * Perperty name that use to loop, must be an array. dot chains is supported. 
				 */
				"property" : "tableNames", 
				/**
				 * OPTIONAL
				 * Put its value at the begining of the loop. 
				 */
				"open" : "(", 
				/**
				 * OPTIONAL 
				 * Put its value at the end of the loop. 
				 */
				"close" : ") AS t1",
				/**
				 * OPTIONAL 
				 * Conjunction of the loop. used for 'AND' or 'OR' or 'UNION ALL' 
				 */
				"conjunction" : "UNION ALL", 
				/** 
				 * OPTIONAL
				 * A string that can be over write. put it at the front of the sql. 
				 */
				"prepend" : "",
				/** 
				 * OPTIONAL
				 * Contents of the loop, consist of some elements which can be [iterate, dynamic,
				 * include, isEqual, isNotEqual, isGreaterThan, isGreaterEqual, isLessThan,
				 * isLessEqual, isPropertyAvailable, isNotPropertyAvailable, isNull, isNotNull, 
				 * isEmpty, isNotEmpty] 
				 */
				"contents" : [
					/**
					 * String type element 
					 */
					"SELECT sip, dip FROM $tableNames[]$", 
					{
						/**
						 * dynamic means that its contents can only contains conditional element, 
						 * such as : [isEqual, isNotEqual, isGreaterThan, isGreaterEqual, isLessThan,
						 * isLessEqual, isPropertyAvailable, isNotPropertyAvailable, isNull, isNotNull,
						 * isEmpty, isNotEmpty] 
						 */
						"type" : "dynamic",
						"prepend" : "WHERE", 
						"contents" : [
							{
								"type" : "isEqual",
								/**
								 * dot chains example. Asssume you have a parameter: ['ip' => ['dstIp' => 1]],
								 * then you could type a dot in the middle of the properties.
								 */
								"property" : "ip.dstIp",
								"compareValue" : "1",
								"prepend" : "AND",
								"contents" : [
									/**
									 * 'dstIp' is a property name of the param, property between '##' means
									 * it will be treated as a prepared statement. It will be parsed to 
									 * " dip <> ? ". character 'i' means 'dstIp' has type integer. 's' means 
									 * type string, 'd' means type float number, and 'b' means type blob. 
									 * s,i,d,b is optional, default value is 's'.
									 * see http://php.net/manual/en/mysqli-stmt.bind-param.php for details. 
									 */
									" dip <> #ip.dstIp#i "
									] 
							},
							{
								"type" : "isPropertyAvailable",
								"property" : "ip.dstIp",
								"prepend" : "AND",
								"contents" : [
									/**
									 * Property name between '$$' will be replaced by the property value. 
									 * in this case, if srcIp is 0, it will be parsed to " sip >= 0 ".  
									 */
									" sip >= $ip.dstIp$ "
									]
							},
							{
								"type" : "isNotNull",
								"property" : "alertName",
								"prepend" : "AND",
								"contents" : [" alert like #alertName#s "]
							}
						]
					},
					"GROUP BY sip"
				]
			},
			{	
				/**
				 * A 'include' type means it is a reference of other SQL ID. 
				 */
				"type" : "include",
				/**
				 * MUST
				 * Name of other SQL ID in this namespace. 
				 */
				"refid" : "orderBy" 
			}
		],
		"orderBy" : [
			"GROUP BY sip ORDER BY sip",
			{
				"type" : "isNotEmpty",
				"property" : "limit",
				"contents" : ["LIMIT #limit#i"]
			}
		]
	}
}
~~~
