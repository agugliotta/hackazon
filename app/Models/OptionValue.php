<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OptionValue extends BaseModel
{
    protected $table = 'tbl_products_opt_val_variants';
    protected $primaryKey = 'variantID';
    public $timestamps = false;
    protected $fillable = ['optionID','name','sort_order'];

    public function option(): BelongsTo { return $this->belongsTo(Option::class, 'optionID', 'optionID'); }
}
