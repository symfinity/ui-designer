<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Command;

use Symfinity\UiDesigner\Export\DtcgExportService;
use Symfinity\UiDesigner\Import\ImportRequest;
use Symfinity\UiDesigner\Import\ShadcnRegistryImporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'ui-designer:import-registry', description: 'Import shadcn registry JSON into DTCG draft files')]
final class ImportRegistryCommand extends Command
{
    public function __construct(
        private readonly ShadcnRegistryImporter $importer,
        private readonly DtcgExportService $exportService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::OPTIONAL, 'Registry theme name')
            ->addOption('url', null, InputOption::VALUE_REQUIRED, 'Registry JSON URL')
            ->addOption('file', null, InputOption::VALUE_REQUIRED, 'Local registry JSON path')
            ->addOption('output', null, InputOption::VALUE_REQUIRED, 'Export directory after import');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = $input->getArgument('name');
        $request = new ImportRequest(
            type: 'shadcn-theme',
            url: $this->nullableString($input->getOption('url')),
            file: $this->nullableString($input->getOption('file')),
            name: is_string($name) && $name !== '' ? $name : null,
        );

        try {
            $result = $this->importer->import($request);
        } catch (\Throwable $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $io->writeln(sprintf('Imported draft: %s', $result->draft->id));
        $outputDir = $this->nullableString($input->getOption('output'));
        if ($outputDir !== null) {
            $path = $this->exportService->export($result->draft, $outputDir);
            $io->success(sprintf('Exported to %s', $path));
        }

        return Command::SUCCESS;
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
