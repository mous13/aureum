<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Command;

use Citadel\Aureum\Core\Entity\Enum\StorageLocationType;
use Citadel\Aureum\Core\Entity\Inventory;
use Citadel\Aureum\Core\Entity\InventoryCategory;
use Citadel\Aureum\Core\Entity\InventoryItem;
use Citadel\Aureum\Core\Entity\StorageLocation;
use Citadel\Aureum\Core\Repository\HotelRepository;
use Citadel\Aureum\Core\Repository\InventoryCategoryRepository;
use Citadel\Aureum\Core\Repository\InventoryItemRepository;
use Citadel\Aureum\Core\Repository\InventoryRepository;
use Citadel\Aureum\Core\Repository\StorageLocationRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'aureum:inventory:seed',
    description: 'Create the General Stock inventory and its items for a hotel',
)]
class SeedInventoryCommand extends Command
{
    public const INVENTORY_NAME = 'General Stock';
    public const DEFAULT_LOCATION = 'Store Room 1';

    public const CATALOGUE = [
        'Essentials' => [
            ['name' => 'Key Cards', 'unit' => 'card', 'packSize' => 500, 'packLabel' => 'box'],
            ['name' => 'Key Wallets', 'unit' => 'wallet', 'packSize' => 500, 'packLabel' => 'box'],
        ],
        'Amenities' => [
            ['name' => 'Sweetest Jelly Bean', 'unit' => 'bag', 'packSize' => null, 'packLabel' => null],
            ['name' => 'Birthday Jelly Bean', 'unit' => 'bag', 'packSize' => null, 'packLabel' => null],
            ['name' => 'M&Ms', 'unit' => 'bag', 'packSize' => 24, 'packLabel' => 'case'],
        ],
        'Stationary' => [
            ['name' => 'Bill Folios', 'unit' => 'folio', 'packSize' => null, 'packLabel' => null],
            ['name' => 'Large Envelopes', 'unit' => 'envelope', 'packSize' => 250, 'packLabel' => 'box'],
            ['name' => 'Medium Envelopes', 'unit' => 'envelope', 'packSize' => 250, 'packLabel' => 'box'],
            ['name' => 'Small Envelopes', 'unit' => 'envelope', 'packSize' => 250, 'packLabel' => 'box'],
            ['name' => 'Tape', 'unit' => 'roll', 'packSize' => null, 'packLabel' => null],
            ['name' => 'Welcome Card Small', 'unit' => 'card', 'packSize' => null, 'packLabel' => null],
            ['name' => 'Welcome Card Large', 'unit' => 'card', 'packSize' => null, 'packLabel' => null],
        ],
        'Concierge' => [
            ['name' => 'Tourist Maps', 'unit' => 'map', 'packSize' => null, 'packLabel' => null],
            ['name' => 'History Books', 'unit' => 'book', 'packSize' => null, 'packLabel' => null],
            ['name' => 'Luggage Tags', 'unit' => 'tag', 'packSize' => null, 'packLabel' => null],
            ['name' => 'Tea Lights', 'unit' => 'light', 'packSize' => 100, 'packLabel' => 'box'],
            ['name' => 'Water', 'unit' => 'bottle', 'packSize' => 24, 'packLabel' => 'case'],
        ],
    ];

    public function __construct(
        private readonly HotelRepository $hotelRepository,
        private readonly InventoryRepository $inventoryRepository,
        private readonly InventoryCategoryRepository $categoryRepository,
        private readonly InventoryItemRepository $itemRepository,
        private readonly StorageLocationRepository $locationRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('hotelCode', InputArgument::REQUIRED, 'The code of the hotel to seed');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $code = (string)$input->getArgument('hotelCode');

        $hotel = $this->hotelRepository->findOneBy(['code' => $code]);
        if ($hotel === null) {
            $io->error("No hotel found with code {$code}.");

            return Command::FAILURE;
        }

        foreach ($this->inventoryRepository->findActiveByHotel($hotel) as $existing) {
            if ($existing->getName() === self::INVENTORY_NAME) {
                $io->warning(self::INVENTORY_NAME . " already exists for {$code}. Nothing was changed.");

                return Command::SUCCESS;
            }
        }

        $location = null;
        foreach ($this->locationRepository->findActiveByHotel($hotel) as $existing) {
            if ($existing->getName() === self::DEFAULT_LOCATION) {
                $location = $existing;
            }
        }

        if ($location === null) {
            $location = new StorageLocation();
            $location->setHotel($hotel);
            $location->setName(self::DEFAULT_LOCATION);
            $location->setType(StorageLocationType::BULK);
            $this->locationRepository->save($location);
        }

        $inventory = new Inventory();
        $inventory->setHotel($hotel);
        $inventory->setName(self::INVENTORY_NAME);
        $this->inventoryRepository->save($inventory);

        $itemCount = 0;
        $position = 0;

        foreach (self::CATALOGUE as $categoryName => $items) {
            $category = new InventoryCategory();
            $category->setInventory($inventory);
            $category->setName($categoryName);
            $category->setPosition($position++);
            $this->categoryRepository->save($category);

            foreach ($items as $definition) {
                $item = new InventoryItem();
                $item->setCategory($category);
                $item->setLocation($location);
                $item->setName($definition['name']);
                $item->setUnit($definition['unit']);
                $item->setPackSize($definition['packSize']);
                $item->setPackLabel($definition['packLabel']);
                $this->itemRepository->save($item);
                $itemCount++;
            }
        }

        $io->success(sprintf(
            'Created %s for %s with %d categories and %d items.',
            self::INVENTORY_NAME,
            $code,
            count(self::CATALOGUE),
            $itemCount,
        ));
        $io->note('Lead times are unset. Every item will show as Needs Setup until they are filled in.');

        return Command::SUCCESS;
    }
}
