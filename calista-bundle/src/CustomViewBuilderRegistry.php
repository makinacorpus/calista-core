<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Bridge\Symfony;

interface CustomViewBuilderRegistry
{
    public function get(string $name): CustomViewBuilder;
}
