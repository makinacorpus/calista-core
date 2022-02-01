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
    const FORMAT_HTML = 'html';
    const FORMAT_REST = 'json';

    /**
     * Build view.
     *
     * @param array<string, null|bool|int|string> $options
     *   Key-value pairs of options for this custom view builder that originate
     *   from user call-site.
     * @param string $format
     *   Arbitrary format, can be dealt with a business option.
     */
    public function build(ViewBuilder $builder, array $options = [], ?string $format = null): void;
}
