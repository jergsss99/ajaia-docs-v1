<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Document extends Model
{
    protected $fillable = ['owner_id', 'title', 'content'];

    protected $appends = [];

    public function getAccessTypeAttribute(): string
    {
        return (int) request()->integer('user_id') === $this->owner_id
            ? 'owned'
            : 'shared';
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function collaborators(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }
}
