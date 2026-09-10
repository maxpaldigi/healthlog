<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Download, Printer } from '@lucide/vue';
import VisitReportView from '@/components/VisitReportView.vue';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    report: Record<string, unknown>;
    shared: boolean;
    share_token?: string;
    expires_at?: string | null;
}>();

const printPage = (): void => {
    window.print();
};

const pdfUrl = (): string =>
    `/shared/${encodeURIComponent(props.share_token ?? '')}/pdf`;
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
                <a
                    v-if="share_token"
                    :href="pdfUrl()"
                    class="bg-primary text-primary-foreground inline-flex h-14 w-full items-center justify-center gap-2 rounded-xl text-lg font-medium"
                    data-test="download-pdf"
                >
                    <Download class="size-5" aria-hidden="true" />
                    Download PDF
                </a>
                <Button
                    type="button"
                    variant="outline"
                    class="h-14 w-full text-lg"
                    @click="printPage"
                >
                    <Printer class="size-5" aria-hidden="true" />
                    Print
                </Button>
            </div>
            <VisitReportView :report="(report as any)" />
        </div>
    </div>
</template>
