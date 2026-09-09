<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

trait ForPeriod
{
    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForPeriod(Builder $query, Carbon|string $from, Carbon|string $to): Builder
    {
        $column = property_exists($this, 'periodColumn')
            ? $this->periodColumn
            : 'measured_at';

        return $query->whereBetween($column, [$from, $to]);
    }
}
