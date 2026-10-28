<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavedReply extends Model
{
    use HasFactory;

    protected $table = 'saved_replies';

    protected $fillable = [
        'organization_id',
        'user_id',
        'reply',
        'path',
    ];

    /**
     * Relations
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}

