<?php

namespace App\Models;

use App\Observers\PersonObserver;
use Database\Factories\PersonFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(PersonObserver::class)]
class Person extends Model
{
    /** @use HasFactory<PersonFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'nick_name',
    ];
}
