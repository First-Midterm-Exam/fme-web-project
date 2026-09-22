<?php

declare(strict_types=1);

namespace Tests\Feature\Appraisals;

use App\Models\Appraisal;
use App\Models\Evidence;
use App\Models\EvidenceStatus;
use App\Models\Practice;
use App\Models\PracticeEvaluation;
use App\Models\Project;
use App\Models\User;
use App\Services\AppraisalReadinessService;
use Database\Seeders\EvidenceStatusSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppraisalReadinessScoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function createProject(): Project
    {
        return Project::create([
            'name' => 'Proyecto CMMI Test',
            'code' => 'PRJ-'.uniqid(),
            'description' => 'Proyecto para pruebas de Readiness',
            'status' => 'activo',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
        ]);
    }

    public function test_readiness_score_calculates_correctly_with_breakdown_and_disclaimer(): void
    {
        $project = $this->createProject();
        $appraisal = Appraisal::create([
            'project_id' => $project->id,
            'name' => 'Appraisal Inicial Q4',
            'domain' => 'Development',
            'target_level' => 3,
            'target_date' => now()->addMonths(3)->toDateString(),
            'status' => 'borrador',
        ]);

        $service = new AppraisalReadinessService;
        $result = $service->calculate($appraisal);

        $this->assertGreaterThanOrEqual(0.0, $result['score']);
        $this->assertLessThanOrEqual(100.0, $result['score']);

        $this->assertArrayHasKey('practices', $result['breakdown']);
        $this->assertArrayHasKey('evidences', $result['breakdown']);
        $this->assertArrayHasKey('gaps', $result['breakdown']);

        $this->assertStringContainsString('no constituye', mb_strtolower($result['disclaimer']));
    }

    public function test_readiness_score_view_renders_successfully(): void
    {
        $user = User::factory()->gestorProcesos()->create();
        $project = $this->createProject();
        $appraisal = Appraisal::create([
            'project_id' => $project->id,
            'name' => 'Appraisal Inicial Q4',
            'domain' => 'Development',
            'target_level' => 3,
            'target_date' => now()->addMonths(3)->toDateString(),
            'status' => 'borrador',
        ]);

        $response = $this->actingAs($user)->get(route('appraisals.readiness', $appraisal));

        $response->assertOk();
        $response->assertSee('Appraisal Readiness Score');
        $response->assertSee('Aviso Legal y de Certificación');
    }

    public function test_readiness_score_counts_real_statuses(): void
    {
        $this->seed(EvidenceStatusSeeder::class);

        $project = $this->createProject();
        $appraisal = Appraisal::create([
            'project_id' => $project->id,
            'name' => 'Appraisal Estados Reales',
            'domain' => 'Development',
            'target_level' => 3,
            'target_date' => now()->addMonths(3)->toDateString(),
            'status' => 'activo',
        ]);
        $practices = Practice::factory()->count(2)->create();
        $appraisal->practices()->attach($practices->pluck('id'));

        PracticeEvaluation::create(['appraisal_id' => $appraisal->id, 'practice_id' => $practices[0]->id, 'status' => PracticeEvaluation::STATUS_VERIFICADA]);
        PracticeEvaluation::create(['appraisal_id' => $appraisal->id, 'practice_id' => $practices[1]->id, 'status' => PracticeEvaluation::STATUS_NO_CUMPLE]);

        Evidence::create([
            'code' => 'EV-0001',
            'project_id' => $project->id,
            'name' => 'Plan verificado',
            'type' => Evidence::TYPE_PLAN,
            'status_id' => EvidenceStatus::VERIFICADA,
            'uploaded_by' => User::factory()->gestorProcesos()->create()->id,
        ]);

        $result = (new AppraisalReadinessService)->calculate($appraisal);

        $this->assertSame(1, $result['breakdown']['practices']['count']);
        $this->assertSame(1, $result['breakdown']['evidences']['count']);
    }

    public function test_readiness_score_requires_permission_and_visible_appraisal(): void
    {
        $project = $this->createProject();
        $appraisal = Appraisal::create([
            'project_id' => $project->id,
            'name' => 'Appraisal Restringido',
            'domain' => 'Development',
            'target_level' => 3,
            'target_date' => now()->addMonths(3)->toDateString(),
            'status' => 'borrador',
        ]);

        $this->get(route('appraisals.readiness', $appraisal))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->colaborador()->create())
            ->get(route('appraisals.readiness', $appraisal))
            ->assertForbidden();

        $this->actingAs(User::factory()->jefeProyecto()->create())
            ->get(route('appraisals.readiness', $appraisal))
            ->assertForbidden();
    }
}
