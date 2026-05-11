<?php

namespace Tests\Unit;

use App\Services\BulkSimulationService;
use Tests\TestCase;

class BulkSimulationServiceTest extends TestCase
{
    private BulkSimulationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BulkSimulationService();
    }

    public function test_run_returns_summary_and_simulations_keys(): void
    {
        $result = $this->service->run($this->teams(), 10);

        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('simulations', $result);
    }

    public function test_summary_contains_one_row_per_team(): void
    {
        $result = $this->service->run($this->teams(), 10);

        $this->assertCount(4, $result['summary']);
    }

    public function test_simulation_count_matches_requested_count(): void
    {
        $result = $this->service->run($this->teams(), 15);

        $this->assertCount(15, $result['simulations']);
    }

    public function test_total_wins_across_all_teams_equals_simulation_count(): void
    {
        $count  = 20;
        $result = $this->service->run($this->teams(), $count);

        $totalWins = array_sum(array_column($result['summary'], 'wins'));

        $this->assertSame($count, $totalWins);
    }

    public function test_win_pct_sums_to_100(): void
    {
        $result   = $this->service->run($this->teams(), 100);
        $totalPct = array_sum(array_column($result['summary'], 'win_pct'));

        $this->assertEqualsWithDelta(100.0, $totalPct, 0.1);
    }

    public function test_summary_is_sorted_by_wins_descending(): void
    {
        $result = $this->service->run($this->teams(), 50);
        $wins   = array_column($result['summary'], 'wins');
        $sorted = $wins;
        rsort($sorted);

        $this->assertSame($sorted, $wins);
    }

    public function test_each_simulation_has_champion_and_standings(): void
    {
        $result = $this->service->run($this->teams(), 5);

        foreach ($result['simulations'] as $sim) {
            $this->assertArrayHasKey('champion', $sim);
            $this->assertArrayHasKey('standings', $sim);
            $this->assertCount(4, $sim['standings']);
        }
    }

    public function test_stronger_team_wins_more_often(): void
    {
        $result  = $this->service->run($this->teams(), 200);
        $summary = $result['summary'];

        $topTeam = $summary[0];

        // The strongest team (strength 90) should come out first after sorting by wins
        $this->assertSame('Alpha', $topTeam['name']);
    }

    private function teams(): array
    {
        return [
            ['id' => 1, 'name' => 'Alpha',  'strength' => 90],
            ['id' => 2, 'name' => 'Beta',   'strength' => 75],
            ['id' => 3, 'name' => 'Gamma',  'strength' => 65],
            ['id' => 4, 'name' => 'Delta',  'strength' => 55],
        ];
    }
}
