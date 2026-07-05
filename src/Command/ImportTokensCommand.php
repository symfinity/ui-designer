<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Command;

use Symfinity\UiDesigner\Export\DtcgExportService;
use Symfinity\UiDesigner\Import\ImportRequest;
use Symfinity\UiDesigner\Import\TokenFileImporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'ui-designer:import-tokens', description: 'Import W3C/Tokens Studio/Figma token file into DTCG draft')]
final class ImportTokensCommand extends Command
{
    public function __construct(
        private readonly TokenFileImporter $importer,
        private readonly DtcgExportService $exportService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'Import adapter type', 'dtcg-yaml')
            ->addOption('file', null, InputOption::VALUE_REQUIRED, 'Token file path')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Draft name')
            ->addOption('output', null, InputOption::VALUE_REQUIRED, 'Export directory after import');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $request = new ImportRequest(
            type: (string) $input->getOption('type'),
            file: $this->nullableString($input->getOption('file')),
            name: $this->nullableString($input->getOption('name')),
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
