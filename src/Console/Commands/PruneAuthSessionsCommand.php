<?php

declare(strict_types=1);

namespace EpicAlgorithms\AuthSessions\Console\Commands;

use EpicAlgorithms\AuthSessions\Models\AuthSession;
use Illuminate\Console\Command;

class PruneAuthSessionsCommand extends Command
{
    protected $signature = 'auth-sessions:prune';

    protected $description = 'Delete auth sessions that expired more than 30 days ago.';

    public function handle(): int
    {
        $deleted = AuthSession::deleteExpired();

        $this->info("Pruned {$deleted} expired auth session(s).");

        return self::SUCCESS;
    }
}
