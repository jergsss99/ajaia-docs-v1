<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    private function user(Request $request): User
    {
        return User::findOrFail($request->integer('user_id'));
    }

    private function accessible(Request $request, Document $document): User
    {
        $user = $this->user($request);

        abort_unless(
            $document->owner_id === $user->id
                || $document->collaborators()->whereKey($user->id)->exists(),
            403,
            'You do not have access to this document.',
        );

        return $user;
    }

    public function users(): JsonResponse
    {
        return response()->json(
            User::query()->orderBy('name')->get(['id', 'name', 'email']),
        );
    }

    public function index(Request $request): JsonResponse
    {
        $user = $this->user($request);

        $documents = Document::query()
            ->with('owner:id,name,email')
            ->where(fn ($query) => $query
                ->where('owner_id', $user->id)
                ->orWhereHas(
                    'collaborators',
                    fn ($query) => $query->whereKey($user->id),
                ))
            ->latest('updated_at')
            ->get()
            ->each
            ->append('access_type');

        return response()->json($documents);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'content' => ['nullable', 'string'],
        ]);

        $document = $this->user($request)->ownedDocuments()->create($data);

        return response()->json(
            $document->load('owner:id,name,email')->append('access_type'),
            201,
        );
    }

    public function show(Request $request, Document $document): JsonResponse
    {
        $this->accessible($request, $document);

        return response()->json(
            $document
                ->load(['owner:id,name,email', 'collaborators:id,name,email'])
                ->append('access_type'),
        );
    }

    public function update(Request $request, Document $document): JsonResponse
    {
        $this->accessible($request, $document);

        $document->update($request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:150'],
            'content' => ['sometimes', 'nullable', 'string'],
        ]));

        return response()->json(
            $document
                ->fresh()
                ->load(['owner:id,name,email', 'collaborators:id,name,email'])
                ->append('access_type'),
        );
    }

    public function share(Request $request, Document $document): JsonResponse
    {
        $user = $this->user($request);

        abort_unless(
            $document->owner_id === $user->id,
            403,
            'Only the owner can share this document.',
        );

        $data = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        $collaborator = User::where('email', $data['email'])->firstOrFail();

        abort_if(
            $collaborator->id === $user->id,
            422,
            'You already own this document.',
        );

        $document->collaborators()->syncWithoutDetaching($collaborator->id);

        return response()->json(
            $document
                ->load(['owner:id,name,email', 'collaborators:id,name,email'])
                ->append('access_type'),
        );
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:txt,md', 'max:2048'],
        ]);

        $file = $request->file('file');
        $plainText = file_get_contents($file->getRealPath());
        $content = '<p>'.nl2br(e($plainText)).'</p>';

        $document = $this->user($request)->ownedDocuments()->create([
            'title' => Str::limit(
                pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                150,
                '',
            ),
            'content' => $content,
        ]);

        return response()->json(
            $document->load('owner:id,name,email')->append('access_type'),
            201,
        );
    }
}
