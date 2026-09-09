<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Printer } from '@lucide/vue';
import VisitReportView from '@/components/VisitReportView.vue';
import { Button } from '@/components/ui/button';

defineProps<{
    report: Record<string, unknown>;
    shared: boolean;
    expires_at?: string | null;
}>();

const printPage = (): void => {
    window.print();
};
</script>

<template>
    <Head title="Shared visit report" />

    <div class="bg-background min-h-dvh px-4 py-6">
        <div class="mx-auto flex w-full max-w-3xl flex-col gap-4">
            <div class="no-print space-y-3">
                <p class="text-muted-foreground text-base">
                    Read-only shared report
                    <span v-if="expires_at"> · expires {{ expires_at }}</span>
                </p>
                <Button
                    type="button"
                    class="h-14 w-full text-lg"
                    @click="printPage"
                >
                    <Printer class="size-5" aria-hidden="true" />
                    Print or save as PDF
                </Button>
            </div>
            <VisitReportView :report="(report as any)" />
        </div>
    </div>
</template>
