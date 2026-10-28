<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'tag',
        'organization_id',
        'created_by',
        'updated_by',
    ];

    public function contacts()
    {
        return $this->belongsToMany(Contact::class, 'contact_tags');
    }
}
