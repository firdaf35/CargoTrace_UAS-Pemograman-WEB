<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'city', 'country', 'country_code', 'type', 'latitude', 'longitude'];

    protected function casts(): array
    {
        return ['latitude' => 'float', 'longitude' => 'float'];
    }

    public function label(): string
    {
        return "{$this->name} ({$this->city}, {$this->country})";
    }
}
