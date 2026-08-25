<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\EventSubscriber;

use DateTime;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;
use Forumify\Core\Entity\User;

#[AsDoctrineListener(event: Events::preRemove)]
#[AsDoctrineListener(event: Events::postFlush)]
class UserRemovalSubscriber
{
    /** @var array<int> */
    private array $pending = [];

    public function preRemove(PreRemoveEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof User) {
            return;
        }

        $employeeId = $args->getObjectManager()->getConnection()->fetchOne(
            'SELECT id FROM aureum_employees WHERE user_id = :user AND archived_at IS NULL',
            ['user' => $entity->getId()],
        );

        if ($employeeId !== false) {
            $this->pending[] = (int)$employeeId;
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if ($this->pending === []) {
            return;
        }

        $connection = $args->getObjectManager()->getConnection();
        foreach ($this->pending as $employeeId) {
            $connection->executeStatement(
                'UPDATE aureum_employees SET archived_at = :now, user_id = NULL WHERE id = :id',
                ['now' => (new DateTime())->format('Y-m-d H:i:s'), 'id' => $employeeId],
            );
            $connection->executeStatement(
                'DELETE FROM aureum_hotel_role_employees WHERE employee_id = :id',
                ['id' => $employeeId],
            );
        }

        $this->pending = [];
    }
}
