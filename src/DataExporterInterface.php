<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\DataExport;

use Verdient\Hyperf3\Logger\HasLoggerInterface;

/**
 * 导出器接口
 * @author Verdient。
 */
interface DataExporterInterface extends HasLoggerInterface
{
    /**
     * 导出
     * @param DataCollectorInterface $collector 数据采集器
     * @param ?string $path 导出的文件路径
     * @author Verdient。
     */
    public function export(
        DataCollectorInterface $collector,
        ?string $path = null
    ): string|false;
}
