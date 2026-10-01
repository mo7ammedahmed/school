<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Communication\Models\Conversation;
use App\Domain\Communication\Models\Message;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Gives legacy `direct` conversations the participants their messages imply.
 *
 * The participants pivot arrives with no data, and a `direct` conversation with
 * no participants is readable by nobody — the correct fail-closed answer, but
 * not a working screen for threads that predate the fix. The only audience the
 * data can support is "everyone who has written in the thread": it is derived,
 * not guessed, and it cannot be verified as the *intended* audience, which is
 * why this is a command an operator runs after reviewing `--dry-run` output
 * rather than a migration that writes as it deploys.
 *
 * `internal` conversations are left alone: they are the school board and need
 * no membership to be readable. A conversation whose thread holds no messages
 * has no derivable audience and is reported and left as it is.
 *
 * Rows are read with `withoutSchoolScope()`, because a console command has no
 * tenant context and the tenant scope's deliberate answer to that is "no rows"
 * — a backfill that saw nothing would report success and leave every legacy
 * thread unreadable.
 */
class BackfillConversationParticipants extends Command
{
    protected $signature = 'conversations:backfill-participants
                            {--dry-run : Report what would be attached without writing anything}';

    protected $description = 'Attach the message senders of legacy direct conversations as their participants';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $scanned = 0;
        $attached = 0;
        $unresolved = 0;

        Conversation::withoutSchoolScope()
            ->where('type', 'direct')
            ->whereDoesntHave('participants')
            ->chunkById(200, function (Collection $conversations) use (&$scanned, &$attached, &$unresolved, $dryRun): void {
                foreach ($conversations as $conversation) {
                    $scanned++;

                    $senderIds = Message::withoutSchoolScope()
                        ->where('conversation_id', $conversation->id)
                        ->distinct()
                        ->pluck('sender_id')
                        ->unique()
                        ->values();

                    if ($senderIds->isEmpty()) {
                        $unresolved++;
                        $this->warn(sprintf(
                            'Conversation %d has no messages, so no participant could be derived; it stays unreadable.',
                            $conversation->id,
                        ));

                        continue;
                    }

                    if (! $dryRun) {
                        $conversation->participants()->attach($senderIds->all());
                    }

                    $attached += $senderIds->count();
                }
            });

        $this->info(sprintf(
            '%s: %d direct conversation(s) without participants; %d participant(s) %s; %d left unresolved.',
            $dryRun ? 'Dry run' : 'Backfill',
            $scanned,
            $attached,
            $dryRun ? 'would be attached' : 'attached',
            $unresolved,
        ));

        if ($dryRun) {
            $this->line('Run again without --dry-run to write the attachments.');
        }

        return self::SUCCESS;
    }
}
