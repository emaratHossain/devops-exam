<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Rules used everywhere in this class:
 *  - Reads use the query builder (DB::table).
 *  - Writes use Eloquent (Note, Tag).
 *  - Every query has "where tenant_id = ?". That is the multi-tenant rule.
 */
class NoteController extends Controller
{
    /** POST /api/notes */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'tags' => ['sometimes', 'array', 'max:20'],
            'tags.*' => ['required', 'string', 'max:50'],
        ]);

        $tenantId = $this->tenantId($request);
        $tags = array_values(array_unique($data['tags'] ?? []));

        $note = DB::transaction(function () use ($data, $tenantId, $tags) {
            $note = Note::create([
                'tenant_id' => $tenantId,
                'title' => $data['title'],
                'body' => $data['body'],
            ]);

            foreach ($tags as $name) {
                Tag::create(['note_id' => $note->id, 'name' => $name]);
            }

            return $note;
        });

        return response()->json([
            'data' => [
                'id' => $note->id,
                'tenant_id' => $note->tenant_id,
                'title' => $note->title,
                'body' => $note->body,
                'created_at' => $note->created_at,
                'tags' => $tags,
            ],
        ], 201);
    }

    /** GET /api/notes?page=1&limit=20 */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);
        [$page, $limit] = $this->pagination($request);

        $total = DB::table('notes')
            ->where('tenant_id', $tenantId)
            ->count();

        $rows = DB::table('notes')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('id')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get(['id', 'tenant_id', 'title', 'body', 'created_at']);

        // Add the tags to every note.
        foreach ($rows as $note) {
            $note->tags = DB::table('tags')
                ->where('note_id', $note->id)
                ->orderBy('id')
                ->pluck('name');
        }

        return response()->json([
            'data' => $rows,
            'meta' => $this->meta($page, $limit, $total),
        ]);
    }

    /** GET /api/notes/{id} */
    public function show(Request $request, int $id): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        $note = DB::table('notes')
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->first(['id', 'tenant_id', 'title', 'body', 'created_at']);

        // A note of another tenant looks like it does not exist.
        if ($note === null) {
            return response()->json(['message' => 'Note not found.'], 404);
        }

        $note->tags = DB::table('tags')
            ->where('note_id', $note->id)
            ->orderBy('id')
            ->pluck('name');

        return response()->json(['data' => $note]);
    }

    /** GET /api/search?q=word&page=1&limit=20 */
    public function search(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'min:1', 'max:255'],
        ]);

        $tenantId = $this->tenantId($request);
        [$page, $limit] = $this->pagination($request);

        // % and _ are wildcards in LIKE, so escape them in user input.
        $pattern = '%'.addcslashes($data['q'], '%_\\').'%';

        // ILIKE is PostgreSQL. SQLite (used in tests) has no ILIKE,
        // and its LIKE is already case-insensitive for plain ASCII.
        $operator = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $total = DB::table('notes')
            ->where('tenant_id', $tenantId)
            ->where('body', $operator, $pattern)
            ->count();

        $rows = DB::table('notes')
            ->where('tenant_id', $tenantId)
            ->where('body', $operator, $pattern)
            ->orderByDesc('id')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get(['id', 'tenant_id', 'title', 'body', 'created_at']);

        return response()->json([
            'data' => $rows,
            'meta' => $this->meta($page, $limit, $total) + ['q' => $data['q']],
        ]);
    }

    /** GET /api/stats - counts for the calling tenant, joining all three tables. */
    public function stats(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId($request);

        $row = DB::table('tenants')
            ->leftJoin('notes', 'notes.tenant_id', '=', 'tenants.id')
            ->leftJoin('tags', 'tags.note_id', '=', 'notes.id')
            ->where('tenants.id', $tenantId)
            ->groupBy('tenants.id', 'tenants.slug')
            ->first([
                'tenants.id as tenant_id',
                'tenants.slug as tenant_slug',
                DB::raw('count(distinct notes.id) as notes_count'),
                DB::raw('count(tags.id) as tags_count'),
            ]);

        return response()->json([
            'data' => [
                'tenant_id' => (int) $row->tenant_id,
                'tenant_slug' => $row->tenant_slug,
                'notes_count' => (int) $row->notes_count,
                'tags_count' => (int) $row->tags_count,
            ],
        ]);
    }

    private function tenantId(Request $request): int
    {
        return (int) $request->attributes->get('tenant_id');
    }

    /** @return array{0:int,1:int} page and limit taken from the query string. */
    private function pagination(Request $request): array
    {
        $page = max(1, (int) $request->query('page', 1));
        $limit = max(1, (int) $request->query('limit', 20));

        return [$page, $limit];
    }

    /** @return array<string,int> */
    private function meta(int $page, int $limit, int $total): array
    {
        return [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => (int) ceil($total / $limit),
        ];
    }
}
