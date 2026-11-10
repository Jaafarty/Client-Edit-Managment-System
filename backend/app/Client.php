<?php

namespace App;

use App\Models\Company;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'phone_number', 'email', 'last_follow_up_date'])]
class Client extends Model
{
    protected $attributes = [
        'email' => null,
        'last_follow_up_date' => null,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['last_follow_up_date' => 'date:Y-m-d'];
    }

    /**
     * @return HasMany<Company, $this>
     */
    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }
}
