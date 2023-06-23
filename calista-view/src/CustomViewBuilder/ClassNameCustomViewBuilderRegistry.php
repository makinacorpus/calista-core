<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View\CustomViewBuilder;

use MakinaCorpus\Calista\View\CustomViewBuilder;
use MakinaCorpus\Calista\View\CustomViewBuilderRegistry;

class ClassNameCustomViewBuilderRegistry implements CustomViewBuilderRegistry
{
    /**
     * {@inheritdoc}
     */
    public function get(string $builderName): CustomViewBuilder
    {
        try {
            $refClass = new \ReflectionClass($builderName);

            if (!$refClass->implementsInterface(CustomViewBuilder::class)) {
                throw new \InvalidArgumentException(\sprintf("Custom view builder with class name '%s' does not implement '%s'.", $builderName, CustomViewBuilder::class));
            }

            $refConstructor = $refClass->getConstructor();
            $args = [];

            foreach ($refConstructor->getParameters() as $parameter) {
                \assert($parameter instanceof \ReflectionParameter);
                if ($parameter->isDefaultValueAvailable()) {
                    $args[] = $parameter->getDefaultValue();
                } else if ($parameter->allowsNull()) {
                    $args[] = null;
                } else {
                    throw new \InvalidArgumentException(\sprintf("Custom view builder with class name '%s' parameter '\$%s' has no default value and does not allows null.", $builderName, $parameter->getName()));
                }
            }

            return $refClass->newInstance(...$args);

        } catch (\ReflectionException $e) {
            throw new \InvalidArgumentException(\sprintf("Custom view builder with class name '%s' does not exist.", $builderName));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function list(): iterable
    {
        return [];
    }
}
