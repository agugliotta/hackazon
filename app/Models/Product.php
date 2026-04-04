<?php
namespace App\Models;
use App\Helpers\ArraysHelper;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use VulnModule\VulnerableField;

/**
 * @property int $productID
 * @property string|null $name
 * @property string|null $description
 * @property float|null $Price
 * @property float $customers_rating
 * @property string|null $picture
 * @property int|null $categoryID
 * @property int $customer_votes
 */
class Product extends BaseModel
{
    protected $table = 'tbl_products';
    protected $primaryKey = 'productID';
    public $timestamps = false;
    private int $annotation_length = 20;

    protected $fillable = [
        'categoryID','name','description','customers_rating','Price','picture','in_stock',
        'thumbnail','customer_votes','items_sold','big_picture','enabled','brief_description',
        'list_price','product_code','hurl','brandID','meta_title','meta_keywords','meta_desc'
    ];

    public function specialOffer(): HasOne
    {
        return $this->hasOne(SpecialOffer::class, 'productID', 'productID');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'productID', 'productID');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'categoryID', 'categoryID');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'tbl_category_product', 'productID', 'categoryID');
    }

    public function options(): BelongsToMany
    {
        return $this->belongsToMany(OptionValue::class, 'tbl_product_options_values', 'productID', 'ID');
    }

    public function productOptions(): HasMany
    {
        return $this->hasMany(ProductOptionValue::class, 'productID', 'productID');
    }

    public function getProduct(int $productID): array
    {
        $product = static::find($productID);
        if ($product) {
            return [
                'productID' => $productID,
                'name' => $product->name,
                'description' => $product->description,
                'price' => $product->Price,
                'customers_votes' => $product->customer_votes,
                'customers_rating' => $product->customers_rating,
                'picture' => $product->picture,
                'reviews' => $product->getReviews()->toArray(),
            ];
        }
        return [];
    }

    public static function getPageTitle(int $productID): ?string
    {
        $product = static::find($productID);
        return $product ? $product->name : null;
    }

    public static function getRandomProducts(int $maxCount): array
    {
        $count = static::count();
        if (!$count) return [];
        $offsets = ArraysHelper::getRandomArray($maxCount, 1, $count);
        $products = [];
        foreach ($offsets as $offset) {
            $product = static::offset($offset - 1)->first();
            if ($product) $products[] = $product;
        }
        return $products;
    }

    public function getAnnotation(?int $length = null): string
    {
        $savedLen = $this->annotation_length;
        if ($length !== null) $this->annotation_length = $length;
        $result = $this->_getBrief($this->description ?? '');
        $this->annotation_length = $savedLen;
        return $result;
    }

    public function getProductData(self $product): array
    {
        return [
            'productID' => $product->productID,
            'name' => $product->name,
            'price' => $product->Price,
            'annotation' => empty($product->brief_description) ? $this->_getBrief($product->description ?? '') : $product->brief_description,
            'thumbnail' => $product->thumbnail,
            'customers_votes' => $product->customer_votes,
            'customers_rating' => $product->customers_rating,
            'picture' => $product->picture,
        ];
    }

    /** Alias for compatibility with callers using the legacy name */
    public static function getRndProduct(int $count): array
    {
        return (new static())->getRandomProducts($count);
    }

    public function checkProductInCookie($productId, $request = null): void
    {
        if ($productId instanceof VulnerableField) {
            $productId = $productId->raw();
        }
        $productIds = $_COOKIE['visited_products'] ?? ',';
        if (strpos($productIds, ",$productId,") === false) {
            setcookie('visited_products', $productIds . $productId . ',', time() + 3600 * 24 * 365, '/');
        }
    }

    public static function getVisitedProducts($productIds, int $count = 4): array
    {
        $rawIds = $productIds instanceof VulnerableField ? $productIds->raw() : $productIds;
        $ids = array_filter(preg_split('/,/', $rawIds, -1, PREG_SPLIT_NO_EMPTY), 'trim');
        if (!count($ids)) return [];

        $idsCount = count($ids);
        $slicedIdsKeys = array_rand($ids, $count > $idsCount ? $idsCount : max($count, 1));
        if (!is_array($slicedIdsKeys)) $slicedIdsKeys = [$slicedIdsKeys];
        shuffle($slicedIdsKeys);

        $idsToSelect = [];
        foreach ($slicedIdsKeys as $key) {
            $idsToSelect[$key] = $ids[$key];
        }

        // Preserve SQLi vulnerability: if field is not SQLi-vulnerable, cast to int
        if ($productIds instanceof VulnerableField && !$productIds->isVulnerableTo('SQL')) {
            $idsToSelect = array_map('intval', $idsToSelect);
        }

        // SQLi preserved via raw concatenation
        $idsExpr = '(' . implode(',', $idsToSelect) . ')';
        return DB::select(DB::raw("SELECT * FROM tbl_products WHERE productID IN " . $idsExpr));
    }

    public function isInUserWishList(?User $user = null): bool
    {
        if ($user === null || !$user->exists || !$this->exists) return false;
        return DB::table('tbl_products', 'p')
            ->join('tbl_wish_list_item as wli', 'wli.product_id', '=', 'p.productID')
            ->join('tbl_wish_list as wl', 'wl.id', '=', 'wli.wish_list_id')
            ->where('wl.user_id', $user->id)
            ->where('p.productID', $this->productID)
            ->count() > 0;
    }

    public function getReviews()
    {
        return $this->reviews()->where('moder', Review::APPROVED)->get();
    }

    private function _getBrief(string $content): string
    {
        $annotation = strip_tags($content);
        $annotation = substr($annotation, 0, $this->annotation_length);
        $annotation = rtrim($annotation, "!,.-");
        return substr($annotation, 0, strrpos($annotation, ' ') ?: strlen($annotation));
    }
}
