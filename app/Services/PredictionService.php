<?php

namespace App\Services;

class PredictionService
{
    private const TOTAL_GAMES = 6; // 4 teams, round-robin home & away

    public function __construct(private LeagueService $leagueService) {}

    public function predict(): array
    {
        $standings = $this->leagueService->getStandings();

        return array_map(function ($row) {
            $ppg       = $row['P'] > 0 ? $row['Pts'] / $row['P'] : 0;
            $projected = round($ppg * self::TOTAL_GAMES, 1);

            return array_merge($row, ['ProjectedPts' => $projected]);
        }, $standings);
    }
}
