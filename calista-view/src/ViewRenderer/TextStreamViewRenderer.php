<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View\ViewRenderer;

use MakinaCorpus\Calista\View\View;
use MakinaCorpus\Calista\View\Attribute\Renderer;

/**
 * Turn your data stream to CSV file.
 */
#[Renderer(name: 'txt')]
#[Renderer(name: 'text')]
class TextStreamViewRenderer extends AbstractStreamViewRenderer
{
    /**
     * {@inheritdoc}
     */
    protected function doRenderInStream(View $view, $resource): void
    {
        $viewDefinition = $view->getDefinition();
        $properties = $view->getNormalizedProperties();

        $encoding = $viewDefinition->getExtraOptionValue('encoding', 'utf-8');

        foreach ($view->getResult() as $item) {
            $row = $this->createItemRow($view, $item);
            foreach ($properties as $property) {
                $line = $property->getLabel() . ': ' . $row[$property->getName()] ?? '<null>';
                if ($encoding !== 'utf-8') {
                    $row = \mb_convert_encoding($line, $encoding);
                }
                \fwrite($resource, $line . "\n");
            }
            \fwrite($resource, "\n");
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
