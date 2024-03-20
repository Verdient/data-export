<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\DataExport;

use Closure;
use Hyperf\Coroutine\Parallel;
use Hyperf\DbConnection\Model\Model;
use InvalidArgumentException;
use Verdient\Hyperf3\Database\Builder;

/**
 * 工具
 * @author Verdient。
 */
class Utils
{
    /**
     * 并行获取数据
     * @param array $ids 编号集合
     * @param callable $fetcher 获取方法
     * @param int $batchSize 批大小
     * @return Closure
     * @author Verdient。
     */
    public static function parallel(array $ids, callable $fetcher, int $batchSize = 10000): Closure
    {
        return function () use ($ids, $fetcher, $batchSize) {
            if (count($ids) <= $batchSize) {
                return call_user_func($fetcher, $ids);
            }
            $chunkIds = array_chunk($ids, $batchSize);
            $parallel = new Parallel();
            foreach ($chunkIds as $partIds) {
                $parallel->add(function () use ($partIds, $fetcher) {
                    return call_user_func($fetcher, $partIds);
                });
            }
            $result = [];
            foreach ($parallel->wait() as $partResult) {
                if (array_is_list($partResult)) {
                    foreach ($partResult as $result2) {
                        $result[] = $result2;
                    }
                } else {
                    foreach ($partResult as $key => $result2) {
                        $result[$key] = $result2;
                    }
                }
            }
            return $result;
        };
    }

    /**
     * 获取器
     * @param string $class 类名
     * @param array $columns 要查询的字段集合
     * @param bool $multiple 是否是多个
     * @param string $filterKey 用于检索的键名
     * @param string $indexKey 用于索引的键名
     * @author Verdient。
     */
    public static function fetcher(
        string $class,
        array $columns,
        bool $multiple = false,
        ?string $filterKey = null,
        ?string $indexKey = null
    ): Closure {

        if (!is_subclass_of($class, Model::class)) {
            throw new InvalidArgumentException('Parameter $class of ' . __METHOD__ . ' must be subclass of ') . Model::class;
        }

        if (!$filterKey) {
            $filterKey = (new $class)->getKeyName();
        }

        if (!$indexKey) {
            $indexKey = $filterKey;
        }

        return function (array $ids) use ($class, $columns, $filterKey, $indexKey, $multiple) {
            $ids = array_filter($ids);
            if (empty($ids)) {
                return [];
            }
            $result = [];
            /** @var Builder */
            $builder = $class::query();
            $builder
                ->select($columns)
                ->whereIn($filterKey, array_unique($ids))
                ->applyScopes()
                ->getQuery();
            if ($multiple) {
                foreach ($builder
                    ->getConnection()
                    ->cursor($builder->toSql(), $builder->getBindings()) as $row) {
                    $key = $row->$indexKey;
                    if (!isset($result[$key])) {
                        $result[$key] = [(array) $row];
                    } else {
                        $result[$key][] = (array) $row;
                    }
                }
            } else {
                foreach ($builder
                    ->getConnection()
                    ->cursor($builder->toSql(), $builder->getBindings()) as $row) {
                    $key = $row->$indexKey;

                    $result[$row->$indexKey] = (array) $row;
                }
            }
            return $result;
        };
    }
}
