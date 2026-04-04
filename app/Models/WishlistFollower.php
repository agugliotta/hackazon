<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WishlistFollower extends BaseModel
{
    protected $table = 'tbl_wishlist_followers';
    public $timestamps = false;
    protected $fillable = ['user_id','follower_id'];

    public function user(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function follower(): BelongsTo { return $this->belongsTo(User::class, 'follower_id'); }
}
