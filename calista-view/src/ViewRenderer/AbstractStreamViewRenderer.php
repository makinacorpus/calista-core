<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View\ViewRenderer;

use MakinaCorpus\Calista\View\PropertyRenderer;
use MakinaCorpus\Calista\View\PropertyView;
use MakinaCorpus\Calista\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

abstract class AbstractStreamViewRenderer extends AbstractViewRenderer
{
    private PropertyRenderer $propertyRenderer;

    public function __construct(PropertyRenderer $propertyRenderer)
    {
        $this->propertyRenderer = $propertyRenderer;
    }

    /**
     * {@inheritdoc}
     */
    public function render(View $view): string
    {
        \ob_start();

        $resource = \fopen('php://output', 'w+');
        $this->doRenderInStream($view, $resource);
        \fclose($resource);

        return \ob_get_clean();
    }

    /**
     * {@inheritdoc}
     */
    public function renderInStream(View $view, $resource): void
    {
        if (!\is_resource($resource)) {
            throw new \InvalidArgumentException("Given \$resource argument is not a resource");
        }

        $this->doRenderInStream($view, $resource);
    }

    /**
     * {@inheritdoc}
     */
    public function renderAsResponse(View $view): Response
    {
        $viewDefinition = $view->getDefinition();

        $response = new StreamedResponse();
        $response->headers->set('Content-Type', \sprintf('%s; charset=%s', $this->doGetContentType($view), $viewDefinition->getExtraOptionValue('encoding', 'utf-8')));

        $filename = $viewDefinition->getExtraOptionValue('filename');
        if ($filename) {
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        }

        $response->setCallback(function () use ($view) {
            $resource = \fopen('php://output', 'w+');
            $this->doRenderInStream($view, $resource);
            \fclose($resource);
        });

        return $response;
    }

    /**
     * Render in stream.
     */
    abstract protected function doRenderInStream(View $view, $resource): void;

    /**
     * Get content type for HTTP responses.
     */
    protected function doGetContentType(View $view): string
    {
        return 'text/plain';
    }

    /**
     * Create header row.
     */
    protected function createHeaderRow(array $properties): array
    {
        $ret = [];

        foreach ($properties as $property) {
            \assert($property instanceof PropertyView);

            $ret[] = $property->getLabel();
        }

        return $ret;
    }

    /**
     * Create item row.
     */
    protected function createItemRow(array $properties, $current): array
    {
        $ret = [];

        foreach ($properties as $property) {
            \assert($property instanceof PropertyView);

            $ret[] = $this->propertyRenderer->renderItemProperty($current, $property);
        }

        return $ret;
    }
}
