<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Component;

use Citadel\Aureum\Core\Entity\Inventory;
use Citadel\Aureum\Core\Entity\InventoryItem;
use Citadel\Aureum\Core\Entity\StockCount;
use Citadel\Aureum\Core\Entity\StockCountLine;
use Citadel\Aureum\Core\Entity\StorageLocation;
use Citadel\Aureum\Core\Repository\InventoryItemRepository;
use Citadel\Aureum\Core\Repository\InventoryRepository;
use Citadel\Aureum\Core\Repository\StockCountLineRepository;
use Citadel\Aureum\Core\Repository\StockCountRepository;
use Citadel\Aureum\Core\Repository\StorageLocationRepository;
use Citadel\Aureum\Core\Service\AureumService;
use Citadel\Aureum\Core\Service\Forecast\InventoryForecastService;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('Aureum\StockTake', '@CitadelAureum/core/components/stock_take.html.twig')]
#[IsGranted('aureum.module.inventory.count')]
class StockTake
{
    use DefaultActionTrait;

    #[LiveProp]
    public int $inventoryId;

    #[LiveProp(writable: true, url: true)]
    public ?int $locationId = null;

    /**
     * @var array<int, array{packs?: string, loose?: string}>
     */
    #[LiveProp(writable: true)]
    public array $entries = [];

    #[LiveProp(writable: true)]
    public string $notes = '';

    #[LiveProp]
    public bool $saved = false;

    #[LiveProp]
    public bool $nothingToSave = false;

    /**
     * @var array<int, array{item: InventoryItem, onHand: int, entered: int, rising: bool}>|null
     */
    private ?array $rows = null;

    public function __construct(
        private readonly AureumService $aureumService,
        private readonly InventoryRepository $inventoryRepository,
        private readonly InventoryItemRepository $itemRepository,
        private readonly StorageLocationRepository $locationRepository,
        private readonly StockCountRepository $stockCountRepository,
        private readonly StockCountLineRepository $countLineRepository,
        private readonly InventoryForecastService $forecastService,
    ) {
    }

    public static function baseUnits(int $packs, int $loose, ?int $packSize): int
    {
        $loose = max(0, $loose);
        if ($packSize === null || $packSize < 1) {
            return $loose;
        }

        return max(0, $packs) * $packSize + $loose;
    }

    public function getInventory(): ?Inventory
    {
        return $this->inventoryRepository->find($this->inventoryId);
    }

    /**
     * @return array<StorageLocation>
     */
    public function getLocations(): array
    {
        $hotel = $this->aureumService->getHotel();

        return $hotel === null ? [] : $this->locationRepository->findActiveByHotel($hotel);
    }

    /**
     * @return array<int, array{item: InventoryItem, onHand: int, entered: int, rising: bool}>
     */
    public function getRows(): array
    {
        if ($this->rows !== null) {
            return $this->rows;
        }

        $inventory = $this->getInventory();
        $hotel = $this->aureumService->getHotel();
        if ($inventory === null || $hotel === null || $inventory->getHotel()->getId() !== $hotel->getId()) {
            return $this->rows = [];
        }

        $items = [];
        foreach ($this->itemRepository->findActiveByInventory($inventory) as $item) {
            if ($this->locationId !== null && $item->getLocation()->getId() !== $this->locationId) {
                continue;
            }

            $items[] = $item;
        }

        $onHandByItem = $this->forecastService->stockOnHandForItems($hotel, $items);

        $rows = [];
        foreach ($items as $item) {
            $onHand = $onHandByItem[$item->getId()] ?? 0;
            $entered = $this->enteredFor($item);

            $rows[] = [
                'item' => $item,
                'onHand' => $onHand,
                'entered' => $entered,
                'rising' => $entered > $onHand && $this->hasEntry($item),
            ];
        }

        return $this->rows = $rows;
    }

    public function getEnteredCount(): int
    {
        $count = 0;
        foreach ($this->getRows() as $row) {
            if ($this->hasEntry($row['item'])) {
                $count++;
            }
        }

        return $count;
    }

    #[LiveAction]
    public function save(): void
    {
        $this->saved = false;
        $this->nothingToSave = false;

        $employee = $this->aureumService->getEmployee();
        $inventory = $this->getInventory();
        if ($employee === null || $inventory === null || $inventory->getHotel()->getId() !== $employee->getHotel()->getId()) {
            return;
        }

        $lines = [];
        foreach ($this->getRows() as $row) {
            if (!$this->hasEntry($row['item'])) {
                continue;
            }

            $lines[] = [$row['item'], $row['entered']];
        }

        if ($lines === []) {
            $this->nothingToSave = true;

            return;
        }

        $count = new StockCount();
        $count->setInventory($inventory);
        $count->setHotel($employee->getHotel());
        $count->setCountedBy($employee);
        $count->setNotes($this->notes !== '' ? $this->notes : null);
        $this->stockCountRepository->save($count);

        foreach ($lines as [$item, $quantity]) {
            $line = new StockCountLine();
            $line->setStockCount($count);
            $line->setItem($item);
            $line->setLocation($item->getLocation());
            $line->setQuantity($quantity);
            $this->countLineRepository->save($line);
        }

        $this->entries = [];
        $this->rows = null;
        $this->notes = '';
        $this->saved = true;
    }

    private function hasEntry(InventoryItem $item): bool
    {
        $entry = $this->entries[$item->getId()] ?? null;
        if ($entry === null) {
            return false;
        }

        return ($entry['packs'] ?? '') !== '' || ($entry['loose'] ?? '') !== '';
    }

    private function enteredFor(InventoryItem $item): int
    {
        $entry = $this->entries[$item->getId()] ?? [];

        return self::baseUnits(
            (int)($entry['packs'] ?? 0),
            (int)($entry['loose'] ?? 0),
            $item->getPackSize(),
        );
    }
}
