<?php
namespace snow\song\util;

use snow\song\constant\Constant;
use DOMDocument;

/**
 * add a configuration utility class
 */
class ConfigurationUtil
{

    /**
     * Read the java ibatis sqlMap XML(&ltsqlMapConfig&gt) configuration file, convert it to JSON format which PHP-ibatis can read.
     * It will add a new JSON file which file name is the same as the XML file in the same directory.
     *
     * @param unknown $fileName            
     */
    public static function convertXmlToJson($fileName)
    {
        $namespaces = [];
        
        $xmlDoc = new DOMDocument();
        $xmlDoc->load($fileName);
        $sqlMapConfig = $xmlDoc->getElementsByTagName('sqlMapConfig');
        $sqlMapConfig = $sqlMapConfig->item(0);
        
        $maps = $sqlMapConfig->getElementsByTagName('sqlMap');
        foreach ($maps as $map) {
            $ext = $map->getAttribute('resource');
            $tempDoc = new DomDocument();
            $tempDoc->load($ext);
            $namespaces = self::namespace2Json($tempDoc->documentElement, $ext);
            $ext = str_replace('.xml', '.json', $ext);
            file_put_contents($ext, json_encode($namespaces, JSON_PRETTY_PRINT));
        }
        
        print_r($namespaces);
    }

    private static function namespace2Json($nameSpace, $fileName)
    {
        $matchArray = [
            'sql',
            'statement',
            'insert',
            'select',
            'update',
            'delete'
        ];
        $nameSpaces = false;
        if ($nameSpace->nodeName == 'sqlMap') {
            $spaceName = $nameSpace->getAttribute('namespace');
            
            if ($nameSpace->hasChildNodes()) {
                foreach ($nameSpace->childNodes as $sqlId) {
                    if (in_array($sqlId->nodeName, $matchArray)) {
                        $nameSpaces[$spaceName][$sqlId->getAttribute('id')] = self::getContentArray($sqlId, $fileName);
                    }
                }
            }
        }
        return $nameSpaces;
    }

    private static function getContentArray($sqlId, $fileName)
    {
        $i = 0;
        
        $array = false;
        
        if ($sqlId->hasChildNodes()) {
            foreach ($sqlId->childNodes as $childNode) {
                if ($childNode->nodeType != XML_TEXT_NODE) {
                    if (in_array($childNode->nodeName, Constant::$CONDITIONAL_TAGS)) {
                        $array[$i]['type'] = $childNode->nodeName;
                        if ($childNode->hasAttributes()) {
                            foreach ($childNode->attributes as $attr) {
                                $array[$i][$attr->nodeName] = $attr->nodeValue;
                            }
                        }
                        if ($contents = self::getContentArray($childNode, $fileName)) {
                            $array[$i]['contents'] = $contents;
                        }
                        $i ++;
                    } else 
                        if ($childNode->nodeName == '#cdata-section') {
                            $array[$i] = self::replaceText($childNode->nodeValue);;
                            $i ++;
                        } else 
                            if ($childNode->nodeName == '#comment') {
                                continue;
                            } else {
                                throw new \Exception('Unrecognised type:' . $childNode->nodeName . ' fileName: ' . $fileName);
                            }
                } else {
                    $plainText = $childNode->nodeValue;
                    if (! empty(preg_replace('/[\r\n\t\s]+/', '', $plainText))) {
                        $array[$i] = self::replaceText($plainText);
                        $i ++;
                    }
                }
            }
        }
        return $array;
    }

    private static function replaceText($text){
        $text = preg_replace('/[\r\n\t\s]+/', ' ', $text);
        $text = str_replace(':Integer#', '#i', $text);
        $text = str_replace(':Long#', '#i', $text);
        $text = str_replace(':Float#', '#i', $text);
        $text = str_replace(':Double#', '#i', $text);
        return $text;
    }
    
    public static function readFromLocation($jsonLocation, &$namespaces)
    {
        if (is_dir($jsonLocation)) {
            self::readDir($jsonLocation, $namespaces);
        } else
            if (is_file($jsonLocation)) {
                self::readJson($jsonLocation, $namespaces);
            } else {
                throw new \Exception($jsonLocation . ' is not a valid dirctory or file name.');
            }
    }
    
    /**
     * read the configuation dir
     */
    public static function readDir($path, &$namespaces)
    {
        $handler = opendir($path);
        if (! empty($handler)) {
            while (($filename = readdir($handler)) !== false) {
                if ($filename != '.' && $filename != '..' && $filename != null) {
                    if (is_dir($path . '/' . $filename)) {
                        self::readDir($path . '/' . $filename, $namespaces);
                    } else {
                        $filePath = $path . '/' . $filename;
                        self::readJson($filePath, $namespaces);
                    }
                }
            }
            @closedir($handler);
        }
    }

    public static function readJson($fileName, &$namespaces)
    {
        if (is_file($fileName) && strstr($fileName, '.json')) {
            $fInfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($fInfo, $fileName);
            finfo_close($fInfo);
            if ($mimeType == 'text/plain' && $result = file_get_contents($fileName)) {
                // replace comments with blank
                $result = preg_replace('/\/\*.*?\*\//s', '', $result);
                $result = preg_replace('/\/\/.*/', ' ', $result);
                // replace \r\n\t with space
                $result = preg_replace('/[\r\n\t]+/', ' ', $result);
                $arr = json_decode($result, TRUE);
                if (json_last_error()) {
                    throw new \Exception($fileName . ' is not a valid json file, reason : ' . json_last_error_msg());
                }
                if (is_array($arr)) {
                    $namespaces = array_merge_recursive($namespaces, $arr);
                }
            }
        }
    }

    public static function validateConfig($jsonLocation)
    {
        $namespaces = [];
        if (is_dir($jsonLocation)) {
            self::readDir($jsonLocation, $namespaces);
        } else 
            if (is_file($jsonLocation)) {
                ConfigurationUtil::readJson($jsonLocation, $namespaces);
            } else {
                throw new \Exception($jsonLocation . ' is not a valid dirctory or file name.');
            }
        return self::validateNameSpaces($namespaces);
    }

    public static function validateNameSpaces($nameSpaces)
    {
        if (count($nameSpaces) < 1) {
            throw new \Exception('Empty json content!!');
        } else {
            foreach ($nameSpaces as $spaceName => $nameSpace) {
                if (empty($nameSpace['daoName'])) {
                    throw new \Exception("namespace: '$spaceName' has no 'daoName' property. ");
                } else if (is_array($nameSpace['daoName'])) {
                    throw new \Exception("namespace: '$spaceName' has multiple 'daoName' property: " . print_r($nameSpace['daoName']));
                } else {
                    foreach ($nameSpace as $sqlId => $child) {
                        if ($sqlId != 'daoName') {
                            foreach ($child as $index => $item) {
                                if (is_array($item)) {
                                    if (empty($item['type'])) {
                                        throw new \Exception("namespace: '$spaceName', sql id: '$sqlId', index: $index has no 'type' property. ");
                                    }
                                    $typeContext = [];
                                    self::pushTypeContext($index, $item['type'], $typeContext);
                                    self::validateCommonItem($nameSpace, $spaceName, $sqlId, $typeContext, $item);
                                }
                            }
                        }
                    }
                }
            }
            return true;
        }
    }

    private static function validateCommonItem($nameSpace, $spaceName, $sqlId, array &$typeContext, array $item)
    {
        switch ($item['type']) {
            case Constant::C_ITERATE:
                if (empty($item['property'])) {
                    throw new \Exception("namespace: '$spaceName', sql id: '$sqlId', " . implode('\'contents\'==>', $typeContext) . ": 'property' is not set. ");
                }
                break;
            case Constant::C_INCLUDE:
                if (empty($item['refid'])) {
                    throw new \Exception("namespace: '$spaceName', sql id: '$sqlId', " . implode('\'contents\'==>', $typeContext) . ": 'refid' is not set. ");
                }
                break;
            case Constant::C_DYNAMIC:
                if (! empty($item['contents']) && is_array($item['contents'])) {
                    foreach ($item['contents'] as $index => $content) {
                        if (is_array($content)) {
                            self::pushTypeContext($index, $content['type'], $typeContext);
                            self::validateDynamicItem($nameSpace, $spaceName, $sqlId, $typeContext, $content);
                        } else {
                            throw new \Exception("namespace: '$spaceName', sql id: '$sqlId', " . implode('\'contents\'==>', $typeContext) . ": 'content' under dynamic can not be string! ");
                        }
                    }
                } else {
                    throw new \Exception("namespace: '$spaceName', sql id: '$sqlId', " . implode('\'contents\'==>', $typeContext) . ": 'contents' must be array. ");
                }
                break;
            case Constant::IS_EQUAL:
            case Constant::IS_NOT_EQUAL:
            case Constant::IS_GREATER_THAN:
            case Constant::IS_GREATER_EQUAL:
            case Constant::IS_LESS_THAN:
            case Constant::IS_LESS_EQUAL:
                if (empty($item['property'])) {
                    throw new \Exception("namespace: '$spaceName', sql id: '$sqlId', " . implode('\'contents\'==>', $typeContext) . ": 'property' is not set. ");
                }
                if (! isset($item['compareValue'])) {
                    throw new \Exception("namespace: '$spaceName', sql id: '$sqlId', " . implode('\'contents\'==>', $typeContext) . ": 'compareValue' is not set. ");
                }
                break;
            case Constant::IS_PROPERTY_AVALILABLE:
            case Constant::IS_NOT_PROPERTY_AVAILABLE:
            case Constant::IS_NULL:
            case Constant::IS_NOT_NULL:
            case Constant::IS_EMPTY:
            case Constant::IS_NOT_EMPTY:
                if (empty($item['property'])) {
                    throw new \Exception("namespace: '$spaceName', sql id: '$sqlId', " . implode('\'contents\'==>', $typeContext) . ": 'property' is not set. ");
                }
                break;
            default:
                throw new \Exception("namespace: '$spaceName', sql id: '$sqlId', " . implode('\'contents\'==>', $typeContext) . ": is not recognised. ");
        }
        if (! empty($item['contents']) && is_array($item['contents'])) {
            foreach ($item['contents'] as $index => $content) {
                if (is_array($content)) {
                    self::pushTypeContext($index, $content['type'], $typeContext);
                    self::validateCommonItem($nameSpace, $spaceName, $sqlId, $typeContext, $content);
                }
            }
        }
        array_pop($typeContext);
    }

    private static function pushTypeContext($index, $type, &$typeContext)
    {
        $typeContext[] = 'index: ' . $index . '(' . $type . '): ';
    }

    private static function validateDynamicItem($nameSpace, $spaceName, $sqlId, array &$typeContext, array $item)
    {
        switch ($item['type']) {
            case Constant::IS_EQUAL:
            case Constant::IS_NOT_EQUAL:
            case Constant::IS_GREATER_THAN:
            case Constant::IS_GREATER_EQUAL:
            case Constant::IS_LESS_THAN:
            case Constant::IS_LESS_EQUAL:
                if (empty($item['property'])) {
                    throw new \Exception("namespace: '$spaceName', sql id: '$sqlId', " . implode('\'contents\'==>', $typeContext) . ": 'property' is not set. ");
                }
                if (empty($item['compareValue'])) {
                    throw new \Exception("namespace: '$spaceName', sql id: '$sqlId', " . implode('\'contents\'==>', $typeContext) . ": 'compareValue' is not set. ");
                }
                break;
            case Constant::IS_PROPERTY_AVALILABLE:
            case Constant::IS_NOT_PROPERTY_AVAILABLE:
            case Constant::IS_NULL:
            case Constant::IS_NOT_NULL:
            case Constant::IS_EMPTY:
            case Constant::IS_NOT_EMPTY:
                if (empty($item['property'])) {
                    throw new \Exception("namespace: '$spaceName', sql id: '$sqlId', " . implode('\'contents\'==>', $typeContext) . ": 'property' is not set. ");
                }
                break;
            case Constant::C_INCLUDE:
                if (empty($item['refid'])) {
                    throw new \Exception("namespace: '$spaceName', sql id: '$sqlId', " . implode('\'contents\'==>', $typeContext) . ": 'refid' is not set. ");
                } else {
                    $error = false;
                    foreach ($nameSpace[$item['refid']] as $content) {
                        if (! is_array($content) || ! in_array($content['type'], Constant::$CONDITIONAL_TAGS)) {
                            throw new \Exception("namespace: '$spaceName', sql id: '$sqlId', " . implode('\'contents\'==>', $typeContext) . ": " . $item['refid'] . " can not include non-conditional tags when it is referenced by dynamic tag! ");
                        }
                    }
                }
                break;
            case Constant::C_DYNAMIC:
                 break;
            default:
                throw new \Exception("namespace: '$spaceName', sql id: '$sqlId', " . implode('\'contents\'==>', $typeContext) . ": is not allowed as children of 'dynamic' type. ");
        }
        if (! empty($item['contents']) && is_array($item['contents'])) {
            foreach ($item['contents'] as $index => $content) {
                if (is_array($content)) {
                    $typeContext[] = 'index: ' . $index . '(' . $content['type'] . ')';
                    self::validateCommonItem($nameSpace, $spaceName, $sqlId, $typeContext, $content);
                }
            }
        }
        array_pop($typeContext);
    }

}

?>