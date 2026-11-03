<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * Saturday 00:00 and Sunday 23:59 of the weekend in the week (Mon-Sun) containing $time.
     */
    protected function weekendOf(\Carbon\Carbon $time): array
    {
        $end = $time->copy()->endOfWeek(\Carbon\Carbon::SUNDAY);
        return [$end->copy()->subDay()->startOfDay(), $end];
    }

    /**
     * e.g. "Last weekend · Oct 3–Oct 4", or just "Sep 19–Sep 20" when further away.
     */
    protected function weekendLabel(\Carbon\Carbon $saturday): string
    {
        $thisSaturday = now()->startOfWeek()->addDays(5)->startOfDay();
        $prefix = match ((int) round($thisSaturday->diffInDays($saturday, false))) {
            0 => 'This weekend · ',
            -7 => 'Last weekend · ',
            7 => 'Next weekend · ',
            default => '',
        };
        $sunday = $saturday->copy()->addDay();
        $year = $sunday->year !== now()->year ? ', ' . $sunday->year : '';
        return $prefix . $saturday->format('M j') . '–' . $sunday->format('M j') . $year;
    }
}
