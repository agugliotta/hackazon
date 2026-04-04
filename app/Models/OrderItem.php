<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends BaseModel
{
    protected $table = 'tbl_order_items';
    public $timestamps = false;
    protected $fillable = ['cart_id','product_id','name','qty','price','order_id'];

    public function order(): BelongsTo { return $this->belongsTo(Order::class, 'order_id'); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class, 'product_id', 'productID'); }
}
