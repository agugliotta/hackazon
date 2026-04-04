<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends BaseModel
{
    protected $table = 'tbl_cart_items';
    public $timestamps = false;
    protected $fillable = ['cart_id','product_id','name','qty','price'];

    public function cart(): BelongsTo { return $this->belongsTo(Cart::class, 'cart_id'); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class, 'product_id', 'productID'); }
}
