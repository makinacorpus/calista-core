<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View\Stream;

use Box\Spout\Common\Type;
use Box\Spout\Writer\WriterFactory;
use Box\Spout\Writer\WriterInterface;
use MakinaCorpus\Calista\Datasource\DatasourceResultInterface;
use MakinaCorpus\Calista\Query\Query;
use MakinaCorpus\Calista\View\AbstractViewRenderer;
use MakinaCorpus\Calista\View\PropertyRenderer;
use MakinaCorpus\Calista\View\ViewDefinition;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Turn your data stream to an XLSX (Excel) file
 */
class SpoutXlsxStreamViewRenderer extends AbstractViewRenderer
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
     * @param \MakinaCorpus\Calista\View\PropertyView[] $properties
     *
     * @return string[]
     */
    private function createHeaderRow(DatasourceResultInterface $items, ViewDefinition $viewDefinition, array $properties): array
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
     * @param \MakinaCorpus\Calista\View\PropertyView[] $properties
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
     * Render row in writer
     */
    private function renderInWriter(ViewDefinition $viewDefinition, DatasourceResultInterface $items, Query $query, WriterInterface $writer)
    {
        $properties = $this->normalizeProperties($viewDefinition, $items);

        // Render the CSV header
        if ($viewDefinition->getExtraOptionValue('add_header', false)) {
            $writer->addRow($this->createHeaderRow($items, $viewDefinition, $properties));
        }

        foreach ($items as $item) {
            $writer->addRow($this->createItemRow($items, $viewDefinition, $properties, $item));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function render(ViewDefinition $viewDefinition, DatasourceResultInterface $items, Query $query): string
    {
        ob_start();

        $writer = WriterFactory::create(Type::XLSX);
        $writer->openToFile('php://output');

        $this->renderInWriter($viewDefinition, $items, $query, $writer);

        $writer->close();

        return ob_get_clean();
    }

    /**
     * {@inheritdoc}
     */
    public function renderAsResponse(ViewDefinition $viewDefinition, DatasourceResultInterface $items, Query $query): Response
    {
        $response = new StreamedResponse();
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=utf-8');

        $filename = $viewDefinition->getExtraOptionValue('filename');
        if ($filename) {
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        }

        $response->setCallback(function () use ($viewDefinition, $items, $query) {

            $writer = WriterFactory::create(Type::XLSX);
            $writer->openToFile('php://output');

            $this->renderInWriter($viewDefinition, $items, $query, $writer);

            $writer->close();
        });

        return $response;
    }
}
