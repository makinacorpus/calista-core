<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Twig\Tests;

use Twig\Source;
use Twig\Error\LoaderError;
use Twig\Loader\LoaderInterface;

final class TestTwigLoader implements LoaderInterface
{
    private array $map = [];

    public function __construct(array $map)
    {
        $this->map = $map;
    }

    /**
     * {@inheritdoc}
     */
    public function getSourceContext(string $name): Source
    {
        $name = (string) $name;
        if (!isset($this->map[$name])) {
            throw new LoaderError(\sprintf('Template "%s" is not defined.', $name));
        }

        $filename = $this->map[$name];

        if (!\file_exists($filename)) {
            throw new LoaderError(\sprintf('Template "%s" is not defined.', $name));
        }

        return new Source(\file_get_contents($filename), $name, $filename);
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheKey(string $name): string
    {
        if (!isset($this->map[$name])) {
            throw new LoaderError(sprintf('Template "%s" is not defined.', $name));
        }

        return $name . ':' . \sha1_file($this->map[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function isFresh(string $name, int $time): bool
    {
        if (!isset($this->map[$name])) {
            throw new LoaderError(\sprintf('Template "%s" is not defined.', $name));
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $name)
    {
        return isset($this->map[$name]);
    }
}
