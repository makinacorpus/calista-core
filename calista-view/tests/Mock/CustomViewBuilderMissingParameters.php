<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View\Tests\Mock;

use MakinaCorpus\Calista\View\CustomViewBuilder;
use MakinaCorpus\Calista\View\ViewBuilder;

final class CustomViewBuilderMissingParameters implements CustomViewBuilder 
{
    public function __construct(string $foo)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function build(ViewBuilder $builder, array $options = [], ?string $format = null): void
    {
        throw new \Exception("I shall not be called because I am a mock");
    }
}
