<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View\Event;

use MakinaCorpus\Calista\View\ViewInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @codeCoverageIgnore
 */
final class ViewEvent extends Event
{
    const EVENT_VIEW = 'view:view';
    const EVENT_SEARCH = 'view:search';

    private $view;

    public function __construct(ViewInterface $view)
    {
        $this->view = $view;
    }

    public function getView(): ViewInterface
    {
        return $this->view;
    }
}
