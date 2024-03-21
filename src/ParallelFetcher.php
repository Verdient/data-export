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
     * @var int[] 索引集合
     * @author Verdient。
     */
    protected array $indexes = [];

    /**
     * @var Fetcher[] 获取器集合
     * @author Verdient。
     */
    protected array $fetchers = [];

    /**
     * @var callable[] 任务集合
     * @author Verdient。
     */
    protected array $tasks = [];

    /**
     * 添加获取器
     * @param array $ids 编号集合
     * @param Fetcher|Fetcher[] $fetcher 获取器
     * @author Verdient。
     */
    public function fetcher(array $ids, Fetcher|array $fetcher): static
    {
        $idsIndex = count($this->ids);
        $fetcherIndex = count($this->fetchers);

        $this->ids[] = $ids;

        if (is_array($fetcher)) {
            foreach ($fetcher as $fetcher2) {
                $this->fetchers[] = $fetcher2;
                $this->indexes[$fetcherIndex] = $idsIndex;
                $fetcherIndex++;
            }
        } else {
            $this->fetchers[] = $fetcher;
            $this->indexes[$fetcherIndex] = $idsIndex;
        }

        return $this;
    }

    /**
     * 添加任务
     * @param callable $task 任务
     * @author Verdient。
     */
    public function task(callable $task)
    {
        $this->tasks[] = $task;
        return $this;
    }

    /**
     * 清空
     * @author Verdient。
     */
    public function clear(): static
    {
        $this->ids[] = [];
        $this->fetchers = [];
        $this->indexes = [];
        $this->tasks = [];
        return $this;
    }

    /**
     * 获取数据
     * @author Verdient。
     */
    public function get(): array
    {
        if (empty($this->tasks)) {
            if (empty($this->fetchers)) {
                return [];
            }

            if (count($this->fetchers) === 1) {
                $fetcher = $this->fetchers[0];
                $ids = $this->ids[$this->indexes[0]];
                return [$fetcher->get($ids)];
            }

            $parallel = new Parallel();

            foreach ($this->fetchers as $index => $fetcher) {
                $ids = $this->ids[$this->indexes[$index]];
                $parallel->add(function () use ($fetcher, $ids) {
                    return $fetcher->get($ids);
                });
            }

            return $parallel->wait();
        } else {
            $parallel = new Parallel();

            foreach ($this->fetchers as $index => $fetcher) {
                $ids = $this->ids[$this->indexes[$index]];
                $parallel->add(function () use ($fetcher, $ids) {
                    return $fetcher->get($ids);
                });
            }

            foreach ($this->tasks as $task) {
                $parallel->add($task);
            }

            return array_slice($parallel->wait(), 0, count($this->fetchers));
        }
    }
}
