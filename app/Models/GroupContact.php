<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupContact extends Model
{
    use HasFactory;

    protected $table = 'group_contacts';

    protected $guarded = [];
}
