import { onBeforeUnmount, onMounted, ref } from 'vue';
import { create as logBp } from '@/routes/bp';

type ReminderConfig = {
    enabled: boolean;
    timezone: string;
    times: { morning: string; afternoon: string; evening: string };
    due: { key: string; label: string; time: string }[];
};

const parseTime = (value: string): [number, number] => {
    const [hour, minute] = value.split(':').map((part) => Number(part));

    return [hour || 0, minute || 0];
};

export const useSlotReminders = (reminders: ReminderConfig) => {
    const permission = ref(
        typeof Notification === 'undefined' ? 'unsupported' : Notification.permission,
    );
    const timers: number[] = [];

    const notify = (label: string): void => {
        if (typeof Notification === 'undefined' || Notification.permission !== 'granted') {
            return;
        }

        const notice = new Notification(`${label} blood pressure`, {
            body: 'Tap to log this reading in HealthyMe.',
        });

        notice.onclick = () => {
            window.focus();
            window.location.assign(logBp());
        };
    };

    const schedule = (): void => {
        if (!reminders.enabled || typeof Notification === 'undefined') {
            return;
        }

        const now = new Date();

        for (const [key, time] of Object.entries(reminders.times)) {
            const [hour, minute] = parseTime(time);
            const at = new Date(now);
            at.setHours(hour, minute, 0, 0);

            const wait = at.getTime() - now.getTime();

            if (wait <= 0 || wait > 1000 * 60 * 60 * 18) {
                continue;
            }

            timers.push(
                window.setTimeout(() => {
                    notify(key.charAt(0).toUpperCase() + key.slice(1));
                }, wait),
            );
        }
    };

    const enableBrowser = async (): Promise<void> => {
        if (typeof Notification === 'undefined') {
            return;
        }

        permission.value = await Notification.requestPermission();
        schedule();
    };

    onMounted(() => {
        if (reminders.enabled && permission.value === 'granted') {
            schedule();
        }
    });

    onBeforeUnmount(() => {
        timers.forEach((id) => window.clearTimeout(id));
    });

    return { permission, enableBrowser };
};
