<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View;

/**
 * Implement this interface in order to provide calista views that can be
 * automatically used by other components, such as the REST API and the Twig
 * view helpers.
 */
interface CustomViewBuilder
{
    /**
     * Build view.
     *
     * @param array<string, null|bool|int|string> $options
     *   Key-value pairs of options for this custom view builder that originate
     *   from user call-site.
     */
    public function build(ViewBuilder $builder, array $options = []): void;
}
