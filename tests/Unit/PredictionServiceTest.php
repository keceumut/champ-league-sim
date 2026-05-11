<?php

namespace Tests\Unit;

use App\Models\Matches;
use App\Models\Team;
use App\Services\LeagueService;
use App\Services\PredictionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PredictionServiceTest extends TestCase
{
    use RefreshDatabase;

    private PredictionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PredictionService(new LeagueService());
    }

    public function test_predict_includes_projected_pts_for_every_team(): void
    {
        Team::create(['name' => 'A', 'strength' => 80]);
        Team::create(['name' => 'B', 'strength' => 70]);

        $predictions = $this->service->predict();

        foreach ($predictions as $row) {
            $this->assertArrayHasKey('ProjectedPts', $row);
        }
    }

    public function test_projected_pts_equals_ppg_times_six_total_games(): void
    {
        $a = Team::create(['name' => 'A', 'strength' => 80]);
        $b = Team::create(['name' => 'B', 'strength' => 70]);

        // A wins 2 games → 6 pts in 2 played → PPG = 3 → Projected = 3 × 6 = 18
        Matches::create(['week' => 1, 'home_team_id' => $a->id, 'guest_team_id' => $b->id, 'home_score' => 1, 'guest_score' => 0, 'is_played' => true]);
        Matches::create(['week' => 2, 'home_team_id' => $a->id, 'guest_team_id' => $b->id, 'home_score' => 2, 'guest_score' => 0, 'is_played' => true]);

        $predictions = $this->service->predict();
        $rowA        = collect($predictions)->firstWhere('id', $a->id);

        $this->assertSame(18.0, $rowA['ProjectedPts']);
    }

    public function test_projected_pts_is_zero_for_team_with_no_games_played(): void
    {
        Team::create(['name' => 'A', 'strength' => 80]);

        $predictions = $this->service->predict();

        $this->assertSame(0.0, (float) $predictions[0]['ProjectedPts']);
    }

    public function test_draw_ppg_projects_correctly(): void
    {
        $a = Team::create(['name' => 'A', 'strength' => 80]);
        $b = Team::create(['name' => 'B', 'strength' => 70]);

        // 1 draw each → 1 pt in 1 game → PPG = 1 → Projected = 6
        Matches::create(['week' => 1, 'home_team_id' => $a->id, 'guest_team_id' => $b->id, 'home_score' => 0, 'guest_score' => 0, 'is_played' => true]);

        $predictions = $this->service->predict();

        foreach ($predictions as $row) {
            $this->assertSame(6.0, $row['ProjectedPts']);
        }
    }

    public function test_predict_result_count_matches_team_count(): void
    {
        Team::create(['name' => 'A', 'strength' => 80]);
        Team::create(['name' => 'B', 'strength' => 70]);
        Team::create(['name' => 'C', 'strength' => 65]);

        $predictions = $this->service->predict();

        $this->assertCount(3, $predictions);
    }
}
