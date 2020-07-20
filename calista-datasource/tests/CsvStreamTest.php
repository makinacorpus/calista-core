<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Datasource\Tests;

use MakinaCorpus\Calista\Datasource\Stream\CsvStreamDatasource;
use MakinaCorpus\Calista\Datasource\Stream\CsvStreamReader;
use MakinaCorpus\Calista\Query\QueryFactory;
use MakinaCorpus\Calista\View\PropertyRenderer;
use MakinaCorpus\Calista\View\ViewDefinition;
use MakinaCorpus\Calista\View\Stream\CsvStreamView;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Tests both the CSV stream reader and stream viewer
 */
class CsvStreamTest extends TestCase
{
    private function createPropertyAccessor()
    {
        return new PropertyAccessor();
    }

    public function testReaderWithoutHeader()
    {
        $filename = __DIR__ . '/stream.csv';

        $reader = new CsvStreamReader($filename, ['delimiter' => ';']);

        $this->assertTrue($reader->valid());
        $this->assertSame(['a', 'b', 'c'], $reader->current());
        $reader->next();
        $this->assertSame(['1', '2', '3'], $reader->current());
        $reader->next();
        $this->assertSame(['4', '5', '6'], $reader->current());
        $reader->next();
        $this->assertSame(['foo', 'bar', '"baz"'], $reader->current());
        $reader->next();
        $this->assertSame(['foo', '#bar#', '###baz###'], $reader->current());
        $reader->next();

        $this->assertSame(null, $reader->current());
        $this->assertFalse($reader->valid());
    }

    public function testReaderWithHeader()
    {
        $filename = __DIR__ . '/stream.csv';

        $reader = new CsvStreamReader($filename, ['delimiter' => ';', 'headers' => true]);

        $this->assertTrue($reader->valid());
        $this->assertSame(['a', 'b', 'c'], $reader->getHeaders());

        $this->assertSame(['a' => '1', 'b' => '2', 'c' => '3'], $reader->current());
        $reader->next();
        $this->assertSame(['a' => '4', 'b' => '5', 'c' => '6'], $reader->current());
        $reader->next();
        $this->assertSame(['a' => 'foo', 'b' => 'bar', 'c' => '"baz"'], $reader->current());
        $reader->next();
        $this->assertSame(['a' => 'foo', 'b' => '#bar#', 'c' => '###baz###'], $reader->current());
        $reader->next();

        $this->assertSame(null, $reader->current());
        $this->assertFalse($reader->valid());
    }

    public function testCsvDatasource()
    {
        $filename = __DIR__ . '/stream.csv';
        $datasource = new CsvStreamDatasource($filename, ['delimiter' => ';']);
        $query = (new QueryFactory())->fromArbitraryArray([]);
        $items = $datasource->getItems($query);

        $this->assertTrue($datasource->supportsStreaming());
        $this->assertFalse($datasource->supportsPagination());
        $this->assertFalse($datasource->supportsFulltextSearch());
        $this->assertFalse($datasource->validateItems($query, ['any']));

        foreach ($items as $index => $item) {
            switch ($index) {
                case 0:
                    $this->assertSame(['a', 'b', 'c'], $item);
                    break;
                case 1:
                    $this->assertSame(['1', '2', '3'], $item);
                    break;
                case 2:
                    $this->assertSame(['4', '5', '6'], $item);
                    break;
                case 3:
                    $this->assertSame(['foo', 'bar', '"baz"'], $item);
                    break;
                case 4:
                    $this->assertSame(['foo', '#bar#', '###baz###'], $item);
                    break;
                default:
                    $this->fail();
                    break;
            }
        }
    }

    public function testCsvInputToCsvOutput()
    {
        if (!\class_exists('MakinaCorpus\\Calista\\View\\ViewDefinition')) {
            $this->markTestSkipped("calista-view must be present for this test to run");
        }

        $filename = __DIR__ . '/stream.csv';
        $datasource = new CsvStreamDatasource($filename, ['delimiter' => ';']);
        $query = (new QueryFactory())->fromArbitraryArray([]);
        $items = $datasource->getItems($query);

        $viewDefinition = new ViewDefinition([
            'extra' => [
                'add_bom' => true,
                'add_header' => true,
                'filename' => 'some_export.csv',
            ],
            'properties' => [
                0 => ['label' => "The first column"],
                1 => ['label' => "The second column"],
                2 => ['label' => "The third column"],
            ],
        ]);

        $view = new CsvStreamView(new PropertyRenderer($this->createPropertyAccessor()));
        $output = $view->render($viewDefinition, $items, $query);

        $reference = <<<EOT
﻿"The first column","The second column","The third column"
a,b,c
1,2,3
4,5,6
foo,bar,"""baz"""
foo,#bar#,###baz###
EOT;

        // We trim because fputscsv() always add a newline at end of file
        $this->assertSame($reference, rtrim($output));

        // And now as a reponse
        $response = $view->renderAsResponse($viewDefinition, $items, $query);
        $this->assertInstanceOf(StreamedResponse::class, $response);

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();
        $this->assertSame($reference, rtrim($content));

        $viewDefinition = new ViewDefinition([
            'extra' => [
                'add_bom' => true,
                'add_header' => false,
                'csv_delimiter' => ';',
                'csv_enclosure' => '#',
            ],
            'properties' => [
                0 => ['label' => "The first column"],
                1 => ['label' => "The second column"],
                2 => ['label' => "The third column"],
            ],
        ]);

        $view = new CsvStreamView(new PropertyRenderer($this->createPropertyAccessor()));
        $output = $view->render($viewDefinition, $items, $query);

        $reference = <<<EOT
a;b;c
1;2;3
4;5;6
foo;"bar";"""baz"""
foo;#bar#;###baz###
EOT;
    }
}
