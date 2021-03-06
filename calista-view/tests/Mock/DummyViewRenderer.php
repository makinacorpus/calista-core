<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View\Tests\Mock;

use MakinaCorpus\Calista\View\PropertyRenderer;
use MakinaCorpus\Calista\View\View;
use MakinaCorpus\Calista\View\ViewRenderer\AbstractViewRenderer;
use Symfony\Component\HttpFoundation\Response;

final class DummyViewRenderer extends AbstractViewRenderer
{
    public function __construct()
    {
        parent::__construct(new PropertyRenderer());
    }

    public function renderAsResponse(View $view): Response
    {
        throw new \Exception("Not implemented.");
    }

    public function render(View $view): string
    {
        return "Dummy content.";
    }

    /**
     * Passthrough to createItemRow() for unit test purpose.
     */
    public function getItemRow(View $view, $item): array
    {
        return $this->createItemRow($view, $item);
    }
}
