<?php

namespace Tests\Feature;

use App\Models\Note;
use App\Models\Tag;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NotesApiTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $acme;

    private Tenant $globex;

    protected function setUp(): void
    {
        parent::setUp();

        $this->acme = Tenant::create(['slug' => 'acme']);
        $this->globex = Tenant::create(['slug' => 'globex']);
    }

    /** @param array<string,mixed> $extra */
    private function asTenant(string $slug, array $extra = []): array
    {
        return array_merge(['X-Tenant' => $slug], $extra);
    }

    public function test_it_creates_a_note_with_tags(): void
    {
        $response = $this->postJson('/api/notes', [
            'title' => 'First note',
            'body' => 'the body has the word rocket inside it',
            'tags' => ['work', 'urgent'],
        ], $this->asTenant('acme'));

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'First note')
            ->assertJsonPath('data.tenant_id', $this->acme->id)
            ->assertJsonPath('data.tags', ['work', 'urgent']);

        $this->assertDatabaseCount('notes', 1);
        $this->assertDatabaseCount('tags', 2);
    }

    public function test_list_shows_only_notes_of_the_calling_tenant(): void
    {
        $mine = Note::create(['tenant_id' => $this->acme->id, 'title' => 'acme note', 'body' => 'acme body']);
        Note::create(['tenant_id' => $this->globex->id, 'title' => 'globex note', 'body' => 'globex body']);
        Tag::create(['note_id' => $mine->id, 'name' => 'work']);

        $response = $this->getJson('/api/notes', $this->asTenant('acme'));

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'acme note')
            ->assertJsonPath('data.0.tags', ['work'])
            ->assertJsonPath('meta.total', 1);
    }

    public function test_list_runs_one_tag_query_for_every_note(): void
    {
        // This test writes down how the endpoint behaves today.
        // It asks the database once for the notes, then once more for each
        // note's tags. That is the N+1 problem. When the endpoint is fixed,
        // this test must be changed to expect 1 tag query.
        for ($i = 1; $i <= 3; $i++) {
            $note = Note::create(['tenant_id' => $this->acme->id, 'title' => "note {$i}", 'body' => 'body']);
            Tag::create(['note_id' => $note->id, 'name' => 'work']);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->getJson('/api/notes?limit=3', $this->asTenant('acme'))->assertOk();

        $tagQueries = collect(DB::getQueryLog())
            ->filter(fn (array $entry) => str_contains($entry['query'], 'from "tags"'))
            ->count();

        DB::disableQueryLog();

        $this->assertSame(3, $tagQueries, 'The list endpoint runs one tags query per note (N+1).');
    }

    public function test_list_respects_page_and_limit(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            Note::create(['tenant_id' => $this->acme->id, 'title' => "note {$i}", 'body' => 'body']);
        }

        $response = $this->getJson('/api/notes?page=2&limit=2', $this->asTenant('acme'));

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            // Newest first, so page 2 of 5 notes holds note 3 and note 2.
            ->assertJsonPath('data.0.title', 'note 3')
            ->assertJsonPath('meta.total', 5)
            ->assertJsonPath('meta.pages', 3);
    }

    public function test_a_note_of_another_tenant_is_not_found(): void
    {
        $note = Note::create(['tenant_id' => $this->globex->id, 'title' => 'secret', 'body' => 'secret body']);

        $this->getJson("/api/notes/{$note->id}", $this->asTenant('acme'))
            ->assertStatus(404);

        $this->getJson("/api/notes/{$note->id}", $this->asTenant('globex'))
            ->assertOk()
            ->assertJsonPath('data.title', 'secret');
    }

    public function test_search_only_finds_bodies_of_the_calling_tenant(): void
    {
        Note::create(['tenant_id' => $this->acme->id, 'title' => 'a', 'body' => 'we launch a rocket today']);
        Note::create(['tenant_id' => $this->acme->id, 'title' => 'b', 'body' => 'nothing to see here']);
        Note::create(['tenant_id' => $this->globex->id, 'title' => 'c', 'body' => 'their rocket is bigger']);

        $this->getJson('/api/search?q=rocket', $this->asTenant('acme'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'a')
            ->assertJsonPath('meta.total', 1);
    }

    public function test_search_needs_a_query(): void
    {
        $this->getJson('/api/search', $this->asTenant('acme'))
            ->assertStatus(422);
    }

    public function test_stats_counts_notes_and_tags_of_the_calling_tenant(): void
    {
        $one = Note::create(['tenant_id' => $this->acme->id, 'title' => 'a', 'body' => 'a']);
        $two = Note::create(['tenant_id' => $this->acme->id, 'title' => 'b', 'body' => 'b']);
        $other = Note::create(['tenant_id' => $this->globex->id, 'title' => 'c', 'body' => 'c']);

        Tag::create(['note_id' => $one->id, 'name' => 'work']);
        Tag::create(['note_id' => $one->id, 'name' => 'urgent']);
        Tag::create(['note_id' => $two->id, 'name' => 'todo']);
        Tag::create(['note_id' => $other->id, 'name' => 'work']);

        $this->getJson('/api/stats', $this->asTenant('acme'))
            ->assertOk()
            ->assertJsonPath('data.tenant_slug', 'acme')
            ->assertJsonPath('data.notes_count', 2)
            ->assertJsonPath('data.tags_count', 3);
    }

    public function test_missing_tenant_header_is_rejected(): void
    {
        $this->getJson('/api/notes')->assertStatus(400);
    }

    public function test_unknown_tenant_slug_is_rejected(): void
    {
        $this->getJson('/api/notes', $this->asTenant('nope'))->assertStatus(404);
    }
}
