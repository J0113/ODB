<?php
namespace J0113\ODB;
/**
 * Class SerializeHelper
 * This class will help with data serialization. These functions are extracted from the Wordpress codebase.
 *
 * @copyright https://wordpress.org/about/license/
 * @package WordPress - wp-includes/functions.php
 * @author Wordpress Contributors
 */
class SerializeHelper
{

    /**
     * Unserialize data, if it is serialized.
     * Can be called freely to check if data is unserializable.
     *
     * @param string $data
     * @return mixed
     */
    public static function maybe_unserialize($data)
    {
        if (static::is_serialized($data)) {
            return @unserialize(trim($data));
        }

        return $data;
    }


    /**
     * Serialize if the data can't be stored without it.
     * Arrays and objects will get serilized, normal data will not.
     *
     * @param mixed $data
     * @return string|mixed
     */
    public static function maybe_serialize($data ) {
        if ( is_array( $data ) || is_object( $data ) ) {
            return serialize( $data );
        }
        return $data;
    }

    /**
     * Check if data is serialized
     *
     * @param string $data
     * @param bool $strict
     * @return bool
     */
    public static function is_serialized($data, $strict = true)
    {
        // If it isn't a string, it isn't serialized.
        if (!is_string($data)) {
            return false;
        }
        $data = trim($data);
        if ('N;' === $data) {
            return true;
        }
        if (strlen($data) < 4) {
            return false;
        }
        if (':' !== $data[1]) {
            return false;
        }
        if ($strict) {
            $lastc = substr($data, -1);
            if (';' !== $lastc && '}' !== $lastc) {
                return false;
            }
        } else {
            $semicolon = strpos($data, ';');
            $brace = strpos($data, '}');
            // Either ; or } must exist.
            if (false === $semicolon && false === $brace) {
                return false;
            }
            // But neither must be in the first X characters.
            if (false !== $semicolon && $semicolon < 3) {
                return false;
            }
            if (false !== $brace && $brace < 4) {
                return false;
            }
        }
        $token = $data[0];
        switch ($token) {
            case 's':
                if ($strict) {
                    if ('"' !== substr($data, -2, 1)) {
                        return false;
                    }
                } elseif (false === strpos($data, '"')) {
                    return false;
                }
            // Or else fall through.
            case 'a':
            case 'O':
                return (bool)preg_match("/^{$token}:[0-9]+:/s", $data);
            case 'b':
            case 'i':
            case 'd':
                $end = $strict ? '$' : '';
                return (bool)preg_match("/^{$token}:[0-9.E+-]+;$end/", $data);
        }
        return false;
    }

}