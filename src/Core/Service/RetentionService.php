<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Service;

use Citadel\Aureum\Core\Entity\AnonymisableInterface;
use Citadel\Aureum\Core\Entity\Enum\Module;
use Citadel\Aureum\Core\Entity\Fine;
use Citadel\Aureum\Core\Entity\LostProperty;
use Citadel\Aureum\Core\Entity\Package;
use Citadel\Aureum\Core\Entity\RetentionPolicy;
use Citadel\Aureum\Core\Entity\Booking;
use Citadel\Aureum\Core\Repository\AccessLogRepository;
use Citadel\Aureum\Core\Repository\RetentionPolicyRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

class RetentionService
{
    private const BATCH_SIZE = 100;

    /**
     * Entity class and the property holding the date retention is measured from,
     * per module. Bookings are measured from the booking date; the rest from
     * the date the record was created.
     */
    private const SUBJECTS = [
        Module::FINES->value => [Fine::class, 'createdAt'],
        Module::PACKAGES->value => [Package::class, 'createdAt'],
        Module::LOST_PROPERTY->value => [LostProperty::class, 'createdAt'],
        Module::BOOKINGS->value => [Booking::class, 'date'],
    ];

    /**
     * Log tables whose changes payload holds copies of the values being removed,
     * keyed by module.
     */
    private const LOG_TABLES = [
        Module::FINES->value => ['aureum_logs_fines', 'fine_id'],
        Module::PACKAGES->value => ['aureum_logs_packages', 'package_id'],
        Module::LOST_PROPERTY->value => ['aureum_logs_lost_property', 'lost_property_id'],
        Module::BOOKINGS->value => ['aureum_logs_bookings', 'booking_id'],
    ];

    /**
     * The access log is itself a record of what staff did, so it cannot be kept
     * forever either. Twelve months is long enough to investigate an incident
     * and answer a hotel asking who saw a guest's details.
     */
    public const ACCESS_LOG_MONTHS = 12;

    public function __construct(
        private readonly RetentionPolicyRepository $policyRepository,
        private readonly AccessLogRepository $accessLogRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function pruneAccessLog(bool $dryRun = false): int
    {
        $cutoff = (new DateTime())->modify('-' . self::ACCESS_LOG_MONTHS . ' months');

        if ($dryRun) {
            return (int)$this->entityManager->getConnection()->fetchOne(
                'SELECT COUNT(*) FROM aureum_logs_access WHERE accessed_at < :cutoff',
                ['cutoff' => $cutoff->format('Y-m-d H:i:s')],
            );
        }

        return $this->accessLogRepository->deleteOlderThan($cutoff);
    }

    public static function supports(Module $module): bool
    {
        return isset(self::SUBJECTS[$module->value]);
    }

    /**
     * @return array<string, int> records anonymised, keyed by "hotel code / module"
     */
    public function run(bool $dryRun = false): array
    {
        // Everything needed from the policies is read up front. applyPolicy
        // clears the entity manager between batches, which would detach any
        // policy still waiting to be processed.
        $plans = [];
        foreach ($this->policyRepository->findEnforced() as $policy) {
            $hotel = $policy->getHotel();
            $cutoff = $policy->getCutoff();
            if ($hotel === null || $cutoff === null) {
                continue;
            }

            $plans[] = [$hotel->getId(), $hotel->getCode(), $policy->getModule(), $cutoff];
        }

        $results = [];
        foreach ($plans as [$hotelId, $hotelCode, $module, $cutoff]) {
            $count = $this->anonymiseOlderThan($hotelId, $module, $cutoff, $dryRun);
            if ($count <= 0) {
                continue;
            }

            $results["{$hotelCode} / {$module->value}"] = $count;
        }

        return $results;
    }

    public function applyPolicy(RetentionPolicy $policy, bool $dryRun = false): int
    {
        $cutoff = $policy->getCutoff();
        $hotel = $policy->getHotel();
        if ($cutoff === null || $hotel === null) {
            return 0;
        }

        return $this->anonymiseOlderThan($hotel->getId(), $policy->getModule(), $cutoff, $dryRun);
    }

    private function anonymiseOlderThan(int $hotelId, Module $module, DateTime $cutoff, bool $dryRun): int
    {
        $subject = self::SUBJECTS[$module->value] ?? null;
        if ($subject === null) {
            return 0;
        }

        [$entityClass, $anchorField] = $subject;

        $processed = 0;
        do {
            $batch = $this->entityManager->createQueryBuilder()
                ->select('e')
                ->from($entityClass, 'e')
                ->where('IDENTITY(e.hotel) = :hotelId')
                ->andWhere("e.{$anchorField} < :cutoff")
                ->andWhere('e.anonymisedAt IS NULL')
                ->setParameter('hotelId', $hotelId)
                ->setParameter('cutoff', $cutoff)
                ->setMaxResults(self::BATCH_SIZE)
                ->getQuery()
                ->getResult();

            if ($batch === []) {
                break;
            }

            foreach ($batch as $record) {
                if (!$record instanceof AnonymisableInterface) {
                    continue;
                }

                $processed++;

                if ($dryRun) {
                    continue;
                }

                $record->anonymise();
                $this->scrubLogs($module, $record->getId());
            }

            if ($dryRun) {
                break;
            }

            $this->entityManager->flush();
            $this->entityManager->clear();
        } while (count($batch) === self::BATCH_SIZE);

        return $processed;
    }

    /**
     * The log changes payload keeps before/after copies of the fields that have
     * just been removed from the record itself, so it has to be cleared as well
     * or the data survives the anonymisation.
     */
    private function scrubLogs(Module $module, int $recordId): void
    {
        $table = self::LOG_TABLES[$module->value] ?? null;
        if ($table === null) {
            return;
        }

        [$tableName, $foreignKey] = $table;

        $this->entityManager->getConnection()->executeStatement(
            "UPDATE {$tableName} SET changes = NULL, notes = NULL WHERE {$foreignKey} = :id",
            ['id' => $recordId],
        );
    }
}
