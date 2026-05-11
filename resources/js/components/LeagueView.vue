<template>
    <div class="min-h-screen">
        <div class="max-w-360 mx-auto px-6 lg:px-12 xl:px-20 py-8">
            <!-- Header -->
            <div class="flex items-center gap-4 mb-8">
                <RouterLink
                    to="/"
                    class="text-slate-400 hover:text-white transition-colors text-sm flex items-center gap-1"
                >
                    ← Back
                </RouterLink>
                <h1 class="text-2xl font-bold text-white">⚽ League Season</h1>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-3 mb-6">
                <button
                    @click="playWeek"
                    :disabled="loading || allPlayed"
                    class="flex-1 py-3 rounded-xl font-semibold text-sm transition-all duration-200 bg-green-500 hover:bg-green-400 active:scale-95 text-white disabled:opacity-40 disabled:cursor-not-allowed"
                >
                    {{ loading ? "Simulating…" : "Play Next Week" }}
                </button>
                <button
                    @click="playAll"
                    :disabled="loading || allPlayed"
                    class="flex-1 py-3 rounded-xl font-semibold text-sm transition-all duration-200 bg-blue-500 hover:bg-blue-400 active:scale-95 text-white disabled:opacity-40 disabled:cursor-not-allowed"
                >
                    Play All Weeks
                </button>
                <button
                    @click="reset"
                    :disabled="loading"
                    class="px-6 py-3 rounded-xl font-semibold text-sm transition-all duration-200 bg-slate-700 hover:bg-slate-600 active:scale-95 text-slate-300 disabled:opacity-40 disabled:cursor-not-allowed"
                >
                    Reset
                </button>
            </div>

            <!-- Fixed-height slot: prevents layout shift when banner appears -->
            <div class="h-7 mb-4 flex items-center justify-center">
                <p v-if="allPlayed" class="text-green-400 text-sm font-medium">
                    Season complete — all weeks played.
                </p>
            </div>

            <!-- Top row: Standings + Championship Odds -->
            <div class="grid grid-cols-1 lg:grid-cols-[1fr_50%] gap-6 mb-6">
                <!-- Standings -->
                <div
                    class="bg-slate-800/60 border border-slate-700/50 rounded-2xl p-5 backdrop-blur-sm"
                >
                    <h2
                        class="text-slate-300 text-xs font-semibold uppercase tracking-widest mb-4"
                    >
                        Standings
                    </h2>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-slate-500 text-xs">
                                <th class="text-left pb-2 pr-2 font-medium w-5">
                                    #
                                </th>
                                <th class="text-left pb-2 font-medium">Team</th>
                                <th class="text-center pb-2 font-medium w-7">
                                    P
                                </th>
                                <th class="text-center pb-2 font-medium w-7">
                                    W
                                </th>
                                <th class="text-center pb-2 font-medium w-7">
                                    D
                                </th>
                                <th class="text-center pb-2 font-medium w-7">
                                    L
                                </th>
                                <th class="text-center pb-2 font-medium w-8">
                                    GF
                                </th>
                                <th class="text-center pb-2 font-medium w-8">
                                    GA
                                </th>
                                <th class="text-center pb-2 font-medium w-8">
                                    GD
                                </th>
                                <th class="text-center pb-2 font-medium w-9">
                                    Pts
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(row, i) in standings"
                                :key="row.id"
                                class="border-t border-slate-700/40"
                                :class="
                                    i === 0 ? 'text-white' : 'text-slate-300'
                                "
                            >
                                <td
                                    class="py-2 pr-2 text-slate-500 font-medium"
                                >
                                    {{ i + 1 }}
                                </td>
                                <td class="py-2 font-semibold">
                                    {{ row.name }} ({{ row.strength }})
                                </td>
                                <td class="py-2 text-center text-slate-400">
                                    {{ row.P }}
                                </td>
                                <td class="py-2 text-center text-slate-400">
                                    {{ row.W }}
                                </td>
                                <td class="py-2 text-center text-slate-400">
                                    {{ row.D }}
                                </td>
                                <td class="py-2 text-center text-slate-400">
                                    {{ row.L }}
                                </td>
                                <td class="py-2 text-center text-slate-400">
                                    {{ row.GF }}
                                </td>
                                <td class="py-2 text-center text-slate-400">
                                    {{ row.GA }}
                                </td>
                                <td
                                    class="py-2 text-center"
                                    :class="
                                        row.GD > 0
                                            ? 'text-green-400'
                                            : row.GD < 0
                                              ? 'text-red-400'
                                              : 'text-slate-400'
                                    "
                                >
                                    {{ row.GD > 0 ? "+" : "" }}{{ row.GD }}
                                </td>
                                <td
                                    class="py-2 text-center font-bold"
                                    :class="
                                        i === 0
                                            ? 'text-green-400'
                                            : 'text-white'
                                    "
                                >
                                    {{ row.Pts }}
                                </td>
                            </tr>
                            <template v-if="standings.length === 0">
                                <tr
                                    v-for="n in 4"
                                    :key="n"
                                    class="border-t border-slate-700/40"
                                >
                                    <td class="py-2 pr-2">
                                        <div
                                            class="w-3 h-3 bg-slate-700/60 rounded animate-pulse"
                                        ></div>
                                    </td>
                                    <td class="py-2">
                                        <div
                                            class="h-3 bg-slate-700/60 rounded animate-pulse w-28"
                                        ></div>
                                    </td>
                                    <td v-for="_ in 8" class="py-2 text-center">
                                        <div
                                            class="h-3 w-5 bg-slate-700/60 rounded animate-pulse mx-auto"
                                        ></div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Championship Odds -->
                <div
                    class="bg-slate-800/60 border border-slate-700/50 rounded-2xl backdrop-blur-sm overflow-hidden flex flex-row"
                >
                    <!-- Champion banner -->
                    <div
                        v-if="isChampionDecided && standings.length"
                        class="bg-linear-to-br from-yellow-500/20 via-amber-500/10 to-transparent border-b border-yellow-500/20 px-5 py-4 flex items-center gap-4"
                    >
                        <div class="text-4xl shrink-0">🏆</div>
                        <div class="flex-1 min-w-0">
                            <p
                                class="text-yellow-400/70 text-xs font-semibold uppercase tracking-widest mb-0.5"
                            >
                                Season Champion
                            </p>
                            <p class="text-white text-base font-bold truncate">
                                {{ standings[0].name }}
                            </p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-yellow-400 text-sm font-bold">
                                {{ standings[0].Pts }} pts
                            </p>
                            <p class="text-yellow-400/60 text-xs">
                                {{ standings[0].W }}W {{ standings[0].D }}D
                                {{ standings[0].L }}L
                            </p>
                        </div>
                    </div>

                    <div class="p-5 flex-1">
                        <h2
                            class="text-slate-300 text-xs font-semibold uppercase tracking-widest mb-4"
                        >
                            Championship Odds
                        </h2>

                        <!-- Always 4 rows: skeleton when locked, real bars when available -->
                        <div class="space-y-4">
                            <template
                                v-if="weeksPlayed < 4 && !isChampionDecided"
                            >
                                <div v-for="n in 4" :key="n">
                                    <div
                                        class="flex items-center justify-between mb-1"
                                    >
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="w-4 h-4 bg-slate-700/60 rounded animate-pulse"
                                            ></div>
                                            <div
                                                class="h-4 w-20 bg-slate-700/60 rounded animate-pulse"
                                            ></div>
                                        </div>
                                        <div
                                            class="h-4 w-8 bg-slate-700/60 rounded animate-pulse"
                                        ></div>
                                    </div>
                                    <div
                                        class="h-2 bg-slate-700 rounded-full overflow-hidden"
                                    >
                                        <div
                                            class="h-full w-0 rounded-full"
                                        ></div>
                                    </div>
                                    <div
                                        class="h-4 w-14 bg-slate-700/60 rounded animate-pulse mt-0.5"
                                    ></div>
                                </div>
                                <p
                                    class="text-slate-600 text-xs text-center pt-1"
                                >
                                    🔒 Unlocks after week 4 &nbsp;({{
                                        weeksPlayed
                                    }}/4)
                                </p>
                            </template>
                            <template v-else>
                                <div
                                    v-for="(team, i) in finalOdds"
                                    :key="team.id"
                                >
                                    <div
                                        class="flex items-center justify-between mb-1"
                                    >
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="text-slate-500 text-xs w-4"
                                                >{{ i + 1 }}</span
                                            >
                                            <span
                                                class="text-sm font-semibold"
                                                :class="
                                                    isChampionDecided && i === 0
                                                        ? 'text-yellow-300'
                                                        : 'text-white'
                                                "
                                                >{{ team.name }}</span
                                            >
                                        </div>
                                        <span
                                            class="text-sm font-bold"
                                            :class="
                                                isChampionDecided && i === 0
                                                    ? 'text-yellow-400'
                                                    : i === 0
                                                      ? 'text-green-400'
                                                      : 'text-slate-400'
                                            "
                                            >{{ team.winPct }}%</span
                                        >
                                    </div>
                                    <div
                                        class="h-2 bg-slate-700 rounded-full overflow-hidden"
                                    >
                                        <div
                                            class="h-full rounded-full transition-all duration-700"
                                            :class="
                                                isChampionDecided && i === 0
                                                    ? 'bg-yellow-400'
                                                    : i === 0
                                                      ? 'bg-green-500'
                                                      : i === 1
                                                        ? 'bg-blue-500'
                                                        : i === 2
                                                          ? 'bg-slate-400'
                                                          : 'bg-slate-600'
                                            "
                                            :style="{
                                                width: team.winPct + '%',
                                            }"
                                        ></div>
                                    </div>
                                    <p class="text-slate-600 text-xs mt-0.5">
                                        {{
                                            Number(team.ProjectedPts).toFixed(1)
                                        }}
                                        {{
                                            isChampionDecided
                                                ? "final pts"
                                                : "projected pts"
                                        }}
                                    </p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Weeks grid: always 6 cards, skeleton until fixtures load -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                <div
                    v-for="w in WEEK_NUMBERS"
                    :key="w"
                    class="bg-slate-800/60 border border-slate-700/50 rounded-2xl overflow-hidden backdrop-blur-sm"
                >
                    <!-- Skeleton: data not yet loaded -->
                    <template v-if="!fixtures[w]">
                        <div
                            class="px-4 py-3 border-b border-slate-700/50 flex items-center justify-between"
                        >
                            <div
                                class="h-3 w-12 bg-slate-700/60 rounded animate-pulse"
                            ></div>
                            <div class="flex gap-1.5">
                                <div
                                    class="w-1.5 h-1.5 rounded-full bg-slate-700/60"
                                ></div>
                                <div
                                    class="w-1.5 h-1.5 rounded-full bg-slate-700/60"
                                ></div>
                            </div>
                        </div>
                        <div class="divide-y divide-slate-700/40">
                            <div
                                v-for="_ in 2"
                                class="px-4 py-3 flex items-center gap-2"
                            >
                                <div
                                    class="flex-1 h-3 bg-slate-700/60 rounded animate-pulse"
                                ></div>
                                <div
                                    class="shrink-0 w-14 h-3 bg-slate-700/60 rounded animate-pulse"
                                ></div>
                                <div
                                    class="flex-1 h-3 bg-slate-700/60 rounded animate-pulse"
                                ></div>
                            </div>
                        </div>
                    </template>

                    <!-- Real content -->
                    <template v-else>
                        <!-- Week header -->
                        <div
                            class="px-4 py-3 border-b border-slate-700/50 flex items-center justify-between"
                        >
                            <h2
                                class="text-slate-300 text-xs font-semibold uppercase tracking-widest"
                            >
                                Week {{ w }}
                            </h2>
                            <div class="flex gap-1.5">
                                <div
                                    v-for="match in fixtures[w]"
                                    :key="match.id"
                                    class="w-1.5 h-1.5 rounded-full"
                                    :class="
                                        match.is_played
                                            ? 'bg-green-400'
                                            : 'bg-slate-700'
                                    "
                                ></div>
                            </div>
                        </div>

                        <!-- Match rows -->
                        <div class="divide-y divide-slate-700/40">
                            <div
                                v-for="match in fixtures[w]"
                                :key="match.id"
                                class="px-4 py-3 overflow-hidden"
                            >
                                <Transition name="match-edit" mode="out-in">
                                    <!-- Edit mode -->
                                    <div
                                        v-if="editingId === match.id"
                                        key="edit"
                                    >
                                        <div
                                            class="flex items-center gap-1 mb-2"
                                        >
                                            <span
                                                class="flex-1 min-w-0 text-right text-xs font-semibold text-white truncate"
                                            >
                                                {{ match.home }}
                                            </span>
                                            <input
                                                v-model.number="editScores.home"
                                                type="number"
                                                min="0"
                                                max="20"
                                                class="w-9 shrink-0 text-center bg-slate-700 text-white rounded px-1 py-0.5 text-xs font-bold border border-slate-600 focus:outline-none focus:border-green-500"
                                            />
                                            <span
                                                class="text-slate-500 text-xs font-bold shrink-0"
                                                >–</span
                                            >
                                            <input
                                                v-model.number="
                                                    editScores.guest
                                                "
                                                type="number"
                                                min="0"
                                                max="20"
                                                class="w-9 shrink-0 text-center bg-slate-700 text-white rounded px-1 py-0.5 text-xs font-bold border border-slate-600 focus:outline-none focus:border-green-500"
                                            />
                                            <span
                                                class="flex-1 min-w-0 text-left text-xs font-semibold text-white truncate"
                                            >
                                                {{ match.guest }}
                                            </span>
                                        </div>
                                        <div class="flex gap-2 justify-end">
                                            <button
                                                @click="saveEdit(match.id)"
                                                :disabled="editSaving"
                                                class="text-xs px-2.5 py-1 rounded-lg bg-green-500 hover:bg-green-400 text-white font-semibold disabled:opacity-50 transition-colors"
                                            >
                                                {{ editSaving ? "…" : "Save" }}
                                            </button>
                                            <button
                                                @click="cancelEdit"
                                                :disabled="editSaving"
                                                class="text-xs px-2.5 py-1 rounded-lg bg-slate-700 hover:bg-slate-600 text-slate-300 font-semibold transition-colors"
                                            >
                                                Cancel
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Display mode -->
                                    <div
                                        v-else
                                        key="display"
                                        class="flex items-center gap-2"
                                    >
                                        <span
                                            class="flex-1 min-w-0 text-right text-xs font-semibold truncate"
                                            :class="
                                                match.is_played
                                                    ? 'text-white'
                                                    : 'text-slate-500'
                                            "
                                            >{{ match.home }}</span
                                        >
                                        <div class="shrink-0 text-center w-24">
                                            <span
                                                v-if="match.is_played"
                                                class="text-sm font-bold text-white"
                                            >
                                                {{ match.home_score }} –
                                                {{ match.guest_score }}
                                            </span>
                                            <span
                                                v-else
                                                class="text-slate-600 text-xs font-medium"
                                                >vs</span
                                            >
                                        </div>
                                        <span
                                            class="flex-1 min-w-0 text-left text-xs font-semibold truncate"
                                            :class="
                                                match.is_played
                                                    ? 'text-white'
                                                    : 'text-slate-500'
                                            "
                                            >{{ match.guest }}</span
                                        >
                                        <button
                                            :disabled="!match.is_played"
                                            @click="startEdit(match)"
                                            class="shrink-0 text-slate-500 hover:text-slate-300 transition-colors text-sm leading-none disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:text-slate-500"
                                            title="Edit result"
                                        >
                                            ✎
                                        </button>
                                    </div>
                                </Transition>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";

const fixtures = ref({});
const standings = ref([]);
const prediction = ref([]);
const loading = ref(false);

const editingId = ref(null);
const editScores = ref({ home: 0, guest: 0 });
const editSaving = ref(false);

function startEdit(match) {
    editingId.value = match.id;
    editScores.value = { home: match.home_score, guest: match.guest_score };
}

function cancelEdit() {
    editingId.value = null;
}

async function saveEdit(matchId) {
    editSaving.value = true;
    try {
        const res = await fetch(`/api/matches/${matchId}`, {
            method: "PATCH",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
            },
            body: JSON.stringify({
                home_score: editScores.value.home,
                guest_score: editScores.value.guest,
            }),
        });
        if (!res.ok) throw new Error(`Server error ${res.status}`);
        const { standings: s, prediction: p } = await res.json();
        standings.value = s;
        prediction.value = p;
        for (const week of Object.values(fixtures.value)) {
            const match = week.find((m) => m.id === matchId);
            if (match) {
                match.home_score = editScores.value.home;
                match.guest_score = editScores.value.guest;
                break;
            }
        }
        editingId.value = null;
    } finally {
        editSaving.value = false;
    }
}

const TOTAL_WEEKS = 6;
const WEEK_NUMBERS = [1, 2, 3, 4, 5, 6];

const weeksPlayed = computed(
    () =>
        Object.values(fixtures.value).filter((ms) =>
            ms.every((m) => m.is_played),
        ).length,
);

const allPlayed = computed(() => weeksPlayed.value === TOTAL_WEEKS);

const predictionWithPct = computed(() => {
    if (!prediction.value.length || !standings.value.length) return [];

    const leaderPts = standings.value[0].Pts;

    const withCeiling = prediction.value.map((t) => ({
        ...t,
        maxPossiblePts: t.Pts + (6 - t.P) * 3,
    }));

    // Sum projected pts only for teams that can still mathematically reach the leader
    const eligibleTotal = withCeiling
        .filter((t) => t.maxPossiblePts >= leaderPts)
        .reduce((sum, t) => sum + t.ProjectedPts, 0);

    return [...withCeiling]
        .sort((a, b) => b.ProjectedPts - a.ProjectedPts)
        .map((t) => ({
            ...t,
            winPct:
                t.maxPossiblePts < leaderPts
                    ? 0
                    : eligibleTotal > 0
                      ? Math.round((t.ProjectedPts / eligibleTotal) * 100)
                      : 0,
        }));
});

const isMathematicallyDecided = computed(() => {
    if (!standings.value.length) return false;
    const leader = standings.value[0];
    return standings.value
        .slice(1)
        .every((team) => leader.Pts > team.Pts + (6 - team.P) * 3);
});

const isChampionDecided = computed(
    () => allPlayed.value || isMathematicallyDecided.value,
);

const finalOdds = computed(() => {
    if (isChampionDecided.value && standings.value.length) {
        return standings.value.map((row, i) => ({
            id: row.id,
            name: row.name,
            ProjectedPts: row.Pts,
            winPct: i === 0 ? 100 : 0,
        }));
    }
    return predictionWithPct.value;
});

function applyState({ fixtures: f, standings: s, prediction: p }) {
    fixtures.value = f;
    standings.value = s;
    prediction.value = p;
}

function applyWeek({ nextWeek, matches, standings: s, prediction: p }) {
    fixtures.value = { ...fixtures.value, [nextWeek]: matches };
    standings.value = s;
    prediction.value = p;
}

async function fetchState() {
    const res = await fetch("/api/state", {
        headers: { Accept: "application/json" },
    });
    applyState(await res.json());
}

async function playWeek() {
    loading.value = true;
    const res = await fetch("/api/simulate/week", {
        method: "POST",
        headers: { Accept: "application/json" },
    });
    applyWeek(await res.json());
    loading.value = false;
}

async function playAll() {
    loading.value = true;
    const res = await fetch("/api/simulate/all", {
        method: "POST",
        headers: { Accept: "application/json" },
    });
    applyState(await res.json());
    loading.value = false;
}

async function reset() {
    loading.value = true;
    const res = await fetch("/api/reset", {
        method: "POST",
        headers: { Accept: "application/json" },
    });
    applyState(await res.json());
    loading.value = false;
}

onMounted(fetchState);
</script>

<style scoped>
.match-edit-enter-active,
.match-edit-leave-active {
    transition:
        opacity 0.12s ease,
        transform 0.12s ease;
}
.match-edit-enter-from,
.match-edit-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>
