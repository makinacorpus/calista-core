<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Twig\Extension;

use MakinaCorpus\Calista\Query\Filter;
use MakinaCorpus\Calista\Query\Query;
use MakinaCorpus\Calista\View\PropertyRenderer;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\Extension\AbstractExtension;

class PageExtension extends AbstractExtension
{
    private bool $debug = false;
    private PropertyRenderer $propertyRenderer;
    private RequestStack $requestStack;
    private ?UrlGeneratorInterface $urlGenerator = null;

    /**
     * Default constructor
     */
    public function __construct(
        RequestStack $requestStack,
        PropertyRenderer $propertyRenderer,
        ?UrlGeneratorInterface $urlGenerator = null
    ) {
        $this->requestStack = $requestStack;
        $this->propertyRenderer = $propertyRenderer;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Enable or disable debug mode, mostly useful for unit tests.
     */
    public function setDebug($debug = true)
    {
        $this->debug = (bool)$debug;
        $this->propertyRenderer->setDebug($debug);
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('calista_item_actions', [$this, 'renderItemActions'], ['is_safe' => ['html']]),
            new TwigFunction('calista_item_property', [$this, 'renderItemProperty'], ['is_safe' => ['html']]),
            new TwigFunction('calista_page_range', [$this, 'computePageRange'], ['is_safe' => ['html']]),
            // Pass-thgouth to URL generator, because sometime, we have a null
            // path, and we will just ignore errors and use '#' as route.
            new TwigFunction('calista_path', [$this, 'renderPath'], ['is_safe' => ['html']]),
        ];
    }

    public function getFilters()
    {
        return [
            new TwigFilter('calista_filter_definition', [$this, 'getFilterDefinition'], ['is_safe' => ['html']]),
            new TwigFilter('calista_filter_query', [$this, 'getFilterQuery'], ['is_safe' => ['html']]),
            new TwigFilter('calista_query_param', [$this, 'flattenQueryParam']),
        ];
    }

    /**
     * Render URL.
     */
    public function renderPath(?string $name = null, array $parameters = [], bool $relative = false): string
    {
        if (!$name) {
            return '#' . \http_build_query($parameters);
        }
        if (!$this->urlGenerator) {
            return $name . '#' . \http_build_query($parameters);
        }

        return $this->urlGenerator->generate($name, $parameters, $relative ? UrlGeneratorInterface::RELATIVE_PATH : UrlGeneratorInterface::ABSOLUTE_PATH);
    }

    /**
     * From given callable, run item action column builder.
     */
    public function renderItemActions($item, $callback): ?string
    {
        if (!\is_callable($callback)) {
            if ($this->debug) {
                throw new \InvalidArgumentException("twig's renderer 'table_action' extra parameter must be a callable.");
            }
            return null;
        }

        $output = $callback($item);

        if (null !== $output &&
            !\is_string($output) &&
            !\is_scalar($output) &&
            (!\is_object($output) || !\method_exists($output, '__toString'))
        ) {
            throw new \InvalidArgumentException(\sprintf("twig's renderer 'table_action' callack did not return a string."));
        }

        return (string)$output;
    }

    /**
     * Render a single item property
     *
     * @param object $item
     *   Item on which to find the property
     * @param string|array|\MakinaCorpus\Calista\View\PropertyView $property
     *   Property name, raws options array, or PropertyView instance.
     * @param mixed[] $options
     *   Display options for the property, dropped if the $property parameter
     *   is an instance of PropertyView
     *
     * @return string
     */
    public function renderItemProperty($item, $property = null, ?array $options = null)
    {
        return $this->propertyRenderer->renderProperty($item, $property, $options);
    }

    /**
     * Flatten query param if array
     *
     * @param string|string[] $value
     *
     * @codeCoverageIgnore
     */
    public function flattenQueryParam($value)
    {
        return Query::valuesEncode($value);
    }

    /**
     * Return a JSON encoded representing the filter definition
     *
     * @param \MakinaCorpus\Calista\Query\Filter[] $filters
     *
     * @codeCoverageIgnore
     */
    public function getFilterDefinition(array $filters): string
    {
        $definition = [];

        /** @var \MakinaCorpus\Calista\Query\Filter $filter */
        foreach ($filters as $filter) {
            $definition[] = [
                'value'   => $filter->getField(),
                'label'   => $filter->getTitle(),
                'options' => !$filter->isSafe() ?: $filter->getChoicesMap(),
            ];
        }

        return \json_encode($definition);
    }

    /**
     * Return a JSON encoded representing the initial filter query
     *
     * @param \MakinaCorpus\Calista\Query\Filter[] $filters
     * @param string[] $query
     *
     * @codeCoverageIgnore
     */
    public function getFilterQuery(array $filters, array $query): string
    {
        $filterQuery = [];

        foreach ($filters as $filter) {
            \assert($filter instanceof Filter);

            $field = $filter->getField();
            if (isset($query[$field])) {
                $filterQuery[$field] = $query[$field];
            }
        }

        return \json_encode($filterQuery);
    }

    /**
     * Compute page range.
     */
    public function computePageRange(?int $total, ?int $page = 1, ?int $limit = Query::LIMIT_DEFAULT): array
    {
        if (!$total || !$page || !$limit) {
            return [];
        }

        $num = \ceil(($total) / $limit);
        $min = \max([$page - 2, 1]);
        $max = \min([$page + 2, $num]);

        if ($max - $min < 4) {
            if (1 == $min) {
                return \range(1, \min([5, $num]));
            } else {
                return \range(\max([$num - 4, 1]), $num);
            }
        } else {
            return \range($min, $max);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'calista_page';
    }
}
