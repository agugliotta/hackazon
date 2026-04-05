<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends BaseModel
{
    const STEP_OVERVIEW  = 1;
    const STEP_SHIPPING  = 2;
    const STEP_BILLING   = 3;
    const STEP_CONFIRM   = 4;
    const STEP_ORDER     = 5;

    protected $table = 'tbl_cart';
    public $timestamps = false;
    protected $fillable = ['uid','customer_id','customer_email','customer_is_guest','payment_method','shipping_method','shipping_address_id','billing_address_id','last_step','items_count','items_qty','total_price'];

    public function items(): HasMany { return $this->hasMany(CartItem::class, 'cart_id'); }
}
