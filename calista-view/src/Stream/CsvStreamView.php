<?php

namespace MakinaCorpus\Calista\View\Stream;

use MakinaCorpus\Calista\Datasource\DatasourceResultInterface;
use MakinaCorpus\Calista\Query\Query;
use MakinaCorpus\Calista\View\AbstractView;
use MakinaCorpus\Calista\View\PropertyRenderer;
use MakinaCorpus\Calista\View\ViewDefinition;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Turn your data stream to CSV file
 */
class CsvStreamView extends AbstractView
{
    private $propertyRenderer;

    /**
     * Default constructor
     *
     * @param PropertyRenderer $propertyRenderer
     */
    public function __construct(PropertyRenderer $propertyRenderer)
    {
        $this->propertyRenderer = $propertyRenderer;
    }

    /**
     * Create header row
     *
     * @param DatasourceResultInterface $items
     * @param ViewDefinition $viewDefinition
     * @param PropertyView[] $properties
     *
     * @return string[]
     */
    private function createHeaderRow(DatasourceResultInterface $items, ViewDefinition $viewDefinition, array $properties)
    {
        $ret = [];

        foreach ($properties as $property) {
            $ret[] = $property->getLabel();
        }

        return $ret;
    }

    /**
     * Create item row
     *
     * @param DatasourceResultInterface $items
     * @param ViewDefinition $viewDefinition
     * @param \MakinaCorpus\Calista\View\PropertyView $properties
     * @param mixed $current
     *
     * @return string[]
     */
    private function createItemRow(DatasourceResultInterface $items, ViewDefinition $viewDefinition, array $properties, $current): array
    {
        $ret = [];

        foreach ($properties as $property) {
            $ret[] = $this->propertyRenderer->renderItemProperty($current, $property);
        }

        return $ret;
    }

    /**
     * Render in stream
     *
     * @param ViewDefinition $viewDefinition
     * @param DatasourceResultInterface $items
     * @param Query $query
     * @param resource $resource
     */
    private function renderInStream(ViewDefinition $viewDefinition, DatasourceResultInterface $items, Query $query, $resource)
    {
        // Add the BOM for Excel to read correctly the file
        if ($viewDefinition->getExtraOptionValue('add_bom', false)) {
            fwrite($resource, "\xEF\xBB\xBF");
        }

        $delimiter = $viewDefinition->getExtraOptionValue('csv_delimiter', ',');
        $enclosure = $viewDefinition->getExtraOptionValue('csv_enclosure', '"');
        $escape = $viewDefinition->getExtraOptionValue('csv_escape_char', '\\');

        $properties = $this->normalizeProperties($viewDefinition, $items);

        // Render the CSV header
        if ($viewDefinition->getExtraOptionValue('add_header', false)) {
            fputcsv($resource, $this->createHeaderRow($items, $viewDefinition, $properties), $delimiter, $enclosure, $escape);
        }

        foreach ($items as $item) {
            fputcsv($resource, $this->createItemRow($items, $viewDefinition, $properties, $item), $delimiter, $enclosure, $escape);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function render(ViewDefinition $viewDefinition, DatasourceResultInterface $items, Query $query): string
    {
        ob_start();

        $resource = fopen('php://output', 'w+');
        $this->renderInStream($viewDefinition, $items, $query, $resource);
        fclose($resource);

        return ob_get_clean();
    }

    /**
     * {@inheritdoc}
     */
    public function renderAsResponse(ViewDefinition $viewDefinition, DatasourceResultInterface $items, Query $query): Response
    {
        $response = new StreamedResponse();
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');

        $filename = $viewDefinition->getExtraOptionValue('filename');
        if ($filename) {
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        }

        $response->setCallback(function () use ($viewDefinition, $items, $query) {
            $resource = fopen('php://output', 'w+');
            $this->renderInStream($viewDefinition, $items, $query, $resource);
            fclose($resource);
        });

        return $response;
    }
}
