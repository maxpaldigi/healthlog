export function clientUuid(): string {
    try {
        return crypto.randomUUID();
    } catch {
        return fallbackUuid();
    }
}

function fallbackUuid(): string {
    const bytes = new Uint8Array(16);

    if (typeof crypto !== 'undefined' && typeof crypto.getRandomValues === 'function') {
        try {
            crypto.getRandomValues(bytes);
        } catch {
            fillRandom(bytes);
        }
    } else {
        fillRandom(bytes);
    }

    bytes[6] = (bytes[6] & 0x0f) | 0x40;
    bytes[8] = (bytes[8] & 0x3f) | 0x80;

    const hex = [...bytes].map((byte) => byte.toString(16).padStart(2, '0')).join('');

    return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`;
}

function fillRandom(bytes: Uint8Array): void {
    for (let index = 0; index < bytes.length; index++) {
        bytes[index] = Math.floor(Math.random() * 256);
    }
}
