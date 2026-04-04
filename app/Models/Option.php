<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Option extends BaseModel
{
    protected $table = 'tbl_product_options';
    protected $primaryKey = 'optionID';
    public $timestamps = false;
    protected $fillable = ['name','sort_order'];

    public function variants(): HasMany { return $this->hasMany(OptionValue::class, 'optionID', 'optionID'); }
}
