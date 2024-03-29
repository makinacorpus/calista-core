<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Datasource\Stream;

/**
 * Decent CSV streamed reader, that will consume very low memory
 */
class CsvStreamReader implements \Iterator, \Countable
{
    private $countApproximation = 0;
    private $currentFileIndex = 0;
    private $decentFgetcsv = false;
    private $eofReached = false;
    private $filename;
    private $forceContentTrim = true;
    private $handle;
    private $headers;
    private $isCountReliable = false;
    private $line;
    private $offset = 0;
    private $parseHeaders = false;
    private $settings = [];

    /**
     * Default constructor
     *
     * @param string $filename
     * @param array $settings
     */
    public function __construct($filename, $settings = [])
    {
        $this->settings = $settings + [
            'delimiter' => ',',
            'enclosure' => '"',
            'escape' => '\\',
            'length' => 2048,
            'headers' => false,
        ];

        $this->parseHeaders = $this->settings['headers'];
        $this->forceContentTrim = true;
        $this->filename = $filename;
        $this->checkFile();
        $this->decentFgetcsv = (version_compare(PHP_VERSION, '5.3.0') >= 0);
    }

    /**
     * Default destructor
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Ensures that file exists, aggregates information about it
     */
    private function checkFile(): void
    {
        if (!\file_exists($this->filename)) {
            throw new \InvalidArgumentException("File '" . $this->filename . "' does not exists");
        }
        if (!\is_readable($this->filename)) {
            throw new \InvalidArgumentException("File '" . $this->filename . "' cannot be read");
        }

        // Set the count approximation.
        if (shell_exec("which cat")) {
            $this->countApproximation = ((int)\shell_exec("cat " . \escapeshellcmd($this->filename) . " | wc -l")) - 1;
            $this->isCountReliable = true;
        } else {
            $this->countApproximation = (int)\filesize($this->filename) / 100;
            $this->isCountReliable = false;
        }
    }

    /**
     * Get current line position in file stream.
     */
    public function getCurrentFileIndex(): int
    {
        return $this->currentFileIndex;
    }

    /**
     * Get CSV headers.
     *
     * @return null|string[]
     */
    public function getHeaders(): ?array
    {
        $this->init();

        return $this->headers;
    }

    /**
     * Does CSV has given header.
     */
    public function hasHeader(string $propertyName): bool
    {
        return \in_array($propertyName, $this->getHeaders());
    }

    /**
     * Unreliable count means that the total count will be an approximation.
     */
    public function isCountReliable(): bool
    {
        return $this->isCountReliable;
    }

    private function handleIsValid(): bool
    {
        return isset($this->handle) && false !== $this->handle;
    }

    private function fetchNextLine(): void
    {
        $this->currentFileIndex = \ftell($this->handle);

        // Prepare some parameters.
        $l = $this->settings['length'];
        $d = $this->settings['delimiter'];
        $e = $this->settings['enclosure'];
        $c = $this->settings['escape'];
        $line = null;
        $this->line = null;

        // PHP 5.3 accepts the 'escape' parameters, using it on PHP 5.2 will make
        // the fgetcsv() function throw a warning and not return any array.
        if ($this->decentFgetcsv) {
            $line = \fgetcsv($this->handle, $l, $d, $e, $c);
        } else {
            $line = \fgetcsv($this->handle, $l, $d, $e);
        }

        // Check for reading sanity.
        if (false === $line) {
            if (\feof($this->handle)) {
                // We reached the end of file, but our object is still valid. Reset
                // the buffer to empty but leave the rest as-is.
                $this->eofReached = true;
            } else {
                throw new \LogicException("Error while reading the file '" . $this->filename . "'");
            }
        } else {
            $this->line = $line;
        }
    }

    private function init(): void
    {
        if (!$this->handleIsValid()) {

            $this->handle = \fopen($this->filename, "r");

            if (false === $this->handle) {
                if (feof($this->handle)) {
                    throw new \LogicException("File '" . $this->filename . "' is empty");
                } else {
                    throw new \LogicException("Cannot fopen() file '" . $this->filename . "'");
                }
            }

            $this->fetchNextLine();

            if (false === $this->line) {
                throw new \LogicException("Empty CSV file");
            }

            if ($this->parseHeaders) {
                foreach ($this->line as $header) {
                    $this->headers[] = \trim($header);
                }

                // Position the stream over the real first item.
                $this->fetchNextLine();

            } else {
                $this->headers = \range(0, \count($this->line));
            }
        }
    }

    private function close(): void
    {
        if (isset($this->handle)) {
            fclose($this->handle);
            unset($this->handle);
        }
    }

    private function reset(): void
    {
        $this->close();
        $this->eofReached = false;
        $this->offset = 0;
        $this->line = null;
        $this->headers = null;
    }

    private function formatLine(array $line): array
    {
        $ret = [];

        foreach ($line as $key => $value) {
            if ($this->parseHeaders && isset($this->headers[$key])) {
                if ($this->forceContentTrim) {
                    $ret[$this->headers[$key]] = \trim($value);
                } else {
                    $ret[$this->headers[$key]] = $value;
                }
            } else {
                if ($this->forceContentTrim) {
                    $ret[] = \trim($value);
                } else {
                    $ret[] = $value;
                }
            }
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function next(): void
    {
        $this->init();
        $this->fetchNextLine();
        ++$this->offset;
    }

    /**
     * {@inheritdoc}
     */
    public function key(): mixed
    {
        return $this->offset;
    }

    /**
     * {@inheritdoc}
     */
    public function valid(): bool
    {
        $this->init();

        return !$this->eofReached && isset($this->line);
    }

    /**
     * {@inheritdoc}
     */
    public function rewind(): void
    {
        $this->reset();
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return $this->countApproximation;
    }

    /**
     * {@inheritdoc}
     */
    public function current(): mixed
    {
        if ($this->eofReached) {
            return null;
        } else {
            if (isset($this->line)) {
                return $this->formatLine($this->line);
            } else {
                return null;
            }
        }
    }
}
