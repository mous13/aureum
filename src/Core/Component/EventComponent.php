<?php

declare(strict_types=1);

namespace Citadel\Aureum\Core\Component;

use Citadel\Aureum\Core\Entity\Enum\EventType;
use Citadel\Aureum\Core\Entity\Event;
use Citadel\Aureum\Core\Form\EventFormType;
use Citadel\Aureum\Core\Repository\EventRepository;
use Citadel\Aureum\Core\Service\AureumService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('Aureum\\EventCalendar', '@CitadelAureum/core/components/event_calendar.html.twig')]
class EventComponent extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    #[LiveProp]
    public string $week;

    #[LiveProp]
    public bool $editorMode = false;

    #[LiveProp]
    public ?string $createDay = null;

    public function __construct(
        private readonly AureumService $aureumService,
        private readonly EventRepository $eventRepository,
    ) {
    }

    public function mount(?string $week = null): void
    {
        $this->week = $this->resolveWeekStart($week ?? '')->format('Y-m-d');
    }

    #[LiveAction]
    public function toggleEditor(): void
    {
        $this->editorMode = !$this->editorMode;
        $this->createDay = null;
    }

    #[LiveAction]
    public function changeWeek(#[LiveArg] int $direction): void
    {
        $this->week = $this->getWeekStart()
            ->modify(($direction * 7) . ' days')
            ->format('Y-m-d');
        $this->createDay = null;
    }

    #[LiveAction]
    public function openCreate(#[LiveArg] string $day): void
    {
        $this->createDay = $day;
        $this->resetForm();
    }

    #[LiveAction]
    public function closeCreate():void
    {
        $this->createDay = null;
        $this->resetForm();
    }

    #[LiveAction]
    public function save(): void
    {
        $this->submitForm();

        $event = $this->getForm()->getData();
        $event->setHotel($this->aureumService->getHotel());
        $event->setCreatedBy($this->aureumService->getEmployee());

        $this->eventRepository->save($event);

        $this->closeCreate();
    }

    protected function instantiateForm(): FormInterface
    {
        $event = new Event();

        $day = $this->createDay !== null
            ? \DateTimeImmutable::createFromFormat('!Y-m-d', $this->createDay)
            : false;
        $event->setEventDate(($day ?: $this->getWeekStart())->setTime(18, 0));

        return $this->createForm(EventFormType::class, $event, [
            'userTimezone' => $this->aureumService->getEmployee()->getUser()->getTimezone(),
        ]);
    }

    public function getWeekStart(): \DateTimeImmutable
    {
        return $this->resolveWeekStart($this->week);
    }

    public function getWeekEnd(): \DateTimeImmutable
    {
        return $this->getWeekStart()->modify('+6 days');
    }

    public function getDays(): array
    {
        $weekStart = $this->getWeekStart();
        $events = $this->eventRepository->findForWeek(
            $this->aureumService->getHotel(),
            $weekStart,
            $this->getWeekEnd(),
        );

        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $weekStart->modify("+{$i} days");
            $days[$date->format('Y-m-d')] = ['date' => $date, 'events' => []];
        }

        foreach ($events as $event) {
            $days[$event->getEventDate()->format('Y-m-d')]['events'][] = $event;
        }

        return array_values($days);
    }

    public function getEventTypes(): array
    {
        return EventType::cases();
    }

    private function resolveWeekStart(string $week): \DateTimeImmutable
    {
        if ($week !== '') {
            $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $week);
            if ($parsed !== false) {
                return $parsed->modify('monday this week');
            }
        }

        return new \DateTimeImmutable('monday this week');
    }
}
