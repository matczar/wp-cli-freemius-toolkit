<?php


namespace CzarSoft\WP_CLI_Freemius_Toolkit\Helpers;


class Utils
{
    public static function endsWith($string, $endString)
    {
        $len = strlen($endString);
        if ($len == 0) {
            return true;
        }
        return (substr($string, -$len) === $endString);
    }
}
