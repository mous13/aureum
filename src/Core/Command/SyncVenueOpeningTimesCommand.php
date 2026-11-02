<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Command;

use Citadel\Aureum\Admin\Service\GooglePlacesKeyManager;
use Citadel\Aureum\Core\Entity\Hotel;
use Citadel\Aureum\Core\Entity\VenueInterface;
use Citadel\Aureum\Core\Repository\HotelRepository;
use Citadel\Aureum\Core\Service\VenueGoogleService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'aureum:venues:sync-opening-times',
    description: 'Refresh opening times from Google Places for linked restaurants and bars of enabled hotels',
    aliases: ['aureum:restaurants:sync-opening-times'],
)]
class SyncVenueOpeningTimesCommand extends Command
{
    public function __construct(
        private readonly GooglePlacesKeyManager $keyManager,
        private readonly HotelRepository $hotelRepository,
        private readonly VenueGoogleService $googleService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            'List the venues that would be synced without calling Google',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool)$input->getOption('dry-run');

        if (!$this->keyManager->hasKey()) {
            $io->error('No Google Places API key is configured.');
            return Command::FAILURE;
        }

        $synced = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($this->hotelRepository->findBy(['googlePlacesEnabled' => true]) as $hotel) {
            foreach ($this->linkedVenues($hotel) as $venue) {
                $label = "{$hotel->getName()} / {$venue->getName()}";

                if ($dryRun) {
                    $io->text("Would sync: {$label}");
                    $synced++;
                    continue;
                }

                try {
                    if ($this->googleService->sync($venue)) {
                        $synced++;
                    } else {
                        $skipped++;
                        $io->text("No hours returned for {$label}");
                    }
                } catch (\Throwable $e) {
                    $failed++;
                    $io->warning("Failed for {$label}: {$e->getMessage()}");
                }
            }
        }

        $io->success("{$synced} synced, {$skipped} skipped, {$failed} failed.");

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    private function linkedVenues(Hotel $hotel): iterable
    {
        foreach ([...$hotel->getRestaurants(), ...$hotel->getBars()] as $venue) {
            if ($venue instanceof VenueInterface && $venue->getGooglePlaceId() !== null) {
                yield $venue;
            }
        }
    }
}
