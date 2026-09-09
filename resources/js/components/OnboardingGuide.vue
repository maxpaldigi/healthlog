<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { complete as completeOnboarding } from '@/routes/onboarding';
import { home } from '@/routes';

type Hole = {
    top: number;
    left: number;
    width: number;
    height: number;
    radius: number;
};

type Step = {
    title: string;
    body: string;
    target: string | null;
    radius?: number;
};

const page = usePage();
const open = ref(false);
const step = ref(0);
const saving = ref(false);
const dismissed = ref(false);
const hole = ref<Hole | null>(null);
const cardRef = ref<HTMLElement | null>(null);
const nextRef = ref<HTMLButtonElement | null>(null);

const firstName = (): string => {
    const name = page.props.auth.user?.name?.trim() ?? '';

    return name.split(/\s+/)[0] || 'there';
};

const steps = (): Step[] => [
    {
        target: 'brand',
        radius: 20,
        title: `Welcome, ${firstName()}`,
        body: 'This is HealthyMe. The logo always brings you Home. Let’s point at the buttons you will use.',
    },
    {
        target: 'scan-photo',
        radius: 28,
        title: 'Take a photo',
        body: 'This big button photographs your blood pressure cuff or sugar meter. Check the numbers before they are saved.',
    },
    {
        target: 'log-manual',
        radius: 20,
        title: 'Or type the numbers',
        body: 'Use these if you would rather type. Log blood pressure from the cuff, or log sugar from the meter.',
    },
    {
        target: 'today',
        radius: 20,
        title: 'Three times a day',
        body: 'Doctors often want blood pressure in the morning, afternoon, and evening. This card shows which times you have done today.',
    },
    {
        target: 'tab-history',
        radius: 16,
        title: 'History and Trends',
        body: 'History is every reading. Trends is next to it and shows the pattern. On those pages, tap Print for doctor for a clinic visit.',
    },
    {
        target: 'tabs',
        radius: 28,
        title: 'The buttons at the bottom',
        body: 'These five stay on every screen: Home, History, the orange Scan button, Trends, and Account.',
    },
    {
        target: 'tab-account',
        radius: 16,
        title: 'Account and this tour',
        body: 'Account is settings and health profiles. You can open this tour again from there, or from How to use this app on Home.',
    },
    {
        target: null,
        title: 'You are ready',
        body: 'When a reading is 180/120 or higher, or you feel unwell, seek medical help. This app is a record, not a doctor.',
    },
];

const current = (): Step => steps()[step.value] ?? steps()[0]!;
const lastStep = (): boolean => step.value >= steps().length - 1;
const stepLabel = (): string => `Step ${step.value + 1} of ${steps().length}`;

const shouldOpen = (): boolean => {
    if (dismissed.value) {
        return false;
    }

    return page.props.auth.show_guide === true || page.url.includes('guide=1');
};

const ensureHome = (): void => {
    if (page.component === 'Home') {
        return;
    }

    router.get(home.url({ query: { guide: '1' } }), {}, { preserveScroll: false });
};

const measure = async (): Promise<void> => {
    await nextTick();

    const target = current().target;

    if (!target) {
        hole.value = null;

        return;
    }

    const element = document.querySelector<HTMLElement>(`[data-tour="${target}"]`);

    if (!element) {
        hole.value = null;

        return;
    }

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (target !== 'tabs' && target !== 'tab-history' && target !== 'tab-account' && target !== 'brand') {
        element.scrollIntoView({
            behavior: reduceMotion ? 'auto' : 'smooth',
            block: 'center',
            inline: 'nearest',
        });
        await new Promise<void>((resolve) => {
            window.setTimeout(resolve, reduceMotion ? 0 : 280);
        });
    }

    const rect = element.getBoundingClientRect();
    const pad = 8;

    hole.value = {
        top: Math.max(8, rect.top - pad),
        left: Math.max(8, rect.left - pad),
        width: Math.min(window.innerWidth - 16, rect.width + pad * 2),
        height: rect.height + pad * 2,
        radius: current().radius ?? 18,
    };
};

const start = (): void => {
    open.value = true;
    step.value = 0;
    ensureHome();
    void measure();
    window.setTimeout(() => {
        if (open.value) {
            void measure();
        }
    }, 350);
};

watch(
    () => [page.props.auth.show_guide, page.url, page.component] as const,
    () => {
        if (!shouldOpen()) {
            return;
        }

        if (!open.value) {
            start();

            return;
        }

        void measure();
    },
    { immediate: true },
);

watch(step, () => {
    if (open.value) {
        void measure().then(() => nextRef.value?.focus());
    }
});

const finish = (): void => {
    if (saving.value) {
        return;
    }

    dismissed.value = true;
    saving.value = true;
    router.post(
        completeOnboarding.url(),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                saving.value = false;
                open.value = false;
                hole.value = null;
                step.value = 0;

                if (page.url.includes('guide=1')) {
                    router.get(home.url(), {}, { replace: true, preserveState: true, preserveScroll: true });
                }
            },
        },
    );
};

const next = (): void => {
    if (lastStep()) {
        finish();

        return;
    }

    step.value += 1;
};

const back = (): void => {
    if (step.value > 0) {
        step.value -= 1;
    }
};

const onKeydown = (event: KeyboardEvent): void => {
    if (!open.value) {
        return;
    }

    if (event.key === 'Escape') {
        event.preventDefault();
        finish();
    }
};

const onResize = (): void => {
    if (open.value) {
        void measure();
    }
};

onMounted(() => {
    window.addEventListener('keydown', onKeydown);
    window.addEventListener('resize', onResize);
    window.addEventListener('scroll', onResize, true);
});

onUnmounted(() => {
    window.removeEventListener('keydown', onKeydown);
    window.removeEventListener('resize', onResize);
    window.removeEventListener('scroll', onResize, true);
});

const cardStyle = (): Record<string, string> => {
    const spotlight = hole.value;
    const gutter = 16;
    const style: Record<string, string> = {
        left: `${gutter}px`,
        right: `${gutter}px`,
        maxWidth: '28rem',
        marginLeft: 'auto',
        marginRight: 'auto',
    };

    if (!spotlight) {
        style.top = '50%';
        style.transform = 'translateY(-50%)';

        return style;
    }

    const spaceAbove = spotlight.top;
    const spaceBelow = window.innerHeight - (spotlight.top + spotlight.height);
    const cardNeeds = 280;

    if (spaceBelow >= cardNeeds) {
        style.top = `${spotlight.top + spotlight.height + 12}px`;

        return style;
    }

    if (spaceAbove >= cardNeeds) {
        style.bottom = `${window.innerHeight - spotlight.top + 12}px`;

        return style;
    }

    style.bottom = '7.5rem';

    return style;
};
</script>

<template>
    <div
        v-if="open"
        class="no-print"
        data-test="onboarding-guide"
    >
        <div
            class="fixed inset-0 z-40 bg-transparent"
            aria-hidden="true"
        />

        <div
            v-if="hole"
            class="app-tour-hole pointer-events-none fixed z-40"
            :style="{
                top: `${hole.top}px`,
                left: `${hole.left}px`,
                width: `${hole.width}px`,
                height: `${hole.height}px`,
                borderRadius: `${hole.radius}px`,
            }"
        />

        <section
            ref="cardRef"
            class="bg-background fixed z-50 grid w-[calc(100%-2rem)] gap-3 rounded-3xl border p-5 shadow-2xl"
            role="dialog"
            aria-modal="true"
            :aria-labelledby="'app-tour-title'"
            :aria-describedby="'app-tour-body'"
            :style="cardStyle()"
        >
            <p class="text-muted-foreground text-base font-medium">
                {{ stepLabel() }}
            </p>
            <h2 id="app-tour-title" class="text-2xl font-semibold leading-tight">
                {{ current().title }}
            </h2>
            <p id="app-tour-body" class="text-lg leading-relaxed">
                {{ current().body }}
            </p>

            <div class="flex justify-center gap-2" :aria-label="stepLabel()">
                <span
                    v-for="index in steps().length"
                    :key="index"
                    class="size-2.5 rounded-full"
                    :class="
                        index - 1 === step
                            ? 'bg-primary'
                            : 'bg-muted-foreground/30'
                    "
                    :aria-current="index - 1 === step ? 'step' : undefined"
                />
            </div>

            <Button
                ref="nextRef"
                type="button"
                class="h-14 w-full text-lg"
                :disabled="saving"
                data-test="onboarding-next"
                @click="next"
            >
                {{ lastStep() ? 'Start using HealthyMe' : 'Next' }}
            </Button>
            <Button
                v-if="step > 0"
                type="button"
                variant="outline"
                class="h-14 w-full text-lg"
                :disabled="saving"
                data-test="onboarding-back"
                @click="back"
            >
                Back
            </Button>
            <button
                type="button"
                class="text-muted-foreground inline-flex min-h-12 items-center justify-center text-base underline-offset-4 hover:underline"
                :disabled="saving"
                data-test="onboarding-skip"
                @click="finish"
            >
                Skip tour
            </button>
        </section>
    </div>
</template>
