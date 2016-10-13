<?php
namespace snow\song\constant;

class Constant
{

    const C_ITERATE = 'iterate';

    const C_INCLUDE = 'include';

    const C_DYNAMIC = 'dynamic';

    const IS_EQUAL = 'isEqual';

    const IS_NOT_EQUAL = 'isNotEqual';

    const IS_GREATER_THAN = 'isGreaterThan';

    const IS_GREATER_EQUAL = 'isGreaterEqual';

    const IS_LESS_THAN = 'isLessThan';

    const IS_LESS_EQUAL = 'isLessEqual';

    const IS_PROPERTY_AVALILABLE = 'isPropertyAvailable';

    const IS_NOT_PROPERTY_AVAILABLE = 'isNotPropertyAvailable';

    const IS_NULL = 'isNull';

    const IS_NOT_NULL = 'isNotNull';

    const IS_EMPTY = 'isEmpty';

    const IS_NOT_EMPTY = 'isNotEmpty';

    public static $CONDITIONAL_TAGS = array(
        self::C_ITERATE,
        self::C_DYNAMIC,
        self::C_INCLUDE,
        self::IS_EQUAL,
        self::IS_NOT_EQUAL,
        self::IS_GREATER_THAN,
        self::IS_GREATER_EQUAL,
        self::IS_LESS_THAN,
        self::IS_LESS_EQUAL,
        self::IS_PROPERTY_AVALILABLE,
        self::IS_NOT_PROPERTY_AVAILABLE,
        self::IS_NULL,
        self::IS_NOT_NULL,
        self::IS_EMPTY,
        self::IS_NOT_EMPTY
    );
    
    const RESULT_TYPE_STRING = 1;
    const RESULT_TYPE_NUMBERIC = 2;
    const RESULT_TYPE_OBJECT = 3;
    const RESULT_TYPE_LIST = 4;
    const RESULT_TYPE_STRING_LIST = 5;
    const RESULT_TYPE_NUMBERIC_LIST = 6;
}

?>