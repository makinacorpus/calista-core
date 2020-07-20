<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View\Event;

use MakinaCorpus\Calista\View\ViewRenderer;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @codeCoverageIgnore
 */
final class ViewEvent extends Event
{
    const EVENT_VIEW = 'view:view';
    const EVENT_SEARCH = 'view:search';

    private $view;

    public function __construct(ViewRenderer $view)
    {
        $this->view = $view;
    }

    public function getView(): ViewRenderer
    {
        return $this->view;
    }
}
