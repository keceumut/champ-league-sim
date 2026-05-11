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
                <h1 class="text-2xl font-bold text-white">
                    Bulk League Simulation
                </h1>
            </div>

            <!-- Controls -->
            <div
                class="bg-slate-800/60 border border-slate-700/50 rounded-2xl p-6 mb-6 backdrop-blur-sm"
            >
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5">
                    <div class="flex-1 w-full">
                        <div class="flex justify-between mb-2">
                            <label class="text-slate-300 text-sm font-medium"
                                >Number of simulations</label
                            >
                            <span class="text-white font-bold text-sm"
                                >{{ count }}</span
                            >
                        </div>
                        <input
                            v-model.number="count"
                            type="range"
                            min="10"
                            max="100"
                            step="5"
                            class="w-full h-2 bg-slate-700 rounded-full appearance-none cursor-pointer accent-green-500"
                        />
                        <div
                            class="flex justify-between text-slate-600 text-xs mt-1"
                        >
                            <span>10</span><span>100</span>
                        </div>
                    </div>
                    <button
                        @click="runBulk"
                        :disabled="loading"
                        class="shrink-0 px-8 py-3 rounded-xl font-semibold text-sm transition-all duration-200 bg-green-500 hover:bg-green-400 active:scale-95 text-white disabled:opacity-40 disabled:cursor-not-allowed"
                    >
                        {{ loading ? "Simulating…" : "Run Simulations" }}
                    </button>
                </div>
                <p v-if="error" class="text-red-400 text-sm mt-3">{{ error }}</p>
            </div>

            <!-- Results -->
            <template v-if="summary.length">
                <!-- Summary table -->
                <div
                    class="bg-slate-800/60 border border-slate-700/50 rounded-2xl p-5 mb-6 backdrop-blur-sm"
                >
                    <h2
                        class="text-slate-300 text-xs font-semibold uppercase tracking-widest mb-4"
                    >
                        Results across {{ simulations.length }} simulations
                    </h2>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm min-w-[520px]">
                            <thead>
                                <tr class="text-slate-500 text-xs">
                                    <th class="text-left pb-2 font-medium">#</th>
                                    <th class="text-left pb-2 font-medium pl-2">
                                        Team
                                    </th>
                                    <th class="text-center pb-2 font-medium w-12">
                                        Str
                                    </th>
                                    <th class="text-center pb-2 font-medium w-14">
                                        Wins
                                    </th>
                                    <th class="text-center pb-2 font-medium w-14">
                                        Win%
                                    </th>
                                    <th class="text-center pb-2 font-medium w-12">
                                        2nd
                                    </th>
                                    <th class="text-center pb-2 font-medium w-12">
                                        3rd
                                    </th>
                                    <th class="text-center pb-2 font-medium w-12">
                                        Last
                                    </th>
                                    <th class="text-center pb-2 font-medium w-16">
                                        Avg Pts
                                    </th>
                                    <th class="text-center pb-2 font-medium w-16">
                                        Avg GD
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(team, i) in summary"
                                    :key="team.id"
                                    class="border-t border-slate-700/40"
                                    :class="
                                        i === 0 ? 'text-white' : 'text-slate-300'
                                    "
                                >
                                    <td class="py-2 text-slate-500 font-medium">
                                        {{ i + 1 }}
                                    </td>
                                    <td class="py-2 pl-2 font-semibold">
                                        {{ team.name }}
                                    </td>
                                    <td class="py-2 text-center text-slate-400">
                                        {{ team.strength }}
                                    </td>
                                    <td class="py-2 text-center font-bold"
                                        :class="i === 0 ? 'text-green-400' : 'text-white'">
                                        {{ team.wins }}
                                    </td>
                                    <td class="py-2 text-center">
                                        <span
                                            class="px-2 py-0.5 rounded-full text-xs font-bold"
                                            :class="winPctClass(team.win_pct)"
                                        >
                                            {{ team.win_pct }}%
                                        </span>
                                    </td>
                                    <td class="py-2 text-center text-slate-400">
                                        {{ team.runner_up }}
                                    </td>
                                    <td class="py-2 text-center text-slate-400">
                                        {{ team.third }}
                                    </td>
                                    <td class="py-2 text-center text-slate-400">
                                        {{ team.last }}
                                    </td>
                                    <td class="py-2 text-center text-slate-400">
                                        {{ team.avg_pts }}
                                    </td>
                                    <td
                                        class="py-2 text-center"
                                        :class="
                                            team.avg_gd > 0
                                                ? 'text-green-400'
                                                : team.avg_gd < 0
                                                  ? 'text-red-400'
                                                  : 'text-slate-400'
                                        "
                                    >
                                        {{ team.avg_gd > 0 ? "+" : "" }}{{ team.avg_gd }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Win% bar chart -->
                    <div class="mt-5 space-y-2">
                        <div v-for="team in summary" :key="'bar-' + team.id">
                            <div class="flex items-center gap-3">
                                <span class="text-slate-300 text-xs w-28 truncate shrink-0">
                                    {{ team.name }}
                                </span>
                                <div class="flex-1 h-3 bg-slate-700 rounded-full overflow-hidden">
                                    <div
                                        class="h-full rounded-full transition-all duration-700"
                                        :class="barColor(summary.indexOf(team))"
                                        :style="{ width: team.win_pct + '%' }"
                                    ></div>
                                </div>
                                <span class="text-xs text-slate-400 w-10 text-right shrink-0">
                                    {{ team.win_pct }}%
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Individual simulations accordion -->
                <div
                    class="bg-slate-800/60 border border-slate-700/50 rounded-2xl overflow-hidden backdrop-blur-sm"
                >
                    <div class="px-5 py-4 border-b border-slate-700/50">
                        <h2 class="text-slate-300 text-xs font-semibold uppercase tracking-widest">
                            Individual Simulations ({{ simulations.length }})
                        </h2>
                    </div>

                    <div class="divide-y divide-slate-700/40">
                        <div
                            v-for="sim in simulations"
                            :key="sim.index"
                        >
                            <!-- Sim header (clickable) -->
                            <button
                                class="w-full flex items-center justify-between px-5 py-3 hover:bg-slate-700/30 transition-colors text-left"
                                @click="toggleSim(sim.index)"
                            >
                                <div class="flex items-center gap-3">
                                    <span class="text-slate-500 text-xs w-12 shrink-0">
                                        #{{ sim.index }}
                                    </span>
                                    <span class="text-white font-semibold text-sm">
                                        {{ sim.champion }}
                                    </span>
                                    <span class="text-slate-500 text-xs">champion</span>
                                </div>
                                <div class="flex items-center gap-4">
                                    <!-- mini standings -->
                                    <div class="hidden sm:flex items-center gap-1">
                                        <span
                                            v-for="(row, i) in sim.standings"
                                            :key="row.id"
                                            class="text-xs px-2 py-0.5 rounded"
                                            :class="i === 0 ? 'bg-green-500/20 text-green-400' : 'text-slate-500'"
                                        >
                                            {{ row.name.split(' ')[0] }} {{ row.Pts }}
                                        </span>
                                    </div>
                                    <span class="text-slate-500 text-xs shrink-0">
                                        {{ openSims.has(sim.index) ? "▲" : "▼" }}
                                    </span>
                                </div>
                            </button>

                            <!-- Expanded: weeks accordion -->
                            <div
                                v-if="openSims.has(sim.index)"
                                class="bg-slate-900/40 px-5 pb-4"
                            >
                                <!-- Final standings for this sim -->
                                <div class="pt-4 pb-3 mb-3 border-b border-slate-700/40">
                                    <p class="text-slate-500 text-xs font-semibold uppercase tracking-widest mb-2">
                                        Final Standings
                                    </p>
                                    <div class="flex gap-4 flex-wrap">
                                        <div
                                            v-for="(row, i) in sim.standings"
                                            :key="row.id"
                                            class="flex items-center gap-1.5"
                                        >
                                            <span class="text-slate-600 text-xs">{{ i + 1 }}.</span>
                                            <span
                                                class="text-sm font-semibold"
                                                :class="i === 0 ? 'text-green-400' : 'text-slate-300'"
                                            >
                                                {{ row.name }}
                                            </span>
                                            <span class="text-xs text-slate-500">
                                                {{ row.Pts }}pts {{ row.GD > 0 ? "+" : "" }}{{ row.GD }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Week-by-week accordion -->
                                <div class="space-y-1">
                                    <div
                                        v-for="(matches, week) in sim.weeks"
                                        :key="week"
                                    >
                                        <button
                                            class="w-full flex items-center justify-between py-1.5 text-left"
                                            @click="toggleWeek(sim.index, week)"
                                        >
                                            <span class="text-slate-400 text-xs font-semibold uppercase tracking-widest">
                                                Week {{ week }}
                                            </span>
                                            <span class="text-slate-600 text-xs">
                                                {{ openWeeks.has(sim.index + '-' + week) ? "▲" : "▼" }}
                                            </span>
                                        </button>

                                        <div
                                            v-if="openWeeks.has(sim.index + '-' + week)"
                                            class="space-y-1 mb-2"
                                        >
                                            <div
                                                v-for="(match, mi) in matches"
                                                :key="mi"
                                                class="flex items-center gap-3 text-sm bg-slate-800/60 rounded-lg px-3 py-2"
                                            >
                                                <span class="flex-1 text-right text-white font-medium text-xs">
                                                    {{ match.home }}
                                                </span>
                                                <span class="text-white font-bold text-sm shrink-0 w-12 text-center">
                                                    {{ match.home_score }} – {{ match.guest_score }}
                                                </span>
                                                <span class="flex-1 text-left text-white font-medium text-xs">
                                                    {{ match.guest }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Empty state before first run -->
            <div
                v-else-if="!loading"
                class="text-center py-20 text-slate-600"
            >
                <div class="text-4xl mb-3">📊</div>
                <p class="text-sm">Configure the count and hit Run Simulations</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive } from "vue";

const count = ref(50);
const loading = ref(false);
const error = ref("");
const summary = ref([]);
const simulations = ref([]);

const openSims = reactive(new Set());
const openWeeks = reactive(new Set());

function winPctClass(pct) {
    if (pct >= 50) return "bg-green-500/20 text-green-400";
    if (pct >= 25) return "bg-blue-500/20 text-blue-400";
    if (pct >= 10) return "bg-slate-600/40 text-slate-400";
    return "bg-red-500/10 text-red-400";
}

function barColor(i) {
    return ["bg-green-500", "bg-blue-500", "bg-slate-400", "bg-slate-600"][i] ?? "bg-slate-600";
}

function toggleSim(index) {
    if (openSims.has(index)) {
        openSims.delete(index);
    } else {
        openSims.add(index);
    }
}

function toggleWeek(simIndex, week) {
    const key = simIndex + "-" + week;
    if (openWeeks.has(key)) {
        openWeeks.delete(key);
    } else {
        openWeeks.add(key);
    }
}

async function runBulk() {
    loading.value = true;
    error.value = "";
    openSims.clear();
    openWeeks.clear();

    try {
        const res = await fetch("/api/bulk-simulate", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
            },
            body: JSON.stringify({ count: count.value }),
        });

        if (!res.ok) {
            const data = await res.json();
            throw new Error(data.message ?? `Server error ${res.status}`);
        }

        const data = await res.json();
        summary.value = data.summary;
        simulations.value = data.simulations;
    } catch (e) {
        error.value = e.message;
    } finally {
        loading.value = false;
    }
}
</script>
