<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View;

/**
 * Value object for template usage only.
 *
 * Later will contain some context information for renderer usage.
 */
class PropertyValue
{
    public ?string $value;

    public function __construct(?string $value)
    {
        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value ?? '';
    }
}
