<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Bridge\Symfony\View;

use MakinaCorpus\Calista\View\View;
use MakinaCorpus\Calista\View\ViewRenderer\AbstractStreamViewRenderer;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\StreamOutput;

/**
 * Render your data in a symfony/console table.
 */
class ConsoleTableViewRenderer extends AbstractStreamViewRenderer
{
    /**
     * {@inheritdoc}
     */
    protected function doRenderInStream(View $view, $resource): void
    {
        $viewDefinition = $view->getDefinition();
        $properties = $view->getNormalizedProperties();

        $output = new StreamOutput($resource);
        $table = new Table($output);

        // Render the CSV header
        if ($viewDefinition->getExtraOptionValue('add_header', false)) {
            $row = $this->createHeaderRow($properties);
            $table->setHeaders($row);
        }

        foreach ($view->getResult() as $item) {
            $row = $this->createItemRow($properties, $item);
            $table->addRow($row);
        }

        $table->render();
    }

    /**
     * {@inheritdoc}
     */
    protected function doGetContentType(View $view): string
    {
        return 'text/plain';
    }
}
