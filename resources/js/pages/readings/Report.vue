<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Check, Copy, Download, Printer, Share2 } from '@lucide/vue';
import { ref } from 'vue';
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

const copied = ref(false);
let copiedTimer: ReturnType<typeof setTimeout> | undefined;

const copyShare = async (): Promise<void> => {
    if (!props.share_url) {
        return;
    }

    try {
        await navigator.clipboard.writeText(props.share_url);
    } catch {
        const field = document.createElement('textarea');
        field.value = props.share_url;
        field.setAttribute('readonly', '');
        field.style.position = 'fixed';
        field.style.left = '-9999px';
        document.body.appendChild(field);
        field.select();
        document.execCommand('copy');
        document.body.removeChild(field);
    }

    copied.value = true;
    window.clearTimeout(copiedTimer);
    copiedTimer = window.setTimeout(() => {
        copied.value = false;
    }, 4000);
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
        </div>

        <VisitReportView :report="(report as any)" />

        <div class="no-print grid gap-2">
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
            <div v-if="share_url" class="grid gap-2">
                <Button
                    type="button"
                    variant="outline"
                    class="h-14 w-full text-lg"
                    data-test="copy-share"
                    aria-live="polite"
                    @click="copyShare"
                >
                    <Check v-if="copied" class="size-5" aria-hidden="true" />
                    <Copy v-else class="size-5" aria-hidden="true" />
                    {{ copied ? 'Link copied' : 'Copy share link' }}
                </Button>
                <p class="text-muted-foreground break-all px-1 text-base">
                    {{ share_url }}
                </p>
            </div>
            <Link
                :href="clinic()"
                class="text-foreground inline-flex min-h-12 items-center justify-center text-base underline-offset-4 hover:underline"
            >
                Add weight, HbA1c, or medications
            </Link>
        </div>
    </div>
</template>
