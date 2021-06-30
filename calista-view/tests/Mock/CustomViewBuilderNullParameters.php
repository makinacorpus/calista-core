<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View\Tests\Mock;

use MakinaCorpus\Calista\View\CustomViewBuilder;
use MakinaCorpus\Calista\View\ViewBuilder;

final class CustomViewBuilderNullParameters implements CustomViewBuilder
{
    public function __construct(?int $baz, string $bar = 'foo', ?string $foo = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function build(ViewBuilder $builder, array $options = []): void
    {
        throw new \Exception("I shall not be called because I am a mock");
    }
}
