<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Twig\View;

use Twig\Environment;

/**
 * Context variable for twig templates and main renderer for pages
 */
class TwigRenderer
{
    private $twig;
    private $template;
    private $arguments;

    /**
     * Default constructor
     */
    public function __construct(Environment $twig, string $template, array $arguments = [])
    {
        $this->twig = $twig;
        $this->template = $template;
        $this->arguments = $arguments ?? [];
    }

    /**
     * Render a single block of this page
     */
    public function renderPartial(string $blockName): string
    {
        return $this->twig->load($this->template)->renderBlock($blockName, $this->arguments);
    }

    /**
     * Get arguments
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * Render the page
     */
    public function render(): string
    {
        return $this->renderPartial('page');
    }
}
