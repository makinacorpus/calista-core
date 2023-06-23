<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\Bridge\Symfony\Command;

use MakinaCorpus\Calista\View\CustomViewBuilderRegistry;
use MakinaCorpus\Calista\View\ViewBuilder;
use MakinaCorpus\Calista\View\ViewManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Request;

#[AsCommand(name: 'calista:view-builder', description: "List and query view builders.")]
final class ViewBuilderCommand extends Command
{
    public function __construct(
        private CustomViewBuilderRegistry $registry,
        private ViewManager $viewManager
    ) {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->addArgument('builder', InputArgument::OPTIONAL, "View builder name to query, if none provided, list all known view builders.");
        $this->addOption('export', null, InputOption::VALUE_NONE, "Set the export status boolean in view builder.");
        $this->addOption('limit', 'l', InputOption::VALUE_REQUIRED, "Limit to apply to query, 0 means no limit.");
        $this->addOption('page', 'p', InputOption::VALUE_REQUIRED, "Page to apply to query, starts at 1.");
        $this->addOption('format', 'f', InputOption::VALUE_REQUIRED, "Format, can be anything such as json, xml, txt. It must be supported by the view builder.", 'csv');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $builderName = $input->getArgument('builder');

        if ($builderName) {
            $this->queryViewBuilder($input, $output, $builderName);
            return self::SUCCESS;
        }

        $this->listKnownViewBuilders($input, $output);
        return self::SUCCESS;
    }

    private function getFormatRenderer(string $format): string
    {
        return match ($format) {
            'csv' => 'csv',
            'html' => 'twig',
            'txt' => 'text',
            default => $format,
        };
    }

    private function queryViewBuilder(InputInterface $input, OutputInterface $output, string $builderName): void
    {
        $format = $input->getOption('format') ?? 'csv';

        $builder = $this->viewManager->createViewBuilder($builderName, [], $format);
        $builder->renderer($this->getFormatRenderer($format));
        $builder->request($this->createRequest($input, $output, $builder));

        $builder->export((bool) $input->getOption('export'));

        $builder->build()->renderInStream(STDOUT);
    }

    private function createRequest(InputInterface $input, OutputInterface $output, ViewBuilder $builder): Request
    {
        $page = $this->parseInt($input->getOption('page'), 1);
        if ($limit = $this->parseInt($input->getOption('limit'), 0)) {
            $builder->limit($limit);
        }

        $query = [];
        $inputDefinition = $builder->getInputDefinition();

        if ($limit) {
            $query[$inputDefinition->getLimitParameter()] = $limit;
        }
        if ($page) {
            $query[$inputDefinition->getPagerParameter()] = $page;
        }

        return new Request($query);
    }

    private function parseInt(?string $value, int $min = 0, ?int $max = null): ?int
    {
        if (null === $value) {
            return null;
        }
        $value = (int) $value;
        if ($value < $min) {
            return $min;
        }
        if (null !== $max && $max < $value) {
            return $max;
        }
        return $value;  
    }

    private function listKnownViewBuilders(InputInterface $input, OutputInterface $output): void
    {
        $list = [];
        foreach ($this->registry->list() as $name) {
            $list[] = $name;
        }

        if ($list) {
            $output->writeln("All known view builders:");
            \sort($list);
            foreach ($list as $name) {
                $output->writeln(' - ' . $name);
            }
        } else {
            $output->writeln("No view builders found.");
        }
    }
}
