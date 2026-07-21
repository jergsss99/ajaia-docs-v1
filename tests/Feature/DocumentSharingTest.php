<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentSharingTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_share_and_collaborator_can_edit(): void
    {
        $owner = User::factory()->create();
        $collaborator = User::factory()->create();
        $document = Document::create([
            'owner_id' => $owner->id,
            'title' => 'Plan',
            'content' => '<p>Draft</p>',
        ]);

        $this->postJson(
            "/api/documents/{$document->id}/share?user_id={$owner->id}",
            ['email' => $collaborator->email],
        )->assertOk();

        $this->putJson(
            "/api/documents/{$document->id}?user_id={$collaborator->id}",
            ['content' => '<p><strong>Finished</strong></p>'],
        )->assertOk();

        $this->assertDatabaseHas('document_user', [
            'document_id' => $document->id,
            'user_id' => $collaborator->id,
        ]);

        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'content' => '<p><strong>Finished</strong></p>',
        ]);
    }

    public function test_unshared_user_cannot_open_document(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $document = Document::create([
            'owner_id' => $owner->id,
            'title' => 'Private',
        ]);

        $this->getJson(
            "/api/documents/{$document->id}?user_id={$stranger->id}",
        )->assertForbidden();
    }
}
