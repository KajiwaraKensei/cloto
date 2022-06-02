<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostMessage extends Model
{
    protected $table = 'post_message';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $dates = ['created_at', 'updated_at'];
    protected $fillable = ['channel_id', 'user_id', 'type', 'content'];
}
