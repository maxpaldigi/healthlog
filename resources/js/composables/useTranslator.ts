import { usePage } from '@inertiajs/vue3';

export function useTranslator(): {
    t: (key: string, replace?: Record<string, string | number>) => string;
} {
    const page = usePage();

    const t = (
        key: string,
        replace: Record<string, string | number> = {},
    ): string => {
        const translations = page.props.translations ?? {};
        let line = translations[key] ?? key;

        for (const [name, value] of Object.entries(replace)) {
            line = line.replaceAll(`:${name}`, String(value));
        }

        return line;
    };

    return { t };
}
