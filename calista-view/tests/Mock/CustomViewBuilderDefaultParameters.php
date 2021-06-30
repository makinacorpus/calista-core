<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View\Tests\Mock;

use MakinaCorpus\Calista\View\CustomViewBuilder;
use MakinaCorpus\Calista\View\ViewBuilder;

final class CustomViewBuilderDefaultParameters implements CustomViewBuilder
{
    public function __construct(string $foo = 'foo')
    {
    }

    /**
     * {@inheritdoc}
     */
    public function build(ViewBuilder $builder): void
    {
        throw new \Exception("I shall not be called because I am a mock");
    }
}
