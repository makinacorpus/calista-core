<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Twig\Extension;

use MakinaCorpus\Calista\Bridge\Symfony\Controller\PageRenderer;
use MakinaCorpus\Calista\Query\Filter;
use MakinaCorpus\Calista\Query\Query;
use MakinaCorpus\Calista\View\PropertyRenderer;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\Extension\AbstractExtension;

class PageExtension extends AbstractExtension
{
    private bool $debug = false;
    private PropertyRenderer $propertyRenderer;
    private RequestStack $requestStack;
    private ?PageRenderer $pageRenderer = null;

    /**
     * Default constructor
     */
    public function __construct(RequestStack $requestStack, PropertyRenderer $propertyRenderer, ?PageRenderer $pageRenderer = null)
    {
        $this->requestStack = $requestStack;
        $this->propertyRenderer = $propertyRenderer;
        $this->pageRenderer = $pageRenderer;
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
            new TwigFunction('calista_item_property', [$this, 'renderItemProperty'], ['is_safe' => ['html']]),
            new TwigFunction('calista_page_range', [$this, 'computePageRange'], ['is_safe' => ['html']]),
            new TwigFunction('calista_page', [$this, 'renderPage'], ['is_safe' => ['html']]),
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
     * Render a single item property
     *
     * @param object $item
     *   Item on which to find the property
     * @param string|\MakinaCorpus\Calista\View\PropertyView $property
     *   Property name
     * @param mixed[] $options
     *   Display options for the property, dropped if the $property parameter
     *   is an instance of PropertyView
     *
     * @return string
     */
    public function renderItemProperty($item, $property, array $options = [])
    {
        return $this->propertyRenderer->renderItemProperty($item, $property, $options);
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
     * Render a complete page
     */
    public function renderPage(string $name, array $inputOptions = [], array $viewOptions = []): string
    {
        if (!$this->pageRenderer) {
            throw new \LogicException("page renderer is not set");
        }

        return $this->pageRenderer->renderPage($name, $this->requestStack->getCurrentRequest(), $inputOptions, $viewOptions);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'calista_page';
    }
}
