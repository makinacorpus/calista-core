<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View\ViewRenderer;

use MakinaCorpus\Calista\View\View;
use MakinaCorpus\Calista\View\Attribute\Renderer;

/**
 * Turn your data stream to CSV file.
 */
#[Renderer(name: 'csv')]
#[Renderer(name: 'csv_stream')]
class CsvStreamViewRenderer extends AbstractStreamViewRenderer
{
    /**
     * {@inheritdoc}
     */
    protected function doRenderInStream(View $view, $resource): void
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
            $row = $this->createItemRow($view, $item);
            if ($encoding !== 'utf-8') {
                $row = \mb_convert_encoding($row, $encoding);
            }
            \fputcsv($resource, $row, $delimiter, $enclosure, $escape);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doGetContentType(View $view): string
    {
        return 'text/csv';
    }
}
