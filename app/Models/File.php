<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class File extends BaseModel
{
    protected $table = 'tbl_files';
    public $timestamps = false;
    protected $fillable = ['user_id','path'];

    public function user(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
}
