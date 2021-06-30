<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View\Tests\Mock;

use MakinaCorpus\Calista\View\CustomViewBuilder;
use MakinaCorpus\Calista\View\ViewBuilder;

final class CustomViewBuilderNoParameters implements CustomViewBuilder
{
    public function __construct()
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
