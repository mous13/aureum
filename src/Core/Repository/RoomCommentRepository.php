<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Repository;

use Citadel\Aureum\Core\Entity\Room;
use Citadel\Aureum\Core\Entity\RoomComment;

class RoomCommentRepository extends AbstractCommentRepository
{
    public static function getEntityClass(): string
    {
        return RoomComment::class;
    }

    protected function getSubjectReference(): string
    {
        return 'room';
    }

    public function findByRoom(Room $room): array
    {
        return $this->findBySubject($room);
    }

    public function countByHotel(int $hotelId): array
    {
        $rows = $this->createQueryBuilder('c')
            ->select('IDENTITY(c.room) AS roomId, COUNT(c.id) AS total')
            ->join('c.room', 'room')
            ->join('room.floor', 'floor')
            ->where('floor.hotel = :hotel')
            ->setParameter('hotel', $hotelId)
            ->groupBy('c.room')
            ->getQuery()
            ->getScalarResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int)$row['roomId']] = (int)$row['total'];
        }

        return $counts;
    }
}
