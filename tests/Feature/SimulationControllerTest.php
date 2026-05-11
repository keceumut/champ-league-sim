<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimulationControllerTest extends TestCase
{
    use RefreshDatabase;

    private array $teams;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teams = [
            ['name' => 'Chelsea',         'strength' => 85],
            ['name' => 'Arsenal',          'strength' => 80],
            ['name' => 'Liverpool',        'strength' => 78],
            ['name' => 'Manchester City',  'strength' => 90],
        ];
    }

    // ── Setup ────────────────────────────────────────────────────────────────

    public function test_setup_creates_league_and_returns_success(): void
    {
        $this->postJson('/api/setup', ['teams' => $this->teams])
            ->assertOk()
            ->assertJson(['message' => 'League created']);
    }

    public function test_setup_requires_exactly_four_teams(): void
    {
        $this->postJson('/api/setup', ['teams' => array_slice($this->teams, 0, 3)])
            ->assertUnprocessable();
    }

    public function test_setup_rejects_strength_below_minimum(): void
    {
        $teams              = $this->teams;
        $teams[0]['strength'] = 59;

        $this->postJson('/api/setup', ['teams' => $teams])
            ->assertUnprocessable();
    }

    public function test_setup_rejects_strength_above_maximum(): void
    {
        $teams              = $this->teams;
        $teams[0]['strength'] = 96;

        $this->postJson('/api/setup', ['teams' => $teams])
            ->assertUnprocessable();
    }

    public function test_setup_creates_12_fixtures(): void
    {
        $this->postJson('/api/setup', ['teams' => $this->teams]);

        $state    = $this->getJson('/api/state')->json();
        $fixtures = collect($state['fixtures'])->flatten(1);

        $this->assertCount(12, $fixtures);
    }

    // ── State ────────────────────────────────────────────────────────────────

    public function test_state_returns_expected_structure(): void
    {
        $this->postJson('/api/setup', ['teams' => $this->teams]);

        $this->getJson('/api/state')
            ->assertOk()
            ->assertJsonStructure(['fixtures', 'standings', 'prediction']);
    }

    public function test_state_standings_contain_all_four_teams(): void
    {
        $this->postJson('/api/setup', ['teams' => $this->teams]);

        $standings = $this->getJson('/api/state')->json('standings');

        $this->assertCount(4, $standings);
    }

    // ── Simulate week ────────────────────────────────────────────────────────

    public function test_simulate_week_plays_first_week(): void
    {
        $this->postJson('/api/setup', ['teams' => $this->teams]);

        $this->postJson('/api/simulate/week')
            ->assertOk()
            ->assertJsonPath('nextWeek', 1)
            ->assertJsonStructure(['nextWeek', 'matches', 'standings', 'prediction']);
    }

    public function test_simulate_week_advances_sequentially(): void
    {
        $this->postJson('/api/setup', ['teams' => $this->teams]);

        $this->postJson('/api/simulate/week');
        $response = $this->postJson('/api/simulate/week');

        $response->assertJsonPath('nextWeek', 2);
    }

    public function test_simulate_week_returns_two_matches_per_week(): void
    {
        $this->postJson('/api/setup', ['teams' => $this->teams]);

        $matches = $this->postJson('/api/simulate/week')->json('matches');

        $this->assertCount(2, $matches);
    }

    public function test_simulate_week_fails_when_all_weeks_are_played(): void
    {
        $this->postJson('/api/setup', ['teams' => $this->teams]);
        $this->postJson('/api/simulate/all');

        $this->postJson('/api/simulate/week')->assertUnprocessable();
    }

    // ── Simulate all ─────────────────────────────────────────────────────────

    public function test_simulate_all_marks_every_match_as_played(): void
    {
        $this->postJson('/api/setup', ['teams' => $this->teams]);
        $this->postJson('/api/simulate/all');

        $fixtures  = $this->getJson('/api/state')->json('fixtures');
        $allPlayed = collect($fixtures)->flatten(1)->every(fn($m) => $m['is_played']);

        $this->assertTrue($allPlayed);
    }

    public function test_simulate_all_populates_scores(): void
    {
        $this->postJson('/api/setup', ['teams' => $this->teams]);
        $this->postJson('/api/simulate/all');

        $fixtures = $this->getJson('/api/state')->json('fixtures');
        $allHaveScores = collect($fixtures)->flatten(1)->every(
            fn($m) => $m['home_score'] !== null && $m['guest_score'] !== null
        );

        $this->assertTrue($allHaveScores);
    }

    // ── Reset ────────────────────────────────────────────────────────────────

    public function test_reset_marks_all_matches_unplayed(): void
    {
        $this->postJson('/api/setup', ['teams' => $this->teams]);
        $this->postJson('/api/simulate/all');
        $this->postJson('/api/reset');

        $fixtures  = $this->getJson('/api/state')->json('fixtures');
        $anyPlayed = collect($fixtures)->flatten(1)->contains('is_played', true);

        $this->assertFalse($anyPlayed);
    }

    public function test_reset_clears_scores(): void
    {
        $this->postJson('/api/setup', ['teams' => $this->teams]);
        $this->postJson('/api/simulate/all');
        $this->postJson('/api/reset');

        $fixtures     = $this->getJson('/api/state')->json('fixtures');
        $anyWithScore = collect($fixtures)->flatten(1)->contains(fn($m) => $m['home_score'] !== null);

        $this->assertFalse($anyWithScore);
    }

    // ── Update match ─────────────────────────────────────────────────────────

    public function test_update_match_changes_score_and_returns_standings(): void
    {
        $this->postJson('/api/setup', ['teams' => $this->teams]);
        $this->postJson('/api/simulate/week');

        $playedMatch = collect($this->getJson('/api/state')->json('fixtures'))
            ->flatten(1)->firstWhere('is_played', true);

        $this->patchJson("/api/matches/{$playedMatch['id']}", ['home_score' => 3, 'guest_score' => 1])
            ->assertOk()
            ->assertJsonStructure(['standings', 'prediction']);
    }

    public function test_update_match_rejects_unplayed_match(): void
    {
        $this->postJson('/api/setup', ['teams' => $this->teams]);

        $unplayed = collect($this->getJson('/api/state')->json('fixtures'))
            ->flatten(1)->firstWhere('is_played', false);

        $this->patchJson("/api/matches/{$unplayed['id']}", ['home_score' => 2, 'guest_score' => 0])
            ->assertUnprocessable();
    }

    public function test_update_match_rejects_negative_scores(): void
    {
        $this->postJson('/api/setup', ['teams' => $this->teams]);
        $this->postJson('/api/simulate/week');

        $played = collect($this->getJson('/api/state')->json('fixtures'))
            ->flatten(1)->firstWhere('is_played', true);

        $this->patchJson("/api/matches/{$played['id']}", ['home_score' => -1, 'guest_score' => 0])
            ->assertUnprocessable();
    }

    // ── Bulk simulate ────────────────────────────────────────────────────────

    public function test_bulk_simulate_returns_summary_and_simulations(): void
    {
        $this->postJson('/api/setup', ['teams' => $this->teams]);

        $this->postJson('/api/bulk-simulate', ['count' => 10])
            ->assertOk()
            ->assertJsonStructure(['summary', 'simulations']);
    }

    public function test_bulk_simulate_requires_count(): void
    {
        $this->postJson('/api/setup', ['teams' => $this->teams]);

        $this->postJson('/api/bulk-simulate', [])->assertUnprocessable();
    }

    public function test_bulk_simulate_fails_without_league_setup(): void
    {
        $this->postJson('/api/bulk-simulate', ['count' => 10])->assertUnprocessable();
    }
}
