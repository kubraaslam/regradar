<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Analysis retention (SRS NFR8)
    |--------------------------------------------------------------------------
    |
    | Analyses older than this many days are removed by regradar:prune-analyses
    | unless their owner has consented to longer retention from their profile.
    |
    */

    'retention_days' => (int) env('REGRADAR_RETENTION_DAYS', 90),

];
