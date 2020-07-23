<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View\Event;

use MakinaCorpus\Calista\View\ViewBuilder;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @codeCoverageIgnore
 */
final class ViewBuilderEvent extends Event
{
    const EVENT_BUILD = 'view-builder:build';

    private $viewBuilder;

    public function __construct(ViewBuilder $viewBuilder)
    {
        $this->viewBuilder = $viewBuilder;
    }

    public function getViewBuilder(): ViewBuilder
    {
        return $this->viewBuilder;
    }
}
