<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\DataExport;

use Hyperf\Coroutine\Parallel as CoroutineParallel;

/**
 * 并行获取
 * @author Verdient。
 */
class Parallel
{
    /**
     * 获取器集合
     * @author Verdient。
     */
    protected array $fetchers = [];

    /**
     * 添加任务
     * @param string $calss 类名
     * @param array $ids 编号集合
     * @param array $columns 编号集合
     * @param bool $group 是否分组
     * @param ?string $filterKey 用于检索的键名
     * @param ?string $indexKey 用于索引的键名
     * @author Verdient。
     */
    public function add(
        array $ids,
        string $class,
        array $columns,
        bool $group = false,
        ?string $filterKey = null,
        ?string $indexKey = null,
    ) {
        $this->fetchers[] = [
            Utils::fetcher($class, $columns, $group, $filterKey, $indexKey),
            $ids
        ];
    }

    /**
     * 获取数据
     * @author Verdient。
     */
    public function get(): array
    {
        if (empty($this->fetchers)) {
            return [];
        }
        if (count($this->fetchers) === 1) {
            [$fetcher, $ids] = $this->fetchers[0];
            return [Utils::parallel($ids, $fetcher)()];
        }
        $parallel = new CoroutineParallel();
        foreach ($this->fetchers as [$fetcher, $ids]) {
            $parallel->add(Utils::parallel($ids, $fetcher));
        }
        return $parallel->wait();
    }
}
