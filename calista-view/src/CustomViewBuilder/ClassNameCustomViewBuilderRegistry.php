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
    public function get(string $name): CustomViewBuilder
    {
        try {
            $refClass = new \ReflectionClass($name);

            if (!$refClass->implementsInterface(CustomViewBuilder::class)) {
                throw new \InvalidArgumentException(\sprintf("Custom view builder with class name '%s' does not implement '%s'.", $name, CustomViewBuilder::class));
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
                    throw new \InvalidArgumentException(\sprintf("Custom view builder with class name '%s' parameter '\$%s' has no default value and does not allows null.", $name, $parameter->getName()));
                }
            }

            return $refClass->newInstance(...$args);

        } catch (\ReflectionException $e) {
            throw new \InvalidArgumentException(\sprintf("Custom view builder with class name '%s' does not exist.", $name));
        }
    }
}
