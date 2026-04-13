<?php
/**
 * Created by IntelliJ IDEA.
 * User: Nikolay Chervyakov 
 * Date: 27.11.2014
 * Time: 17:55
 */


namespace App\Helpers;


class NameTools 
{
    public static function underscoredToCamelized($name)
    {
        $parts = preg_split('/_+/', $name, -1, PREG_SPLIT_NO_EMPTY);
        return implode('', array_map('ucfirst', $parts));
    }
}