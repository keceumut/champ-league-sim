<?php

namespace App\Http\Controllers;

use App\Models\Matches;
use App\Models\Team;
use App\Services\BulkSimulationService;
use App\Services\LeagueService;
use App\Services\PredictionService;
use App\Services\SimulationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SimulationController extends Controller
{
    public function __construct(
        private SimulationService     $simulation,
        private LeagueService         $league,
        private PredictionService     $prediction,
        private BulkSimulationService $bulkSimulation,
    ) {}

    public function setup(Request $request): JsonResponse
    {
        $teams = $request->validate([
            'teams'            => 'required|array|size:4',
            'teams.*.name'     => 'required|string',
            'teams.*.strength' => 'required|integer|min:60|max:95',
        ])['teams'];

        // Wipe existing data
        Matches::query()->delete();
        Team::query()->delete();

        // Create teams
        $created = collect($teams)->map(
            fn($t) => Team::create(['name' => $t['name'], 'strength' => $t['strength']])
        );

        // Round-robin fixtures: 6 weeks, 2 matches/week, each pair plays home & away
        $ids = $created->pluck('id')->values();
        $schedule = [
            [[$ids[0], $ids[1]], [$ids[2], $ids[3]]],
            [[$ids[0], $ids[2]], [$ids[1], $ids[3]]],
            [[$ids[0], $ids[3]], [$ids[1], $ids[2]]],
            [[$ids[1], $ids[0]], [$ids[3], $ids[2]]],
            [[$ids[2], $ids[0]], [$ids[3], $ids[1]]],
            [[$ids[3], $ids[0]], [$ids[2], $ids[1]]],
        ];

        foreach ($schedule as $week => $pairs) {
            foreach ($pairs as [$home, $guest]) {
                Matches::create([
                    'week'         => $week + 1,
                    'home_team_id' => $home,
                    'guest_team_id' => $guest,
                ]);
            }
        }

        return response()->json(['message' => 'League created']);
    }

    public function state(): JsonResponse
    {
        return response()->json($this->buildState());
    }

    public function simulateWeek(): JsonResponse
    {
        $nextWeek = Matches::query()
            ->where('is_played', false)
            ->min('week');

        if ($nextWeek === null) {
            return response()->json(['message' => 'All weeks already simulated'], 422);
        }

        $this->simulation->simulateWeek($nextWeek);

        $matches = Matches::query()
            ->where('week', $nextWeek)
            ->with(['homeTeam', 'guestTeam'])
            ->get()
            ->map(fn($m) => $this->formatMatch($m));

        $standings   = $this->league->getStandings();
        $weeksPlayed = Matches::where('is_played', true)->distinct('week')->count('week');
        $prediction  = $weeksPlayed >= 4 ? $this->prediction->predict() : [];

        return response()->json(compact('nextWeek', 'matches', 'standings', 'prediction'));
    }

    public function simulateAll(): JsonResponse
    {
        $this->simulation->simulateAll();

        return response()->json($this->buildState());
    }

    public function bulkSimulate(Request $request): JsonResponse
    {
        $count = $request->validate(['count' => 'required|integer|min:10|max:100'])['count'];

        $teams = Team::all(['id', 'name', 'strength'])->toArray();
        if (count($teams) !== 4) {
            return response()->json(['message' => 'League not set up'], 422);
        }

        return response()->json($this->bulkSimulation->run($teams, $count));
    }

    public function updateMatch(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'home_score'  => 'required|integer|min:0',
            'guest_score' => 'required|integer|min:0',
        ]);

        $match = Matches::findOrFail($id);

        if (!$match->is_played) {
            return response()->json(['message' => 'Cannot edit an unplayed match'], 422);
        }

        $match->home_score  = $data['home_score'];
        $match->guest_score = $data['guest_score'];
        $match->save();

        $standings   = $this->league->getStandings();
        $weeksPlayed = Matches::where('is_played', true)->distinct('week')->count('week');
        $prediction  = $weeksPlayed >= 4 ? $this->prediction->predict() : [];

        return response()->json(compact('standings', 'prediction'));
    }

    public function reset(): JsonResponse
    {
        Matches::query()->update([
            'is_played'   => false,
            'home_score'  => null,
            'guest_score' => null,
        ]);

        return response()->json($this->buildState());
    }

    private function buildState(): array
    {
        $fixtures = Matches::query()
            ->with(['homeTeam', 'guestTeam'])
            ->orderBy('week')
            ->get()
            ->groupBy('week')
            ->map(fn($week) => $week->map(fn($m) => $this->formatMatch($m)))
            ->toArray();

        $standings   = $this->league->getStandings();
        $weeksPlayed = Matches::where('is_played', true)->distinct('week')->count('week');
        $prediction  = $weeksPlayed >= 4 ? $this->prediction->predict() : [];

        return compact('fixtures', 'standings', 'prediction');
    }

    private function formatMatch(Matches $m): array
    {
        return [
            'id'          => $m->id,
            'home'        => $m->homeTeam->name,
            'guest'       => $m->guestTeam->name,
            'home_score'  => $m->home_score,
            'guest_score' => $m->guest_score,
            'is_played'   => $m->is_played,
        ];
    }
}
