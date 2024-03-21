<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\DataExport;

use Closure;
use Hyperf\Coroutine\Parallel;
use InvalidArgumentException;
use Verdient\Hyperf3\Database\AbstractModel;
use Verdient\Hyperf3\Database\Builder;

/**
 * 获取器
 * @author Verdient。
 */
class Fetcher
{
    /**
     * 用于检索的键名
     * @author Verdient。
     */
    protected ?string $filterKey = null;

    /**
     * 用于索引的键名
     * @author Verdient。
     */
    protected ?string $indexKey = null;

    /**
     * 是否分组
     * @author Verdient。
     */
    protected bool $isGroup = false;

    /**
     * 模型
     * @author Verdient。
     */
    protected ?AbstractModel $model = null;

    /**
     * 分块大小
     * @author Verdient。
     */
    protected int $chunkSize = 10000;

    /**
     * 条件集合
     * @author Verdient。
     */
    protected array $conditions = [];

    /**
     * @param string $class 类名
     * @param string[] $columns 要查询的字段集合
     * @author Verdient。
     */
    public function __construct(
        protected string $class,
        protected array $columns
    ) {
        if (!is_subclass_of($class, AbstractModel::class)) {
            throw new InvalidArgumentException('Parameter $class of ' . __METHOD__ . ' must be subclass of ' . AbstractModel::class);
        }
    }

    /**
     * 创建新的获取器
     * @param string $class 类名
     * @param string[] $columns 要查询的字段集合
     * @author Verdient。
     */
    public static function create(string $class, array $columns): static
    {
        return new static($class, $columns);
    }

    /**
     * 获取模型
     * @author Verdient。
     */
    public function getModel(): AbstractModel
    {
        if ($this->model === null) {
            $class = $this->class;
            $this->model = new $class;
        }
        return $this->model;
    }

    /**
     * 获取用于检索的键名
     * @author Verdient。
     */
    public function getFilterKey(): string
    {
        if ($this->filterKey === null) {
            $this->filterKey = $this->getModel()->getKeyName();
        }
        return $this->filterKey;
    }

    /**
     * 获取用于检索的键名
     * @author Verdient。
     */
    public function getIndexKey(): string
    {
        if ($this->indexKey === null) {
            $this->indexKey = $this->getFilterKey();
        }
        return $this->indexKey;
    }

    /**
     * 设置检索键名
     * @param string $name 名称
     * @author Verdient。
     */
    public function filterBy(string $name)
    {
        $this->filterKey = $name;
        return $this;
    }

    /**
     * 设置索引键名
     * @param string $name 名称
     * @author Verdient。
     */
    public function keyBy(string $name)
    {
        $this->indexKey = $name;
        $this->isGroup = false;
        return $this;
    }

    /**
     * 设置分组键名
     * @param string $name 名称
     * @author Verdient。
     */
    public function groupBy(string $name)
    {
        $this->indexKey = $name;
        $this->isGroup = true;
        return $this;
    }

    /**
     * 设置分块大小
     * @param int $number 分块大小
     * @author Verdient。
     */
    public function chunkSize(int $number): static
    {
        $this->chunkSize = $number;
        return $this;
    }

    /**
     * 检索条件
     * @param array|Closure|string $column 字段
     * @param null|mixed $operator 操作符
     * @param null|mixed $value 值
     * @param string $boolean 关系
     * @author Verdient。
     */
    public function where(array|Closure|string $column, $operator = null, $value = null, string $boolean = 'and'): static
    {
        $this->conditions[] = [$column, $operator, $value, $boolean];
        return $this;
    }

    /**
     * 获取数据
     * @param array $ids 编号集合
     * @author Verdient。
     */
    public function get(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $ids = array_unique($ids);

        if (count($ids) > $this->chunkSize) {
            $chunkIds = array_chunk($ids, $this->chunkSize);

            $parallel = new Parallel();

            foreach ($chunkIds as $partIds) {
                $parallel->add(function () use ($partIds) {
                    return $this->fetch($partIds);
                });
            }

            $result = [];

            foreach ($parallel->wait() as $partResult) {
                if (!$this->isGroup) {
                    foreach ($partResult as $key => $result2) {
                        $result[$key] = $result2;
                    }
                } else {
                    foreach ($partResult as $key => $result2) {
                        if (isset($result[$key])) {
                            $result[$key] = array_merge($result[$key], $result2);
                        } else {
                            $result[$key] = $result2;
                        }
                    }
                }
            }

            return $result;
        }

        return $this->fetch($ids);
    }

    /**
     * 获取数据
     * @param array $ids 编号集合
     * @author Verdient。
     */
    protected function fetch(array $ids)
    {
        /** @var Builder */
        $builder = $this
            ->getModel()
            ->newQuery()
            ->select($this->columns)
            ->whereIn($this->getFilterKey(), $ids);

        foreach ($this->conditions as $condition) {
            $builder->where(...$condition);
        }

        $builder = $builder->toBase();

        $connection = $builder->getConnection();

        $indexKey = $this->getIndexKey();

        if ($this->isGroup) {
            foreach ($connection
                ->cursor($builder->toSql(), $builder->getBindings()) as $row) {
                $key = $row->$indexKey;
                if (!isset($result[$key])) {
                    $result[$key] = [(array) $row];
                } else {
                    $result[$key][] = (array) $row;
                }
            }
        } else {
            foreach ($connection
                ->cursor($builder->toSql(), $builder->getBindings()) as $row) {
                $key = $row->$indexKey;
                $result[$key] = (array) $row;
            }
        }

        return $result;
    }
}
