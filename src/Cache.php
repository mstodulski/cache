<?php
/** @noinspection ALL */

/**
 * This file is part of the EasyCore package.
 *
 * (c) Marcin Stodulski <marcin.stodulski@devsprint.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace mstodulski\cache;

class Cache
{
    const CACHE_SYSTEM_XCACHE = 'xcache';
    const CACHE_SYSTEM_APCU = 'apcu';
    const CACHE_SYSTEM_APC = 'apc';

    public static function getInstalledCache() :string|bool
    {
        if (function_exists('xcache_set')) {
            return self::CACHE_SYSTEM_XCACHE;
        } elseif (function_exists('apcu_add')) {
            return self::CACHE_SYSTEM_APCU;
        } elseif (function_exists('apc_add')) {
            return self::CACHE_SYSTEM_APC;
        } else {
            return false;
        }
    }

    public static function checkIfVariableExistsInCache($variableName) : bool
    {
        $variableName = self::getVariablePath($variableName);
        $cacheSystem = self::getInstalledCache();

        switch ($cacheSystem) {
            case self::CACHE_SYSTEM_XCACHE:
                return xcache_isset($variableName);
            case self::CACHE_SYSTEM_APCU:
                return apcu_exists($variableName);
            case self::CACHE_SYSTEM_APC:
                return apc_exists($variableName);
            default:
                return false;
        }
    }

    public static function getVariableValueFromCache(string $variableName) : mixed
    {
        $variableName = self::getVariablePath($variableName);
        $cacheSystem = self::getInstalledCache();

        switch ($cacheSystem) {
            case self::CACHE_SYSTEM_XCACHE:
                return xcache_get($variableName);
            case self::CACHE_SYSTEM_APCU:
                return apcu_fetch($variableName);
            case self::CACHE_SYSTEM_APC:
                return apc_fetch($variableName);
            default:
                return false;
        }
    }

    public static function setVariableValueInCache(string $variableName, mixed $variableValue, int $expires = 300) : bool
    {
        $variableName = self::getVariablePath($variableName);
        $cacheSystem = self::getInstalledCache();

        switch ($cacheSystem) {
            case self::CACHE_SYSTEM_XCACHE:
                return xcache_set($variableName, $variableValue, $expires);
            case self::CACHE_SYSTEM_APCU:
                return apcu_store($variableName, $variableValue, $expires);
            case self::CACHE_SYSTEM_APC:
                return apc_store($variableName, $variableValue, $expires);
            default:
                return false;
        }
    }

    public static function removeVariableFromCache(string $variableName) : bool
    {
        $variableName = self::getVariablePath($variableName);
        $cacheSystem = self::getInstalledCache();

        switch ($cacheSystem) {
            case self::CACHE_SYSTEM_XCACHE:
                return xcache_unset($variableName);
            case self::CACHE_SYSTEM_APCU:
                return apcu_delete($variableName);
            case self::CACHE_SYSTEM_APC:
                return apc_delete($variableName);
            default:
                return false;
        }
    }

    private static function getVariablePath(string $variableName) : string
    {
        if (isset($_SERVER['HTTP_HOST'])) {
            $rewriteBase = str_replace(pathinfo($_SERVER['SCRIPT_NAME'], PATHINFO_BASENAME), '', $_SERVER['SCRIPT_NAME']);
            $rewriteBase = trim($rewriteBase, '/');
            $variableName = str_replace('.', '_', $_SERVER['HTTP_HOST']) . '_' . str_replace('/', '_', $rewriteBase) . '_' . $variableName;
        }
        else {
            $rewriteBase = str_replace(pathinfo($_SERVER['SCRIPT_NAME'], PATHINFO_BASENAME), '', $_SERVER['SCRIPT_NAME']);
            $rewriteBase = trim($rewriteBase, '/');
            $variableName = str_replace('/', '_', $rewriteBase) . '_' . $variableName;
        }

        return $variableName;
    }

    public static function clearCache() : bool
    {
        $cacheSystem = self::getInstalledCache();

        switch ($cacheSystem) {
            case self::CACHE_SYSTEM_XCACHE:
                xcache_clear_cache('user');
                xcache_clear_cache('system');
                return true;
                break;
            case self::CACHE_SYSTEM_APCU:
                apcu_clear_cache();
                return true;
                break;
            case self::CACHE_SYSTEM_APC:
                apc_clear_cache('user');
                apc_clear_cache();
                return true;
                break;
            default:
                return false;
        }

        return true;
    }
}
