<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Bell, HeartPulse } from '@lucide/vue';
import HealthProfileController from '@/actions/App/Http/Controllers/Settings/HealthProfileController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { current as switchProfile } from '@/routes/profiles';
import { edit } from '@/routes/health-profiles';
import { store as invite } from '@/routes/health-profiles/invites';
import { destroy as revokeMember } from '@/routes/health-profiles/members';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Health profiles',
                href: edit(),
            },
        ],
    },
});

defineProps<{
    current: {
        id: number;
        name: string;
        timezone: string;
        glucose_unit: string;
        glucose_unit_label: string;
        weight_unit: string;
        weight_unit_label: string;
        is_default: boolean;
        glucose_low: string;
        glucose_high: string;
        glucose_target_set_by: string;
        reminders_enabled: boolean;
        reminder_morning: string;
        reminder_afternoon: string;
        reminder_evening: string;
    };
    profiles: {
        id: number;
        name: string;
        is_default: boolean;
        is_current: boolean;
    }[];
    timezones: Record<string, string>;
    glucoseUnits: { value: string; label: string }[];
    weightUnits: { value: string; label: string }[];
    canDelete: boolean;
    role?: string | null;
    canManage?: boolean;
    canInvite?: boolean;
    members?: {
        id: number;
        email: string;
        role: string;
        role_label: string;
        accepted: boolean;
        can_revoke: boolean;
    }[];
}>();
</script>

<template>
    <Head title="Health profiles" />

    <div class="flex flex-col space-y-8">
        <Heading
            variant="small"
            title="Health profiles"
            description="One phone can keep logs for you and a parent. Times use this profile’s timezone."
        />

        <div v-if="profiles.length > 1" class="flex flex-wrap gap-2">
            <Form
                v-for="item in profiles"
                :key="item.id"
                v-bind="switchProfile.form({ profile: item.id })"
            >
                <Button
                    type="submit"
                    class="h-12 px-4 text-base"
                    :variant="item.is_current ? 'default' : 'outline'"
                >
                    {{ item.name }}
                </Button>
            </Form>
        </div>

        <section class="space-y-3" data-test="access-list">
            <h2 class="text-lg font-semibold">Who can see this log</h2>
            <p class="text-muted-foreground text-base leading-relaxed">
                Helpers can add readings. Only the owner can change settings or
                remove access. Silent monitoring is not allowed.
            </p>
            <ul class="space-y-2">
                <li
                    v-for="member in members ?? []"
                    :key="member.id"
                    class="flex min-h-12 flex-wrap items-center justify-between gap-2 rounded-2xl border px-3 py-2"
                >
                    <span class="text-base">
                        {{ member.email }}
                        <span class="text-muted-foreground">
                            · {{ member.role_label }}
                            <template v-if="!member.accepted">
                                · invite sent</template
                            >
                        </span>
                    </span>
                    <Form
                        v-if="member.can_revoke"
                        v-bind="
                            revokeMember.form({
                                profile: current.id,
                                member: member.id,
                            })
                        "
                    >
                        <Button
                            type="submit"
                            variant="outline"
                            class="h-11 text-base"
                            data-test="revoke-member"
                        >
                            Remove access
                        </Button>
                    </Form>
                </li>
            </ul>
            <Form
                v-if="canInvite"
                v-bind="invite.form(current.id)"
                class="grid gap-2"
                v-slot="{ errors, processing }"
            >
                <Label for="invite_email">Invite by email</Label>
                <Input
                    id="invite_email"
                    name="email"
                    type="email"
                    required
                    class="h-12 text-lg"
                    placeholder="family@example.com"
                    data-test="invite-email"
                />
                <InputError :message="errors.email" />
                <Button
                    type="submit"
                    variant="outline"
                    class="h-12"
                    :disabled="processing"
                    data-test="send-invite"
                >
                    Send invite
                </Button>
            </Form>
        </section>

        <p
            v-if="!canManage"
            class="text-muted-foreground text-base leading-relaxed"
        >
            Only the owner can change targets, reminders, or delete this log.
        </p>

        <Form
            v-if="canManage"
            v-bind="HealthProfileController.update.form(current.id)"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="name">Name on this log</Label>
                <Input
                    id="name"
                    name="name"
                    :default-value="current.name"
                    required
                    class="h-12 text-lg"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="timezone">Country</Label>
                <select
                    id="timezone"
                    name="timezone"
                    :value="current.timezone"
                    class="border-input bg-background h-12 w-full rounded-md border px-3 text-lg"
                >
                    <option
                        v-for="(label, value) in timezones"
                        :key="value"
                        :value="value"
                    >
                        {{ label }}
                    </option>
                </select>
                <InputError :message="errors.timezone" />
            </div>

            <div class="grid gap-2">
                <Label for="glucose_unit">Sugar units</Label>
                <select
                    id="glucose_unit"
                    name="glucose_unit"
                    :value="current.glucose_unit"
                    class="border-input bg-background h-12 w-full rounded-md border px-3 text-lg"
                >
                    <option
                        v-for="unit in glucoseUnits"
                        :key="unit.value"
                        :value="unit.value"
                    >
                        {{ unit.label }}
                    </option>
                </select>
            </div>

            <div class="grid gap-2">
                <Label for="weight_unit">Weight units</Label>
                <select
                    id="weight_unit"
                    name="weight_unit"
                    :value="current.weight_unit"
                    class="border-input bg-background h-12 w-full rounded-md border px-3 text-lg"
                >
                    <option
                        v-for="unit in weightUnits"
                        :key="unit.value"
                        :value="unit.value"
                    >
                        {{ unit.label }}
                    </option>
                </select>
            </div>

            <div class="space-y-3">
                <p class="flex items-center gap-2 text-base font-medium">
                    <HeartPulse class="size-5" aria-hidden="true" />
                    Your sugar targets
                </p>
                <p class="text-muted-foreground text-sm leading-relaxed">
                    Optional. These only highlight numbers on your log. They are
                    not medical advice. Many people use 70 and 180 mg/dL as a
                    starting point after asking their clinic.
                </p>
                <p
                    v-if="current.glucose_target_set_by === 'clinician'"
                    class="text-base leading-relaxed"
                    data-test="clinic-targets-note"
                >
                    These numbers were last saved from a clinic reply. Saving
                    this form keeps them as yours.
                </p>
                <div class="grid grid-cols-2 gap-3">
                    <div class="grid gap-2">
                        <Label for="glucose_low">Low</Label>
                        <Input
                            id="glucose_low"
                            name="glucose_low"
                            inputmode="decimal"
                            :default-value="current.glucose_low"
                            class="h-12 text-lg"
                        />
                        <InputError :message="errors.glucose_low" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="glucose_high">High</Label>
                        <Input
                            id="glucose_high"
                            name="glucose_high"
                            inputmode="decimal"
                            :default-value="current.glucose_high"
                            class="h-12 text-lg"
                        />
                        <InputError :message="errors.glucose_high" />
                    </div>
                </div>
            </div>

            <div class="space-y-3">
                <p class="flex items-center gap-2 text-base font-medium">
                    <Bell class="size-5" aria-hidden="true" />
                    Reminders
                </p>
                <label class="flex items-center gap-2 text-base">
                    <input
                        type="checkbox"
                        name="reminders_enabled"
                        value="1"
                        :checked="current.reminders_enabled"
                        class="size-5"
                    />
                    Email me if a blood pressure slot is still empty
                </label>
                <div class="grid gap-3">
                    <div class="grid gap-2">
                        <Label for="reminder_morning">Morning</Label>
                        <Input
                            id="reminder_morning"
                            type="time"
                            name="reminder_morning"
                            :default-value="current.reminder_morning"
                            required
                            class="h-12 text-lg"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="reminder_afternoon">Afternoon</Label>
                        <Input
                            id="reminder_afternoon"
                            type="time"
                            name="reminder_afternoon"
                            :default-value="current.reminder_afternoon"
                            required
                            class="h-12 text-lg"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="reminder_evening">Evening</Label>
                        <Input
                            id="reminder_evening"
                            type="time"
                            name="reminder_evening"
                            :default-value="current.reminder_evening"
                            required
                            class="h-12 text-lg"
                        />
                    </div>
                </div>
                <p class="text-muted-foreground text-sm">
                    Times use {{ current.timezone }}. The Home screen can also
                    show a banner and a browser notification when you leave it
                    open.
                </p>
            </div>

            <Button :disabled="processing" class="h-12 text-base">
                Save this profile
            </Button>
        </Form>

        <Form
            v-if="canManage"
            v-bind="HealthProfileController.store.form()"
            class="space-y-4 border-t pt-6"
            v-slot="{ errors, processing }"
        >
            <h2 class="text-lg font-semibold">Add another person</h2>
            <p class="text-muted-foreground text-base">
                Use this for a parent or anyone you help. Their readings stay on
                their own log.
            </p>
            <div class="grid gap-2">
                <Label for="new_name">Name</Label>
                <Input
                    id="new_name"
                    name="name"
                    required
                    class="h-12 text-lg"
                />
                <InputError :message="errors.name" />
            </div>
            <div class="grid gap-2">
                <Label for="new_timezone">Country</Label>
                <select
                    id="new_timezone"
                    name="timezone"
                    :value="current.timezone"
                    class="border-input bg-background h-12 w-full rounded-md border px-3 text-lg"
                >
                    <option
                        v-for="(label, value) in timezones"
                        :key="value"
                        :value="value"
                    >
                        {{ label }}
                    </option>
                </select>
            </div>
            <input
                type="hidden"
                name="glucose_unit"
                :value="current.glucose_unit"
            />
            <input
                type="hidden"
                name="weight_unit"
                :value="current.weight_unit"
            />
            <Button
                type="submit"
                variant="outline"
                class="h-12"
                :disabled="processing"
            >
                Add health profile
            </Button>
        </Form>

        <Form
            v-if="canDelete"
            v-bind="HealthProfileController.destroy.form(current.id)"
            class="border-t pt-6"
            v-slot="{ processing }"
            @submit="
                (event) => {
                    if (
                        !window.confirm(
                            'Remove this health profile and its readings?',
                        )
                    ) {
                        event.preventDefault();
                    }
                }
            "
        >
            <Button
                type="submit"
                variant="destructive"
                class="h-12"
                :disabled="processing"
            >
                Remove {{ current.name }}
            </Button>
        </Form>
    </div>
</template>
