<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Query;

/**
 * This interface exists because the Link class needs the View object to be
 * able to build links. Sad story is that the Query namespace MUST NOT be
 * dependant upon the View namespace to keep consistency.
 *
 * @codeCoverageIgnore
 */
interface RouteHolder
{
    public function getRoute(): ?string;

    public function getRouteParameters(array ...$additional): array;
}
