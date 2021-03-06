<?php

namespace MakinaCorpus\Calista\Datasource\Stream;

use MakinaCorpus\Calista\Datasource\AbstractDatasource;
use MakinaCorpus\Calista\Datasource\DatasourceResultInterface;
use MakinaCorpus\Calista\Query\Query;

/**
 * Decent CSV streamed reader, that will consume very low memory
 */
class CsvStreamDatasource extends AbstractDatasource
{
    private string $filename;
    private array $options;

    public function __construct(string $filename, array $options = [])
    {
        $this->filename = $filename;
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function getItems(Query $query): DatasourceResultInterface
    {
        $reader = new CsvStreamReader($this->filename, $this->options);

        return $this->createResult($reader, $reader->isCountReliable() ? \count($reader) : null);
    }
}
