<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\DataExport;

use Iterator;
use Verdient\Hyperf3\Logger\HasLoggerInterface;

/**
 * 收集器接口
 * @author Verdient。
 */
interface DataCollectorInterface extends HasLoggerInterface
{
    /**
     * 采集数据
     * @return Iterator
     * @author Verdient。
     */
    public function collect(): Iterator;

    /**
     * 获取预估的行数
     * @return int
     * @author Verdient。
     */
    public function estimate(): int;

    /**
     * 文件名称
     * @return string
     * @author Verdient。
     */
    public function fileName(): string;

    /**
     * 表头
     * @return array
     * @author Verdient。
     */
    public function headers(): array;
}
