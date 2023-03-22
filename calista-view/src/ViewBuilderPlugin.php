<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View;

/**
 * Provide site global plugin on view builders, for interacting with views.
 */
interface ViewBuilderPlugin
{
    /**
     * Interact on a view builder when creating it.
     *
     * Parameters are propagated via ViewManager::createViewBuilder() and
     * CustomViewbuilder::build().
     *
     * @param array<string, null|bool|int|string> $options
     *   Key-value pairs of options for this custom view builder that originate
     *   from user call-site.
     * @param string $format
     *   Arbitrary format, can be dealt with a business option.
     *
     * @see ViewManager::createViewBuilder()
     * @see CustomViewbuilder::build()
     */
    public function apply(ViewBuilder $builder, array $options = [], ?string $format = null): void;
}
