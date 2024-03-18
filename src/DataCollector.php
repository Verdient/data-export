<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\DataExport;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;

/**
 * 数据采集器
 * @author Verdient。
 */
#[Attribute(Attribute::TARGET_CLASS)]
class DataCollector extends AbstractAnnotation
{
    /**
     * @param string $dataSet 数据集
     * @author Verdient。
     */
    public function __construct(public string $dataSet)
    {
    }

    /**
     * @inheritdoc
     * @author Verdient。
     */
    public function collectClass(string $className): void
    {
        DataCollectorCollector::collectClass($className, $this);
    }
}
