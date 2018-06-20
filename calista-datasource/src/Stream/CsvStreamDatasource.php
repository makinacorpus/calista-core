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
    private $filename;
    private $options;

    /**
     * Default constructor
     *
     * @param string $filename
     * @param string[] $options
     *   Options for the CsvStreamReader constructor
     */
    public function __construct($filename, array $options = [])
    {
        $this->filename = $filename;
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsStreaming(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsPagination(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsFulltextSearch(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getItems(Query $query): DatasourceResultInterface
    {
        $reader = new CsvStreamReader($this->filename, $this->options);

        return $this->createResult($reader, $reader->isCountReliable() ? count($reader) : null);
    }
}
