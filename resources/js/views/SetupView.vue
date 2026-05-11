<template>
    <div class="h-screen flex items-center justify-center">
        <div class="w-full max-w-360 px-6 lg:px-12 xl:px-20 py-16">
            <!-- Header -->
            <div class="text-center mb-12">
                <div class="text-6xl mb-4">⚽</div>
                <h1 class="text-5xl font-bold text-white tracking-tight">
                    Champions League Sim
                </h1>
                <p class="text-slate-400 mt-3 text-base">
                    Configure team strengths then kick off the season
                </p>
            </div>

            <!-- Team Cards: 1 col → 2 col → 4 col -->
            <div
                class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-10"
            >
                <TeamCard
                    v-for="team in teams"
                    :key="team.id"
                    :team="team"
                    @update:strength="team.strength = $event"
                />
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4">
                <button
                    @click="startLeague"
                    :disabled="!!loading"
                    class="flex-1 py-5 rounded-2xl font-bold text-xl tracking-wide transition-all duration-200 bg-green-500 hover:bg-green-400 active:scale-[0.99] text-white shadow-xl shadow-green-900/40 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span
                        v-if="loading === 'league'"
                        class="flex items-center justify-center gap-2"
                    >
                        <svg
                            class="animate-spin h-5 w-5"
                            viewBox="0 0 24 24"
                            fill="none"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            />
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8v8H4z"
                            />
                        </svg>
                        Setting up league…
                    </span>
                    <span v-else>Start Fixture League →</span>
                </button>

                <button
                    @click="startBulk"
                    :disabled="!!loading"
                    class="flex-1 py-5 rounded-2xl font-bold text-xl tracking-wide transition-all duration-200 bg-slate-700 hover:bg-slate-600 active:scale-[0.99] text-slate-200 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span
                        v-if="loading === 'bulk'"
                        class="flex items-center justify-center gap-2"
                    >
                        <svg
                            class="animate-spin h-5 w-5"
                            viewBox="0 0 24 24"
                            fill="none"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            />
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8v8H4z"
                            />
                        </svg>
                        Setting up…
                    </span>
                    <span v-else>📊 Bulk Simulate</span>
                </button>
            </div>

            <p v-if="error" class="text-red-400 text-sm text-center mt-4">
                {{ error }}
            </p>
        </div>
    </div>
</template>

<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import TeamCard from "../components/TeamCard.vue";

const router = useRouter();

const randomStrength = () => Math.floor(Math.random() * (95 - 60 + 1)) + 60;

const teams = ref([
    { id: 1, name: "Chelsea", color: "#034694", strength: randomStrength() },
    { id: 2, name: "Arsenal", color: "#EF0107", strength: randomStrength() },
    { id: 3, name: "Liverpool", color: "#C8102E", strength: randomStrength() },
    {
        id: 4,
        name: "Manchester City",
        color: "#6CABDD",
        strength: randomStrength(),
    },
]);

const loading = ref("");
const error = ref("");

async function setupTeams() {
    const res = await fetch("/api/setup", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
        },
        body: JSON.stringify({
            teams: teams.value.map((t) => ({
                name: t.name,
                strength: t.strength,
            })),
        }),
    });
    if (!res.ok) throw new Error(`Server error ${res.status}`);
}

async function startLeague() {
    loading.value = "league";
    error.value = "";
    try {
        await setupTeams();
        router.push("/league");
    } catch (e) {
        error.value = e.message;
    } finally {
        loading.value = "";
    }
}

async function startBulk() {
    loading.value = "bulk";
    error.value = "";
    try {
        await setupTeams();
        router.push("/bulk");
    } catch (e) {
        error.value = e.message;
    } finally {
        loading.value = "";
    }
}
</script>
