<?php

namespace Alchemy\Phrasea\Core\Connection;

use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Connection as BaseConnection;
use Doctrine\DBAL\Result;

/**
 * Accepts named parameters given with their leading colon (":name"), as Doctrine DBAL 2 did.
 */
class Connection extends BaseConnection
{
    public function executeQuery(string $sql, array $params = [], $types = [], ?QueryCacheProfile $qcp = null): Result
    {
        return parent::executeQuery($sql, self::normalizeKeys($params), self::normalizeKeys($types), $qcp);
    }

    public function executeCacheQuery($sql, $params, $types, QueryCacheProfile $qcp): Result
    {
        return parent::executeCacheQuery($sql, self::normalizeKeys($params), self::normalizeKeys($types), $qcp);
    }

    public function executeStatement($sql, array $params = [], array $types = [])
    {
        return parent::executeStatement($sql, self::normalizeKeys($params), self::normalizeKeys($types));
    }

    private static function normalizeKeys(array $values): array
    {
        $normalized = [];
        foreach ($values as $key => $value) {
            if (is_string($key) && str_starts_with($key, ':')) {
                $key = substr($key, 1);
            }
            $normalized[$key] = $value;
        }

        return $normalized;
    }
}
