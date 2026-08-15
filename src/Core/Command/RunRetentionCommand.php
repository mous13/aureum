<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Command;

use Citadel\Aureum\Core\Service\RetentionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'aureum:retention:run',
    description: 'Anonymise guest records that have passed their hotel retention period',
)]
class RunRetentionCommand extends Command
{
    public function __construct(
        private readonly RetentionService $retentionService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            'Report what would be anonymised without changing anything',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool)$input->getOption('dry-run');

        $io->title($dryRun ? 'Retention run (dry run)' : 'Retention run');

        $results = $this->retentionService->run($dryRun);

        if ($results === []) {
            $io->success('Nothing has passed its retention period.');

            return Command::SUCCESS;
        }

        $rows = [];
        $total = 0;
        foreach ($results as $key => $count) {
            $rows[] = [$key, $count];
            $total += $count;
        }

        $io->table(['Hotel / module', 'Records'], $rows);

        if ($dryRun) {
            $io->warning("{$total} records would be anonymised. Re-run without --dry-run to apply.");

            return Command::SUCCESS;
        }

        $io->success("{$total} records anonymised.");

        return Command::SUCCESS;
    }
}
