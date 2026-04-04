<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpecialOffer extends BaseModel
{
    protected $table = 'tbl_special_offers';
    protected $primaryKey = 'offerID';
    public $timestamps = false;
    protected $fillable = ['productID','sort_order'];

    public function product(): BelongsTo { return $this->belongsTo(Product::class, 'productID', 'productID'); }

    public static function getRandomOffers(int $count): array
    {
        $total = static::count();
        if (!$total) return [];
        return static::inRandomOrder()->limit($count)->get()->all();
    }
}
