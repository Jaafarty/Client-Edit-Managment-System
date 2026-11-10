<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attachment extends Model
{
    protected $fillable = ['path', 'original_name', 'description'];

    protected $hidden = ['path'];

    /**
     * @return BelongsTo<Edit, $this>
     */
    public function edit(): BelongsTo
    {
        return $this->belongsTo(Edit::class);
    }
}
