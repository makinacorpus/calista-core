<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View\Tests\Mock;

use MakinaCorpus\Calista\Datasource\AbstractDatasource;
use MakinaCorpus\Calista\Datasource\DatasourceResult;
use MakinaCorpus\Calista\Datasource\DefaultDatasourceResult;
use MakinaCorpus\Calista\Query\DefaultFilter;
use MakinaCorpus\Calista\Query\Query;

/**
 * Uses an array as datasource
 */
class IntArrayDatasource extends AbstractDatasource
{
    private $values;

    /**
     * Default constructor
     */
    public function __construct()
    {
        $this->values = \range(1, 255);
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters(): array
    {
        return [
            (new DefaultFilter('odd_or_even', "Odd or Even"))->setChoicesMap([
                'odd' => "Odd",
                'even' => "Even",
            ]),
            (new DefaultFilter('mod3', "Modulo 3"))->setChoicesMap([
                1 => "Yes",
                0 => "No",
            ]),
            (new DefaultFilter('modX', "Modulo X"))->setChoicesMap(\array_combine(\range(0, 10), \range(0, 10))),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getSorts(): array
    {
        return [
            'value' => "Value",
            'odd_or_even' => 'Odd first',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getItems(Query $query): DatasourceResult
    {
        $limit = $query->getLimit();
        $offset = $query->getOffset();
        $allowedValues = $this->values;

        if ($query->has('odd_or_even')) {
            switch ($query->get('odd_or_even')) {

                case 'odd':
                    $allowedValues = \array_filter($allowedValues, function ($value) {
                        return 1 === $value % 2;
                    });
                    break;

                case 'even':
                    $allowedValues = \array_filter($allowedValues, function ($value) {
                        return 0 === $value % 2;
                    });
                    break;

                default:
                    $allowedValues = [];
                    break;
            }
        }

        if ($query->has('mod3')) {
            switch ($query->get('mod3')) {

                case 1:
                    $allowedValues = \array_filter($allowedValues, function ($value) {
                        return 0 === $value % 3;
                    });
                    break;

                case 0:
                    $allowedValues = \array_filter($allowedValues, function ($value) {
                        return 1 === $value % 3;
                    });
                    break;

                default:
                    $allowedValues = [];
                    break;
            }
        }

        if ($query->hasSortField()) {
            if ('value' === $query->getSortField()) {
                if (Query::SORT_DESC === $query->getSortOrder()) {
                    $allowedValues = \array_reverse($allowedValues);
                }
            }
            if ('odd_or_even' === $query->getSortField()) {
                if (Query::SORT_DESC === $query->getSortOrder()) {
                    // Not implemented yet
                } else {
                    // Not implemented yet
                }
            }
        }

        $items = \array_slice($allowedValues, $offset, $limit);
        $items = \array_map(function ($value) { return new IntItem($value); }, $items);

        $result = new DefaultDatasourceResult($items);
        $result->setPagerInformation($limit, \count($allowedValues), $query->getCurrentPage());

        return $result;
    }
}
