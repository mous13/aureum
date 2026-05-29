<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Entity;

use Citadel\Aureum\Core\Entity\Trait\LogEntityTrait;
use Citadel\Aureum\Core\Repository\TransferLogRepository;
use Doctrine\ORM\Mapping as ORM;
use Forumify\Core\Entity\IdentifiableEntityTrait;

#[ORM\Entity(repositoryClass: TransferLogRepository::class)]
#[ORM\Table(name: 'aureum_logs_transfers')]
#[ORM\Index(columns: ['hotel_id', 'created_at'])]
#[ORM\Index(columns: ['transfer_id', 'created_at'])]
class TransferLog
{
    use IdentifiableEntityTrait;
    use LogEntityTrait;

    #[ORM\ManyToOne(targetEntity: Transfer::class)]
    private Transfer $transfer;

    public function getTransfer(): Transfer
    {
        return $this->transfer;
    }

    public function setTransfer(Transfer $transfer): void
    {
        $this->transfer = $transfer;
    }

    public function getEntityType(): string
    {
        return 'transfer';
    }

    public function getEntityId(): int
    {
        return $this->transfer->getId();
    }
}
