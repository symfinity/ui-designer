<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Command;

use Symfinity\UiDesigner\Accessibility\StrictExportGate;
use Symfinity\UiDesigner\Export\DtcgExportService;
use Symfinity\UiDesigner\Factory\DtcgDraftFactory;
use Symfinity\UiDesigner\Kernel\BuiltinDraftLoader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'ui-designer:export', description: 'Export a built-in preset draft to DTCG theme pack')]
final class ExportCommand extends Command
{
    public function __construct(
        private readonly BuiltinDraftLoader $loader,
        private readonly DtcgExportService $exportService,
        private readonly StrictExportGate $strictExportGate,
        private readonly DtcgDraftFactory $draftFactory,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('theme_id', InputArgument::REQUIRED, 'Built-in theme id (e.g. semantic)')
            ->addOption('skip-accessibility', null, InputOption::VALUE_NONE, 'Skip strict accessibility gate')
            ->addOption('strict', null, InputOption::VALUE_NONE, 'Force strict accessibility gate');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $themeId = (string) $input->getArgument('theme_id');

        try {
            $draft = $this->loader->loadVariant($themeId);
            $this->strictExportGate->assertExportAllowed(
                $draft,
                (bool) $input->getOption('skip-accessibility'),
                (bool) $input->getOption('strict'),
            );
            $path = $this->exportService->export($draft, $this->draftFactory->exportRoot());
        } catch (\Throwable $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $io->success(sprintf('Exported to %s', $path));

        return Command::SUCCESS;
    }
}
