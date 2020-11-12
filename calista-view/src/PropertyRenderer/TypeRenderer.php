<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View\PropertyRenderer;

interface TypeRenderer
{
    /**
     * Get types this renderer supports.
     *
     * @return string[]
     *   Array of native PHP types, builtin (scalar types) or not (classes).
     */
    public function getSupportedTypes(): array;

    /**
     * Render single value.
     *
     * @param string $type
     *   Value type.
     * @param null|mixed $value
     *   Value that should have the given type.
     * @param array $options
     *   Options from the PropertyView object.
     *
     * @return null|string
     *   If you return null here, other renderers will have the chance to
     *   render the value.
     */
    public function render(string $type, $value, array $options): ?string;
}
