<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View\Stream;

use Box\Spout\Common\Type;
use Box\Spout\Writer\WriterFactory;
use Box\Spout\Writer\WriterInterface;
use MakinaCorpus\Calista\View\AbstractViewRenderer;
use MakinaCorpus\Calista\View\PropertyRenderer;
use MakinaCorpus\Calista\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Turn your data stream to an XLSX (Excel) file
 */
class SpoutXlsxStreamViewRenderer extends AbstractViewRenderer
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
            $ret[] = $this->propertyRenderer->renderItemProperty($current, $property);
        }

        return $ret;
    }

    /**
     * Render row in writer
     */
    private function renderInWriter(View $view, WriterInterface $writer)
    {
        $viewDefinition = $view->getDefinition();
        $properties = $view->getNormalizedProperties();

        // Render the CSV header
        if ($viewDefinition->getExtraOptionValue('add_header', false)) {
            $writer->addRow($this->createHeaderRow($properties));
        }

        foreach ($view->getResult() as $item) {
            $writer->addRow($this->createItemRow($properties, $item));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function render(View $view): string
    {
        \ob_start();

        $writer = WriterFactory::create(Type::XLSX);
        $writer->openToFile('php://output');

        $this->renderInWriter($view, $writer);

        $writer->close();

        return \ob_get_clean();
    }

    /**
     * {@inheritdoc}
     */
    public function renderAsResponse(View $view): Response
    {
        $viewDefinition = $view->getDefinition();

        $response = new StreamedResponse();
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=utf-8');

        $filename = $viewDefinition->getExtraOptionValue('filename');
        if ($filename) {
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        }

        $response->setCallback(function () use ($view) {

            $writer = WriterFactory::create(Type::XLSX);
            $writer->openToFile('php://output');

            $this->renderInWriter($view, $writer);

            $writer->close();
        });

        return $response;
    }
}
