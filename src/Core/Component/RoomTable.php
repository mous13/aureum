<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Component;

use Citadel\Aureum\Core\Entity\Room;
use Citadel\Aureum\Core\Service\RoomCommentService;
use Doctrine\ORM\QueryBuilder;
use Forumify\Core\Component\Table\AbstractDoctrineTable;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

#[AsLiveComponent('Aureum\RoomTable', '@CitadelAureum/core/components/table.html.twig')]
#[IsGranted('aureum.module.rooms.view')]
class RoomTable extends AbstractDoctrineTable
{
    /**
     * @var array<string, "ASC"|"DESC"|null>
     */
    #[LiveProp]
    public array $sort = ['number' => self::SORT_ASC];

    #[LiveProp]
    public int $hotelId;

    private ?array $commentCounts = null;

    public function __construct(
        private readonly RoomCommentService $commentService,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    protected function getEntityClass(): string
    {
        return Room::class;
    }

    protected function getQuery(array $search): QueryBuilder
    {
        return parent::getQuery($search)
            ->join('e.floor', 'room_floor')
            ->andWhere('room_floor.hotel = :hotel')
            ->setParameter('hotel', $this->hotelId);
    }

    protected function buildTable(): void
    {
        $this
            ->addColumn('number', [
                'label' => 'Room',
                'field' => 'number',
                'sortable' => true,
                'searchable' => true,
                'renderer' => $this->renderText(...),
            ])
            ->addColumn('roomType', [
                'label' => 'Room Type',
                'field' => 'roomType.name',
                'sortable' => true,
                'searchable' => true,
                'renderer' => $this->renderText(...),
            ])
            ->addColumn('bathroomStyle', [
                'label' => 'Bathroom',
                'field' => 'bathroomStyle',
                'sortable' => true,
                'searchable' => true,
                'renderer' => $this->renderEnum(...),
            ])
            ->addColumn('view', [
                'label' => 'View',
                'field' => 'view',
                'sortable' => true,
                'searchable' => true,
                'renderer' => $this->renderEnum(...),
            ])
            ->addColumn('bedSize', [
                'label' => 'Bed Size',
                'field' => 'bedSize',
                'sortable' => true,
                'searchable' => true,
                'renderer' => $this->renderEnum(...),
            ])
            ->addColumn('hasStairs', [
                'label' => 'Stairs',
                'field' => 'hasStairs',
                'sortable' => true,
                'searchable' => false,
                'renderer' => $this->renderBoolean(...),
            ])
            ->addColumn('interconnecting', [
                'label' => 'Interconnecting',
                'field' => 'interconnecting',
                'sortable' => true,
                'searchable' => false,
                'renderer' => $this->renderBoolean(...),
            ])
            ->addColumn('lastLet', [
                'label' => 'Last Let',
                'field' => 'lastLet',
                'sortable' => true,
                'searchable' => false,
                'renderer' => $this->renderBoolean(...),
            ])
            ->addColumn('comments', [
                'label' => 'Comments',
                'class' => 'text-small',
                'field' => 'id',
                'sortable' => false,
                'searchable' => false,
                'renderer' => $this->renderComments(...),
            ])
        ;
    }

    private function renderText(mixed $value): string
    {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES);
    }

    private function renderEnum(mixed $value): string
    {
        return $this->renderText($value instanceof \BackedEnum ? $value->value : $value);
    }

    private function renderComments(mixed $value, Room $room): string
    {
        $count = $this->getCommentCounts()[$room->getId()] ?? 0;
        $url = $this->urlGenerator->generate('aureum_room_comments_list', [
            'type' => 'room',
            'id' => $room->getId(),
        ]);

        return sprintf(
            '<button type="button" class="btn btn-link btn-small rooms-list-comments"'
            . ' data-action="click->room-modal#open" data-kind="room" data-comments-only="1"'
            . ' data-title="Room %1$s" data-comments-url="%2$s" data-comment-count="%3$d"'
            . ' aria-label="Comments on room %1$s">'
            . '<i class="ph ph-chat-circle-dots"></i><span data-comment-count-text>%4$s</span>'
            . '</button>',
            htmlspecialchars($room->getNumber(), ENT_QUOTES),
            htmlspecialchars($url, ENT_QUOTES),
            $count,
            $count > 0 ? $count : '',
        );
    }

    private function getCommentCounts(): array
    {
        return $this->commentCounts ??= $this->commentService->countsForHotel($this->hotelId);
    }

    private function renderBoolean(mixed $value): string
    {
        return $value
            ? '<i class="ph ph-check" title="Yes" aria-label="Yes"></i>'
            : '<span class="text-small" aria-label="No">&mdash;</span>';
    }
}
