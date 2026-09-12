import { createHash } from 'node:crypto';
import { existsSync, readFileSync, writeFileSync } from 'node:fs';
import { resolve } from 'node:path';
import type { Plugin } from 'vite';

export function stampServiceWorker(): Plugin {
    const root = process.cwd();

    return {
        name: 'stamp-service-worker',
        apply: 'build',
        closeBundle() {
            const template = resolve(root, 'resources/pwa/sw.js');
            const dest = resolve(root, 'public/sw.js');
            const manifest = resolve(root, 'public/build/manifest.json');

            if (!existsSync(template)) {
                return;
            }

            const version = existsSync(manifest)
                ? createHash('sha256')
                    .update(readFileSync(manifest))
                    .digest('hex')
                    .slice(0, 12)
                : 'dev';

            writeFileSync(
                dest,
                readFileSync(template, 'utf8').replaceAll(
                    '__CACHE_VERSION__',
                    version,
                ),
            );
        },
    };
}
