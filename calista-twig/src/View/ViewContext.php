<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Twig\View;

use MakinaCorpus\Calista\Query\InputDefinition;
use MakinaCorpus\Calista\Query\Query;
use MakinaCorpus\Calista\View\View;
use MakinaCorpus\Calista\View\ViewDefinition;

/**
 * Normalized this part of the Twig view context (that should be an array) into
 * an object, for reference passing along blocks in custom Twig-based rendering
 * pipeline.
 */
final class ViewContext
{
    private Query $query;
    private InputDefinition $input;
    private View $view;
    private ViewDefinition $definition;

    public function __construct(Query $query, InputDefinition $input, View $view, ViewDefinition $definition)
    {
        $this->definition = $definition;
        $this->input = $input;
        $this->query = $query;
        $this->view = $view;
    }

    public function toArray(): array
    {
        return [
            'definition' => $this->definition,
            'input' => $this->input,
            'query' => $this->query,
            'view' => $this->view,
        ];
    }
}
