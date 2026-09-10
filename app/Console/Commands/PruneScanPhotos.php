<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PruneScanPhotos extends Command
{
    protected $signature = 'healthlog:prune-scans';

    protected $description = 'Delete leftover meter photos older than one hour';

    public function handle(): int
    {
        $disk = Storage::disk('local');
        $cutoff = now()->subHour()->getTimestamp();
        $deleted = 0;

        foreach ($disk->files('scans') as $path) {
            if ($disk->lastModified($path) <= $cutoff) {
                $disk->delete($path);
                $deleted++;
            }
        }

        $this->info('Deleted '.$deleted.' leftover scan photo'.($deleted === 1 ? '' : 's').'.');

        return self::SUCCESS;
    }
}
