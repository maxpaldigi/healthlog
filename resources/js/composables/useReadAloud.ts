import type { Ref } from 'vue';
import { onMounted, ref } from 'vue';

export type UseReadAloudReturn = {
    readAloud: Ref<boolean>;
    updateReadAloud: (value: boolean) => void;
    speak: (text: string) => void;
    stop: () => void;
};

const setCookie = (name: string, value: string, days = 365): void => {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = days * 24 * 60 * 60;

    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const readAloud = ref(false);

const synthesis = (): SpeechSynthesis | null => {
    if (typeof window === 'undefined' || !('speechSynthesis' in window)) {
        return null;
    }

    return window.speechSynthesis;
};

export function stopSpeech(): void {
    synthesis()?.cancel();
}

export function speak(text: string): void {
    if (!readAloud.value) {
        return;
    }

    const trimmed = text.trim();

    if (trimmed === '') {
        return;
    }

    const engine = synthesis();

    if (engine === null) {
        return;
    }

    const utterance = new SpeechSynthesisUtterance(trimmed);
    utterance.rate = 0.9;
    engine.speak(utterance);
}

export function initializeReadAloud(): void {
    if (typeof window === 'undefined') {
        return;
    }

    readAloud.value = localStorage.getItem('read_aloud') === '1';

    window.addEventListener('keydown', (event: KeyboardEvent) => {
        if (event.key === 'Escape') {
            stopSpeech();
        }
    });
}

export function useReadAloud(): UseReadAloudReturn {
    onMounted(() => {
        readAloud.value = localStorage.getItem('read_aloud') === '1';
    });

    function updateReadAloud(value: boolean): void {
        readAloud.value = value;
        localStorage.setItem('read_aloud', value ? '1' : '0');
        setCookie('read_aloud', value ? '1' : '0');

        if (!value) {
            stopSpeech();

            return;
        }

        speak('Read aloud is on.');
    }

    return {
        readAloud,
        updateReadAloud,
        speak,
        stop: stopSpeech,
    };
}
