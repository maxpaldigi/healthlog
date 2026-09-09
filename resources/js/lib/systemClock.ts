export const systemTimezone = (): string => {
    try {
        return Intl.DateTimeFormat().resolvedOptions().timeZone || '';
    } catch {
        return '';
    }
};

export const systemDateTimeLocal = (): string => {
    const now = new Date();
    const pad = (value: number): string => value.toString().padStart(2, '0');

    return [
        now.getFullYear(),
        '-',
        pad(now.getMonth() + 1),
        '-',
        pad(now.getDate()),
        'T',
        pad(now.getHours()),
        ':',
        pad(now.getMinutes()),
    ].join('');
};

export const systemDate = (): string => systemDateTimeLocal().slice(0, 10);

export const rememberSystemTimezone = (): string => {
    const timezone = systemTimezone();

    if (timezone !== '') {
        document.cookie = `system_timezone=${encodeURIComponent(timezone)}; path=/; max-age=31536000; samesite=lax`;
    }

    return timezone;
};
