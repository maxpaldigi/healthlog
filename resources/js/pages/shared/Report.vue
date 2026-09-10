<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Download, Printer } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import VisitReportView from '@/components/VisitReportView.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type RespondForm = {
    glucose_unit: string;
    glucose_low: string;
    glucose_high: string;
    instruction: string;
};

const props = defineProps<{
    report: Record<string, unknown>;
    shared: boolean;
    share_token?: string;
    expires_at?: string | null;
    can_respond?: boolean;
    respond?: RespondForm | null;
}>();

const printPage = (): void => {
    window.print();
};

const pdfUrl = (): string =>
    `/shared/${encodeURIComponent(props.share_token ?? '')}/pdf`;

const fhirUrl = (): string =>
    `/shared/${encodeURIComponent(props.share_token ?? '')}/fhir`;

const respondUrl = (): string =>
    `/shared/${encodeURIComponent(props.share_token ?? '')}/respond`;
</script>

<template>
    <Head title="Shared visit report" />

    <div class="bg-background min-h-dvh px-4 py-6">
        <div class="mx-auto flex w-full max-w-3xl flex-col gap-4">
            <div class="no-print space-y-3">
                <p class="text-muted-foreground text-base">
                    {{
                        can_respond
                            ? 'Shared report · you can leave a note on this log'
                            : 'Read-only shared report'
                    }}
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
                <a
                    v-if="share_token"
                    :href="fhirUrl()"
                    class="inline-flex h-14 w-full items-center justify-center gap-2 rounded-xl border text-lg font-medium"
                    data-test="download-fhir"
                >
                    <Download class="size-5" aria-hidden="true" />
                    Download FHIR JSON
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
            <Form
                v-if="can_respond && share_token"
                :action="respondUrl()"
                method="post"
                class="no-print hl-card grid gap-4 px-4 py-4"
                data-test="clinic-reply-form"
                v-slot="{ errors, processing }"
            >
                <h2 class="text-lg font-semibold">Note for this log</h2>
                <p class="text-muted-foreground text-base leading-relaxed">
                    Optional. The person whose readings these are will see this
                    at home and on the visit report. This is their record, not a
                    device output.
                </p>
                <label class="flex min-h-14 items-start gap-3 text-base">
                    <input
                        type="checkbox"
                        name="acknowledged"
                        value="1"
                        required
                        class="mt-1 size-6"
                        data-test="clinic-acknowledged"
                    />
                    I have looked at this report
                </label>
                <InputError :message="errors.acknowledged" />
                <div class="grid gap-2">
                    <Label for="clinic_instruction">Note (optional)</Label>
                    <textarea
                        id="clinic_instruction"
                        name="instruction"
                        maxlength="500"
                        rows="4"
                        class="border-input bg-background min-h-28 w-full rounded-xl border px-3 py-3 text-lg"
                        :default-value="respond?.instruction ?? ''"
                        data-test="clinic-instruction"
                    />
                    <InputError :message="errors.instruction" />
                </div>
                <p class="text-base font-medium">
                    Sugar targets for this log (optional, {{ respond?.glucose_unit }})
                </p>
                <div class="grid grid-cols-2 gap-3">
                    <div class="grid gap-2">
                        <Label for="clinic_glucose_low">Low</Label>
                        <Input
                            id="clinic_glucose_low"
                            name="glucose_low"
                            inputmode="decimal"
                            class="h-14 text-lg"
                            :default-value="respond?.glucose_low ?? ''"
                            data-test="clinic-glucose-low"
                        />
                        <InputError :message="errors.glucose_low" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="clinic_glucose_high">High</Label>
                        <Input
                            id="clinic_glucose_high"
                            name="glucose_high"
                            inputmode="decimal"
                            class="h-14 text-lg"
                            :default-value="respond?.glucose_high ?? ''"
                            data-test="clinic-glucose-high"
                        />
                        <InputError :message="errors.glucose_high" />
                    </div>
                </div>
                <Button
                    type="submit"
                    class="h-14 text-lg"
                    :disabled="processing"
                    data-test="clinic-reply-save"
                >
                    Save note
                </Button>
            </Form>
        </div>
    </div>
</template>
