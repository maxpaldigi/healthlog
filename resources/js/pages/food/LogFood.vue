<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Camera, Search, Trash2, Utensils } from '@lucide/vue';
import { computed, onUnmounted, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import {
    barcodeScanAvailable,
    detectBarcodeFromVideo,
} from '@/lib/barcode';
import { compressPhoto } from '@/lib/compressPhoto';
import { postPhoto } from '@/lib/postPhoto';
import { systemDateTimeLocal } from '@/lib/systemClock';
import {
    barcode as barcodeRoute,
    destroy,
    photo as photoRoute,
    search as searchRoute,
    store,
} from '@/routes/food';

type Portion = { key: string; label: string; grams: string };

type Product = {
    barcode: string;
    name: string;
    brands: string | null;
    sodium_mg_100g: string | null;
    sugars_g_100g: string | null;
    carbs_g_100g: string | null;
    nutriscore: string | null;
    serving_g: string | null;
    flags: string[];
    portions: Portion[];
    attribution: string;
    who_reference: string;
};

type Recent = {
    id: number;
    label: string;
    portion_label: string;
    portion_g: string;
    eaten_at_label: string;
    attribution: string | null;
};

type FoodHistory = {
    after_meal: string | null;
    morning_blood_pressure: string | null;
    clinic_targets: string | null;
    hint: string | null;
};

type Identified = {
    label: string;
    note: string;
    confidence: string;
    product: Product | null;
    results: Product[];
    history?: FoodHistory;
};

const props = defineProps<{
    defaults: { eaten_at: string };
    whoReference: string;
    portions: Portion[];
    recent: Recent[];
    identified: Identified | null;
    scanStatus?: string | null;
}>();

const query = ref('');
const barcode = ref('');
const results = ref<Product[]>([]);
const selected = ref<Product | null>(null);
const customLabel = ref('');
const portion = ref<Portion>(props.portions[2] ?? props.portions[0]);
const searching = ref(false);
const lookingUp = ref(false);
const scanError = ref('');
const scanning = ref(false);
const videoEl = ref<HTMLVideoElement | null>(null);
let mediaStream: MediaStream | null = null;
let scanTimer: number | null = null;
let searchTimer: number | null = null;

const source = (): 'open_food_facts' | 'custom' =>
    selected.value ? 'open_food_facts' : 'custom';

const foodName = computed(() =>
    selected.value ? selected.value.name : (customLabel.value || query.value).trim(),
);

const canSave = computed(() => foodName.value.length >= 2);

const showBarcode = ref(false);
const pendingConfirm = ref<Identified | null>(null);
const previewUrl = ref<string | null>(null);
const pickedResult = ref<Product | null>(null);
const photoLabel = ref('Photograph the food');
const preparingPhoto = ref(false);

const confirmProduct = computed(
    () => pendingConfirm.value?.product ?? pickedResult.value,
);

const revokePreview = (): void => {
    if (previewUrl.value !== null) {
        URL.revokeObjectURL(previewUrl.value);
        previewUrl.value = null;
    }
};

const stopScan = (): void => {
    scanning.value = false;

    if (scanTimer !== null) {
        window.clearInterval(scanTimer);
        scanTimer = null;
    }

    mediaStream?.getTracks().forEach((track) => track.stop());
    mediaStream = null;
};

const lookupBarcode = async (code: string): Promise<void> => {
    lookingUp.value = true;
    scanError.value = '';

    try {
        const response = await fetch(
            barcodeRoute.url({ query: { code } }),
            {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            },
        );
        const data = (await response.json()) as {
            product: Product | null;
            message?: string;
        };

        if (!data.product) {
            scanError.value = data.message ?? 'No product found for that barcode.';
            selected.value = null;

            return;
        }

        selected.value = data.product;
        portion.value =
            data.product.portions.find((item) => item.key === 'serving') ??
            data.product.portions[0];
        results.value = [];
        customLabel.value = '';
    } finally {
        lookingUp.value = false;
    }
};

const startScan = async (): Promise<void> => {
    scanError.value = '';

    if (!barcodeScanAvailable()) {
        scanError.value = 'This browser cannot open the camera. Type the barcode numbers instead.';

        return;
    }

    try {
        mediaStream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: { ideal: 'environment' } },
            audio: false,
        });
    } catch {
        scanError.value = 'Camera permission is off. Type the barcode numbers instead.';

        return;
    }

    scanning.value = true;
    await Promise.resolve();

    if (videoEl.value) {
        videoEl.value.srcObject = mediaStream;
        await videoEl.value.play();
    }

    scanTimer = window.setInterval(() => {
        void (async () => {
            if (!videoEl.value || !scanning.value) {
                return;
            }

            try {
                const code = await detectBarcodeFromVideo(videoEl.value);

                if (code) {
                    stopScan();
                    barcode.value = code;
                    await lookupBarcode(code);
                }
            } catch {
                // Keep scanning.
            }
        })();
    }, 700);
};

const runSearch = async (): Promise<void> => {
    const term = query.value.trim();

    if (term.length < 2) {
        results.value = [];

        return;
    }

    searching.value = true;

    try {
        const response = await fetch(
            searchRoute.url({ query: { q: term } }),
            {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            },
        );
        const data = (await response.json()) as { results: Product[] };
        results.value = data.results ?? [];
    } finally {
        searching.value = false;
    }
};

watch(query, () => {
    if (pendingConfirm.value) {
        return;
    }

    if (!selected.value) {
        customLabel.value = query.value;
    }

    if (searchTimer !== null) {
        window.clearTimeout(searchTimer);
    }

    searchTimer = window.setTimeout(() => {
        void runSearch();
    }, 400);
});

const choose = (product: Product): void => {
    selected.value = product;
    portion.value =
        product.portions.find((item) => item.key === 'serving') ??
        product.portions[0];
    results.value = [];
    customLabel.value = '';
    query.value = product.name;
};

const useCustomName = (): void => {
    selected.value = null;
    portion.value = props.portions.find((item) => item.key === 'serving') ?? props.portions[0];
};

const acceptIdentified = (): void => {
    const found = pendingConfirm.value;

    if (!found) {
        return;
    }

    if (found.product) {
        choose(found.product);
    } else if (pickedResult.value) {
        choose(pickedResult.value);
    } else {
        query.value = found.label;
        customLabel.value = found.label;
        results.value = found.results;
        selected.value = null;
    }

    pendingConfirm.value = null;
    pickedResult.value = null;
    photoLabel.value = 'Photograph the food';
    revokePreview();
};

const rejectIdentified = (): void => {
    pendingConfirm.value = null;
    pickedResult.value = null;
    query.value = '';
    customLabel.value = '';
    results.value = [];
    selected.value = null;
    photoLabel.value = 'Photograph the food';
    revokePreview();
};

watch(
    () => props.identified,
    (found) => {
        if (!found) {
            return;
        }

        pendingConfirm.value = found;
        pickedResult.value = found.product ?? found.results[0] ?? null;
        selected.value = null;
        results.value = [];
    },
    { immediate: true },
);

onUnmounted(() => {
    stopScan();
    revokePreview();

    if (searchTimer !== null) {
        window.clearTimeout(searchTimer);
    }
});

const confirmDelete = (event: Event): void => {
    if (!window.confirm('Remove this food?')) {
        event.preventDefault();
    }
};

const onFoodPhoto = async (event: Event): Promise<void> => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    input.value = '';

    if (!file) {
        return;
    }

    preparingPhoto.value = true;
    photoLabel.value = 'Looking at the photo…';

    try {
        const compressed = await compressPhoto(file);

        revokePreview();
        previewUrl.value = URL.createObjectURL(compressed);

        postPhoto(photoRoute.url(), compressed, {}, {
            onFinish: () => {
                preparingPhoto.value = false;
            },
            onFail: () => {
                preparingPhoto.value = false;
                photoLabel.value = 'Photograph the food';
                revokePreview();
            },
        });
    } catch {
        preparingPhoto.value = false;
        photoLabel.value = 'Photograph the food';
        revokePreview();
    }
};
</script>

<template>
    <Head title="Add food" />

    <div class="flex flex-1 flex-col gap-6">
        <h1 id="log-food-heading" class="flex items-center gap-2 text-2xl font-semibold">
            <Utensils class="size-7" aria-hidden="true" />
            Add food
        </h1>
        <p class="text-muted-foreground text-base leading-relaxed">
            Photograph the food, or type the name. Data from Open Food Facts
            (ODbL).
        </p>
        <p
            v-if="scanStatus && !pendingConfirm"
            class="rounded-2xl border border-amber-400 bg-amber-50 px-4 py-3 text-base leading-relaxed text-amber-950"
            data-test="scan-status"
            role="alert"
        >
            {{ scanStatus }}
        </p>

        <section
            v-if="pendingConfirm"
            class="hl-card space-y-4 px-4 py-5"
            data-test="food-photo-confirm"
        >
            <img
                v-if="previewUrl"
                :src="previewUrl"
                alt="Photo you took"
                class="h-48 w-full rounded-2xl object-cover"
                data-test="food-photo-preview"
            />
            <h2 class="text-2xl font-bold" data-test="food-photo-label">
                {{ pendingConfirm.label }}
            </h2>
            <p class="text-base leading-relaxed" data-test="food-photo-note">
                {{ pendingConfirm.note }}
            </p>

            <div v-if="confirmProduct" class="space-y-2" data-test="food-photo-facts">
                <p v-if="confirmProduct.brands" class="text-muted-foreground text-base">
                    {{ confirmProduct.brands }}
                </p>
                <ul class="grid gap-1 text-base">
                    <li v-if="confirmProduct.sodium_mg_100g">
                        {{ confirmProduct.sodium_mg_100g }} mg sodium per 100 g
                    </li>
                    <li v-if="confirmProduct.sugars_g_100g">
                        {{ confirmProduct.sugars_g_100g }} g sugars per 100 g
                    </li>
                    <li v-if="confirmProduct.carbs_g_100g">
                        {{ confirmProduct.carbs_g_100g }} g carbs per 100 g
                    </li>
                </ul>
                <p
                    v-for="flag in confirmProduct.flags"
                    :key="flag"
                    class="text-base leading-relaxed"
                >
                    {{ flag }}
                </p>
                <p class="text-muted-foreground text-sm">
                    {{ confirmProduct.attribution }}
                </p>
            </div>

            <ul
                v-if="!pendingConfirm.product && pendingConfirm.results.length > 1"
                class="grid gap-2"
                aria-label="Possible matches"
            >
                <li v-for="item in pendingConfirm.results" :key="item.barcode">
                    <button
                        type="button"
                        class="hl-card flex min-h-14 w-full cursor-pointer flex-col items-start px-4 py-3 text-left"
                        :class="
                            pickedResult?.barcode === item.barcode
                                ? 'ring-primary ring-2'
                                : ''
                        "
                        :data-test="`food-photo-pick-${item.barcode}`"
                        @click="pickedResult = item"
                    >
                        <span class="text-lg font-semibold">{{ item.name }}</span>
                        <span
                            v-if="item.brands"
                            class="text-muted-foreground text-base"
                        >
                            {{ item.brands }}
                        </span>
                    </button>
                </li>
            </ul>

            <div class="space-y-2 text-base leading-relaxed" data-test="food-photo-history">
                <p v-if="pendingConfirm.history?.after_meal">
                    {{ pendingConfirm.history.after_meal }}
                </p>
                <p v-if="pendingConfirm.history?.morning_blood_pressure">
                    {{ pendingConfirm.history.morning_blood_pressure }}
                </p>
                <p v-if="pendingConfirm.history?.clinic_targets">
                    {{ pendingConfirm.history.clinic_targets }}
                </p>
                <p
                    v-if="pendingConfirm.history?.hint"
                    class="text-muted-foreground"
                >
                    {{ pendingConfirm.history.hint }}
                </p>
                <p class="text-muted-foreground">
                    Check with your clinic.
                </p>
            </div>

            <div class="grid gap-2">
                <Button
                    type="button"
                    class="h-16 w-full rounded-full text-xl"
                    data-test="food-photo-yes"
                    @click="acceptIdentified"
                >
                    Yes, use this
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    class="h-14 w-full rounded-full text-lg"
                    data-test="food-photo-no"
                    @click="rejectIdentified"
                >
                    No, I’ll type it
                </Button>
            </div>
        </section>

        <div v-if="!selected && !pendingConfirm" class="grid gap-2">
            <label
                class="bg-nav text-nav-foreground flex min-h-40 cursor-pointer flex-col items-center justify-center gap-3 rounded-[1.75rem] px-5 text-center shadow-[0_16px_40px_-18px_rgb(28_25_23_/_0.45)]"
                :class="preparingPhoto ? 'opacity-70' : ''"
            >
                <span
                    class="bg-cta text-cta-foreground flex size-16 items-center justify-center rounded-full"
                    aria-hidden="true"
                >
                    <Spinner v-if="preparingPhoto" class="size-7" />
                    <Camera v-else class="size-8" />
                </span>
                <span class="text-xl font-bold">
                    {{
                        preparingPhoto
                            ? 'Looking at the photo…'
                            : photoLabel
                    }}
                </span>
                <span class="text-nav-muted text-base leading-relaxed">
                    An apple, a plate, or a packet. On a computer, choose a photo.
                </span>
                <input
                    type="file"
                    accept="image/jpeg,image/png,image/webp,image/heic,image/heif"
                    class="sr-only"
                    :aria-label="photoLabel"
                    data-test="food-photo"
                    @change="onFoodPhoto"
                />
            </label>
        </div>

        <div v-if="!selected && !pendingConfirm" class="grid gap-2">
            <Label for="food-search" class="text-base">Or type the name</Label>
            <div class="relative">
                <Search
                    class="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-5 -translate-y-1/2"
                    aria-hidden="true"
                />
                <Input
                    id="food-search"
                    v-model="query"
                    type="search"
                    autocomplete="off"
                    class="h-14 pl-11 text-lg md:text-lg"
                    placeholder="Apple, roti, yogurt…"
                    data-test="food-search"
                    aria-describedby="food-search-hint"
                    @focus="useCustomName"
                />
            </div>
            <p id="food-search-hint" class="text-muted-foreground text-base">
                Pick a match, or save the name you typed.
            </p>
            <p v-if="searching" class="text-muted-foreground text-base">
                Searching…
            </p>
            <ul
                v-if="results.length > 0"
                class="grid gap-2"
                aria-label="Search results"
            >
                <li v-for="item in results" :key="item.barcode">
                    <button
                        type="button"
                        class="hl-card flex min-h-14 w-full cursor-pointer flex-col items-start px-4 py-3 text-left"
                        :data-test="`food-result-${item.barcode}`"
                        @click="choose(item)"
                    >
                        <span class="text-lg font-semibold">{{ item.name }}</span>
                        <span
                            v-if="item.brands"
                            class="text-muted-foreground text-base"
                        >
                            {{ item.brands }}
                        </span>
                    </button>
                </li>
            </ul>

            <button
                type="button"
                class="text-foreground inline-flex min-h-14 items-center justify-center text-base font-semibold underline-offset-4 hover:underline"
                data-test="food-barcode-toggle"
                @click="
                    showBarcode = !showBarcode;
                    if (!showBarcode) {
                        stopScan();
                    }
                "
            >
                {{ showBarcode ? 'Hide barcode' : 'Packet has a barcode' }}
            </button>
        </div>

        <div v-if="!selected && !pendingConfirm && showBarcode" class="grid gap-2">
            <div class="flex flex-col gap-2 sm:flex-row">
                <Button
                    type="button"
                    class="h-14 text-base"
                    variant="outline"
                    data-test="food-scan"
                    @click="scanning ? stopScan() : startScan()"
                >
                    <Camera class="size-5" aria-hidden="true" />
                    {{ scanning ? 'Stop camera' : 'Scan barcode' }}
                </Button>
            </div>
            <video
                v-show="scanning"
                ref="videoEl"
                class="bg-nav aspect-video w-full rounded-2xl"
                autoplay
                muted
                playsinline
                aria-label="Barcode camera"
            />
            <Label for="barcode" class="text-base">Barcode numbers</Label>
            <Input
                id="barcode"
                v-model="barcode"
                inputmode="numeric"
                autocomplete="off"
                class="h-14 text-lg md:text-lg"
                data-test="food-barcode"
                aria-describedby="barcode-hint"
                @keyup.enter="lookupBarcode(barcode)"
            />
            <p id="barcode-hint" class="text-muted-foreground text-base">
                The digits printed under the bars.
            </p>
            <Button
                type="button"
                class="h-14 text-base"
                :disabled="lookingUp || barcode.replace(/\D/g, '').length < 8"
                data-test="food-barcode-lookup"
                @click="lookupBarcode(barcode)"
            >
                <Spinner v-if="lookingUp" />
                Look up barcode
            </Button>
            <p v-if="scanError" class="text-destructive text-base" role="alert">
                {{ scanError }}
            </p>
        </div>

        <section
            v-if="selected && !pendingConfirm"
            class="hl-card space-y-3 px-4 py-4"
            data-test="food-product"
        >
            <h2 class="text-xl font-semibold">{{ selected.name }}</h2>
            <p v-if="selected.brands" class="text-muted-foreground text-base">
                {{ selected.brands }}
            </p>
            <ul class="grid gap-1 text-base">
                <li v-if="selected.sodium_mg_100g">
                    {{ selected.sodium_mg_100g }} mg sodium per 100 g
                </li>
                <li v-if="selected.sugars_g_100g">
                    {{ selected.sugars_g_100g }} g sugars per 100 g
                </li>
                <li v-if="selected.carbs_g_100g">
                    {{ selected.carbs_g_100g }} g carbs per 100 g
                </li>
                <li v-if="selected.nutriscore">
                    Nutri-Score {{ selected.nutriscore.toUpperCase() }}
                </li>
            </ul>
            <p
                v-for="flag in selected.flags"
                :key="flag"
                class="text-base leading-relaxed"
            >
                {{ flag }}
            </p>
            <p class="text-muted-foreground text-sm">
                {{ selected.attribution }}
            </p>
            <button
                type="button"
                class="text-foreground inline-flex min-h-14 items-center text-base font-semibold underline-offset-4 hover:underline"
                data-test="food-change"
                @click="useCustomName"
            >
                Not this food
            </button>
        </section>

        <Form
            v-if="!pendingConfirm"
            v-bind="store.form()"
            v-slot="{ errors, processing }"
            class="flex flex-1 flex-col gap-6"
            aria-labelledby="log-food-heading"
        >
            <input type="hidden" name="source" :value="source()" />
            <input
                v-if="selected"
                type="hidden"
                name="source_ref"
                :value="selected.barcode"
            />
            <div v-if="!selected" class="grid gap-2">
                <input type="hidden" name="label" :value="foodName" />
            </div>

            <fieldset class="grid gap-2">
                <legend class="text-base font-medium">How much?</legend>
                <input type="hidden" name="portion_g" :value="portion.grams" />
                <input type="hidden" name="portion_label" :value="portion.label" />
                <div
                    class="grid grid-cols-2 gap-2"
                    role="radiogroup"
                    aria-label="How much?"
                >
                    <Button
                        v-for="option in selected?.portions ?? portions"
                        :key="option.key"
                        type="button"
                        role="radio"
                        :aria-checked="portion.key === option.key"
                        class="h-14 text-base"
                        :variant="portion.key === option.key ? 'default' : 'outline'"
                        :data-test="`portion-${option.key}`"
                        @click="portion = option"
                    >
                        {{ option.label }}
                    </Button>
                </div>
                <InputError :message="errors.portion_g" />
            </fieldset>

            <div class="grid gap-2">
                <Label for="eaten_at" class="text-base">When</Label>
                <Input
                    id="eaten_at"
                    type="datetime-local"
                    name="eaten_at"
                    :default-value="defaults.eaten_at || systemDateTimeLocal()"
                    required
                    aria-required="true"
                    class="h-14 text-lg md:text-lg"
                    :aria-invalid="errors.eaten_at ? true : undefined"
                    aria-describedby="eaten-at-error"
                />
                <InputError id="eaten-at-error" :message="errors.eaten_at" />
            </div>

            <p class="text-muted-foreground text-base leading-relaxed">
                {{ whoReference }}
            </p>
            <InputError :message="errors.label" />

            <Button
                type="submit"
                class="mt-auto h-16 w-full rounded-full text-xl"
                :disabled="processing || !canSave"
                data-test="save-food"
            >
                <Spinner v-if="processing" />
                Save food
            </Button>
        </Form>

        <section v-if="recent.length > 0 && !pendingConfirm" class="space-y-3">
            <h2 class="text-xl font-bold">Recently eaten</h2>
            <ul class="grid gap-2">
                <li
                    v-for="item in recent"
                    :key="item.id"
                    class="hl-card flex items-center justify-between gap-3 px-4 py-3"
                >
                    <div>
                        <p class="text-lg font-semibold">{{ item.label }}</p>
                        <p class="text-muted-foreground text-base">
                            {{ item.portion_label }} · {{ item.portion_g }} g ·
                            {{ item.eaten_at_label }}
                        </p>
                        <p
                            v-if="item.attribution"
                            class="text-muted-foreground text-sm"
                        >
                            {{ item.attribution }}
                        </p>
                    </div>
                    <Form v-bind="destroy.form(item.id)">
                        <Button
                            type="submit"
                            variant="ghost"
                            class="h-14 min-w-14"
                            data-test="delete-food"
                            @click="confirmDelete"
                        >
                            <Trash2 class="size-5" aria-hidden="true" />
                            <span class="sr-only">Remove {{ item.label }}</span>
                        </Button>
                    </Form>
                </li>
            </ul>
        </section>
    </div>
</template>
