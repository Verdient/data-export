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
     * @author Verdient。
     */
    public function add(
        string $class,
        array $columns,
        bool $multiple = false,
        ?string $filterKey = null,
        ?string $indexKey = null,
    ) {
        $this->fetchers[] = Utils::fetcher($class, $columns, $multiple, $filterKey, $indexKey);
    }

    /**
     * 获取数据
     * @author Verdient。
     */
    public function get(array $ids): array
    {
        if (empty($this->fetchers)) {
            return [];
        }
        if (count($this->fetchers) === 1) {
            return [Utils::parallel($ids, $this->fetchers[0])()];
        }
        $parallel = new CoroutineParallel();
        foreach ($this->fetchers as $fetcher) {
            $parallel->add(Utils::parallel($ids, $fetcher));
        }
        return $parallel->wait();
    }
}
