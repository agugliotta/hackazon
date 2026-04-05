<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $categoryID
 * @property string|null $name
 * @property int|null $parent
 */
class Category extends BaseModel
{
    protected $table = 'tbl_categories';
    protected $primaryKey = 'categoryID';
    public $timestamps = false;
    protected $fillable = ['name','parent','products_count','description','picture','enabled','hidden','lpos','rpos','depth'];

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent', 'categoryID');
    }

    public function parentCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent', 'categoryID');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'tbl_category_product', 'categoryID', 'productID');
    }

    public function getCategoriesSidebar(): array
    {
        $roots = static::where(function($q) { $q->whereNull('parent')->orWhere('parent', 0); })
            ->where('hidden', 0)->where('name', '!=', '0_ROOT')->get();
        $result = [];
        foreach ($roots as $root) {
            $root->childs = static::where('parent', $root->categoryID)->where('hidden', 0)->get()->all();
            $result[] = $root;
        }
        return $result;
    }

    public function getRootCategories(): array
    {
        return static::whereNull('parent')->orWhere('parent', 0)->where('hidden', 0)->get()->all();
    }

    public function getPageTitle(int $categoryID): string
    {
        $cat = static::find($categoryID);
        return $cat ? $cat->name : '';
    }

    public static function loadCategory($categoryID): ?static
    {
        return static::where('categoryID', $categoryID)->first();
    }

    public function getChildrenIDs(): array
    {
        $ids = [$this->categoryID];
        foreach ($this->children as $child) {
            $ids = array_merge($ids, $child->getChildrenIDs());
        }
        return $ids;
    }

    public function parents(): array
    {
        $parents = [];
        $current = $this->parentCategory;
        while ($current) {
            $parents[] = $current;
            $current = $current->parentCategory;
        }
        return array_reverse($parents);
    }
}
