<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View;

/**
 * Boilerplate code for view implementations.
 */
abstract class AbstractViewRenderer implements ViewRenderer
{
    /**
     * {@inheritdoc}
     */
    public function renderInStream(View $view, $resource): void
    {
        @\trigger_error(\sprintf("%s::%s uses default slow implementation, consider implementing it", static::class, __METHOD__), E_USER_NOTICE);

        if (!\is_resource($resource)) {
            throw new \InvalidArgumentException("Given \$resource argument is not a resource");
        }

        if (false === \fwrite($resource, $this->render($view))) {
            throw new \RuntimeException("Could not write in stream");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function renderInFile(View $view, string $filename): void
    {
        if (\file_exists($filename) && 0 !== \filesize($filename)) {
            throw new \InvalidArgumentException(\sprintf("'%s' not overwrite existing file", $filename));
        }
        try {
            if (!$resource = \fopen($filename, "wb+")) {
                throw new \InvalidArgumentException(\sprintf("'%s' could not open file for writing", $filename));
            }
            $this->renderInStream($view, $resource);
        } finally {
            if ($resource) {
                @\fclose($resource);
            }
        }
    }
}
