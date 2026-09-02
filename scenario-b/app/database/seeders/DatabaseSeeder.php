<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Makes the test data:
 *   5 tenants, 50,000 notes (uneven), 150,000 tags.
 *
 * Note: the notes and tags are added with bulk insert (query builder),
 * not one Eloquent save at a time. 200,000 single saves would take many
 * minutes. Bulk insert takes seconds.
 */
class DatabaseSeeder extends Seeder
{
    /** How many notes each tenant gets. Total is 50,000. */
    private const TENANTS = [
        'acme' => 30000,
        'globex' => 8000,
        'initech' => 6000,
        'umbrella' => 4000,
        'hooli' => 2000,
    ];

    private const TOTAL_TAGS = 150000;

    private const NOTE_BATCH = 1000;

    private const TAG_BATCH = 5000;

    /** Words used to build note bodies, so search has something to find. */
    private const WORDS = [
        'alpha', 'anchor', 'apple', 'archive', 'backup', 'balance', 'basket', 'beacon',
        'bridge', 'budget', 'buffer', 'cable', 'camera', 'canvas', 'carbon', 'cargo',
        'castle', 'cellar', 'cement', 'center', 'change', 'circle', 'client', 'cloud',
        'cobalt', 'coffee', 'column', 'copper', 'corner', 'crater', 'credit', 'crystal',
        'daily', 'dagger', 'danger', 'deadline', 'delta', 'design', 'device', 'diesel',
        'dinner', 'domain', 'driver', 'eagle', 'earth', 'echo', 'eleven', 'email',
        'engine', 'escape', 'export', 'fabric', 'falcon', 'farmer', 'ferry', 'filter',
        'finance', 'forest', 'fossil', 'garden', 'gather', 'glacier', 'golden', 'granite',
        'ground', 'guitar', 'hammer', 'harbor', 'harvest', 'helmet', 'hidden', 'holiday',
        'import', 'income', 'indigo', 'invoice', 'island', 'jacket', 'jungle', 'kernel',
        'kitchen', 'ladder', 'lantern', 'laptop', 'laundry', 'legend', 'lemon', 'letter',
        'lumber', 'magnet', 'marble', 'market', 'meadow', 'mentor', 'meteor', 'mirror',
        'module', 'monkey', 'morning', 'motion', 'mountain', 'napkin', 'nectar', 'needle',
        'network', 'nickel', 'notice', 'number', 'ocean', 'office', 'orange', 'orbit',
        'packet', 'palace', 'parrot', 'pastry', 'pencil', 'pepper', 'permit', 'pigeon',
        'pillow', 'planet', 'pocket', 'polish', 'portal', 'potato', 'powder', 'prairie',
        'printer', 'puzzle', 'quarry', 'quartz', 'rabbit', 'radar', 'rocket', 'ribbon',
        'river', 'rubber', 'saddle', 'safety', 'salmon', 'sample', 'sandal', 'school',
        'season', 'sector', 'server', 'shadow', 'shovel', 'signal', 'silver', 'sketch',
        'socket', 'spider', 'spring', 'square', 'stable', 'steady', 'stream', 'sugar',
        'summer', 'sunset', 'system', 'table', 'talent', 'tanker', 'target', 'temple',
        'tender', 'thread', 'ticket', 'timber', 'tissue', 'toilet', 'tomato', 'torch',
        'tower', 'traffic', 'travel', 'tunnel', 'turtle', 'umbrella', 'uniform', 'update',
        'valley', 'vendor', 'velvet', 'violet', 'vision', 'voyage', 'wagon', 'wallet',
        'walnut', 'washer', 'watch', 'water', 'weather', 'wheel', 'window', 'winter',
        'wizard', 'wonder', 'wooden', 'yellow', 'zebra', 'zenith',
    ];

    /** Tag names. Fewer than the body words, so tags repeat a lot. */
    private const TAG_NAMES = [
        'urgent', 'todo', 'done', 'idea', 'meeting', 'bug', 'feature', 'draft',
        'review', 'personal', 'work', 'finance', 'travel', 'health', 'research',
        'archive', 'shared', 'private', 'followup', 'blocked',
    ];

    public function run(): void
    {
        $this->clear();

        $tenantIds = $this->seedTenants();
        [$firstNoteId, $lastNoteId] = $this->seedNotes($tenantIds);
        $this->seedTags($firstNoteId, $lastNoteId);

        $this->say('Done.');
    }

    /** Remove old rows so the seeder can run again. Children first. */
    private function clear(): void
    {
        DB::table('tags')->delete();
        DB::table('notes')->delete();
        DB::table('tenants')->delete();
    }

    /** @return array<string,int> slug => tenant id */
    private function seedTenants(): array
    {
        $ids = [];

        foreach (array_keys(self::TENANTS) as $slug) {
            // Only 5 rows, so Eloquent is fine here.
            $ids[$slug] = Tenant::create(['slug' => $slug])->id;
        }

        $this->say('Tenants: '.count($ids));

        return $ids;
    }

    /**
     * @param  array<string,int>  $tenantIds
     * @return array{0:int,1:int} first and last note id
     */
    private function seedNotes(array $tenantIds): array
    {
        $wordCount = count(self::WORDS) - 1;
        $now = time();
        $yearAgo = $now - (365 * 24 * 60 * 60);

        foreach (self::TENANTS as $slug => $howMany) {
            $tenantId = $tenantIds[$slug];
            $left = $howMany;

            while ($left > 0) {
                $size = min(self::NOTE_BATCH, $left);
                $rows = [];

                for ($i = 0; $i < $size; $i++) {
                    $rows[] = [
                        'tenant_id' => $tenantId,
                        'title' => ucfirst($this->words(mt_rand(3, 6), $wordCount)),
                        'body' => $this->words(mt_rand(25, 45), $wordCount).'.',
                        'created_at' => date('Y-m-d H:i:s', mt_rand($yearAgo, $now)),
                    ];
                }

                DB::table('notes')->insert($rows);
                $left -= $size;
            }

            $this->say("Notes for {$slug}: {$howMany}");
        }

        $range = DB::table('notes')->first([
            DB::raw('min(id) as first_id'),
            DB::raw('max(id) as last_id'),
        ]);

        return [(int) $range->first_id, (int) $range->last_id];
    }

    /** Tags point at random notes, so some notes get many and some get none. */
    private function seedTags(int $firstNoteId, int $lastNoteId): void
    {
        $tagCount = count(self::TAG_NAMES) - 1;
        $left = self::TOTAL_TAGS;

        while ($left > 0) {
            $size = min(self::TAG_BATCH, $left);
            $rows = [];

            for ($i = 0; $i < $size; $i++) {
                $rows[] = [
                    'note_id' => mt_rand($firstNoteId, $lastNoteId),
                    'name' => self::TAG_NAMES[mt_rand(0, $tagCount)],
                ];
            }

            DB::table('tags')->insert($rows);
            $left -= $size;
        }

        $this->say('Tags: '.self::TOTAL_TAGS);
    }

    private function words(int $howMany, int $maxIndex): string
    {
        $picked = [];

        for ($i = 0; $i < $howMany; $i++) {
            $picked[] = self::WORDS[mt_rand(0, $maxIndex)];
        }

        return implode(' ', $picked);
    }

    private function say(string $message): void
    {
        $this->command?->getOutput()->writeln("  <info>{$message}</info>");
    }
}
