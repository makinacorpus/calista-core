<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Bridge\Symfony\Command;

use MakinaCorpus\Calista\Bridge\Symfony\DependencyInjection\ViewFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Introspect views
 */
final class ViewCommand extends Command
{
    protected static $defaultName = 'calista:view';
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
        $this->setDescription("Introspect views");
    }

    /**
     * List available datasources
     */
    private function listAction(InputInterface $input, OutputInterface $output): void
    {
        $index = $this->viewFactory->listViews();

        if (!$input) {
            $output->writeln('<info>there is no defined datasource</info>');
            return;
        }

        $table = new Table($output);
        $table->setHeaders(['identifier', 'service', 'class', 'status']);
        foreach ($index as $id => $data) {
            try {
                $this->viewFactory->getView($id);
                $status = 'ok';
            } catch (\Exception $e) {
                $status = '<error>broken</error>';
            }
            $row = [$id, $data['service'], $data['class'], $status];
            $table->addRow($row);
        }
        $table->render();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        return $this->listAction($input, $output);
    }
}
