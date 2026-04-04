<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WishlistItem extends BaseModel
{
    protected $table = 'tbl_wish_list_item';
    public $timestamps = false;
    protected $fillable = ['wish_list_id','product_id','created'];

    public function wishlist(): BelongsTo { return $this->belongsTo(WishList::class, 'wish_list_id'); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class, 'product_id', 'productID'); }
}
