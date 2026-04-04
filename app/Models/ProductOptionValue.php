<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductOptionValue extends BaseModel
{
    protected $table = 'tbl_product_options_values';
    protected $primaryKey = 'ID';
    public $timestamps = false;
    protected $fillable = ['productID','variantID','price_surplus','default','picture','count'];

    public function product(): BelongsTo { return $this->belongsTo(Product::class, 'productID', 'productID'); }
}
