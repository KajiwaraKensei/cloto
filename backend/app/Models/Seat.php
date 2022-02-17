<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Seat extends Model
{
    protected $primaryKey = 'id';
    protected $dates = ['created_at', 'updated_at'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'section_id', 'name', 'size', 'position', 'role', 'media', 'status', 'reservation_user_id'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['position' => 'json', 'media' => 'json'];

    /**
     * Section モデルのリレーション
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function section()
    {
        return $this->belongsTo('App\Models\Section');
    }

    /**
     * User モデルのリレーション
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user()
    {
        return $this->hasOne('App\Models\User');
    }
}
