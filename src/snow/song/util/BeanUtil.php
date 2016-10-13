<?php
namespace snow\song\util;

use ReflectionClass;

/**
 *
 * Bean utility.
 *
 * @author kings36503
 *        
 */
class BeanUtil
{

    private static $beanMap;
    /**
     * Get a bean object according bean's class path
     *
     * @param string $beanName
     */
    public static function getClassInstanceByPath($clsPath){
        if (isset(self::$beanMap[$clsPath])) {
            return self::$beanMap[$clsPath];
        }else{
            $reflector = new ReflectionClass($clsPath);
            $instance = $reflector->newInstance();
            self::$beanMap[$clsPath] = $instance;
            return $instance;
        }
    }
}

?>