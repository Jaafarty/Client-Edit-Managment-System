<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['project_id', 'title', 'description', 'priority', 'status', 'price', 'is_paid', 'due_date'])]
class Edit extends Model
{
    public const UPDATED_AT = null;

    public const PRIORITIES = ['High', 'Medium', 'Low'];

    public const STATUSES = ['Pending', 'Confirmed', 'In Progress', 'Done', 'Cancelled'];

    protected $attributes = [
        'description' => null,
        'price' => null,
        'is_paid' => false,
        'due_date' => null,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'project_id' => 'integer',
            'price' => 'decimal:2',
            'is_paid' => 'boolean',
            'due_date' => 'date:Y-m-d',
        ];
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return HasMany<Attachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }
}
