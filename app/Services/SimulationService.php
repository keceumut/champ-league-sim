<?php

namespace App\Services;

use App\Models\Matches;

class SimulationService
{
    // Each team gets up to 10 scoring attempts; probability per attempt scales with strength.
    private function generateGoals(int $strength): int
    {
        $goals = 0;
        $prob  = ($strength / 100) * 0.3; // at strength 100 → ~30% per attempt
        for ($i = 0; $i < 10; $i++) {
            if ((mt_rand() / mt_getrandmax()) < $prob) {
                $goals++;
            }
        }
        return $goals;
    }

    public function simulateMatch(Matches $match): void
    {
        $match->home_score  = $this->generateGoals($match->homeTeam->strength);
        $match->guest_score = $this->generateGoals($match->guestTeam->strength);
        $match->is_played   = true;
        $match->save();
    }

    public function simulateWeek(int $week): void
    {
        Matches::query()
            ->where('week', $week)
            ->where('is_played', false)
            ->with(['homeTeam', 'guestTeam'])
            ->get()
            ->each(fn($match) => $this->simulateMatch($match));
    }

    public function simulateAll(): void
    {
        Matches::query()
            ->where('is_played', false)
            ->with(['homeTeam', 'guestTeam'])
            ->get()
            ->each(fn($match) => $this->simulateMatch($match));
    }
}
