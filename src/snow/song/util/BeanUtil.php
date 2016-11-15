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

    /**
     * Get a bean object according bean's class path
     *
     * @param string $beanName            
     */
    public static function getClassInstanceByPath($clsPath)
    {
        $reflector = new ReflectionClass($clsPath);
        $instance = $reflector->newInstance();
        return $instance;
    }
}

?>