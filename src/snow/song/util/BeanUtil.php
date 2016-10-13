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
     * An array contains beans list which name is the key, and namespace is the value.
     *
     * @var unknown
     */
    private static $beanNameMap = array(
        'report_dao_mysql' => 'snow\song\db\mysql\ReportDao'
    );

    private static $beanMap;
    /**
     * Get a bean object according bean's name
     *
     * @param string $beanName            
     */
    public static function getBean($beanName)
    {
        if (isset(self::$beanMap[$beanName])) {
            return self::$beanMap[$beanName];
        } else {
            $clsPath = self::$beanNameMap[$beanName];
            if (! empty($clsPath)) {
                $reflector = new ReflectionClass($clsPath);
                $item = $reflector->newInstance();
                self::$beanMap[$beanName] = $item;
                return $item;
            } else {
                throw new \Exception('There is no bean identified by : ' . $beanName);
            }
        }
    }
}

?>