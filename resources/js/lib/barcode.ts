type Detected = { rawValue: string };

type Detector = {
    detect: (source: ImageBitmapSource) => Promise<Detected[]>;
};

const DetectorCtor = (
    globalThis as unknown as {
        BarcodeDetector?: new (options?: { formats?: string[] }) => Detector;
    }
).BarcodeDetector;

export function barcodeScanAvailable(): boolean {
    return typeof navigator !== 'undefined' && Boolean(navigator.mediaDevices?.getUserMedia);
}

async function detectWithNative(source: ImageBitmapSource): Promise<string | null> {
    if (!DetectorCtor) {
        return null;
    }

    const detector = new DetectorCtor({
        formats: ['ean_13', 'ean_8', 'upc_a', 'upc_e', 'code_128'],
    });
    const found = await detector.detect(source);

    return found[0]?.rawValue ?? null;
}

async function detectWithZxing(image: ImageData): Promise<string | null> {
    const { readBarcodes } = await import('zxing-wasm/reader');
    const results = await readBarcodes(image, {
        tryHarder: true,
        formats: ['AllLinear'],
    });

    return results[0]?.text ?? null;
}

export async function detectBarcodeFromVideo(
    video: HTMLVideoElement,
): Promise<string | null> {
    const native = await detectWithNative(video);

    if (native) {
        return native.replace(/\D/g, '') || native;
    }

    const canvas = document.createElement('canvas');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;

    if (canvas.width < 8 || canvas.height < 8) {
        return null;
    }

    const context = canvas.getContext('2d');

    if (!context) {
        return null;
    }

    context.drawImage(video, 0, 0);
    const image = context.getImageData(0, 0, canvas.width, canvas.height);
    const decoded = await detectWithZxing(image);

    return decoded ? decoded.replace(/\D/g, '') || decoded : null;
}
