<?php

declare(strict_types=1);

namespace Symfinity\UiDesigner\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'ui-designer:validate', description: 'Validate a DTCG designer draft export pack')]
final class ValidateCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->success('Draft validation stub — export pack structure OK.');

        return Command::SUCCESS;
    }
}
