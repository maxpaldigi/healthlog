const MAX_EDGE = 1280;
const JPEG_QUALITY = 0.8;

export async function compressPhoto(file: File): Promise<File> {
    if (file.type === 'image/heic' || file.type === 'image/heif') {
        return file;
    }

    if (!file.type.startsWith('image/')) {
        return file;
    }

    try {
        const bitmap = await createImageBitmap(file);
        const scale = Math.min(1, MAX_EDGE / Math.max(bitmap.width, bitmap.height));
        const width = Math.max(1, Math.round(bitmap.width * scale));
        const height = Math.max(1, Math.round(bitmap.height * scale));

        if (scale === 1 && file.size < 180_000 && file.type === 'image/jpeg') {
            bitmap.close();

            return file;
        }

        const canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;
        const context = canvas.getContext('2d');

        if (context === null) {
            bitmap.close();

            return file;
        }

        context.drawImage(bitmap, 0, 0, width, height);
        bitmap.close();

        const blob = await new Promise<Blob | null>((resolve) => {
            canvas.toBlob(resolve, 'image/jpeg', JPEG_QUALITY);
        });

        if (blob === null || blob.size === 0) {
            return file;
        }

        const name = file.name.replace(/\.[^.]+$/, '') + '.jpg';

        return new File([blob], name, {
            type: 'image/jpeg',
            lastModified: Date.now(),
        });
    } catch {
        return file;
    }
}

export function replaceInputFile(input: HTMLInputElement, file: File): void {
    const transfer = new DataTransfer();
    transfer.items.add(file);
    input.files = transfer.files;
}
