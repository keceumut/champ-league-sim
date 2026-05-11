<template>
    <div
        class="bg-slate-800/60 border border-slate-700/50 rounded-2xl p-5 backdrop-blur-sm"
    >
        <div class="flex items-center gap-3 mb-4">
            <div
                class="w-10 h-10 rounded-full flex items-center justify-center text-lg font-bold text-white shrink-0"
                :style="{ backgroundColor: team.color }"
            >
                {{ team.name[0] }}
            </div>
            <div>
                <p class="text-white font-semibold leading-tight">
                    {{ team.name }}
                </p>
                <p class="text-slate-400 text-xs">
                    Strength: {{ team.strength }}
                </p>
            </div>
            <div class="ml-auto">
                <div
                    class="text-xs font-bold px-2 py-1 w-20 text-center rounded-full"
                    :class="strengthBadgeClass"
                >
                    {{ strengthLabel }}
                </div>
            </div>
        </div>

        <div class="h-2 bg-slate-700 rounded-full mb-3 overflow-hidden">
            <div
                class="h-full rounded-full transition-all duration-300"
                :class="strengthBarColor"
                :style="{ width: strengthPercent + '%' }"
            ></div>
        </div>

        <input
            type="range"
            min="60"
            max="95"
            :value="team.strength"
            @input="$emit('update:strength', Number($event.target.value))"
            class="w-full accent-green-500 cursor-pointer"
        />
        <div class="flex justify-between text-slate-500 text-xs mt-1">
            <span>60</span>
            <span>95</span>
        </div>
    </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
    team: { type: Object, required: true },
});

defineEmits(["update:strength"]);

const strengthPercent = computed(
    () => ((props.team.strength - 60) / (95 - 60)) * 100,
);

const strengthLabel = computed(() => {
    const s = props.team.strength;
    if (s >= 88) return "Elite";
    if (s >= 78) return "Strong";
    if (s >= 68) return "Average";
    return "Weak";
});

const strengthBadgeClass = computed(() => {
    const s = props.team.strength;
    if (s >= 88) return "bg-yellow-500/20 text-yellow-300";
    if (s >= 78) return "bg-green-500/20 text-green-300";
    if (s >= 68) return "bg-blue-500/20 text-blue-300";
    return "bg-slate-600/40 text-slate-400";
});

const strengthBarColor = computed(() => {
    const s = props.team.strength;
    if (s >= 88) return "bg-yellow-400";
    if (s >= 78) return "bg-green-400";
    if (s >= 68) return "bg-blue-400";
    return "bg-slate-500";
});
</script>
