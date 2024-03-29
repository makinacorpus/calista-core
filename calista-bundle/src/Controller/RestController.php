<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Bridge\Symfony\Controller;

use MakinaCorpus\Calista\Query\Filter;
use MakinaCorpus\Calista\View\CustomViewBuilder;
use MakinaCorpus\Calista\View\CustomViewBuilderRegistry;
use MakinaCorpus\Calista\View\PropertyRenderer;
use MakinaCorpus\Calista\View\PropertyView;
use MakinaCorpus\Calista\View\View;
use MakinaCorpus\Calista\View\ViewBuilder;
use MakinaCorpus\Calista\View\ViewManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class RestController
{
    private CustomViewBuilderRegistry $customViewBuilderRegistry;
    private PropertyRenderer $propertyRenderer;
    private UrlGeneratorInterface $urlGenerator;
    private ViewManager $viewManager;

    public function __construct(
        CustomViewBuilderRegistry $customViewBuilderRegistry,
        ViewManager $viewManager,
        PropertyRenderer $propertyRenderer,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->customViewBuilderRegistry = $customViewBuilderRegistry;
        $this->propertyRenderer = $propertyRenderer;
        $this->urlGenerator = $urlGenerator;
        $this->viewManager = $viewManager;
    }

    /**
     * Fetch intput and view definition.
     */
    public function definition(Request $request): Response
    {
        $builder = $this->buildViewDefinition($request);

        $inputDefinition = $builder->getInputDefinition();
        $viewDefinition = $builder->getViewDefinition();
        $view = new View($viewDefinition, []);

        return new JsonResponse([
            'allowedSortFields' => \array_keys($inputDefinition->getAllowedSorts()),
            'defaultLimit' => $inputDefinition->getDefaultLimit(),
            'defaultQuery' => $inputDefinition->getDefaultQuery(),
            'defaultSortField' => $inputDefinition->getDefaultSortField(),
            'defaultSortOrder' => $inputDefinition->getDefaultSortOrder(),
            'export' => $viewDefinition->getExtraOptionValue('export', null),
            'exportUrl' => $this->urlGenerator->generate('calista_rest_export', ['_name' => $request->get('_name')]),
            'filters' => \array_map(fn ($filter) => $this->normalizeFilter($filter), $inputDefinition->getFilters()),
            'limitChangeAllowed' => $inputDefinition->isLimitAllowed(),
            'limitQueryParam' => $inputDefinition->getLimitParameter(),
            'maximumLimit' => $inputDefinition->getMaxLimit(),
            'pagerEnabled' => $inputDefinition->isPagerEnabled(),
            'pagerQueryParam' => $inputDefinition->getPagerParameter(),
            'properties' => $this->normalizeProperties($view),
            'propertyDisplayEnabled' => $inputDefinition->isPropertyEnabled(),
            'propertyDisplayParam' => $inputDefinition->getPropertyParameter(),
            'sortFieldQueryParam' => $inputDefinition->getSortFieldParameter(),
            'sortOrderQueryParam' => $inputDefinition->getSortOrderParameter(),
            'url' => $this->urlGenerator->generate('calista_rest_query', ['_name' => $request->get('_name')]),
        ]);
    }

    /**
     * Query data.
     */
    public function query(Request $request): Response
    {
        $builder = $this->buildViewDefinition($request);

        $view = $builder->request($request)->getView();

        $query = $view->getQuery();
        $result = $view->getResult();

        return new StreamedResponse(
            function () use ($query, $result, $view): void {
                // Using "php://memory" without explicit read is a noop
                // but it will prevent PHPUnit test console log to be
                // filled with our streamed responses.
                $handle = \fopen(('test' === \getenv('APP_ENV') ? 'php://memory' : 'php://output'), 'w+');

                \fwrite($handle, '{' .
                    '"limit":' . $result->getLimit() . ',' .
                    '"total":' . ($result->getTotalCount() ?? "null") . ',' .
                    '"page":' . $result->getCurrentPage() . ',' .
                    '"sortField": "' . $query->getSortField() . '",' .
                    '"sortOrder": "' . $query->getSortOrder() . '",' .
                    '"items":['
                );
                $first = true;
                foreach ($result as $item) {
                    if (!$first) {
                        \fwrite($handle, ",");
                    } else {
                        $first = false;
                    }
                    $normalized = $this->propertyRenderer->computeItemRowValues($view, $item);
                    \fwrite($handle, \json_encode($normalized));
                }
                \fwrite($handle, ']}');
            },
            200,
            [
                'Content-Type' => 'application/json',
                'X-Accel-Buffering' => 'no',
            ]
        );
    }

    /**
     * Export data.
     */
    public function export(Request $request): Response
    {
        $builder = $this->buildViewDefinition($request);

        return $builder
            ->request($request)
            ->limit(100000000)
            ->build()
            ->renderAsResponse()
        ;
    }

    private function normalizeProperties(View $view): array
    {
        return \array_map(
            fn ($property) => $this->normalizeProperty($property),
            \array_filter(
                $view->getNormalizedProperties(),
                fn (PropertyView $property) => !$property->isHidden()
            )
        );
    }

    private function normalizeProperty(PropertyView $property): array
    {
        $options = $property->getOptions();

        return [
            'name' => $property->getName(),
            'label' => $property->getLabel(),
            'type' => $property->getType(),
            'options' => [
                'boolAsInt' => (bool) $options['bool_as_int'],
                'boolValueFalse' => $options['bool_value_false'],
                'boolValueTrue' => $options['bool_value_true'],
                'collectionSeparator' => $options['collection_separator'],
                'dateFormat' => $options['date_format'],
                'decimalPrecision' => (int) $options['decimal_precision'],
                'decimalSeparator' => $options['decimal_separator'],
                'decimalThousandSeparator' => $options['thousand_separator'],
                'safeHtml' => (bool) $options['safe_html'],
                'stringRaw' => (bool) $options['string_raw'],
                'stringEllipsis' => $options['string_ellipsis'],
                'stringMaxLength' => (int) $options['string_maxlength'],
            ],
        ];
    }

    private function normalizeFilter(Filter $filter): array
    {
        return [
            'attributes' => $filter->getAttributes(),
            'choicesMap' => $filter->getChoicesMap(),
            'description' => $filter->getDescription(),
            'field' => $filter->getFilterName(), // Deprecated.
            'filterName' => $filter->getFilterName(),
            'mandatory' => $filter->isMandatory(),
            'multiple' => $filter->isMultiple(),
            'noneOption' => $filter->getNoneOption(),
            'propertyName' => $filter->getPropertyName(),
            'title' => $filter->getTitle(),
            'type' => $filter->getTemplateBlockSuffix(),
        ];
    }

    private function buildViewDefinition(Request $request): ViewBuilder
    {
        $builderName = $request->get('_name');
        $options = $request->get('_options') ?? [];
        $format = $request->get('_format') ?? CustomViewBuilder::FORMAT_REST;

        if (!$builderName) {
            throw new NotFoundHttpException('Not Found');
        }

        try {
            $customViewBuilder = $this->customViewBuilderRegistry->get($builderName);
        } catch (\InvalidArgumentException $e) {
            throw new NotFoundHttpException('Not Found');
        }

        $viewBuilder = $this->viewManager->createViewBuilder();
        $customViewBuilder->build($viewBuilder, (array) $options, $format);

        return $viewBuilder;
    }
}
