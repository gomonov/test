<?php

namespace App\Infrastructure\Redis;

use App\Application\Redis\RedisStorageInterface;
use Generator;
use Redis;
use RedisException;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use UnexpectedValueException;

class Adapter implements RedisStorageInterface
{
    /** @var string */
    private string $dsn;

    /** @var array */
    private array $option;

    /** @var ?Redis */
    private ?Redis $connection;

    public function __construct(string $dsn, array $option = [])
    {
        $this->dsn    = $dsn;
        $this->option = $option;
        $this->connection = null;
    }

    /**
     * Создание подключения
     * @throws RedisException
     */
    private function getConnection()
    {
        if (is_null($this->connection)) {
            $this->connection = RedisAdapter::createConnection($this->dsn, $this->option);
            if (!$this->connection->isConnected()) {
                throw new RedisException('Connection error');
            }

            $this->connection->setOption(Redis::OPT_SCAN, Redis::SCAN_RETRY);
            $this->connection->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_JSON);
        }
        if (!$this->connection->isConnected()) {
            throw new RedisException('Connection error');
        }
        return $this->connection;
    }

    /**
     * @param       $variable
     * @param array $availableType
     * @throws UnexpectedValueException
     */
    private function checkType($variable, array $availableType = ['integer', 'double', 'string', 'array'])
    {
        if (!in_array(gettype($variable), $availableType)) {
            throw new UnexpectedValueException('Unsupported type: ' . gettype($variable));
        }
    }

    /**
     * @param array $keys
     * @throws UnexpectedValueException
     */
    private function checkTypeKeys(array $keys)
    {
        foreach ($keys as $key) {
            if (!is_string($key)) {
                throw new UnexpectedValueException('Unsupported type key: ' . gettype($key));
            }
        }
    }

    /**
     * @param array $values
     * @param array $availableType
     * @throws UnexpectedValueException
     */
    private function checkTypeValues(array $values, array $availableType = ['integer', 'double', 'string', 'array'])
    {
        foreach ($values as $value) {
            $this->checkType($value, $availableType);
        }
    }

    /**
     * @param array $data
     * @throws UnexpectedValueException
     */
    private function checkData(array $data)
    {
        $this->checkTypeKeys(array_keys($data));
        $this->checkTypeValues(array_values($data));
    }

    ##################################################### DB ###########################################################

    /**
     * @return bool
     * @throws RedisException
     */
    public function flushDb(): bool
    {
        return $this->getConnection()->flushDB();
    }

    /**
     * @return int
     * @throws RedisException
     */
    public function dbSize(): int
    {
        return $this->getConnection()->dbSize();
    }

    #################################################### KEYS ##########################################################

    /**
     * @param string $key
     * @return bool
     * @throws RedisException
     */
    public function exist(string $key): bool
    {
        return (boolean)$this->getConnection()->exists($key);
    }

    /**
     * @param array $keys
     * @return array
     * @throws RedisException
     * @throws UnexpectedValueException
     */
    public function exists(array $keys): array
    {
        $this->checkTypeKeys($keys);
        $pipe = $this->getConnection()->multi();
        foreach ($keys as $key) {
            $pipe->exists($key);
        }
        $result = $pipe->exec();
        return array_combine(
            $keys,
            array_map(
                function ($value) {
                    return (boolean)$value;
                },
                $result
            )
        );
    }

    /**
     * @param string ...$keys
     * @return int
     * @throws RedisException
     */
    public function del(string ...$keys): int
    {
        return $this->getConnection()->del($keys);
    }

    /**
     * @param array $keys
     * @return array
     * @throws RedisException
     * @throws UnexpectedValueException
     */
    public function mDel(array $keys): array
    {
        $this->checkTypeKeys($keys);
        $pipe = $this->getConnection()->multi();
        foreach ($keys as $key) {
            $pipe->del($key);
        }
        $result = $pipe->exec();
        return array_combine(
            $keys,
            array_map(
                function ($value) {
                    return (boolean)$value;
                },
                $result
            )
        );
    }

    /**
     * @return string|null
     * @throws RedisException
     */
    public function randomKey(): ?string
    {
        $result = $this->getConnection()->randomKey();
        return ($result !== false) ? $result : null;
    }

    /**
     * @param string $srcKey
     * @param string $dstKey
     * @return bool
     * @throws RedisException
     */
    public function rename(string $srcKey, string $dstKey): bool
    {
        return $this->getConnection()->rename($srcKey, $dstKey);
    }

    /**
     * @param string $srcKey
     * @param string $dstKey
     * @return bool
     * @throws RedisException
     */
    public function renameNX(string $srcKey, string $dstKey): bool
    {
        return $this->getConnection()->renameNx($srcKey, $dstKey);
    }

    /**
     * @param string $key
     * @param int    $ttl
     * @return bool
     * @throws RedisException
     */
    public function expire(string $key, int $ttl): bool
    {
        return $this->getConnection()->expire($key, $ttl);
    }

    /**
     * @param string $key
     * @param int    $timestamp
     * @return bool
     * @throws RedisException
     */
    public function expireAt(string $key, int $timestamp): bool
    {
        return $this->getConnection()->expireAt($key, $timestamp);
    }

    /**
     * @param string $key
     * @return int|null
     * @throws RedisException
     */
    public function ttl(string $key): ?int
    {
        $result = $this->getConnection()->ttl($key);
        return ($result !== -2) ? $result : null;
    }

    /**
     * @param $key
     * @return bool
     * @throws RedisException
     */
    public function persist($key): bool
    {
        return $this->getConnection()->persist($key);
    }

    /**
     * @param string $pattern
     * @return array
     * @throws RedisException
     */
    public function keys(string $pattern): array
    {
        return $this->getConnection()->keys($pattern);
    }

    /**
     * @param string|null $pattern
     * @return Generator
     * @throws RedisException
     */
    public function scan(string $pattern = null): Generator
    {
        $iterator = null;
        while ($keysIteration = $this->getConnection()->scan($iterator, $pattern, 5000)) {
            foreach ($keysIteration as $key) {
                yield $key;
            }
        }
    }

    /**
     * @param string $key
     * @return string
     * @throws RedisException
     */
    public function type(string $key): string
    {
        $result = $this->getConnection()->type($key);
        switch ($result) {
            case Redis::REDIS_STRING:
                return RedisStorageInterface::TYPE_SCALAR;
            case Redis::REDIS_SET:
                return RedisStorageInterface::TYPE_SET;
            case Redis::REDIS_LIST:
                return RedisStorageInterface::TYPE_LIST;
            case Redis::REDIS_ZSET:
                return RedisStorageInterface::TYPE_ZSET;
            case Redis::REDIS_HASH:
                return RedisStorageInterface::TYPE_HASH;
            default:
                return RedisStorageInterface::TYPE_UNKNOWN;
        }
    }

    ################################################ KEY - VALUE #######################################################

    /**
     * @param string $key
     * @return int|string|float|array|null
     * @throws RedisException
     */
    public function get(string $key)
    {
        $result = $this->getConnection()->get($key);
        return ($result !== false) ? $result : null;
    }

    /**
     * @param array $keys
     * @return array
     * @throws RedisException
     * @throws UnexpectedValueException
     */
    public function mGet(array $keys): array
    {
        $this->checkTypeKeys($keys);
        $result = array_combine($keys, $this->getConnection()->mget($keys));
        return array_map(
            function ($value) {
                return ($value !== false) ? $value : null;
            },
            $result
        );
    }

    /**
     * @param string                 $key
     * @param int|string|float|array $value
     * @param int|null               $ttl
     * @return bool
     * @throws RedisException
     */
    public function set(string $key, $value, int $ttl = null): bool
    {
        $this->checkType($value);
        if (is_null($ttl)) {
            return $this->getConnection()->set($key, $value);
        }
        return $this->getConnection()->set($key, $value, $ttl);
    }

    /**
     * @param string                 $key
     * @param int|string|float|array $value
     * @param int|null               $ttl
     * @return bool
     * @throws RedisException
     * @throws UnexpectedValueException
     */
    public function setNx(string $key, $value, int $ttl = null): bool
    {
        $this->checkType($value);
        if (is_null($ttl)) {
            return $this->getConnection()->setnx($key, $value);
        }
        return $this->getConnection()->set($key, $value, ['nx', 'ex' => $ttl]);
    }

    /**
     * @param array    $data
     * @param int|null $ttl
     * @return array
     * @throws RedisException
     * @throws UnexpectedValueException
     */
    public function mSet(array $data, int $ttl = null): array
    {
        $this->checkData($data);
        $pipe = $this->getConnection()->multi();
        foreach ($data as $key => $value) {
            if (is_null($ttl)) {
                $pipe->set($key, $value);
            } else {
                $pipe->set($key, $value, $ttl);
            }
        }
        return array_combine(array_keys($data), $pipe->exec());
    }

    /**
     * @param array    $data
     * @param int|null $ttl
     * @return array
     * @throws RedisException
     * @throws UnexpectedValueException
     */
    public function mSetNx(array $data, int $ttl = null): array
    {
        $this->checkData($data);
        $pipe = $this->getConnection()->multi();
        foreach ($data as $key => $value) {
            if (is_null($ttl)) {
                $pipe->setnx($key, $value);
            } else {
                $pipe->set($key, $value, ['nx', 'ex' => $ttl]);
            }
        }
        return array_combine(array_keys($data), $pipe->exec());
    }

    /**
     * @param string                 $key
     * @param int|string|float|array $value
     * @return int|string|float|array|null
     * @throws RedisException
     * @throws UnexpectedValueException
     */
    public function getSet(string $key, $value)
    {
        $this->checkType($value);
        $result = $this->getConnection()->getSet($key, $value);
        return ($result !== false) ? $result : null;
    }

    /**
     * @param string   $key
     * @param int      $value
     * @param int|null $ttl
     * @return int
     * @throws RedisException
     */
    public function incrBy(string $key, int $value = 1, int $ttl = null): int
    {
        if (is_null($ttl)) {
            return $this->getConnection()->incrBy($key, $value);
        } else {
            $pipe = $this->getConnection()->multi();
            $this->getConnection()->incrBy($key, $value);
            $this->getConnection()->expire($key, $ttl);
            return $pipe->exec()[0];
        }
    }

    /**
     * @param string $key
     * @param int    $value
     * @return int
     * @throws RedisException
     */
    public function decrBy(string $key, int $value = 1): int
    {
        return $this->getConnection()->decrBy($key, $value);
    }

    ################################################## HASH TABLE ######################################################

    /**
     * @param string                 $key
     * @param string                 $hashKey
     * @param array|float|int|string $value
     * @return bool
     * @throws RedisException
     * @throws UnexpectedValueException
     */
    public function hSet(string $key, string $hashKey, $value): bool
    {
        $this->checkType($value);
        $result = $this->getConnection()->hSet($key, $hashKey, $value);
        return !($result === false);
    }

    /**
     * @param string $key
     * @param array  $data
     * @return array
     * @throws RedisException
     * @throws UnexpectedValueException
     */
    public function hMSet(string $key, array $data): array
    {
        $this->checkData($data);
        $pipe = $this->getConnection()->multi();
        foreach ($data as $hashKey => $value) {
            $pipe->hSet($key, $hashKey, $value);
        }
        $result = $pipe->exec();
        return array_combine(
            array_keys($data),
            array_map(
                function ($value) {
                    return !($value === false);
                },
                $result
            )
        );
    }

    /**
     * @param string                 $key
     * @param string                 $hashKey
     * @param array|float|int|string $value
     * @return bool
     * @throws RedisException
     * @throws UnexpectedValueException
     */
    public function hSetNx(string $key, string $hashKey, $value): bool
    {
        $this->checkType($value);
        return $this->getConnection()->hSetNx($key, $hashKey, $value);
    }

    /**
     * @param string $key
     * @param array  $data
     * @return array
     * @throws RedisException
     * @throws UnexpectedValueException
     */
    public function hMSetNx(string $key, array $data): array
    {
        $this->checkData($data);
        $pipe = $this->getConnection()->multi();
        foreach ($data as $hashKey => $value) {
            $pipe->hSetNx($key, $hashKey, $value);
        }
        $result = $pipe->exec();
        return array_combine(array_keys($data), $result);
    }

    /**
     * @param string $key
     * @param string $hashKey
     * @return array|float|int|string|null
     * @throws RedisException
     */
    public function hGet(string $key, string $hashKey)
    {
        $result = $this->getConnection()->hGet($key, $hashKey);
        return ($result !== false) ? $result : null;
    }

    /**
     * @param string $key
     * @param array  $hashKeys
     * @return array
     * @throws RedisException
     * @throws UnexpectedValueException
     */
    public function hMGet(string $key, array $hashKeys): array
    {
        $this->checkTypeKeys($hashKeys);
        $result = $this->getConnection()->hMGet($key, $hashKeys);

        if ($result === false) {
            return array_fill_keys($hashKeys, false);
        }
        return array_combine($hashKeys, $result);
    }

    /**
     * @param string $key
     * @return int
     * @throws RedisException
     */
    public function hLen(string $key): int
    {
        return (int)$this->getConnection()->hLen($key);
    }

    /**
     * @param string $key
     * @param string ...$hashKeys
     * @return int
     * @throws RedisException
     */
    public function hDel(string $key, string ...$hashKeys): int
    {
        return (int)call_user_func_array([$this->getConnection(), 'hDel'], func_get_args());
    }

    /**
     * @param string $key
     * @param array  $hashKeys
     * @return array
     * @throws RedisException
     * @throws UnexpectedValueException
     */
    public function hMDel(string $key, array $hashKeys): array
    {
        $this->checkTypeKeys($hashKeys);
        $pipe = $this->getConnection()->multi();
        foreach ($hashKeys as $hashKey) {
            $pipe->hDel($key, $hashKey);
        }
        $result = $pipe->exec();
        return array_combine(
            $hashKeys,
            array_map(
                function ($value) {
                    return (boolean)$value;
                },
                $result
            )
        );
    }

    /**
     * @param string $key
     * @return array
     * @throws RedisException
     */
    public function hKeys(string $key): array
    {
        $result = $this->getConnection()->hKeys($key);
        return ($result !== false) ? $result : [];
    }

    /**
     * @param string $key
     * @return array
     * @throws RedisException
     */
    public function hValues(string $key): array
    {
        $result = $this->getConnection()->hVals($key);
        return ($result !== false) ? $result : [];
    }

    /**
     * @param string $key
     * @return array
     * @throws RedisException
     */
    public function hGetAll(string $key): array
    {
        $result = $this->getConnection()->hGetAll($key);
        return ($result !== false) ? $result : [];
    }

    /**
     * @param string $key
     * @param string $hashKey
     * @return bool
     * @throws RedisException
     */
    public function hExists(string $key, string $hashKey): bool
    {
        return (boolean)$this->getConnection()->hExists($key, $hashKey);
    }

    /**
     * @param string $key
     * @param string $hashKey
     * @param int    $value
     * @return int
     * @throws RedisException
     */
    public function hIncrBy(string $key, string $hashKey, int $value): int
    {
        return $this->getConnection()->hIncrBy($key, $hashKey, $value);
    }

    /**
     * @param string      $key
     * @param string|null $pattern
     * @return Generator
     * @throws RedisException
     */
    public function hScan(string $key, string $pattern = null): Generator
    {
        $iterator = null;
        while ($arrKeys = $this->getConnection()->hScan($key, $iterator, $pattern, 10000)) {
            foreach ($arrKeys as $hashKey => $value) {
                yield $hashKey => $value;
            }
        }
    }

    ################################################### SET ############################################################

    /**
     * @param string $key
     * @param mixed  ...$members
     * @return int
     * @throws RedisException
     * @throws UnexpectedValueException
     */
    public function sAdd(string $key, ...$members): int
    {
        foreach ($members as $member) {
            $this->checkType($member);
        }
        return (int)call_user_func_array([$this->getConnection(), 'sAdd'], func_get_args());
    }

    /**
     * @param string $key
     * @param array  $members
     * @return int
     * @throws RedisException
     * @throws UnexpectedValueException
     */
    public function sMAdd(string $key, array $members): int
    {
        $this->checkTypeValues($members);
        return (int)$this->getConnection()->sAddArray($key, $members);
    }

    /**
     * @param string $key
     * @param mixed  ...$members
     * @return int
     * @throws RedisException
     * @throws UnexpectedValueException
     */
    public function sDel(string $key, ...$members): int
    {
        foreach ($members as $member) {
            $this->checkType($member);
        }
        return (int)call_user_func_array([$this->getConnection(), 'sRem'], func_get_args());
    }

    /**
     * @param string $key
     * @param array  $members
     * @return int
     * @throws RedisException
     * @throws UnexpectedValueException
     */
    public function sMDel(string $key, array $members): int
    {
        $this->checkTypeValues($members);
        $pipe = $this->getConnection()->multi();
        foreach ($members as $member) {
            $pipe->sRem($key, $member);
        }
        $result = $pipe->exec();
        return array_sum($result);
    }

    /**
     * @param string                 $srcKey
     * @param string                 $dstKey
     * @param array|float|int|string $member
     * @return bool
     * @throws RedisException
     * @throws UnexpectedValueException
     */
    public function sMove(string $srcKey, string $dstKey, $member): bool
    {
        $this->checkType($member);
        return $this->getConnection()->sMove($srcKey, $dstKey, $member);
    }

    /**
     * @param string                 $key
     * @param array|float|int|string $value
     * @return bool
     * @throws RedisException
     * @throws UnexpectedValueException
     */
    public function sIsMember(string $key, $value): bool
    {
        $this->checkType($value);
        return $this->getConnection()->sIsMember($key, $value);
    }

    /**
     * @param string $key
     * @return int
     * @throws RedisException
     */
    public function sCard(string $key): int
    {
        return $this->getConnection()->sCard($key);
    }

    /**
     * @param string $key
     * @param int    $count
     * @return array
     * @throws RedisException
     */
    public function sPop(string $key, int $count = 1): array
    {
        $result = $this->getConnection()->sPop($key, $count);
        return ($result !== false) ? $result : [];
    }

    /**
     * @param string $key
     * @param int    $count
     * @return array
     * @throws RedisException
     */
    public function sRandMember(string $key, int $count = 1): array
    {
        $result = $this->getConnection()->sRandMember($key, $count);
        return ($result !== false) ? $result : [];
    }

    /**
     * @param string ...$keys
     * @return array
     * @throws RedisException
     */
    public function sInter(string ...$keys): array
    {
        $result = call_user_func_array([$this->getConnection(), 'sInter'], func_get_args());
        return ($result !== false) ? $result : [];
    }

    /**
     * @param string $dstKey
     * @param string ...$keys
     * @return int
     * @throws RedisException
     */
    public function sInterStore(string $dstKey, string ...$keys): int
    {
        return (int)call_user_func_array([$this->getConnection(), 'sInterStore'], func_get_args());
    }

    /**
     * @param string ...$keys
     * @return array
     * @throws RedisException
     */
    public function sUnion(string ...$keys): array
    {
        $result = call_user_func_array([$this->getConnection(), 'sUnion'], func_get_args());
        return ($result !== false) ? $result : [];
    }

    /**
     * @param string $dstKey
     * @param string ...$keys
     * @return int
     * @throws RedisException
     */
    public function sUnionStore(string $dstKey, string ...$keys): int
    {
        return (int)call_user_func_array([$this->getConnection(), 'sUnionStore'], func_get_args());
    }

    /**
     * @param string $key
     * @param string ...$keys
     * @return array
     * @throws RedisException
     */
    public function sDiff(string $key, string ...$keys): array
    {
        $result = call_user_func_array([$this->getConnection(), 'sDiff'], func_get_args());
        return ($result !== false) ? $result : [];
    }

    /**
     * @param string $dstKey
     * @param string ...$keys
     * @return int
     * @throws RedisException
     */
    public function sDiffStore(string $dstKey, string ...$keys): int
    {
        return (int)call_user_func_array([$this->getConnection(), 'sDiffStore'], func_get_args());
    }

    /**
     * @param string $key
     * @return array
     * @throws RedisException
     */
    public function sMembers(string $key): array
    {
        $result = $this->getConnection()->sMembers($key);
        return ($result !== false) ? $result : [];
    }

    /**
     * @param string      $key
     * @param string|null $pattern
     * @return Generator
     * @throws RedisException
     */
    public function sScan(string $key, string $pattern = null): Generator
    {
        $iterator = null;
        while ($arrKeys = $this->getConnection()->sScan($key, $iterator, $pattern, 10000)) {
            foreach ($arrKeys as $hashKey => $value) {
                yield $hashKey => $value;
            }
        }
    }

    ################################################# SORTED SET #######################################################

    /**
     * @param string           $key
     * @param float|int|string $member
     * @param int              $score
     * @return bool
     * @throws RedisException
     * @throws UnexpectedValueException
     */
    public function zAdd(string $key, $member, int $score): bool
    {
        $this->checkType($member, ['integer', 'double', 'string']);
        return (boolean)$this->getConnection()->zAdd($key, [], $score, $member);
    }

    /**
     * @param string $key
     * @param int    $posStart
     * @param int    $posEnd
     * @param bool   $withScores
     * @return array
     * @throws RedisException
     */
    public function zRange(string $key, int $posStart, int $posEnd, $withScores = false): array
    {
        $result = $this->getConnection()->zRange($key, $posStart, $posEnd, $withScores);
        return ($result !== false) ? $result : [];
    }

    /**
     * @param string $key
     * @param int    $posStart
     * @param int    $posEnd
     * @param bool   $withScores
     * @return array
     * @throws RedisException
     */
    public function zReversRange(string $key, int $posStart, int $posEnd, $withScores = false): array
    {
        $result = $this->getConnection()->zRevRange($key, $posStart, $posEnd, $withScores);
        return ($result !== false) ? $result : [];
    }

    /**
     * @param string $key
     * @param mixed  ...$members
     * @return int
     * @throws RedisException
     * @throws UnexpectedValueException
     */
    public function zDel(string $key, ...$members): int
    {
        $this->checkTypeValues($members, ['integer', 'double', 'string']);
        return call_user_func_array([$this->getConnection(), 'zRem'], func_get_args());
    }

    /**
     * В каком порядке укажим
     * @param string $key
     * @param int    $scoreStart
     * @param int    $scoreEnd
     * @param bool   $withScores
     * @return array
     * @throws RedisException
     */
    public function zRangeByScore(string $key, int $scoreStart, int $scoreEnd, $withScores = false): array
    {
        $options = [];
        if ($withScores) {
            $options = ['withscores' => true];
        }
        $result = $this->getConnection()->zRangeByScore($key, $scoreStart, $scoreEnd, $options);
        return ($result !== false) ? $result : [];
    }

    /**
     * @param string $key
     * @param int    $scoreStart
     * @param int    $scoreEnd
     * @return int
     * @throws RedisException
     */
    public function zCountByScore(string $key, int $scoreStart, int $scoreEnd): int
    {
        return $this->getConnection()->zCount($key, $scoreStart, $scoreEnd);
    }

    /**
     * @param string $key
     * @param int    $scoreStart
     * @param int    $scoreEnd
     * @return int
     * @throws RedisException
     */
    public function zDelRangeByScore(string $key, int $scoreStart, int $scoreEnd): int
    {
        return $this->getConnection()->zRemRangeByScore($key, $scoreStart, $scoreEnd);
    }

    /**
     * @param string $key
     * @param int    $posStart
     * @param int    $posEnd
     * @return int
     * @throws RedisException
     */
    public function zDelRangeByPosition(string $key, int $posStart, int $posEnd): int
    {
        return $this->getConnection()->zRemRangeByRank($key, $posStart, $posEnd);
    }

    /**
     * @param string $key
     * @return int
     * @throws RedisException
     */
    public function zCard(string $key): int
    {
        return $this->getConnection()->zCard($key);
    }

    /**
     * @param string           $key
     * @param float|int|string $member
     * @return int|null
     * @throws RedisException
     * @throws UnexpectedValueException
     */
    public function zScore(string $key, $member): ?int
    {
        $this->checkType($member, ['integer', 'double', 'string']);
        return $this->getConnection()->zScore($key, $member);
    }

    /**
     * @param string           $key
     * @param float|int|string $member
     * @return int|null
     * @throws RedisException
     * @throws UnexpectedValueException
     */
    public function zPosition(string $key, $member): ?int
    {
        $this->checkType($member, ['integer', 'double', 'string']);
        return $this->getConnection()->zRank($key, $member);
    }

    /**
     * @param string           $key
     * @param float|int|string $member
     * @return int|null
     * @throws RedisException
     * @throws UnexpectedValueException
     */
    public function zReversePosition(string $key, $member): ?int
    {
        $this->checkType($member, ['integer', 'double', 'string']);
        return $this->getConnection()->zRevRank($key, $member);
    }

    /**
     * @param string           $key
     * @param int              $value
     * @param float|int|string $member
     * @return int
     * @throws RedisException
     * @throws UnexpectedValueException
     */
    public function zIncrBy(string $key, int $value, $member): int
    {
        $this->checkType($member, ['integer', 'double', 'string']);
        return $this->getConnection()->zIncrBy($key, $value, $member);
    }

    /**
     * @param string     $output
     * @param array      $zSetKeys
     * @param array|null $weights
     * @return int
     * @throws RedisException
     */
    public function zUnionStoreSum(string $output, array $zSetKeys, array $weights = null): int
    {
        return $this->getConnection()->zUnionStore($output, $zSetKeys, $weights);
    }

    /**
     * @param string     $output
     * @param array      $zSetKeys
     * @param array|null $weights
     * @return int
     * @throws RedisException
     */
    public function zUnionStoreMin(string $output, array $zSetKeys, array $weights = null): int
    {
        return $this->getConnection()->zUnionStore($output, $zSetKeys, $weights, 'MIN');
    }

    /**
     * @param string     $output
     * @param array      $zSetKeys
     * @param array|null $weights
     * @return int
     * @throws RedisException
     */
    public function zUnionStoreMax(string $output, array $zSetKeys, array $weights = null): int
    {
        return $this->getConnection()->zUnionStore($output, $zSetKeys, $weights, 'MAX');
    }

    /**
     * @param string     $output
     * @param array      $zSetKeys
     * @param array|null $weights
     * @return int
     * @throws RedisException
     */
    public function zInterStoreSum(string $output, array $zSetKeys, array $weights = null): int
    {
        return $this->getConnection()->zInterStore($output, $zSetKeys, $weights);
    }

    /**
     * @param string     $output
     * @param array      $zSetKeys
     * @param array|null $weights
     * @return int
     * @throws RedisException
     */
    public function zInterStoreMin(string $output, array $zSetKeys, array $weights = null): int
    {
        return $this->getConnection()->zInterStore($output, $zSetKeys, $weights, 'MIN');
    }

    /**
     * @param string     $output
     * @param array      $zSetKeys
     * @param array|null $weights
     * @return int
     * @throws RedisException
     */
    public function zInterStoreMax(string $output, array $zSetKeys, array $weights = null): int
    {
        return $this->getConnection()->zInterStore($output, $zSetKeys, $weights, 'MAX');
    }

    /**
     * @param string $key
     * @param int    $count
     * @return array
     * @throws RedisException
     */
    public function zPopMax(string $key, int $count = 1): array
    {
        $result = $this->getConnection()->zPopMax($key, $count);
        return ($result !== false) ? $result : [];
    }

    /**
     * @param string $key
     * @param int    $count
     * @return array
     * @throws RedisException
     */
    public function zPopMin(string $key, int $count = 1): array
    {
        $result = $this->getConnection()->zPopMin($key, $count);
        return ($result !== false) ? $result : [];
    }

    ################################################### LIST ###########################################################

    /**
     * @param string                 $key
     * @param int|string|float|array ...$values
     * @return int
     * @throws RedisException
     * @throws UnexpectedValueException
     */
    public function lPush(string $key, ...$values): int
    {
        $this->checkTypeValues($values);
        return (int)call_user_func_array([$this->getConnection(), 'lPush'], func_get_args());
    }

    /**
     * @param string                 $key
     * @param int|string|float|array ...$values
     * @return int
     * @throws RedisException
     * @throws UnexpectedValueException
     */
    public function rPush(string $key, ...$values): int
    {
        $this->checkTypeValues($values);
        return (int)call_user_func_array([$this->getConnection(), 'rPush'], func_get_args());
    }

    /**
     * @param string $key
     * @return int|string|float|array|null
     * @throws RedisException
     */
    public function lPop(string $key)
    {
        $result = $this->getConnection()->lPop($key);
        return ($result !== false) ? $result : null;
    }

    /**
     * @param string $key
     * @return int|string|float|array|null
     * @throws RedisException
     */
    public function rPop(string $key)
    {
        $result = $this->getConnection()->rPop($key);
        return ($result !== false) ? $result : null;
    }

    /**
     * @param string $key
     * @return int
     * @throws RedisException
     */
    public function lLen(string $key): int
    {
        return (int)$this->getConnection()->lLen($key);
    }

    /**
     * @param string $key
     * @param int    $index
     * @return int|string|float|array|null
     * @throws RedisException
     */
    public function lIndex(string $key, int $index)
    {
        $result = $this->getConnection()->lIndex($key, $index);
        return ($result !== false) ? $result : null;
    }

    /**
     * @param string                 $key
     * @param int                    $index
     * @param array|float|int|string $value
     * @return bool
     * @throws RedisException
     * @throws UnexpectedValueException
     */
    public function lSet(string $key, int $index, $value): bool
    {
        $this->checkType($value);
        return $this->getConnection()->lSet($key, $index, $value);
    }

    /**
     * @param string $key
     * @param int    $start
     * @param int    $end
     * @return array
     * @throws RedisException
     */
    public function lRange(string $key, int $start, int $end): array
    {
        $result = $this->getConnection()->lRange($key, $start, $end);
        return ($result !== false) ? $result : [];
    }

    /**
     * @param string $key
     * @param int    $start
     * @param int    $end
     * @return bool
     * @throws RedisException
     */
    public function lTrim(string $key, int $start, int $end): bool
    {
        return (boolean)$this->getConnection()->lTrim($key, $start, $end);
    }

    /**
     * @param string                 $key
     * @param array|float|int|string $value
     * @param int                    $count
     * @return int
     * @throws RedisException
     * @throws UnexpectedValueException
     */
    public function lRem(string $key, $value, int $count = 0): int
    {
        $this->checkType($value);
        return (int)$this->getConnection()->lRem($key, $value, $count);
    }

    /**
     * @param string                 $key
     * @param array|float|int|string $pivot
     * @param array|float|int|string $value
     * @return int
     * @throws RedisException
     * @throws UnexpectedValueException
     */
    public function lInsertBefore(string $key, $pivot, $value): int
    {
        $this->checkType($value);
        $this->checkType($pivot);
        $result = $this->getConnection()->lInsert($key, Redis::BEFORE, $pivot, $value);
        return $result > 0 ? $result : 0;
    }

    /**
     * @param string                 $key
     * @param array|float|int|string $pivot
     * @param array|float|int|string $value
     * @return int
     * @throws RedisException
     * @throws UnexpectedValueException
     */
    public function lInsertAfter(string $key, $pivot, $value): int
    {
        $this->checkType($value);
        $this->checkType($pivot);
        $result = $this->getConnection()->lInsert($key, Redis::AFTER, $pivot, $value);
        return $result > 0 ? $result : 0;
    }

}