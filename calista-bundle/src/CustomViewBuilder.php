<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Bridge\Symfony;

use MakinaCorpus\Calista\View\ViewBuilder;

/**
 * Implement this interface in order to provide calista views that can be
 * automatically used by other components, such as the REST API and the Twig
 * view helpers.
 */
interface CustomViewBuilder
{
    public function build(ViewBuilder $builder): void;
}
