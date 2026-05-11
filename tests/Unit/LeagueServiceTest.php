<?php

namespace Tests\Unit;

use App\Models\Matches;
use App\Models\Team;
use App\Services\LeagueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeagueServiceTest extends TestCase
{
    use RefreshDatabase;

    private LeagueService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LeagueService();
    }

    public function test_empty_standings_returns_all_teams_with_zero_stats(): void
    {
        Team::create(['name' => 'Team A', 'strength' => 80]);
        Team::create(['name' => 'Team B', 'strength' => 70]);

        $standings = $this->service->getStandings();

        $this->assertCount(2, $standings);
        foreach ($standings as $row) {
            $this->assertSame(0, $row['P']);
            $this->assertSame(0, $row['Pts']);
        }
    }

    public function test_win_awards_three_points_to_winning_team(): void
    {
        $home = Team::create(['name' => 'Home', 'strength' => 80]);
        $away = Team::create(['name' => 'Away', 'strength' => 70]);

        Matches::create([
            'week'          => 1,
            'home_team_id'  => $home->id,
            'guest_team_id' => $away->id,
            'home_score'    => 2,
            'guest_score'   => 0,
            'is_played'     => true,
        ]);

        $standings = $this->service->getStandings();
        $homeRow   = collect($standings)->firstWhere('id', $home->id);
        $awayRow   = collect($standings)->firstWhere('id', $away->id);

        $this->assertSame(3, $homeRow['Pts']);
        $this->assertSame(1, $homeRow['W']);
        $this->assertSame(0, $awayRow['Pts']);
        $this->assertSame(1, $awayRow['L']);
    }

    public function test_draw_awards_one_point_to_each_team(): void
    {
        $home = Team::create(['name' => 'Home', 'strength' => 80]);
        $away = Team::create(['name' => 'Away', 'strength' => 70]);

        Matches::create([
            'week'          => 1,
            'home_team_id'  => $home->id,
            'guest_team_id' => $away->id,
            'home_score'    => 1,
            'guest_score'   => 1,
            'is_played'     => true,
        ]);

        $standings = $this->service->getStandings();

        foreach ($standings as $row) {
            $this->assertSame(1, $row['Pts']);
            $this->assertSame(1, $row['D']);
        }
    }

    public function test_goal_difference_is_calculated_correctly(): void
    {
        $home = Team::create(['name' => 'Home', 'strength' => 80]);
        $away = Team::create(['name' => 'Away', 'strength' => 70]);

        Matches::create([
            'week'          => 1,
            'home_team_id'  => $home->id,
            'guest_team_id' => $away->id,
            'home_score'    => 3,
            'guest_score'   => 1,
            'is_played'     => true,
        ]);

        $standings = $this->service->getStandings();
        $homeRow   = collect($standings)->firstWhere('id', $home->id);
        $awayRow   = collect($standings)->firstWhere('id', $away->id);

        $this->assertSame(2, $homeRow['GD']);
        $this->assertSame(-2, $awayRow['GD']);
    }

    public function test_standings_sorted_by_points_then_goal_difference(): void
    {
        $a = Team::create(['name' => 'A', 'strength' => 80]);
        $b = Team::create(['name' => 'B', 'strength' => 75]);
        $c = Team::create(['name' => 'C', 'strength' => 70]);

        // A wins 3-0: A gets 3pts GD+3, B gets 0pts GD-3
        Matches::create(['week' => 1, 'home_team_id' => $a->id, 'guest_team_id' => $b->id, 'home_score' => 3, 'guest_score' => 0, 'is_played' => true]);
        // B wins 1-0: B gets 3pts GD+1, C gets 0pts GD-1
        Matches::create(['week' => 2, 'home_team_id' => $b->id, 'guest_team_id' => $c->id, 'home_score' => 1, 'guest_score' => 0, 'is_played' => true]);

        // A: 3pts GD=+3 | B: 3pts GD=(+1-3)=-2 | C: 0pts
        $standings = $this->service->getStandings();

        $this->assertSame($a->id, $standings[0]['id']);
        $this->assertSame($b->id, $standings[1]['id']);
        $this->assertSame($c->id, $standings[2]['id']);
    }

    public function test_unplayed_matches_do_not_affect_standings(): void
    {
        $home = Team::create(['name' => 'Home', 'strength' => 80]);
        $away = Team::create(['name' => 'Away', 'strength' => 70]);

        Matches::create([
            'week'          => 1,
            'home_team_id'  => $home->id,
            'guest_team_id' => $away->id,
        ]);

        $standings = $this->service->getStandings();

        foreach ($standings as $row) {
            $this->assertSame(0, $row['P']);
            $this->assertSame(0, $row['Pts']);
        }
    }

    public function test_guest_team_win_recorded_correctly(): void
    {
        $home = Team::create(['name' => 'Home', 'strength' => 70]);
        $away = Team::create(['name' => 'Away', 'strength' => 80]);

        Matches::create([
            'week'          => 1,
            'home_team_id'  => $home->id,
            'guest_team_id' => $away->id,
            'home_score'    => 0,
            'guest_score'   => 2,
            'is_played'     => true,
        ]);

        $standings = $this->service->getStandings();
        $awayRow   = collect($standings)->firstWhere('id', $away->id);

        $this->assertSame(3, $awayRow['Pts']);
        $this->assertSame(1, $awayRow['W']);
    }
}
