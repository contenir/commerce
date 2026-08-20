<?php

declare(strict_types=1);

namespace Contenir\Commerce;

/**
 * Laminas MVC entry point; Mezzio applications use ConfigProvider directly.
 */
final class Module
{
    /**
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return (new ConfigProvider())();
    }
}
