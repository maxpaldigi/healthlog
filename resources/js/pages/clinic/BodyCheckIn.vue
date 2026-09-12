<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

const props = defineProps<{
    height_cm: string;
    can_edit_height: boolean;
    weight_unit: string;
    weight_due: boolean;
    last_weight_on: string | null;
}>();

const heightUnit = ref<'cm' | 'ft'>('cm');
</script>

<template>
    <Head title="Height and weight" />

    <div class="flex flex-1 flex-col gap-6">
        <header class="space-y-1">
            <h1 class="text-3xl font-bold">
                {{ props.height_cm === '' ? 'Height and weight' : 'Today’s weight' }}
            </h1>
            <p class="text-muted-foreground text-lg leading-relaxed">
                Optional. We show BMI as a number on Clinic and the visit
                printout. Check with your clinic. You can skip.
            </p>
            <p
                v-if="props.last_weight_on"
                class="text-muted-foreground text-base"
            >
                Last weight was {{ props.last_weight_on }}.
            </p>
        </header>

        <Form
            action="/clinic/body"
            method="post"
            class="hl-card flex flex-col gap-5 px-4 py-4"
            v-slot="{ errors, processing }"
        >
            <input type="hidden" name="height_unit" :value="heightUnit" />

            <div v-if="can_edit_height" class="grid gap-3">
                <p class="text-lg font-bold">Height</p>
                <div class="grid grid-cols-2 gap-3">
                    <Button
                        type="button"
                        class="h-12 text-base"
                        :variant="heightUnit === 'cm' ? 'default' : 'outline'"
                        @click="heightUnit = 'cm'"
                    >
                        cm
                    </Button>
                    <Button
                        type="button"
                        class="h-12 text-base"
                        :variant="heightUnit === 'ft' ? 'default' : 'outline'"
                        @click="heightUnit = 'ft'"
                    >
                        ft / in
                    </Button>
                </div>
                <div v-if="heightUnit === 'cm'" class="grid gap-2">
                    <Label for="height_cm">Height (cm)</Label>
                    <Input
                        id="height_cm"
                        name="height_cm"
                        inputmode="decimal"
                        :default-value="props.height_cm"
                        class="h-14 text-lg"
                    />
                </div>
                <div v-else class="grid grid-cols-2 gap-3">
                    <div class="grid gap-2">
                        <Label for="height_ft">Feet</Label>
                        <Input
                            id="height_ft"
                            name="height_ft"
                            inputmode="decimal"
                            class="h-14 text-lg"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="height_in">Inches</Label>
                        <Input
                            id="height_in"
                            name="height_in"
                            inputmode="decimal"
                            class="h-14 text-lg"
                        />
                    </div>
                </div>
                <InputError :message="errors.height_cm" />
            </div>

            <div class="grid gap-2">
                <Label for="weight">Weight ({{ weight_unit }})</Label>
                <Input
                    id="weight"
                    name="weight"
                    inputmode="decimal"
                    class="h-14 text-lg"
                    data-test="body-weight"
                />
                <InputError :message="errors.weight" />
            </div>

            <Button
                type="submit"
                class="h-14 w-full rounded-full text-lg"
                :disabled="processing"
                data-test="body-save"
            >
                <Spinner v-if="processing" />
                Save
            </Button>
        </Form>

        <Form action="/clinic/body/skip" method="post" v-slot="{ processing }">
            <Button
                type="submit"
                variant="outline"
                class="h-14 w-full rounded-full text-lg"
                :disabled="processing"
                data-test="body-skip"
            >
                Skip for now
            </Button>
        </Form>
    </div>
</template>
