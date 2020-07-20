<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Bridge\Symfony\Command;

use MakinaCorpus\Calista\Bridge\Symfony\DependencyInjection\ViewFactory;
use MakinaCorpus\Calista\Datasource\DatasourceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Introspect datasources
 */
final class DatasourceCommand extends Command
{
    protected static $defaultName = 'calista:datasource';
    private $viewFactory;

    /**
     * Default constructor
     */
    public function __construct(ViewFactory $viewFactory)
    {
        parent::__construct();

        $this->viewFactory = $viewFactory;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription("Introspect datasources");
        $this->setDefinition([
            new InputArgument('action', InputArgument::REQUIRED, "One of: info, list, filter, sort"),
            new InputArgument('datasource', InputArgument::OPTIONAL, "Datasource class, identifier or service identifer, required for all other actions than 'list'"),
        ]);
    }

    /**
     * List available datasources
     */
    private function listAction(InputInterface $input, OutputInterface $output): void
    {
        $index = $this->viewFactory->listDatasources();

        if (!$input) {
            $output->writeln('<info>there is no defined datasource</info>');
            return;
        }

        $table = new Table($output);
        $table->setHeaders(['identifier', 'service', 'datasource']);
        foreach ($index as $id => $data) {
            $row = [$id, $data['service'], $data['class']];
            $table->addRow($row);
        }
        $table->render();
    }

    /**
     * Render boolean as yes or no
     */
    private function renderYesOrNo($value)
    {
        if ($value) {
            return 'yes';
        }
        return 'no';
    }

    /**
     * Display datasource available filters
     */
    private function infoAction(InputInterface $input, OutputInterface $output, DatasourceInterface $datasource): void
    {
        $table = new Table($output);
        $table->addRow(["datasource class", \get_class($datasource)]);
        $table->addRow(["can stream data", $this->renderYesOrNo($datasource->supportsStreaming())]);
        $table->addRow(["supports pagination", $this->renderYesOrNo($datasource->supportsPagination())]);
        $table->addRow(["supports fulltext search", $this->renderYesOrNo($datasource->supportsFulltextSearch())]);
        $table->addRow(["filter count", \count($datasource->getFilters())]);
        $table->addRow(["sort count", \count($datasource->getSorts())]);
        $table->render();
    }

    /**
     * Display datasource available filters
     */
    private function filterAction(InputInterface $input, OutputInterface $output, DatasourceInterface $datasource)
    {
        $filters = $datasource->getFilters();

        if (!$filters) {
            $output->writeln('<info>datasource has no filters</info>');
            return;
        }

        $table = new Table($output);
        $table->setHeaders(['name', 'label', 'safe', 'choices']);
        $table->setStyle('compact');
        /** @var \MakinaCorpus\Calista\Query\Filter $filter */
        foreach ($filters as $filter) {

            if ($filter->count()) {
                $choices = [];
                foreach ($filter->getChoicesMap() as $value => $label) {
                    $choices[] = $value.': '.$label;
                }
                $choices = \implode("\n", $choices);
            } else {
                $choices = "arbitrary";
            }

            $table->addRow([
                $filter->getField(),
                $filter->getTitle(),
                $filter->isSafe() ? "yes" : 'no',
                $choices
            ]);
        }
        $table->render();
    }

    /**
     * Display datasource available sorts
     */
    private function sortAction(InputInterface $input, OutputInterface $output, DatasourceInterface $datasource): void
    {
        $sorts = $datasource->getSorts();

        if (!$sorts) {
            $output->writeln('<info>datasource has no sort</info>');
            return;
        }

        $table = new Table($output);
        $table->setHeaders(['name', 'label']);
        foreach ($sorts as $name => $label) {
            $table->addRow([$name, $label]);
        }
        $table->render();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $action = $input->getArgument('action');

        if ('list' === $action) {
            return $this->listAction($input, $output);
        }

        if (!$input->hasArgument('datasource')) {
            $output->writeln("<error>datasource argument is required</error>");
            return -1;
        }

        $datasourceId = $input->getArgument('datasource');
        $datasource = $this->viewFactory->getDatasource($datasourceId);

        switch ($action) {

            case 'info':
                return $this->infoAction($input, $output, $datasource);

            case 'filter':
                return $this->filterAction($input, $output, $datasource);

            case 'sort':
                return $this->sortAction($input, $output, $datasource);

            default:
                $output->writeln(\sprintf("unknown action '%s'", $action));
                return -1;
        }
    }
}
