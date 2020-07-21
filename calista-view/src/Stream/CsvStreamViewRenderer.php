<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View\Stream;

use MakinaCorpus\Calista\View\AbstractViewRenderer;
use MakinaCorpus\Calista\View\PropertyRenderer;
use MakinaCorpus\Calista\View\PropertyView;
use MakinaCorpus\Calista\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Turn your data stream to CSV file
 */
class CsvStreamViewRenderer extends AbstractViewRenderer
{
    private PropertyRenderer $propertyRenderer;

    public function __construct(PropertyRenderer $propertyRenderer)
    {
        $this->propertyRenderer = $propertyRenderer;
    }

    /**
     * Create header row.
     */
    private function createHeaderRow(array $properties): array
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
    private function createItemRow(array $properties, $current): array
    {
        $ret = [];

        foreach ($properties as $property) {
            \assert($property instanceof PropertyView);

            $ret[] = $this->propertyRenderer->renderItemProperty($current, $property);
        }

        return $ret;
    }

    /**
     * Render in stream.
     */
    private function doRenderInStream(View $view, $resource): void
    {
        $viewDefinition = $view->getDefinition();
        $properties = $view->getNormalizedProperties();

        // Add the BOM for Excel to read correctly the file
        if ($viewDefinition->getExtraOptionValue('add_bom', false)) {
            \fwrite($resource, "\xEF\xBB\xBF");
        }

        $delimiter = $viewDefinition->getExtraOptionValue('csv_delimiter', ',');
        $enclosure = $viewDefinition->getExtraOptionValue('csv_enclosure', '"');
        $escape = $viewDefinition->getExtraOptionValue('csv_escape_char', '\\');
        $encoding = $viewDefinition->getExtraOptionValue('encoding', 'utf-8');

        // Render the CSV header
        if ($viewDefinition->getExtraOptionValue('add_header', false)) {
            $row = $this->createHeaderRow($properties);
            if ($encoding !== 'utf-8') {
                $row = \mb_convert_encoding( $row, $encoding);
            }
            \fputcsv($resource, $row, $delimiter, $enclosure, $escape);
        }

        foreach ($view->getResult() as $item) {
            $row = $this->createItemRow($properties, $item);
            if ($encoding !== 'utf-8') {
                $row = \mb_convert_encoding($row, $encoding);
            }
            \fputcsv($resource, $row, $delimiter, $enclosure, $escape);
        }
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
        $response->headers->set('Content-Type', sprintf('text/csv; charset=%s', $viewDefinition->getExtraOptionValue('encoding', 'utf-8')));

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
}
