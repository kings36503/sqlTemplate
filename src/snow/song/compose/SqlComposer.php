<?php
namespace snow\song\compose;

use snow\song\util\BeanUtil;
use snow\song\constant\Constant;
use snow\song\util\ConfigurationUtil;
use snow\song\db\IDBDao;

/**
 * php-ibatis sqlComposer entry class.
 *
 * @version 0.0.1
 * @author kings36503
 */
class SqlComposer
{

    /**
     * contains all namespaces
     *
     * @var unknown
     */
    private $nameSpaces;

    private static $daoArr;
    
    /**
     *
     * @param string $jsonLocations
     *            array contain filename or directory is accepted. Or single filename or directory.
     * @param boolean $validate
     *            validation of configuration file, if it is not valid, Exception will throw.
     *            It is recommanded to set TRUE in test mode.
     *            You could switch it to FALSE in product mode for the sake of performence.
     */
    function __construct($jsonLocations, $validate = false)
    {
        $namespaces = [];
        if (is_array($jsonLocations)) {
            foreach ($jsonLocations as $jsonLocation) {
                ConfigurationUtil::readFromLocation($jsonLocation, $namespaces);
            }
        } else {
            ConfigurationUtil::readFromLocation($jsonLocations, $namespaces);
        }
        
        if ($validate == TRUE) {
            ConfigurationUtil::validateNameSpaces($namespaces);
        }
        $this->nameSpaces = $namespaces;
    }

    
    /**
     * get a single string from db.
     * return example : 'string_value'
     *
     * @param unknown $nsDotSqlId            
     * @param array $param            
     */
    public function queryForString($nsDotSqlId, array $param = null)
    {
        return $this->query($nsDotSqlId, $param, Constant::RESULT_TYPE_STRING);
    }

    /**
     * get a single number from db.
     * return example : 123
     *
     * @param unknown $nsDotSqlId            
     * @param array $param            
     */
    public function queryForNumberic($nsDotSqlId, array $param = null)
    {
        return $this->query($nsDotSqlId, $param, Constant::RESULT_TYPE_NUMBERIC);
    }

    /**
     * get an object from db.
     * return example : ['attr1'=> value1, 'attr2' => 345 ]
     *
     * @param unknown $nsDotSqlId            
     * @param array $param            
     */
    public function queryForObject($nsDotSqlId, array $param = null)
    {
        return $this->query($nsDotSqlId, $param, Constant::RESULT_TYPE_OBJECT);
    }

    /**
     * get an object array from db.
     * return example : [['attr1'=> value1, 'attr2' => 345], ['attr1'=> value2, 'attr2' => 569]]
     *
     * @param unknown $nsDotSqlId            
     * @param array $param            
     */
    public function queryForList($nsDotSqlId, array $param = null)
    {
        return $this->query($nsDotSqlId, $param, Constant::RESULT_TYPE_LIST);
    }

    /**
     * get an number array from db.
     * return example : [ 1, 2, 3, 4 ]
     *
     * @param unknown $nsDotSqlId            
     * @param array $param            
     */
    public function queryForNumbericList($nsDotSqlId, array $param = null)
    {
        return $this->query($nsDotSqlId, $param, Constant::RESULT_TYPE_NUMBERIC_LIST);
    }

    /**
     * get an string array from db.
     * return example : [ '1', '2', '3', '4' ]
     *
     * @param unknown $nsDotSqlId            
     * @param array $param            
     */
    public function queryForStringList($nsDotSqlId, array $param = null)
    {
        return $this->query($nsDotSqlId, $param, Constant::RESULT_TYPE_STRING_LIST);
    }

    /**
     * begin a transcation.
     * 
     * @param unknown $spaceName            
     */
    public function beginTranscation($spaceName)
    {
        $this->getDaoBySpaceName($spaceName)->beginTranscation();
    }

    /**
     * end a transcation.
     * 
     * @param unknown $spaceName            
     */
    public function endTranscation($spaceName)
    {
        $this->getDaoBySpaceName($spaceName)->endTranscation();
    }

    /**
     * roll back database changes.
     * 
     * @param unknown $spaceName            
     */
    public function rollBack($spaceName)
    {
        $this->getDaoBySpaceName($spaceName)->rollBack();
    }

    /**
     * 
     * change db data.
     * fit for the Sql begin with "CREATE, DELETE, INSERT INTO, TRUNCATE, USE".
     * @param unknown $nsDotSqlId
     * @param string $param
     * @return boolean 
     */
    public function execute($nsDotSqlId, $param = null)
    {
        return $this->queryOrExecute($nsDotSqlId, $param, false);
    }

    /**
     * get some data from db.
     * fit for the Sql begin with "SELECT".
     * 
     * @param unknown $nsDotSqlId
     *            namespace.sqlid
     * @param string $param            
     * @param integer $resultType
     *            <br>
     *            Constant::RESULT_TYPE_STRING_LIST <br>
     *            Constant::RESULT_TYPE_STRING <br>
     *            Constant::RESULT_TYPE_NUMBERIC <br>
     *            Constant::RESULT_TYPE_NUMBERIC_LIST <br>
     *            Constant::RESULT_TYPE_OBJECT <br>
     *            Constant::RESULT_TYPE_LIST <br>
     * @throws \Exception
     */
    public function query($nsDotSqlId, $param = null, $resultType = Constant::RESULT_TYPE_LIST)
    {
        return self::queryOrExecute($nsDotSqlId, $param, true, $resultType);
    }

    private function getDaoBySpaceName($spaceName)
    {
        if (! empty($this->nameSpaces[$spaceName])) {
            if (! empty($this->nameSpaces[$spaceName]['daoName'])) {
                $daoName = $this->nameSpaces[$spaceName]['daoName'];
                if (is_array($daoName)) {
                    $daoName = $daoName[0];
                }
                if (isset(self::$daoArr[$daoName])) {
                    $dbDao = self::$daoArr[$daoName];
                } else {
                    $dbDao = BeanUtil::getClassInstanceByPath($daoName);
                    self::$daoArr[$daoName] = $dbDao;
                }
                return $dbDao;
            } else {
                throw new \Exception('Invalid json sql config, \'daoName\' property is not configured.');
            }
        } else {
            throw new \Exception("There is no namespace named $spaceName!");
        }
    }

    private function queryOrExecute($nsDotSqlId, $param = null, $query = true, $resultType = Constant::RESULT_TYPE_LIST)
    {
        if (! empty($nsDotSqlId)) {
            if (strstr($nsDotSqlId, '.') != false) {
                $devidedIds = explode('.', $nsDotSqlId);
                if (count($devidedIds) == 2) {
                    if (! empty($this->nameSpaces[$devidedIds[0]])) {
                        if (! empty($this->nameSpaces[$devidedIds[0]][$devidedIds[1]])) {
                            return self::parseSqlJsonAndQuery($this->nameSpaces[$devidedIds[0]], $this->nameSpaces[$devidedIds[0]][$devidedIds[1]], $this->getDaoBySpaceName($devidedIds[0]), $query, $param, $resultType);
                        } else {
                            throw new \Exception('There is no sql id named \'' . $devidedIds[1] . '\' in namespace : \'' . $devidedIds[0] . '\' ');
                        }
                    } else {
                        throw new \Exception('There is no namespace named \'' . $devidedIds[0] . '\' ');
                    }
                } else {
                    throw new \Exception('Invalid $nsDotSqlId : \'' . $nsDotSqlId . '\' ');
                }
            } else {
                throw new \Exception('Invalid $nsDotSqlId : \'' . $nsDotSqlId . '\' ');
            }
        } else {
            throw new \Exception('$nsDotSqlId can not be empty!' . ' ');
        }
    }

    private static function parseSqlJsonAndQuery(array $nameSpace, array $sqlJson, IDBDao $dbDao, $query = true, array $param = null, $resultType = Constant::RESULT_TYPE_LIST)
    {
        $sqlStr = '';
        $types = '';
        $stmtParams = [];
        $result = [];
        self::parseAllTypes($nameSpace, $sqlJson, $result, $param);
        $wholeSql = implode(' ', $result);
        if (! empty($param)) {
            // process $$
            self::parseDollarTags($wholeSql, $param);
            // process ##
            self::parsePreStmtTags($wholeSql, $param, $types, $stmtParams);
        }
        
        if (empty($types)) {
            $wholeParams = NULL;
        } else {
            $wholeParams[] = $types;
            foreach ($stmtParams as &$item) {
                $wholeParams[] = &$item;
            }
        }
        if ($query === true) {
            $result = $dbDao->query($wholeSql, $wholeParams);
            return self::customResult($result, $resultType);
        } else {
            return $dbDao->execute($wholeSql, $wholeParams);
        }
    }

    private static function customResult(&$result, $resultType = Constant::RESULT_TYPE_LIST)
    {
        switch ($resultType) {
            case Constant::RESULT_TYPE_STRING:
                if (! empty($result)) {
                    if (count($result) == 1) {
                        if (count(array_values($result[0])) == 1) {
                            return strval(array_values($result[0])[0]);
                        }
                    }
                }
                throw new \Exception('result from sql can not case to String type!');
                break;
            case Constant::RESULT_TYPE_NUMBERIC:
                if (! empty($result)) {
                    if (count($result) == 1) {
                        if (count(array_values($result[0])) == 1) {
                            return floatval(array_values($result[0])[0]);
                        }
                    }
                }
                throw new \Exception('result from sql can not case to Numberic type!');
                break;
            case Constant::RESULT_TYPE_OBJECT:
                if (count($result) == 1) {
                    return $result[0];
                }
                throw new \Exception('mulitiple objects returned, which object do you want?!!');
                break;
            case Constant::RESULT_TYPE_NUMBERIC_LIST:
                foreach ($result as $item) {
                    $ret[] = floatval(array_values($item)[0]);
                }
                return $ret;
                break;
            case Constant::RESULT_TYPE_STRING_LIST:
                foreach ($result as $item) {
                    $ret[] = strval(array_values($item)[0]);
                }
                return $ret;
                break;
            case Constant::RESULT_TYPE_LIST:
            default:
                return $result;
                break;
        }
    }

    private static function parseDollarTags(&$sql, array $param)
    {
        $regex = '/\$([a-zA-Z0-9_.]+)\$/i';
        $sql = preg_replace_callback($regex, function ($matches) use($param) {
            return self::getValue($param, $matches[1]);
        }, $sql);
    }

    private static function parsePreStmtTags(&$sql, array $param, &$types, &$stmtParams)
    {
        $regex = '/\#([a-zA-Z0-9_.]+)\#([ids]|)/i';
        $sql = preg_replace_callback($regex, function ($matches) use($param, &$types, &$stmtParams) {
            $wholeArr = explode('#', $matches[0]);
            if (empty($wholeArr[2])) {
                // if type: 'i','d','s' is not written, 's' is default value
                $types .= 's';
            } else {
                $types .= $wholeArr[2];
            }
            $stmtParams[] = self::getValue($param, $wholeArr[1]);
            return '?';
        }, $sql);
    }

    private static function parseInclude(array $nameSpace, array $wholeJson, array &$result, array &$param = null)
    {
        if (! empty($nameSpace[$wholeJson['refid']])) {
            self::parseAllTypes($nameSpace, $nameSpace[$wholeJson['refid']], $result, $param);
        } else {
            throw new \Exception('can not find refid : \'' . $wholeJson['refid'] . '\'');
        }
    }

    private static function parseIterate(array $nameSpace, array $wholeJson, array &$result, array &$param = null)
    {
        $iterateResult = [];
        $propertyValue = self::getValue($param, $wholeJson['property']);
        if (! empty($wholeJson['property']) && is_array($propertyValue)) {
            if (! empty($wholeJson['prepend'])) {
                $result[] = $wholeJson['prepend'];
            }
            if (! empty($wholeJson['open'])) {
                $result[] = $wholeJson['open'];
            }
            
            if (! empty($wholeJson['contents'])) {
                self::parseAllTypes($nameSpace, $wholeJson['contents'], $iterateResult, $param);
            } else {
                throw new \Exception('Iterate configuration invalid!');
            }
            
            $iterateString = implode(' ', $iterateResult);
            $iterateResult = [];
            $mateches = [];
            if (preg_match('/\[\]/', $iterateString)) {
                // 将[]替换成.1 .2 .3 .4 .n , n为property数组长度
                foreach ($propertyValue as $key => $item) {
                    $iterateResult[] = preg_replace_callback('/\[\]/', function ($matches) use($key) {
                        return '.' . $key;
                    }, $iterateString);
                }
            }
            $conjuncation = '';
            if (! empty($wholeJson['conjunction'])) {
                $conjuncation = $wholeJson['conjunction'];
            }
            $result[] = implode(' ' . $conjuncation . ' ', $iterateResult);
            
            if (! empty($wholeJson['close'])) {
                $result[] = $wholeJson['close'];
            }
        }
    }

    private static function getAllDynamicConfigs(&$newContents, array $namespace, array $wholeJson)
    {
        foreach ($wholeJson['contents'] as $index => $json) {
            switch ($json['type']) {
                case Constant::C_INCLUDE:
                    self::getDynamicIncludeConfigs($newContents, $namespace[$json['refid']], $namespace); // array_merge($newContents, $namespace[$json['refid']]);
                    break;
                case Constant::C_DYNAMIC:
                    $newContents[] = self::getDynamicDynamicConfigs($namespace, $json);
                    break;
                case Constant::IS_EQUAL:
                case Constant::IS_NOT_EQUAL:
                case Constant::IS_GREATER_THAN:
                case Constant::IS_GREATER_EQUAL:
                case Constant::IS_LESS_THAN:
                case Constant::IS_LESS_EQUAL:
                case Constant::IS_PROPERTY_AVALILABLE:
                case Constant::IS_NOT_PROPERTY_AVAILABLE:
                case Constant::IS_NULL:
                case Constant::IS_NOT_NULL:
                case Constant::IS_EMPTY:
                case Constant::IS_NOT_EMPTY:
                    $newContents[] = $json;
                    break;
                default:
                    throw new \Exception($json['type'] . ' is not allowed in dynamic tag!');
            }
        }
    }

    private static function getDynamicDynamicConfigs(array $namespace, array $wholeJson)
    {
        $newContents = [];
        foreach ($wholeJson['contents'] as $key => $json) {
            switch ($json['type']) {
                case Constant::C_INCLUDE:
                    self::getDynamicIncludeConfigs($newContents, $namespace[$json['refid']], $namespace);
                    break;
                case Constant::C_DYNAMIC:
                    $newContents[] = self::getDynamicDynamicConfigs($namespace, $json);
                    break;
                case Constant::IS_EQUAL:
                case Constant::IS_NOT_EQUAL:
                case Constant::IS_GREATER_THAN:
                case Constant::IS_GREATER_EQUAL:
                case Constant::IS_LESS_THAN:
                case Constant::IS_LESS_EQUAL:
                case Constant::IS_PROPERTY_AVALILABLE:
                case Constant::IS_NOT_PROPERTY_AVAILABLE:
                case Constant::IS_NULL:
                case Constant::IS_NOT_NULL:
                case Constant::IS_EMPTY:
                case Constant::IS_NOT_EMPTY:
                    $newContents[] = $json;
                    break;
                default:
                    throw new \Exception($json['type'] . ' is not allowed in dynamic tag!');
            }
        }
        unset($wholeJson['contents']);
        $wholeJson['contents'] = $newContents;
        return $wholeJson;
    }

    private static function getDynamicIncludeConfigs(&$newContents, array $refArr, array $namespace)
    {
        foreach ($refArr as $json) {
            switch ($json['type']) {
                case Constant::IS_EQUAL:
                case Constant::IS_NOT_EQUAL:
                case Constant::IS_GREATER_THAN:
                case Constant::IS_GREATER_EQUAL:
                case Constant::IS_LESS_THAN:
                case Constant::IS_LESS_EQUAL:
                case Constant::IS_PROPERTY_AVALILABLE:
                case Constant::IS_NOT_PROPERTY_AVAILABLE:
                case Constant::IS_NULL:
                case Constant::IS_NOT_NULL:
                case Constant::IS_EMPTY:
                case Constant::IS_NOT_EMPTY:
                    $newContents[] = $json;
                    break;
                case Constant::C_INCLUDE:
                    self::getDynamicIncludeConfigs($newContents, $namespace[$json['refid']], $namespace);
                    break;
                case Constant::C_DYNAMIC:
                    $newContents[] = self::getDynamicDynamicConfigs($namespace, $json);
                    break;
                default:
                    throw new \Exception($json['type'] . ' is not allowed in dynamic tag!');
            }
        }
    }

    private static function realParseDynamic($newContents, array $namespace, array $wholeJson, array &$result, array &$param = null)
    {
        // dynamic包含属性prepend，当其内的条件有一个或多个成立时，prepend生效。内部第一个成立条件的prepend要设置成为''
        $retResult = [];
        $numberInResult = 0;
        $conditionIsTrue = false;
        if (! empty($wholeJson['prepend'])) {
            $retResult['index'] = array_push($result, $wholeJson['prepend']) - 1; // $result[] = $wholeJson['prepend'];
            $numberInResult ++;
        }
        
        if (! empty($wholeJson['open'])) {
            $result[] = $wholeJson['open'];
            $numberInResult ++;
        }
        
        $count = 0;
        foreach ($newContents as $json) {
            switch ($json['type']) {
                case Constant::IS_EQUAL:
                case Constant::IS_NOT_EQUAL:
                case Constant::IS_GREATER_THAN:
                case Constant::IS_GREATER_EQUAL:
                case Constant::IS_LESS_THAN:
                case Constant::IS_LESS_EQUAL:
                case Constant::IS_PROPERTY_AVALILABLE:
                case Constant::IS_NOT_PROPERTY_AVAILABLE:
                case Constant::IS_NULL:
                case Constant::IS_NOT_NULL:
                case Constant::IS_EMPTY:
                case Constant::IS_NOT_EMPTY:
                    // 首先判断条件是否成立
                    if (call_user_func_array(__NAMESPACE__ . '\SqlComposer::' . $json['type'], [
                        $json,
                        $param
                    ])) {
                        $conditionIsTrue = true;
                        $count ++;
                        if ($count == 1) {
                            unset($json['prepend']);
                        }
                        // 成立则进行处理
                        self::parseCommonCondition($namespace, $json, $result, $param);
                    }
                    break;
                case Constant::C_DYNAMIC:
                    $retArr = self::realParseDynamic($json['contents'], $namespace, $json, $result, $param);
                    if ($retArr['conditionIsTrue'] == TRUE) {
                        $conditionIsTrue = true;
                        $count ++;
                        if ($count == 1 && isset($retArr['index'])) {
                            unset($json['prepend']);
                            unset($result[$retArr['index']]);
                        }
                    }
                    break;
                default:
            }
        }
        
        if (! empty($wholeJson['close'])) {
            $result[] = $wholeJson['close'];
            $numberInResult ++;
        }
        
        if ($conditionIsTrue == false) {
            for (; $numberInResult > 0; $numberInResult --) {
                array_pop($result);
            }
        }
        $retResult['conditionIsTrue'] = $conditionIsTrue;
        return $retResult;
    }

    /**
     * parse dynamic tag contents, only conditional tags exists under it except 'include' which children are conditional tag and dynamic tag.
     *
     * @param array $contents            
     * @param array $param            
     * @return boolean
     */
    private static function parseDynamic(array $namespace, array $wholeJson, array &$result, array &$param = null)
    {
        // 将所有dynamic下所有的条件集成在一起
        $newContents = [];
        self::getAllDynamicConfigs($newContents, $namespace, $wholeJson);
        
        self::realParseDynamic($newContents, $namespace, $wholeJson, $result, $param);
    }

    private static function parseAllTypes(array $nameSpace, array $contents, array &$result, array &$param = null)
    {
        foreach ($contents as $json) {
            if (! is_array($json)) {
                // 如果是普通的字符串
                $result[] = $json;
            } else {
                switch ($json['type']) {
                    case Constant::C_ITERATE:
                    case Constant::C_INCLUDE:
                    case Constant::C_DYNAMIC:
                    case Constant::IS_EQUAL:
                    case Constant::IS_NOT_EQUAL:
                    case Constant::IS_GREATER_THAN:
                    case Constant::IS_GREATER_EQUAL:
                    case Constant::IS_LESS_THAN:
                    case Constant::IS_LESS_EQUAL:
                    case Constant::IS_PROPERTY_AVALILABLE:
                    case Constant::IS_NOT_PROPERTY_AVAILABLE:
                    case Constant::IS_NULL:
                    case Constant::IS_NOT_NULL:
                    case Constant::IS_EMPTY:
                    case Constant::IS_NOT_EMPTY:
                        call_user_func_array(__NAMESPACE__ . '\SqlComposer::parse' . ucfirst($json['type']), [
                            $nameSpace,
                            $json,
                            &$result,
                            &$param
                        ]);
                        break;
                    default:
                        return false;
                }
            }
        }
    }

    private static function parseCommonCondition(array $namespace, array $json, array &$result, array &$param = null)
    {
        if (! empty($json['prepend'])) {
            $result[] = $json['prepend'];
        }
        if (! empty($json['open'])){
            $result[] = $json['open'];
        }
        self::parseAllTypes($namespace, $json['contents'], $result, $param);
        if (! empty($json['close'])){
            $result[] = $json['close'];
        }
    }

    private static function isEqual(array $json, array $param)
    {
        if (! empty($json['property']) && self::_isset($param, $json['property']) && isset($json['compareValue'])) {
            if (self::getValue($param, $json['property']) === $json['compareValue']) {
                return true;
            }
        }
        return false;
    }

    private static function parseIsEqual(array $namespace, array $json, array &$result, array &$param = null)
    {
        if (self::isEqual($json, $param)) {
            self::parseCommonCondition($namespace, $json, $result, $param);
            return true;
        } else {
            return false;
        }
        throw new \Exception('isEqual configuration invalid!');
    }

    private static function isNotEqual(array $json, array $param)
    {
        return ! self::isEqual($json, $param);
    }

    private static function parseIsNotEqual(array $namespace, array $json, array &$result, array &$param = null)
    {
        if (self::isNotEqual($json, $param)) {
            self::parseCommonCondition($namespace, $json, $result, $param);
            return true;
        } else {
            return false;
        }
        throw new \Exception('isNotEqual configuration invalid!');
    }

    private static function isGreaterThan(array $json, array $param)
    {
        if (! empty($json['property']) && self::_isset($param, $json['property']) && isset($json['compareValue'])) {
            if (self::getValue($param, $json['property']) > $json['compareValue']) {
                return true;
            }
        }
        return false;
    }

    private static function parseIsGreaterThan(array $namespace, array $json, array &$result, array &$param = null)
    {
        if (self::isGreaterThan($json, $param)) {
            self::parseCommonCondition($namespace, $json, $result, $param);
            return true;
        } else {
            return false;
        }
        throw new \Exception('isGreaterThan configuration invalid!');
    }

    private static function isGreaterEqual(array $json, array $param)
    {
        if (! empty($json['property']) && self::_isset($param, $json['property']) && isset($json['compareValue'])) {
            if (self::getValue($param, $json['property']) >= $json['compareValue']) {
                return true;
            }
        }
        return false;
    }

    private static function parseIsGreaterEqual(array $namespace, array $json, array &$result, array &$param = null)
    {
        if (self::isGreaterEqual($json, $param)) {
            self::parseCommonCondition($namespace, $json, $result, $param);
            return true;
        } else {
            return false;
        }
        throw new \Exception('isGreaterEqual configuration invalid!');
    }

    private static function isLessThan(array $json, array $param)
    {
        if (! empty($json['property']) && self::_isset($param, $json['property']) && isset($json['compareValue'])) {
            if (self::getValue($param, $json['property']) < $json['compareValue']) {
                return true;
            }
        }
        return false;
    }

    private static function parseIsLessThan(array $namespace, array $json, array &$result, array &$param = null)
    {
        if (self::isLessThan($json, $param)) {
            self::parseCommonCondition($namespace, $json, $result, $param);
            return true;
        } else {
            return false;
        }
        throw new \Exception('isLessThan configuration invalid!');
    }

    private static function isLessEqual(array $json, array $param)
    {
        if (! empty($json['property']) && self::_isset($param, $json['property']) && isset($json['compareValue'])) {
            if (self::getValue($param, $json['property']) <= $json['compareValue']) {
                return true;
            }
        }
        return false;
    }

    private static function parseIsLessEqual(array $namespace, array $json, array &$result, array &$param = null)
    {
        if (self::isLessEqual($json, $param)) {
            self::parseCommonCondition($namespace, $json, $result, $param);
            return true;
        } else {
            return false;
        }
        throw new \Exception('isLessEqual configuration invalid!');
    }

    private static function isPropertyAvailable(array $json, array $param)
    {
        if (! empty($json['property'])) {
            if (self::_isset($param, $json['property'])) {
                return true;
            }
        }
        return false;
    }

    private static function parseIsPropertyAvailable(array $namespace, array $json, array &$result, array &$param = null)
    {
        if (self::isPropertyAvailable($json, $param)) {
            self::parseCommonCondition($namespace, $json, $result, $param);
            return true;
        } else {
            return false;
        }
        throw new \Exception('isPropertyAvailable configuration invalid!');
    }

    private static function isNotPropertyAvailable(array $json, array $param)
    {
        return ! self::isPropertyAvailable($json, $param);
    }

    private static function parseIsNotPropertyAvailable(array $namespace, array $json, array &$result, array &$param = null)
    {
        if (self::isNotPropertyAvailable($json, $param)) {
            self::parseCommonCondition($namespace, $json, $result, $param);
            return true;
        } else {
            return false;
        }
        throw new \Exception('isNotPropertyAvailable configuration invalid!');
    }

    private static function isNull(array $json, array $param)
    {
        return ! self::isNotNull($json, $param);
    }

    private static function parseIsNull(array $namespace, array $json, array &$result, array &$param = null)
    {
        if (self::isNull($json, $param)) {
            self::parseCommonCondition($namespace, $json, $result, $param);
            return true;
        } else {
            return false;
        }
        throw new \Exception('isNull configuration invalid!');
    }

    private static function isNotNull(array $json, array $param)
    {
        if (! empty($json['property']) && self::_isset($param, $json['property'])) {
            if (self::getValue($param, $json['property']) !== NULL) {
                return true;
            }
        }
        return false;
    }

    private static function parseIsNotNull(array $namespace, array $json, array &$result, array &$param = null)
    {
        if (self::isNotNull($json, $param)) {
            self::parseCommonCondition($namespace, $json, $result, $param);
            return true;
        } else {
            return false;
        }
        throw new \Exception('isNotNull configuration invalid!');
    }

    private static function isEmpty(array $json, array $param)
    {
        return ! self::isNotEmpty($json, $param);
    }

    private static function parseIsEmpty(array $namespace, array $json, array &$result, array &$param = null)
    {
        if (self::isEmpty($json, $param)) {
            self::parseCommonCondition($namespace, $json, $result, $param);
            return true;
        } else {
            return false;
        }
        throw new \Exception('isEmpty configuration invalid!');
    }

    private static function isNotEmpty(array $json, array $param)
    {
        if (! empty($json['property'])) {
            $value = self::getValue($param, $json['property']);
            if ($value === '0' || $value === 0 || $value === false) {
                return true;
            }
            if (! self::_empty($param, $json['property'])) {
                return true;
            }
        }
        return false;
    }

    private static function parseIsNotEmpty(array $namespace, array $json, array &$result, array &$param = null)
    {
        if (self::isNotEmpty($json, $param)) {
            self::parseCommonCondition($namespace, $json, $result, $param);
            return true;
        } else {
            return false;
        }
        throw new \Exception('isNotEmpty configuration invalid!');
    }

    private static function getValue($param, $propertyName)
    {
        $propertyName = '[\'' . implode('\'][\'', explode('.', $propertyName)) . '\']';
        return eval('return isset($param' . $propertyName . ') ? $param' . $propertyName . ' : null;');
    }

    private static function _isset($param, $propertyName)
    {
        $propertyName = '[\'' . implode('\'][\'', explode('.', $propertyName)) . '\']';
        return eval('if(isset($param ' . $propertyName . ')){return true;} return false;');
    }

    private static function _empty($param, $propertyName)
    {
        $propertyName = '[\'' . implode('\'][\'', explode('.', $propertyName)) . '\']';
        return eval('if(empty($param ' . $propertyName . ')){return true;} return false;');
    }
}

?>
