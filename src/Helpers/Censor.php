<?php

namespace App\Helpers;

/**
 *  * Class for make regular expression tests for incoming values
 *   * Class Censor
 *    * @package Helpers
 *     */
class Censor
{

    private static $expressions = array(
        'any_symbols'           => '/^.*$/',
        'any_symbols_not_empty' => '/^.+$/m',
        'any_digits'            => '/^[0-9]+$/',
        'name'                  => '/^[a-zA-z1-9 @\.]+$/',
        'password'              => '/^[a-zA-Z0-9 #*:()%^@\-_\$\.]{12,26}$/',
        'select'                => '/^([1-9]{1})|([0-9]{2,})$/',
        'smscode'               => '/^[0-9]{4}$/',
        'yes_no'          => '/^(yes|no){1}$/',
        'float'           => '/^(\d+|.\d+|\d+.\d+)$/',

        'email' => '/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix',
    );

    private static $converted = array();

    /**
     * Test if passed regular expression name exist in class
     * @param string $name regular expression name
     * @return bool true if expression exist
     */
    public static function has($name)
    {
        return isset(self::$expressions[$name]);
    }

    /**
     * Get regular expression by name
     * @param string $name regular expression name in censor class
     * @return string regular exception patter
     * @throws \InvalidArgumentException throws in case of undefined regular expression name
     */
    public static function get($name)
    {
        if (!self::has($name)) {
            throw new \InvalidArgumentException("Expression with name - $name is not exist");
        }

        return self::$expressions[$name];
    }

    /**
     * Set new regular expression in class
     * @param string $name regular expression name for store
     * @param string $pattern regular exception patter
     */
    public static function set($name, $pattern)
    {
        self::$expressions[$name] = $pattern;
    }

    /**
     * Check is passed value pass the regular expression test
     * @param mixed $value value for test
     * @param string $args name of the regular expression in censor class
     * @return bool true if value pass the regular expression check
     */
    public static function is($value, $args)
    {
        $pass = true;

        $arguments = func_get_args();
        foreach ($arguments as $key => $name) {
            if (!$key) {
                continue;
            }

            self::get($name);
            if (!isset(self::$converted[$name])) {
                self::$converted[$name] = self::convert(self::$expressions[$name]);
            }
            if (!$pass = preg_match(self::$converted[$name], $arguments[0])) {
                break;
            }
        }

        return (bool)$pass;
    }

    /**
     *
     * @param mixed $value target value
     * @return bool true if value is cysend voucher
     * @throws \InvalidArgumentException throws in case of undefined regular expression name
     */
    public static function __callStatic($name, $arg)
    {
        $is = substr($name, 0, 2);
        $rule = strtolower(substr($name, 2));
        if ($is == 'is' && array_key_exists($rule, self::$expressions)) {
            return self::is($arg[0], $rule);
        } else {
            throw new \InvalidArgumentException("Rule with name - $rule is not exist");
        }
    }

    /**
     * getRegexp return regular expression with name as $name
     *
     * @param string $name
     * @return string
     */
    public static function getRegexp($name)
    {
        return isset(self::$expressions[$name]) ? self::$expressions[$name] : '';
    }

    /**
     * Replace UTF-8 characters by PHP regular standard
     * @param string $pattern regular expression pattern to convert
     * @return string converted regular expression pattern
     */
    private static function convert($pattern)
    {
        return preg_replace('/\\\\u([0-9a-fA-F]{4})/', '\x{${1}}', $pattern) . 'u';
    }
}

