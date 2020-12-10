<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Twig\View;

use Twig\Environment;
use Twig\TemplateWrapper;

/**
 * Holds the list of templates that contains rendering blocks for calista pages
 * in order of precendence, and lookup for blocks when asked at rendering time.
 */
final class DefaultTwigBlockRenderer implements TwigBlockRenderer
{
    private Environment $twig;
    private array $blockCache = [];

    /**
     * Array of templates in which to lookup for blocks, in order. Common
     * ordering scenario is:
     *   - first of all is the user list template, which should not extend
     *     the default template,
     *   - then come site-templates, which may contain any or all of the table
     *     rendering blocks, as well as filter rendering blocks,
     *   - always last, the common template.
     *
     * @var string[]
     */
    private array $templates;

    public function __construct(Environment $twig, array $templates)
    {
        if (!$templates) {
            $this->templates = ['@calista/page/page.html.twig'];
        }

        $this->twig = $twig;
        $this->templates = $templates;
    }

    public function getEnvironment(): Environment
    {
        return $this->twig;
    }

    public function create(?string $customTemplate): TwigBlockRenderer
    {
        if ($customTemplate) {
            return new CustomTwigBlockRenderer($this, $customTemplate);
        }
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function render(array $arguments = []): string
    {
        return $this->renderBlock('page', $arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function renderBlock(string $blockName, array $arguments = []): string
    {
        return $this->getBlockTemplate($blockName)->renderBlock($blockName, $arguments);
    }

    private function getBlockTemplate(string $blockName): TemplateWrapper
    {
        return $this->blockCache[$blockName] ?? ($this->blockCache[$blockName] = $this->findBlockTemplate($blockName));
    }

    private function findBlockTemplate(string $blockName): TemplateWrapper
    {
        foreach ($this->templates as $filename) {
            $wrapper = $this->twig->load($filename);

            if ($wrapper->hasBlock($blockName)) {
                return $wrapper;
            }
        }

        throw new \InvalidArgumentException(\sprintf("Block with name '%s' could not be found in templates.", $blockName));
    }
}
