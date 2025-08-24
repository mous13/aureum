<?php

namespace Citadel\Aureum\Admin\Component;

use Citadel\Aureum\Core\Entity\Employee;
use Citadel\Aureum\Core\Repository\EmployeeRepository;
use Forumify\Core\Component\Table\AbstractDoctrineTable;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Doctrine\ORM\QueryBuilder;


#[AsLiveComponent('EmployeeTable', '@Forumify/components/table/table.html.twig')]
#[IsGranted('aureum.admin.employees.manage')]
class EmployeeTable extends AbstractDoctrineTable
{
    private ?int $hotelId = null;

    public function __construct(
        private readonly EmployeeRepository $employeeRepository,
        private readonly RequestStack $requestStack,
    ){
    }

    protected function getEntityClass(): string
    {
        return Employee::class;
    }

    protected function buildTable(): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request && $request->attributes->has('hotelId')) {
            $this->hotelId = (int) $request->attributes->get('hotelId');
        }

        $this
            ->addColumn('name', [
                'field' => 'name',
                'sortable' => true,
            ])
            ->addColumn('role', [
                'field' => 'role',
                'sortable' => true,
            ])
            ->addColumn('hotel', [
                'field' => 'hotel.name',
                'sortable' => true,
            ])
            ->addColumn('actions', [
                'field' => 'id',
                'label' => '',
                'renderer' => $this->renderActions(...),
                'searchable' => false,
                'sortable' => false,
            ]);
    }

    protected function createQuery(): QueryBuilder
    {
        return $this->employeeRepository->createQueryBuilderForHotel($this->hotelId);
    }

    private function renderActions(int $id): string
    {
        $actions = '';
        if($this->security->isGranted('aureum.admin.hotels.manage')) {
            $actions .= $this->renderAction('aureum_admin_employees_edit', ['id' => $id], 'pencil-simple-line');
        }
        if($this->security->isGranted('aureum.admin.hotels.delete')) {
            $actions .= $this->renderAction('aureum_admin_employees_delete', ['id' => $id], 'x');
        }

        return $actions;
    }
}