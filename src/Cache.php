<?php

/**
 * This file is part of the EasyCore package.
 *
 * (c) Marcin Stodulski <marcin.stodulski@devsprint.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace mstodulski\cache;

/**
 * psalm notice: https://psalm.dev/361
 * wyjaśnienie: psalm nie wie tego, że ta klasa może być nadpisana w projekcie używającym tej biblioteki
 * @psalm-suppress ClassMustBeFinal
 */
class Cache
{
    public static function getInstalledCache() : CacheSystem|bool
    {
        if (function_exists('xcache_set')) {
            return CacheSystem::xcache;
        } elseif (function_exists('apcu_add')) {
            return CacheSystem::apcu;
        } elseif (function_exists('apc_add')) {
            return CacheSystem::apc;
        } else {
            return false;
        }
    }

    public static function checkIfVariableExistsInCache(string $variableName) : bool
    {
        $variableName = self::getVariablePath($variableName);
        $cacheSystem = self::getInstalledCache();

        /** @noinspection ALL */
        return match ($cacheSystem) {
            CacheSystem::xcache => xcache_isset($variableName),
            CacheSystem::apcu => apcu_exists($variableName),
            CacheSystem::apc => apc_exists($variableName),
            default => false,
        };
    }

    public static function getVariableValueFromCache(string $variableName) : mixed
    {
        $variableName = self::getVariablePath($variableName);
        $cacheSystem = self::getInstalledCache();

        /** @noinspection ALL */
        return match ($cacheSystem) {
            CacheSystem::xcache => xcache_get($variableName),
            CacheSystem::apcu => apcu_fetch($variableName),
            CacheSystem::apc => apc_fetch($variableName),
            default => false,
        };
    }

    public static function setVariableValueInCache(string $variableName, mixed $variableValue, int $expires = 300) : bool
    {
        $variableName = self::getVariablePath($variableName);
        $cacheSystem = self::getInstalledCache();

        /** @noinspection ALL */
        return match ($cacheSystem) {
            CacheSystem::xcache => xcache_set($variableName, $variableValue, $expires),
            CacheSystem::apcu => apcu_store($variableName, $variableValue, $expires),
            CacheSystem::apc => apc_store($variableName, $variableValue, $expires),
            default => false,
        };
    }

    public static function removeVariableFromCache(string $variableName) : bool
    {
        $variableName = self::getVariablePath($variableName);
        $cacheSystem = self::getInstalledCache();

        /** @noinspection ALL */
        return match ($cacheSystem) {
            CacheSystem::xcache => xcache_unset($variableName),
            CacheSystem::apcu => apcu_delete($variableName),
            CacheSystem::apc => apc_delete($variableName),
            default => false,
        };
    }

    private static function getVariablePath(string $variableName) : string
    {
        if (isset($_SERVER['HTTP_HOST'], $_SERVER['SCRIPT_NAME'])) {
            $rewriteBase = str_replace(pathinfo($_SERVER['SCRIPT_NAME'], PATHINFO_BASENAME), '', $_SERVER['SCRIPT_NAME']);
            $rewriteBase = trim($rewriteBase, '/');
            $variableName = str_replace('.', '_', $_SERVER['HTTP_HOST']) . '_' . str_replace('/', '_', $rewriteBase) . '_' . $variableName;
        } elseif (isset($_SERVER['SCRIPT_NAME'])) {
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
            case CacheSystem::xcache:
                for ($i = 0; $i < 3; $i++) {
                    /** @noinspection ALL */
                    $count = xcache_count($i);
                    for ($j = 0; $j < $count; $j++) {
                        /** @noinspection ALL */
                        xcache_clear_cache($i, $j);
                    }
                }
                return true;
            case CacheSystem::apcu:
                /** @noinspection ALL */
                apcu_clear_cache();
                return true;
                break;
            case CacheSystem::apc:
                /** @noinspection ALL */
                apc_clear_cache('user');
                apc_clear_cache();
                return true;
            default:
                return false;
        }
    }
}
