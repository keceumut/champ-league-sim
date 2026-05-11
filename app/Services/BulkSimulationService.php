<?php

namespace App\Services;

class BulkSimulationService
{
    private function generateGoals(int $strength): int
    {
        $goals = 0;
        $prob  = ($strength / 100) * 0.3;
        for ($i = 0; $i < 10; $i++) {
            if ((mt_rand() / mt_getrandmax()) < $prob) {
                $goals++;
            }
        }
        return $goals;
    }

    private function buildSchedule(array $ids): array
    {
        return [
            [[$ids[0], $ids[1]], [$ids[2], $ids[3]]],
            [[$ids[0], $ids[2]], [$ids[1], $ids[3]]],
            [[$ids[0], $ids[3]], [$ids[1], $ids[2]]],
            [[$ids[1], $ids[0]], [$ids[3], $ids[2]]],
            [[$ids[2], $ids[0]], [$ids[3], $ids[1]]],
            [[$ids[3], $ids[0]], [$ids[2], $ids[1]]],
        ];
    }

    private function simulateLeague(array $teamMap, array $schedule): array
    {
        $weeks = [];
        foreach ($schedule as $weekIdx => $pairs) {
            $week    = $weekIdx + 1;
            $matches = [];
            foreach ($pairs as [$homeId, $guestId]) {
                $home    = $teamMap[$homeId];
                $guest   = $teamMap[$guestId];
                $matches[] = [
                    'home'        => $home['name'],
                    'guest'       => $guest['name'],
                    'home_id'     => $homeId,
                    'guest_id'    => $guestId,
                    'home_score'  => $this->generateGoals($home['strength']),
                    'guest_score' => $this->generateGoals($guest['strength']),
                ];
            }
            $weeks[$week] = $matches;
        }
        return $weeks;
    }

    private function calcStandings(array $teamMap, array $weeks): array
    {
        $stats = [];
        foreach ($teamMap as $id => $team) {
            $stats[$id] = [
                'id'       => $id,
                'name'     => $team['name'],
                'strength' => $team['strength'],
                'P' => 0, 'W' => 0, 'D' => 0, 'L' => 0,
                'GF' => 0, 'GA' => 0,
            ];
        }

        foreach ($weeks as $matches) {
            foreach ($matches as $m) {
                $hid = $m['home_id'];
                $gid = $m['guest_id'];
                $hs  = $m['home_score'];
                $gs  = $m['guest_score'];

                $stats[$hid]['P']++;  $stats[$gid]['P']++;
                $stats[$hid]['GF'] += $hs; $stats[$hid]['GA'] += $gs;
                $stats[$gid]['GF'] += $gs; $stats[$gid]['GA'] += $hs;

                if ($hs > $gs) {
                    $stats[$hid]['W']++;
                    $stats[$gid]['L']++;
                } elseif ($hs < $gs) {
                    $stats[$gid]['W']++;
                    $stats[$hid]['L']++;
                } else {
                    $stats[$hid]['D']++;
                    $stats[$gid]['D']++;
                }
            }
        }

        $rows = array_values($stats);
        foreach ($rows as &$r) {
            $r['GD']  = $r['GF'] - $r['GA'];
            $r['Pts'] = $r['W'] * 3 + $r['D'];
        }

        usort($rows, fn($a, $b) => $b['Pts'] <=> $a['Pts'] ?: $b['GD'] <=> $a['GD']);
        return $rows;
    }

    public function run(array $teams, int $count): array
    {
        $teamMap  = array_column($teams, null, 'id');
        $ids      = array_column($teams, 'id');
        $schedule = $this->buildSchedule($ids);

        $totals = [];
        foreach ($teams as $t) {
            $totals[$t['id']] = ['wins' => 0, 'runner_up' => 0, 'third' => 0, 'last' => 0, 'pts' => 0, 'gd' => 0];
        }

        $simulations = [];
        for ($i = 1; $i <= $count; $i++) {
            $weeks     = $this->simulateLeague($teamMap, $schedule);
            $standings = $this->calcStandings($teamMap, $weeks);

            foreach ($standings as $pos => $row) {
                $id = $row['id'];
                $totals[$id]['pts'] += $row['Pts'];
                $totals[$id]['gd']  += $row['GD'];
                match ($pos) {
                    0 => $totals[$id]['wins']++,
                    1 => $totals[$id]['runner_up']++,
                    2 => $totals[$id]['third']++,
                    default => $totals[$id]['last']++,
                };
            }

            $simulations[] = [
                'index'     => $i,
                'champion'  => $standings[0]['name'],
                'standings' => $standings,
                'weeks'     => $weeks,
            ];
        }

        $summary = array_map(function ($team) use ($totals, $count) {
            $id = $team['id'];
            return [
                'id'        => $id,
                'name'      => $team['name'],
                'strength'  => $team['strength'],
                'wins'      => $totals[$id]['wins'],
                'win_pct'   => round(($totals[$id]['wins'] / $count) * 100, 1),
                'runner_up' => $totals[$id]['runner_up'],
                'third'     => $totals[$id]['third'],
                'last'      => $totals[$id]['last'],
                'avg_pts'   => round($totals[$id]['pts'] / $count, 1),
                'avg_gd'    => round($totals[$id]['gd'] / $count, 1),
            ];
        }, $teams);

        usort($summary, fn($a, $b) => $b['wins'] <=> $a['wins']);

        return compact('summary', 'simulations');
    }
}
