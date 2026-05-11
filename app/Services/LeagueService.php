<?php

namespace App\Services;

use App\Models\Matches;
use App\Models\Team;

class LeagueService
{
    public function getStandings(): array
    {
        $teams = Team::all()->keyBy('id');

        $standings = $teams->map(fn($team) => [
            'id'   => $team->id,
            'name' => $team->name,
            'strength' => $team->strength,
            'P'    => 0,
            'W'    => 0,
            'D'    => 0,
            'L'    => 0,
            'GF'   => 0,
            'GA'   => 0,
            'GD'   => 0,
            'Pts'  => 0,
        ])->toArray();

        Matches::query()
            ->where('is_played', true)
            ->get()
            ->each(function ($match) use (&$standings) {
                $h = $match->home_team_id;
                $g = $match->guest_team_id;
                $hs = $match->home_score;
                $gs = $match->guest_score;

                $standings[$h]['P']++;
                $standings[$g]['P']++;
                $standings[$h]['GF'] += $hs;
                $standings[$h]['GA'] += $gs;
                $standings[$g]['GF'] += $gs;
                $standings[$g]['GA'] += $hs;

                if ($hs > $gs) {
                    $standings[$h]['W']++;
                    $standings[$h]['Pts'] += 3;
                    $standings[$g]['L']++;
                } elseif ($hs < $gs) {
                    $standings[$g]['W']++;
                    $standings[$g]['Pts'] += 3;
                    $standings[$h]['L']++;
                } else {
                    $standings[$h]['D']++;
                    $standings[$g]['D']++;
                    $standings[$h]['Pts']++;
                    $standings[$g]['Pts']++;
                }
            });

        foreach ($standings as &$row) {
            $row['GD'] = $row['GF'] - $row['GA'];
        }

        usort($standings, fn($a, $b) =>
            $b['Pts'] <=> $a['Pts'] ?: $b['GD'] <=> $a['GD']
        );

        return array_values($standings);
    }
}
