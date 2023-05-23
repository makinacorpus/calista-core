<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View;

/**
 * Provide site global plugin on view builders, for interacting with views.
 */
interface ViewBuilderPlugin
{
    /**
     * Called before custom view builder is built.
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
    public function preBuild(ViewBuilder $builder, array $options = [], ?string $format = null): void;

    /**
     * Called after custom view builder is built.
     *
     * @see self::preInit()
     *   For documentation.
     */
    public function postBuild(ViewBuilder $builder, array $options = [], ?string $format = null): void;

    /**
     * Called during view builder is building.
     *
     * See preBuild() for documentation.
     */
    public function preBuildView(ViewBuilder $builder): void;
}
