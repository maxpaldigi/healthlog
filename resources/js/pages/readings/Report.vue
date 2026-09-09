<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Download, Printer, Share2 } from '@lucide/vue';
import HistoryDateRange from '@/components/HistoryDateRange.vue';
import VisitReportView from '@/components/VisitReportView.vue';
import { Button } from '@/components/ui/button';
import { report } from '@/routes';
import { index as clinic } from '@/routes/clinic';
import { store as storeShare } from '@/routes/share-links';

type Filters = {
    from: string;
    to: string;
    from_label: string;
    to_label: string;
    range_label: string;
    today: string;
    earliest: string;
    preset: string | null;
};

const props = defineProps<{
    report: Record<string, unknown>;
    filters: Filters;
    presets: { key: string; label: string; from: string; to: string }[];
    month: {
        label: string;
        prev_label: string;
        next_label: string;
        prev_from: string;
        prev_to: string;
        next_from: string;
        next_to: string;
        can_next: boolean;
    };
    shared?: boolean;
    share_url?: string | null;
    expires_at?: string | null;
}>();

const printPage = (): void => {
    window.print();
};

const csvUrl = (): string =>
    `/report.csv?from=${encodeURIComponent(props.filters.from)}&to=${encodeURIComponent(props.filters.to)}`;

const copyShare = async (): Promise<void> => {
    if (!props.share_url) {
        return;
    }

    await navigator.clipboard.writeText(props.share_url);
};
</script>

<template>
    <Head title="Visit report" />

    <div class="flex flex-1 flex-col gap-4">
        <div class="no-print space-y-3">
            <h1 class="text-2xl font-semibold">Print for doctor</h1>
            <HistoryDateRange
                :filters="filters"
                :presets="presets"
                :month="month"
                :url-for="(from, to) => report.url({ query: { from, to } })"
            />
            <div class="grid gap-2">
                <Button
                    type="button"
                    class="h-14 w-full text-lg"
                    data-test="print-report"
                    @click="printPage"
                >
                    <Printer class="size-5" aria-hidden="true" />
                    Print or save as PDF
                </Button>
                <a
                    :href="csvUrl()"
                    class="inline-flex h-14 w-full items-center justify-center gap-2 rounded-xl border text-lg font-medium"
                    data-test="download-csv"
                >
                    <Download class="size-5" aria-hidden="true" />
                    Download CSV
                </a>
                <Form
                    v-bind="storeShare.form()"
                    class="grid gap-2"
                    v-slot="{ processing }"
                >
                    <input type="hidden" name="from" :value="filters.from" />
                    <input type="hidden" name="to" :value="filters.to" />
                    <input type="hidden" name="days" value="7" />
                    <Button
                        type="submit"
                        variant="outline"
                        class="h-14 w-full text-lg"
                        :disabled="processing"
                        data-test="create-share"
                    >
                        <Share2 class="size-5" aria-hidden="true" />
                        Make a 7-day share link
                    </Button>
                </Form>
                <p v-if="share_url" class="break-all text-base">
                    <button
                        type="button"
                        class="text-primary underline"
                        data-test="copy-share"
                        @click="copyShare"
                    >
                        Copy share link
                    </button>
                    <span class="text-muted-foreground mt-1 block">{{ share_url }}</span>
                </p>
                <Link
                    :href="clinic()"
                    class="text-foreground inline-flex min-h-12 items-center justify-center text-base underline-offset-4 hover:underline"
                >
                    Add weight, HbA1c, or medications
                </Link>
            </div>
        </div>

        <VisitReportView :report="(report as any)" />
    </div>
</template>
