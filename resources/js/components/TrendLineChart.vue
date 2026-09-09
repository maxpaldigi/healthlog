<script setup lang="ts">
import {
    CategoryScale,
    Chart as ChartJS,
    Filler,
    Legend,
    LinearScale,
    LineElement,
    PointElement,
    Title,
    Tooltip,
} from 'chart.js';
import { computed } from 'vue';
import { Line } from 'vue-chartjs';

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler,
);

type Series = {
    label: string;
    values: Array<number | null>;
    dashed?: boolean;
    color: string;
};

const props = defineProps<{
    labels: string[];
    series: Series[];
    yLabel: string;
}>();

const prefersReducedMotion = computed(
    () =>
        typeof window !== 'undefined' &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches,
);

const chartData = computed(() => ({
    labels: props.labels,
    datasets: props.series.map((item) => ({
        label: item.label,
        data: item.values,
        borderColor: item.color,
        backgroundColor: item.color,
        borderWidth: 3,
        borderDash: item.dashed ? [8, 6] : [],
        pointRadius: 4,
        pointHoverRadius: 6,
        tension: 0.25,
        fill: false,
    })),
}));

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    animation: prefersReducedMotion.value ? false : { duration: 250 },
    plugins: {
        legend: {
            display: true,
            position: 'bottom' as const,
            labels: {
                font: { size: 14 },
                boxWidth: 18,
                padding: 16,
            },
        },
        tooltip: {
            titleFont: { size: 14 },
            bodyFont: { size: 16 },
        },
    },
    scales: {
        x: {
            ticks: { font: { size: 12 }, maxRotation: 0, autoSkip: true },
            grid: { color: 'rgba(12, 74, 110, 0.08)' },
        },
        y: {
            title: {
                display: true,
                text: props.yLabel,
                font: { size: 13 },
            },
            ticks: { font: { size: 12 } },
            grid: { color: 'rgba(12, 74, 110, 0.08)' },
        },
    },
}));
</script>

<template>
    <div class="relative h-56 w-full">
        <Line :data="chartData" :options="chartOptions" />
    </div>
</template>
