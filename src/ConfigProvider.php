<?php

declare(strict_types=1);

namespace Verdient\Hyperf3\DataExport;

use Verdient\Hyperf3\DataExport\DataCollectorCollector;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'annotations' => [
                'scan' => [
                    'collectors' => [
                        DataCollectorCollector::class
                    ]
                ]
            ],
        ];
    }
}
