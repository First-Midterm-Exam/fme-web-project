<?php

declare(strict_types=1);

namespace Tests\Feature\Appraisals;

use App\Models\Appraisal;
use App\Models\Project;
use App\Models\User;
use App\Services\AppraisalReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppraisalReadinessScoreTest extends TestCase
{
    use RefreshDatabase;

    private function createProject(): Project
    {
        return Project::create([
            'name' => 'Proyecto CMMI Test',
            'code' => 'PRJ-' . uniqid(),
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

        $service = new AppraisalReadinessService();
        $result = $service->calculate($appraisal);

        // CA-1: Score de 0% a 100%
        $this->assertGreaterThanOrEqual(0.0, $result['score']);
        $this->assertLessThanOrEqual(100.0, $result['score']);

        // CA-2: Desglose por componentes
        $this->assertArrayHasKey('practices', $result['breakdown']);
        $this->assertArrayHasKey('evidences', $result['breakdown']);
        $this->assertArrayHasKey('gaps', $result['breakdown']);

        // CA-3 / RNF-14: Texto de descargo institucional obligatorio
        $this->assertStringContainsString('no constituye', mb_strtolower($result['disclaimer']));
    }

    public function test_readiness_score_view_renders_successfully(): void
    {
        $user = User::factory()->create();
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
        $response->assertSee('RNF-14');
    }
}