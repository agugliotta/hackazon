<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends BaseModel
{
    const APPROVED = 1;
    const PENDING = 0;

    protected $table = 'tbl_review';
    protected $primaryKey = 'reviewID';
    public $timestamps = false;
    protected $fillable = ['productID','username','email','review','rating','moder'];

    public function product(): BelongsTo { return $this->belongsTo(Product::class, 'productID', 'productID'); }

    public static function getRandomReviews(int $count): array
    {
        return static::where('moder', self::APPROVED)->inRandomOrder()->limit($count)->get()->all();
    }
}
