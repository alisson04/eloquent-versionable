<?php

namespace Kiqstyle\EloquentVersionable\Test;

use Kiqstyle\EloquentVersionable\SyncManyToManyWithVersioning;
use Kiqstyle\EloquentVersionable\Test\Models\Competency;
use Kiqstyle\EloquentVersionable\Test\Models\Employee;
use Kiqstyle\EloquentVersionable\Test\Models\Position;
use Kiqstyle\EloquentVersionable\Test\Models\PositionCompetency;

class VersionableQueryTest extends TestCase
{

    /** @test */
    public function it_finds_last_versioned_register()
    {
        $employee = Employee::first();
        $this->update($employee, ['name' => 'updated']);
        $this->update($employee, ['name' => 'updated 2']);

        $employeeModel = new Employee;
        $employeeModel->unsetVersioning();
        $employee = $employeeModel->first();
        $versionedDummy = Employee::find($employee->id);

        $this->assertOriginalEqualsVersioning($employee, $versionedDummy);
    }

    /** @test */
    public function it_works_with_soft_delete()
    {
        $employee = Employee::first();
        $this->update($employee, ['name' => 'updated']);
        $employee->delete();

        $employeeModel = new Employee;
        $employeeModel->unsetVersioning();
        $employee = $employeeModel->withTrashed()->first();
        $versioned = $this->getVersioned($employee);

        $this->assertOriginalEqualsVersioning($employee, $versioned->get(2));
    }

    /** @test */
    public function it_finds_old_registers_based_on_versioning_date()
    {
        $employee = Employee::first();
        $this->update($employee, ['name' => 'updated']);
        $this->update($employee, ['name' => 'updated 2']);

        $now = $this->setFakeNow('2019-01-01 12:00:01');
        versioningDate()->setDate($now);

        $employee = Employee::find($employee->id);

        $this->assertEquals($employee->id, 1);
        $this->assertEquals($employee->name, 'updated');
        $this->assertNotNull($employee->next);
    }

    /** @test */
    public function it_works_with_update_or_create()
    {
        $this->setFakeNow('2019-01-01 12:00:01');
        $employee = Employee::updateOrCreate(['id' => '999'], ['name' => 'new employee']);
        $versioned = $this->getVersioned($employee);

        $this->assertOriginalEqualsVersioning($employee, $versioned->get(0));

        $employee = Employee::updateOrCreate(['id' => '999'], ['name' => 'updated employee']);
        $versioned = $this->getVersioned($employee);

        $this->assertOriginalEqualsVersioning($employee, $versioned->get(1));
    }

    /** @test */
    public function it_finds_versioned_results_with_has_one()
    {
        $employee = Employee::with('position')->first();
        $this->update($employee->position, ['name' => 'updated']);
        $this->update($employee->position, ['name' => 'updated 2']);

        $versioned = $this->getVersioned($employee->position);

        $this->assertOriginalEqualsVersioning($employee->position, $versioned->get(2));
    }

    /** @test */
    public function it_works_with_soft_delete_in_has_one()
    {
        $employee = Employee::with('position')->first();
        $this->update($employee->position, ['name' => 'updated']);
        $employee->position->delete();

        $positionModel = new Position;
        $positionModel->unsetVersioning();
        $position = $positionModel->withTrashed()->first();
        $versioned = $this->getVersioned($position);

        $this->assertOriginalEqualsVersioning($position, $versioned->get(2));
    }

    /** @test */
    public function it_finds_versioned_results_with_has_one_based_on_versioning_date()
    {
        $employee = Employee::with('position')->first();
        $this->update($employee->position, ['name' => 'updated']);
        $this->update($employee->position, ['name' => 'updated 2']);

        $now = $this->setFakeNow('2019-01-01 12:00:01');
        versioningDate()->setDate($now);

        $employee = Employee::with('position')->first();

        $this->assertEquals($employee->position->id, 1);
        $this->assertEquals($employee->position->name, 'updated');
        $this->assertNotNull($employee->position->next);
    }

    /** @test */
    public function it_finds_versioned_results_with_many_to_many_relationship_based_on_versioning_date()
    {
        $position = Position::first();
        $competencies = collect(range(1, 3))->map(function (int $i) {
            return Competency::create(['name' => $i]);
        });

        (new SyncManyToManyWithVersioning)->run($position, $competencies->pluck('id')->toArray(), new PositionCompetency, ['entityKey' => 'position_id', 'relationKey' => 'competency_id']);

        $now = $this->setFakeNow('2019-01-01 12:00:01');
        versioningDate()->setDate($now);
        $competencies = collect(range(1, 3))->map(function (int $i) {
            return Competency::create(['name' => $i]);
        });

        (new SyncManyToManyWithVersioning)->run($position, $competencies->pluck('id')->toArray(), new PositionCompetency, ['entityKey' => 'position_id', 'relationKey' => 'competency_id']);

        $now = $this->setFakeNow('2019-01-01 12:00:00');
        versioningDate()->setDate($now);

        $position = Position::first();
        $this->assertCount(3, $position->competencies);
        $this->assertEquals(1, $position->competencies->get(0)->id);
        $this->assertEquals(2, $position->competencies->get(1)->id);
        $this->assertEquals(3, $position->competencies->get(2)->id);
    }
}
