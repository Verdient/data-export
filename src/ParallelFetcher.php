<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\DataExport;

use Hyperf\Coroutine\Parallel;

/**
 * 并行获取器
 * @author Verdient。
 */
class ParallelFetcher
{
    /**
     * @var array[] 编号集合
     * @author Verdient。
     */
    protected array $ids = [];

    /**
     * @var Fetcher[] 获取器集合
     * @author Verdient。
     */
    protected array $fetchers = [];

    /**
     * 添加获取器
     * @param array $ids 编号集合
     * @param Fetcher $fetcher 获取器
     * @author Verdient。
     */
    public function add(array $ids, Fetcher $fetcher): static
    {
        $this->ids[] = $ids;
        $this->fetchers[] = $fetcher;
        return $this;
    }

    /**
     * 清空获取器
     * @author Verdient。
     */
    public function clear(): static
    {
        $this->ids[] = [];
        $this->fetchers = [];
        return $this;
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
            $ids = $this->ids[0];
            $fetcher = $this->fetchers[0];
            return [$fetcher->get($ids)];
        }

        $parallel = new Parallel();

        foreach ($this->fetchers as $index => $fetcher) {
            $ids = $this->ids[$index];
            $parallel->add(function () use ($fetcher, $ids) {
                return $fetcher->get($ids);
            });
        }

        return $parallel->wait();
    }
}
