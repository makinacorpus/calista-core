<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View\Attribute;

#[\Attribute(flags: \Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class Renderer
{
    public function __construct(
        public readonly string $name,
    ) {}
}
