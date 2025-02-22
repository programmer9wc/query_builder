<?php

namespace Programmer9WC\QueryBuilder\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Auth;

class QueryReports extends Model
{
    use HasFactory;

    protected $table = 'reports';
    
    protected $fillable = [
        'title',
        'slug',
        'query_details',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'created_at',
        'updated_at',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'query_details' => 'array',
    ];
}
