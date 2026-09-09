<script setup lang="ts">
import {
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    Legend,
    LinearScale,
    Title,
    Tooltip,
} from 'chart.js';
import { computed } from 'vue';
import { Bar } from 'vue-chartjs';

ChartJS.register(
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    Tooltip,
    Legend,
);

type Series = {
    label: string;
    values: Array<number | null>;
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
        backgroundColor: item.color,
        borderColor: item.color,
        borderWidth: 1,
        borderRadius: 8,
        maxBarThickness: 36,
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
            ticks: { font: { size: 13 } },
            grid: { display: false },
        },
        y: {
            beginAtZero: true,
            title: {
                display: true,
                text: props.yLabel,
                font: { size: 13 },
            },
            ticks: { font: { size: 12 }, precision: 0 },
            grid: { color: 'rgba(12, 74, 110, 0.08)' },
        },
    },
}));
</script>

<template>
    <div class="relative h-56 w-full">
        <Bar :data="chartData" :options="chartOptions" />
    </div>
</template>
