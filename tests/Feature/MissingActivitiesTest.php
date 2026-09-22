<?php

namespace Tests\Feature;

use App\Livewire\Appraisals\MissingActivitiesView;
use App\Models\Appraisal;
use App\Models\Gap;
use App\Models\Practice;
use App\Models\PracticeArea;
use App\Models\PracticeEvaluation;
use App\Models\Project;
use App\Models\User;
use App\Services\MissingActivitiesService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MissingActivitiesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Project $project;

    private Appraisal $appraisal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->user = User::factory()->gestorProcesos()->create();

        $this->project = Project::create([
            'name' => 'Proyecto Test HU-25',
            'code' => 'PRJ-TEST-25',
            'status' => 'activo',
            'start_date' => now()->toDateString(),
        ]);

        $this->appraisal = Appraisal::create([
            'project_id' => $this->project->id,
            'name' => 'Appraisal Test HU-25',
            'domain' => 'Development',
            'target_level' => 3,
            'target_date' => now()->addMonths(2)->toDateString(),
            'status' => 'borrador',
        ]);
    }

    public function test_guests_cannot_access_missing_activities_view()
    {
        $response = $this->get(route('appraisals.missing-activities', $this->appraisal));
        $response->assertRedirect('/login');
    }

    public function test_a_user_outside_the_project_cannot_access_missing_activities_view()
    {
        $this->actingAs(User::factory()->colaborador()->create())
            ->get(route('appraisals.missing-activities', $this->appraisal))
            ->assertForbidden();

        $this->actingAs(User::factory()->jefeProyecto()->create())
            ->get(route('appraisals.missing-activities', $this->appraisal))
            ->assertForbidden();
    }

    public function test_authenticated_user_can_view_missing_activities_screen()
    {
        $response = $this->actingAs($this->user)
            ->get(route('appraisals.missing-activities', $this->appraisal));

        $response->assertStatus(200);
        $response->assertSee('¿Qué me falta? - Pendientes Priorizados');
    }

    public function test_prioritizes_overdue_gaps_at_the_top()
    {
        $area = PracticeArea::create([
            'code' => 'TS',
            'name' => 'Technical Solution',
            'category' => 'Engineering',
        ]);

        $practice = Practice::create([
            'practice_area_id' => $area->id,
            'code' => 'TS 1.1',
            'name' => 'Develop Design',
            'level' => 1,
        ]);

        $eval = PracticeEvaluation::create([
            'appraisal_id' => $this->appraisal->id,
            'practice_id' => $practice->id,
            'status' => 'no_cumple',
        ]);

        Gap::create([
            'practice_evaluation_id' => $eval->id,
            'code' => 'GAP-001',
            'title' => 'Gap Normal',
            'description' => 'Falta documentar',
            'severity' => 'media',
            'status' => 'abierto',
            'due_date' => now()->addDays(5)->toDateString(),
        ]);

        Gap::create([
            'practice_evaluation_id' => $eval->id,
            'code' => 'GAP-002',
            'title' => 'Gap Critico Vencido',
            'description' => 'Falla grave de seguridad',
            'severity' => 'critica',
            'status' => 'abierto',
            'due_date' => now()->subDays(2)->toDateString(),
        ]);

        $service = new MissingActivitiesService;
        $activities = $service->getMissingActivities($this->appraisal);

        $this->assertNotEmpty($activities);
        $this->assertEquals('Gap: Gap Critico Vencido', $activities->first()['title']);
        $this->assertTrue($activities->first()['is_overdue']);
    }

    public function test_livewire_component_renders_and_filters()
    {
        Livewire::actingAs($this->user)
            ->test(MissingActivitiesView::class, ['appraisal' => $this->appraisal])
            ->assertStatus(200)
            ->set('filterType', 'gap')
            ->assertStatus(200);
    }
}
