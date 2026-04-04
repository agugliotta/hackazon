<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderAddress extends BaseModel
{
    protected $table = 'tbl_order_address';
    public $timestamps = false;
    protected $fillable = ['full_name','address_line_1','address_line_2','city','region','zip','country_id','phone','customer_id','address_type','order_id'];

    public function order(): BelongsTo { return $this->belongsTo(Order::class, 'order_id'); }
}
