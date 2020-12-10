<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Twig\View;

/**
 * Renders the calista page block using Twig, using its own block override
 * mechanism (very close to what symfony/form does).
 *
 * For this to work, all blocks that need to be overridable need to be called
 * using Twig functions, to ensure we bypass Twig own inheritance mechanism
 * and use our own instead.
 */
interface TwigBlockRenderer
{
    /**
     * Render whole page.
     */
    public function render(array $arguments = []): string;

    /**
     * Render a single block.
     */
    public function renderBlock(string $blockName, array $arguments = []): string;
}
