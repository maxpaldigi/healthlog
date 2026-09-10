import type { Ref } from 'vue';
import { onMounted, ref } from 'vue';

export type UseLargeTextReturn = {
    largeText: Ref<boolean>;
    updateLargeText: (value: boolean) => void;
};

const setCookie = (name: string, value: string, days = 365): void => {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = days * 24 * 60 * 60;

    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const applyLargeText = (enabled: boolean): void => {
    if (typeof document === 'undefined') {
        return;
    }

    document.documentElement.classList.toggle('large-text', enabled);
};

export function initializeLargeText(): void {
    if (typeof window === 'undefined') {
        return;
    }

    applyLargeText(localStorage.getItem('large_text') === '1');
}

const largeText = ref(false);

export function useLargeText(): UseLargeTextReturn {
    onMounted(() => {
        largeText.value = localStorage.getItem('large_text') === '1';
        applyLargeText(largeText.value);
    });

    function updateLargeText(value: boolean): void {
        largeText.value = value;
        localStorage.setItem('large_text', value ? '1' : '0');
        setCookie('large_text', value ? '1' : '0');
        applyLargeText(value);
    }

    return {
        largeText,
        updateLargeText,
    };
}
