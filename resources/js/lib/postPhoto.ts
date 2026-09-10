import { router } from '@inertiajs/vue3';

type PhotoCallbacks = {
    onFinish?: () => void;
    onError?: (errors: Record<string, string>) => void;
    onFail?: () => void;
};

export function postPhoto(
    url: string,
    file: File,
    fields: Record<string, string> = {},
    callbacks: PhotoCallbacks = {},
): void {
    const data = new FormData();
    data.append('photo', file, file.name);

    Object.entries(fields).forEach(([key, value]) => {
        data.append(key, value);
    });

    router.post(url, data, {
        forceFormData: true,
        onFinish: callbacks.onFinish,
        onError: callbacks.onError,
        onHttpException: callbacks.onFail,
        onNetworkError: callbacks.onFail,
    });
}
