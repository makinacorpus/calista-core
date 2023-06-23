<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View;

interface CustomViewBuilderRegistry
{
    /**
     * Get custom view builder.
     *
     * @throws \InvalidArgumentException
     *   If custom view builder mathing the name does not exists.
     */
    public function get(string $builderName): CustomViewBuilder;

    /**
     * Get known custom view builder list.
     *
     * Warning: some implementations might not be able to list anything.
     */
    public function list(): iterable;
}
