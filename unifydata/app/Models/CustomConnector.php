<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomConnector extends Model
{
    use HasFactory;

    protected $connection = "mongodb";
    protected $collection = "custom_connectors";

    protected $fillable = ['name', 'base_url', 'auth_type', 'auth_details', 'streams', 'is_published'];

    protected $casts = [
        'auth_details' => 'array',
        'streams' => 'array',
        'is_published' => 'boolean',
    ];
}
