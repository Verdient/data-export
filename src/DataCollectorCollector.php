<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\DataExport;

use Hyperf\Di\MetadataCollector;

/**
 * 数据采集器收集器
 * @author Verdient。
 */
class DataCollectorCollector extends MetadataCollector
{
    /**
     * 容器
     * @author Verdient。
     */
    protected static array $container = [];

    /**
     * 收集类
     * @param string $className 类的名称
     * @param DataCollector $annotation 数据采集器注解
     * @author Verdient。
     */
    public static function collectClass($className, DataCollector $annotation): void
    {
        static::$container[$annotation->dataSet] = $className;
    }

    /**
     * @inheritdoc
     * @author Verdient。
     */
    public static function clear(?string $key = null): void
    {
        if ($key) {
            foreach (static::$container as $dataSet => $className) {
                if ($className === $key) {
                    unset(static::$container[$dataSet]);
                    break;
                }
            }
        } else {
            static::$container = [];
        }
    }
}
