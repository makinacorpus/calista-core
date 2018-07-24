<?php

namespace MakinaCorpus\Calista\Twig\Extension;

use MakinaCorpus\Calista\Query\Query;
use MakinaCorpus\Calista\View\PropertyRenderer;
use Symfony\Component\HttpFoundation\RequestStack;

class PageExtension extends \Twig_Extension
{
    private $debug = false;
    private $pageRenderer;
    private $propertyRenderer;
    private $requestStack;

    /**
     * Default constructor
     */
    public function __construct(RequestStack $requestStack, PropertyRenderer $propertyRenderer /*, PageRenderer $pageRenderer = null */)
    {
        $this->requestStack = $requestStack;
        $this->propertyRenderer = $propertyRenderer;
        /* $this->pageRenderer = $pageRenderer; */
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
            new \Twig_SimpleFunction('calista_item_property', [$this, 'renderItemProperty'], ['is_safe' => ['html']]),
            // new \Twig_SimpleFunction('calista_page', [$this, 'renderPage'], ['is_safe' => ['html']]),
        ];
    }

    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('calista_filter_definition', [$this, 'getFilterDefinition'], ['is_safe' => ['html']]),
            new \Twig_SimpleFilter('calista_filter_query', [$this, 'getFilterQuery'], ['is_safe' => ['html']]),
            new \Twig_SimpleFilter('calista_query_param', [$this, 'flattenQueryParam']),
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
        if (\is_array($value)) {
            return \implode(Query::URL_VALUE_SEP, $value);
        }

        return $value;
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

        /** @var \MakinaCorpus\Calista\Datasource\Filter $filter */
        foreach ($filters as $filter) {
            $field = $filter->getField();
            if (isset($query[$field])) {
                $filterQuery[$field] = $query[$field];
            }
        }

        return \json_encode($filterQuery);
    }

    /**
     * Render a complete page
     */
    public function renderPage(string $name, array $inputOptions = []): string
    {
        if (!$this->pageRenderer) {
            throw new \LogicException("page renderer is not set");
        }

        // return $this->pageRenderer->renderPage($name, $this->requestStack->getCurrentRequest(), $inputOptions);
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'calista_page';
    }
}
