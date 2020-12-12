<?php

namespace App\Application\Redis;

use Generator;

interface RedisStorageInterface
{
    public const TYPE_SCALAR  = 'scalar';
    public const TYPE_SET     = 'set';
    public const TYPE_LIST    = 'list';
    public const TYPE_ZSET    = 'zset';
    public const TYPE_HASH    = 'hash';
    public const TYPE_UNKNOWN = 'unknown';

    ##################################################### DB ###########################################################

    /**
     * @return int
     */
    public function dbSize(): int;

    /**
     * @return bool
     */
    public function flushDb(): bool;

    #################################################### KEYS ##########################################################

    /**
     * @param string $key
     * @return bool
     */
    public function exist(string $key): bool;

    /**
     * @param array $keys
     * @return array
     */
    public function exists(array $keys): array;

    /**
     * @param string ...$keys
     * @return int
     */
    public function del(string ...$keys): int;

    /**
     * @param array $keys
     * @return array
     */
    public function mDel(array $keys): array;

    /**
     * @return string|null
     */
    public function randomKey(): ?string;

    /**
     * @param string $srcKey
     * @param string $dstKey
     * @return bool
     */
    public function rename(string $srcKey, string $dstKey): bool;

    /**
     * @param string $srcKey
     * @param string $dstKey
     * @return bool
     */
    public function renameNX(string $srcKey, string $dstKey): bool;

    /**
     * @param string $key
     * @param int    $ttl
     * @return bool
     */
    public function expire(string $key, int $ttl): bool;

    /**
     * @param string $key
     * @param int    $timestamp
     * @return bool
     */
    public function expireAt(string $key, int $timestamp): bool;

    /**
     * @param string $key
     * @return int|null
     */
    public function ttl(string $key): ?int;

    /**
     * @param $key
     * @return bool
     */
    public function persist($key): bool;

    /**
     * @param string $pattern
     * @return array
     */
    public function keys(string $pattern): array;

    /**
     * @param string|null $pattern
     * @return Generator
     */
    public function scan(string $pattern = null): Generator;

    /**
     * @param string $key
     * @return string
     */
    public function type(string $key): string;


    ################################################ KEY - VALUE #######################################################

    /**
     * @param string $key
     * @return int|string|float|array|null
     */
    public function get(string $key);

    /**
     * @param array $keys
     * @return array
     */
    public function mGet(array $keys): array;

    /**
     * @param string                 $key
     * @param int|string|float|array $value
     * @param int|null               $ttl
     * @return bool
     */
    public function set(string $key, $value, int $ttl = null): bool;

    /**
     * @param string                 $key
     * @param int|string|float|array $value
     * @param int|null               $ttl
     * @return bool
     */
    public function setNx(string $key, $value, int $ttl = null): bool;

    /**
     * @param array    $data
     * @param int|null $ttl
     * @return array
     */
    public function mSet(array $data, int $ttl = null): array;

    /**
     * @param array    $data
     * @param int|null $ttl
     * @return array
     */
    public function mSetNx(array $data, int $ttl = null): array;

    /**
     * @param string                 $key
     * @param int|string|float|array $value
     * @return int|string|float|array|null
     */
    public function getSet(string $key, $value);

    /**
     * @param string   $key
     * @param int      $value (default = 1)
     * @param int|null $ttl
     * @return int
     */
    public function incrBy(string $key, int $value = 1, int $ttl = null): int;

    /**
     * @param string $key
     * @param int    $value (default = 1)
     * @return int
     */
    public function decrBy(string $key, int $value = 1): int;


    ################################################## HASH TABLE ######################################################

    /**
     * @param string                 $key
     * @param string                 $hashKey
     * @param int|string|float|array $value
     * @return bool
     */
    public function hSet(string $key, string $hashKey, $value): bool;

    /**
     * @param string $key
     * @param array  $data
     * @return array
     */
    public function hMSet(string $key, array $data): array;

    /**
     * @param string                 $key
     * @param string                 $hashKey
     * @param int|string|float|array $value
     * @return bool
     */
    public function hSetNx(string $key, string $hashKey, $value): bool;

    /**
     * @param string $key
     * @param array  $data
     * @return array
     */
    public function hMSetNx(string $key, array $data): array;

    /**
     * @param string $key
     * @param string $hashKey
     * @return int|string|float|array|null
     */
    public function hGet(string $key, string $hashKey);

    /**
     * @param string $key
     * @param array  $hashKeys
     * @return array
     */
    public function hMGet(string $key, array $hashKeys): array;

    /**
     * @param string $key
     * @return int
     */
    public function hLen(string $key): int;

    /**
     * @param string $key
     * @param string ...$hashKeys
     * @return int
     */
    public function hDel(string $key, string ...$hashKeys): int;

    /**
     * @param string $key
     * @param array  $hashKeys
     * @return array
     */
    public function hMDel(string $key, array $hashKeys): array;

    /**
     * @param string $key
     * @return array
     */
    public function hKeys(string $key): array;

    /**
     * @param string $key
     * @return array
     */
    public function hValues(string $key): array;

    /**
     * @param string $key
     * @return array
     */
    public function hGetAll(string $key): array;

    /**
     * @param string $key
     * @param string $hashKey
     * @return bool
     */
    public function hExists(string $key, string $hashKey): bool;

    /**
     * @param string $key
     * @param string $hashKey
     * @param int    $value
     * @return int
     */
    public function hIncrBy(string $key, string $hashKey, int $value): int;

    /**
     * @param string      $key
     * @param string|null $pattern
     * @return Generator
     */
    public function hScan(string $key, string $pattern = null): Generator;

    ################################################### SET ############################################################

    /**
     * @param string                 $key
     * @param int|string|float|array ...$members
     * @return int
     */
    public function sAdd(string $key, ...$members): int;

    /**
     * @param string $key
     * @param array  $members
     * @return int
     */
    public function sMAdd(string $key, array $members): int;

    /**
     * @param string                 $key
     * @param int|string|float|array ...$members
     * @return int
     */
    public function sDel(string $key, ...$members): int;

    /**
     * @param string $key
     * @param array  $members
     * @return int
     */
    public function sMDel(string $key, array $members): int;

    /**
     * Уберёт из первого множества, даже если во втором есть это значение
     * @param string                 $srcKey
     * @param string                 $dstKey
     * @param int|string|float|array $member
     * @return bool
     */
    public function sMove(string $srcKey, string $dstKey, $member): bool;

    /**
     * @param string                 $key
     * @param int|string|float|array $value
     * @return bool
     */
    public function sIsMember(string $key, $value): bool;

    /**
     * @param string $key
     * @return int
     */
    public function sCard(string $key): int;

    /**
     * @param string $key
     * @param int    $count
     * @return array
     */
    public function sPop(string $key, int $count = 1): array;

    /**
     * @param string $key
     * @param int    $count
     * @return array
     */
    public function sRandMember(string $key, int $count = 1): array;

    /**
     * @param string ...$keys
     * @return array
     */
    public function sInter(string ...$keys): array;

    /**
     * @param string $dstKey
     * @param string ...$keys
     * @return int
     */
    public function sInterStore(string $dstKey, string ...$keys): int;

    /**
     * @param string ...$keys
     * @return array
     */
    public function sUnion(string ...$keys): array;

    /**
     * @param string $dstKey
     * @param string ...$keys
     * @return int
     */
    public function sUnionStore(string $dstKey, string ...$keys): int;

    /**
     * @param string $key
     * @param string ...$keys
     * @return array
     */
    public function sDiff(string $key, string ...$keys): array;

    /**
     * @param string $dstKey
     * @param string ...$keys
     * @return int
     */
    public function sDiffStore(string $dstKey, string ...$keys): int;

    /**
     * @param string $key
     * @return array
     */
    public function sMembers(string $key): array;

    /**
     * @param string      $key
     * @param string|null $pattern
     * @return Generator
     */
    public function sScan(string $key, string $pattern = null): Generator;

    ################################################# SORTED SET #######################################################

    /**
     * @param string           $key
     * @param int|string|float $member
     * @param int              $score
     * @return bool
     */
    public function zAdd(string $key, $member, int $score): bool;

    /**
     * @param string $key
     * @param int    $posStart
     * @param int    $posEnd
     * @param bool   $withScores
     * @return array
     */
    public function zRange(string $key, int $posStart, int $posEnd, $withScores = false): array;

    /**
     * @param string $key
     * @param int    $posStart
     * @param int    $posEnd
     * @param bool   $withScores
     * @return array
     */
    public function zReversRange(string $key, int $posStart, int $posEnd, $withScores = false): array;

    /**
     * @param string           $key
     * @param int|string|float ...$members
     * @return int
     */
    public function zDel(string $key, ...$members): int;

    /**
     * @param string $key
     * @param int    $scoreStart
     * @param int    $scoreEnd
     * @param bool   $withScores
     * @return array
     */
    public function zRangeByScore(string $key, int $scoreStart, int $scoreEnd, $withScores = false): array;

    /**
     * @param string $key
     * @param int    $scoreStart
     * @param int    $scoreEnd
     * @return int
     */
    public function zCountByScore(string $key, int $scoreStart, int $scoreEnd): int;

    /**
     * @param string $key
     * @param int    $scoreStart
     * @param int    $scoreEnd
     * @return int
     */
    public function zDelRangeByScore(string $key, int $scoreStart, int $scoreEnd): int;

    /**
     * @param string $key
     * @param int    $posStart
     * @param int    $posEnd
     * @return int
     */
    public function zDelRangeByPosition(string $key, int $posStart, int $posEnd): int;

    /**
     * @param string $key
     * @return int
     */
    public function zCard(string $key): int;

    /**
     * @param string           $key
     * @param int|string|float $member
     * @return int|null
     */
    public function zScore(string $key, $member): ?int;

    /**
     * @param string           $key
     * @param int|string|float $member
     * @return int|null
     */
    public function zPosition(string $key, $member): ?int;

    /**
     * @param string           $key
     * @param int|string|float $member
     * @return int|null
     */
    public function zReversePosition(string $key, $member): ?int;

    /**
     * @param string           $key
     * @param int              $value
     * @param int|string|float $member
     * @return int
     */
    public function zIncrBy(string $key, int $value, $member): int;

    /**
     * @param string     $output
     * @param array      $zSetKeys
     * @param array|null $weights
     * @return int
     */
    public function zUnionStoreSum(string $output, array $zSetKeys, array $weights = null): int;

    /**
     * @param string     $output
     * @param array      $zSetKeys
     * @param array|null $weights
     * @return int
     */
    public function zUnionStoreMin(string $output, array $zSetKeys, array $weights = null): int;

    /**
     * @param string     $output
     * @param array      $zSetKeys
     * @param array|null $weights
     * @return int
     */
    public function zUnionStoreMax(string $output, array $zSetKeys, array $weights = null): int;

    /**
     * @param string     $output
     * @param array      $zSetKeys
     * @param array|null $weights
     * @return int
     */
    public function zInterStoreSum(string $output, array $zSetKeys, array $weights = null): int;

    /**
     * @param string     $output
     * @param array      $zSetKeys
     * @param array|null $weights
     * @return int
     */
    public function zInterStoreMin(string $output, array $zSetKeys, array $weights = null): int;

    /**
     * @param string     $output
     * @param array      $zSetKeys
     * @param array|null $weights
     * @return int
     */
    public function zInterStoreMax(string $output, array $zSetKeys, array $weights = null): int;

    /**
     * @param string $key
     * @param int    $count
     * @return array
     */
    public function zPopMax(string $key, int $count = 1): array;

    /**
     * @param string $key
     * @param int    $count
     * @return array
     */
    public function zPopMin(string $key, int $count = 1): array;


    ################################################### LIST ###########################################################

    /**
     * @param string                 $key
     * @param int|string|float|array ...$values
     * @return int
     */
    public function lPush(string $key, ...$values): int;

    /**
     * @param string                 $key
     * @param int|string|float|array ...$values
     * @return int
     */
    public function rPush(string $key, ...$values): int;

    /**
     * @param string $key
     * @return int|string|float|array|null
     */
    public function lPop(string $key);

    /**
     * @param string $key
     * @return int|string|float|array|null
     */
    public function rPop(string $key);

    /**
     * @param string $key
     * @return int
     */
    public function lLen(string $key): int;

    /**
     * @param string $key
     * @param int    $index
     * @return int|string|float|array|null
     */
    public function lIndex(string $key, int $index);

    /**
     * @param string                 $key
     * @param int                    $index
     * @param int|string|float|array $value
     * @return bool
     */
    public function lSet(string $key, int $index, $value): bool;

    /**
     * @param string $key
     * @param int    $start
     * @param int    $end
     * @return array
     */
    public function lRange(string $key, int $start, int $end): array;

    /**
     * @param string $key
     * @param int    $start
     * @param int    $end
     * @return bool
     */
    public function lTrim(string $key, int $start, int $end): bool;

    /**
     * @param string                 $key
     * @param int|string|float|array $value
     * @param int                    $count
     * @return int
     */
    public function lRem(string $key, $value, int $count = 0): int;

    /**
     * @param string                 $key
     * @param int|string|float|array $pivot
     * @param int|string|float|array $value
     * @return int
     */
    public function lInsertBefore(string $key, $pivot, $value): int;

    /**
     * @param string                 $key
     * @param int|string|float|array $pivot
     * @param int|string|float|array $value
     * @return int
     */
    public function lInsertAfter(string $key, $pivot, $value): int;

}