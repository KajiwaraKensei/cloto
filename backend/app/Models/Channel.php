<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Channel extends Model
{
    protected $table = 'channels';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $dates = ['created_at', 'updated_at'];
    protected $fillable = ['members'];

    public function getChannelID($from, $to){
        $res =  self::select('id')
        ->where('members', 'like', "%$from%")
        ->where('members', 'like', "%$to%")
        ->whereRaw('LENGTH(":") = 1')
        ->first();

        if(empty($res)){
            return null;
        }
        
        return $res -> id;
    }
}
