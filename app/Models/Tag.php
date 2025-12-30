<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Tag extends Model
{
    /** @use HasFactory<\Database\Factories\TagFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'color',
    ];

    public function incomes(): MorphToMany
    {
        return $this->morphedByMany(Income::class, 'taggable');
    }

    public function expenses(): MorphToMany
    {
        return $this->morphedByMany(Expense::class, 'taggable');
    }

    public function transfers(): MorphToMany
    {
        return $this->morphedByMany(Transfer::class, 'taggable');
    }
}
