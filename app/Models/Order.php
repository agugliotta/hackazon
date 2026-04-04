<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends BaseModel
{
    protected $table = 'tbl_orders';
    public $timestamps = false;
    protected $fillable = ['customer_firstname','customer_lastname','customer_email','status','comment','customer_id','payment_method','shipping_method','coupon_id','discount'];

    public function items(): HasMany { return $this->hasMany(OrderItem::class, 'order_id'); }
    public function addresses(): HasMany { return $this->hasMany(OrderAddress::class, 'order_id'); }
    public function customer(): BelongsTo { return $this->belongsTo(User::class, 'customer_id'); }
    public function coupon(): BelongsTo { return $this->belongsTo(Coupon::class, 'coupon_id'); }

    public function getMyOrdersPager(int $page = 1, int $perPage = 5)
    {
        return static::where('customer_id', auth()->id())->orderBy('created_at', 'DESC')->paginate($perPage, ['*'], 'page', $page);
    }
}
