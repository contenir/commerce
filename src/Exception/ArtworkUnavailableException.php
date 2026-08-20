<?php

declare(strict_types=1);

namespace Contenir\Commerce\Exception;

use RuntimeException;

use function implode;
use function sprintf;

final class ArtworkUnavailableException extends RuntimeException
{
    /** @var list<string> */
    private array $titles;

    /**
     * @param list<string> $titles
     */
    public static function forTitles(array $titles): self
    {
        $exception         = new self(sprintf(
            'No longer available: %s',
            implode(', ', $titles)
        ));
        $exception->titles = $titles;

        return $exception;
    }

    /**
     * @return list<string>
     */
    public function getTitles(): array
    {
        return $this->titles;
    }
}
