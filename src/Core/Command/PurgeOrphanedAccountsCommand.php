<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Command;

use Doctrine\ORM\EntityManagerInterface;
use Forumify\Core\Entity\User;
use Forumify\Core\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'aureum:staff:orphaned-accounts',
    description: 'List sign-in accounts belonging to no employee, and delete named ones',
)]
class PurgeOrphanedAccountsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'delete',
            null,
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'Account id to delete. Repeatable. Omit to list only.',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $rows = $this->entityManager->getConnection()->fetchAllAssociative(
            'SELECT u.id, u.username, u.email
             FROM user u
             WHERE u.id NOT IN (
                 SELECT e.user_id FROM aureum_employees e WHERE e.user_id IS NOT NULL
             )
             ORDER BY u.id'
        );

        $toDelete = array_map('intval', $input->getOption('delete'));

        if ($toDelete === []) {
            $io->title('Accounts belonging to no employee');

            if ($rows === []) {
                $io->success('None found.');

                return Command::SUCCESS;
            }

            $io->table(['ID', 'Username', 'Email'], $rows);
            $io->warning(
                'This list also contains any forum account that was never an employee. '
                . 'Check each one before deleting.'
            );
            $io->note('Delete with: aureum:staff:orphaned-accounts --delete=3 --delete=4');

            return Command::SUCCESS;
        }

        $orphanIds = array_map(static fn (array $row): int => (int)$row['id'], $rows);
        $unknown = array_diff($toDelete, $orphanIds);

        if ($unknown !== []) {
            $io->error(
                'These ids are not in the orphaned list and will not be touched: '
                . implode(', ', $unknown)
            );

            return Command::FAILURE;
        }

        $io->title('Deleting accounts');
        $io->listing(array_map(static fn (int $id): string => "account #{$id}", $toDelete));

        if (!$io->confirm('This cannot be undone. Continue?', false)) {
            $io->note('Cancelled, nothing deleted.');

            return Command::SUCCESS;
        }

        foreach ($toDelete as $id) {
            $user = $this->userRepository->find($id);
            if (!$user instanceof User) {
                continue;
            }

            $this->entityManager->initializeObject($user);
            $this->userRepository->remove($user, false);
        }

        $this->entityManager->flush();

        $io->success(count($toDelete) . ' account(s) deleted.');

        return Command::SUCCESS;
    }
}
