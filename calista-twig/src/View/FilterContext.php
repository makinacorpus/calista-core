<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Twig\View;

use MakinaCorpus\Calista\Query\Filter;

final class FilterContext
{
    private Filter $filter;
    private ViewContext $context;

    public function __construct(Filter $filter, ViewContext $context)
    {
        $this->filter = $filter;
        $this->context = $context;
    }

    public function getFilter(): Filter
    {
        return $this->filter;
    }

    public function getViewContext(): ViewContext
    {
        return $this->context;
    }
}
