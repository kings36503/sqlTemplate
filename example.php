<?php
use snow\song\compose\SqlComposer;
require_once __DIR__ . '/vendor/autoload.php';
// spl_autoload_extensions('.php');
// spl_autoload_register();

// load configuration file
$sqlComposer = new SqlComposer(__DIR__ . '/sqlmap/sqlmapACC.json', true);

// add some test data
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
        'report.table_1',
        'report.table_2'
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

?>

