<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeatureRegistry extends Model
{
    protected $fillable = [
        'user_id',
        'feature_name',
        'code_areas',
        'endpoints',
        'description'
    ];

    protected $casts = [
        'code_areas' => 'array',
        'endpoints' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}