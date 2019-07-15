<?php

namespace NGSOFT\Manju;

/**
 * Checks if haystack begins with needle
 * @link https://stackoverflow.com/questions/834303/startswith-and-endswith-functions-in-php
 * @param string $haystack
 * @param string $needle
 * @return bool
 */
function startsWith(string $haystack, string $needle): bool {
    return substr_compare($haystack, $needle, 0, strlen($needle)) === 0;
}

/**
 * Checks if haystack ends with needle
 * @link https://stackoverflow.com/questions/834303/startswith-and-endswith-functions-in-php
 * @param string $haystack
 * @param string $needle
 * @return bool
 */
function endsWith(string $haystack, string $needle): bool {
    return substr_compare($haystack, $needle, -strlen($needle)) === 0;
}

/**
 * Convert CamelCased to camel_cased
 * @param string $camelCased
 * @return string
 */
function toSnake(string $camelCased): string {
    return strtolower(preg_replace('/[A-Z]/', '_\\0', lcfirst($camelCased)));
}

/**
 * Convert snake_case to SnakeCase
 * @param string $snake_case
 * @return string
 */
function toCamelCase(string $snake_case): string {
    return preg_replace_callback('/(^|_|\.)+(.)/', function ($match) {
        return ('.' === $match[1] ? '_' : '') . strtoupper($match[2]);
    }, $snake_case);
}

/**
 * Convert array to object
 * @param array $array
 * @return stdClass|array<stdClass>|null
 */
function array_to_object(array $array) {
    if ($json = json_encode($array)) {
        return json_decode($json);
    }
    return null;
}

/**
 * Loads all .php files found recursively
 * @param string $path
 * @return void
 */
function autoloadDir(string $path): void {

    if (is_dir($path)) {
        foreach (scandir($path) as $file) {
            if ($file[0] === ".") continue;
            autoloadDir($path . DIRECTORY_SEPARATOR . $file);
        }
    } else if (is_file($path) and endsWith($path, '.php')) includeOnce($path);
}

/**
 * Find ClassList extending or implementing a parent Class
 * @param string $parentClass An Interface or an extended class
 * @return array<int, string> A list of class extending or implementing given class that are not abstract, traits, or interfaces
 */
function findClassesImplementing(string $parentClass): array {
    $result = [];
    if (
            (class_exists($parentClass) or interface_exists($parentClass))
            and $classList = array_reverse(get_declared_classes())
    ) {
        foreach ($classList as $class) {
            $reflect = new ReflectionClass($class);
            if ($reflect->isAbstract() or $reflect->isTrait() or $reflect->isInterface()) continue;
            if ($reflect->isSubclassOf($parentClass)) $result[] = $class;
        }
    }
    return $result;
}
