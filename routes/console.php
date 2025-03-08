<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('queue:prune-batches')->daily();
Schedule::command('sanctum:prune-expired --hours=24')->daily();