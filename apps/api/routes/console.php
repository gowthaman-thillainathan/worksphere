<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;
use Meridian\Modules\Identity\Infrastructure\Persistence\Models\RefreshToken;

Schedule::command('model:prune', ['--model' => [RefreshToken::class]])->daily()->onOneServer();
