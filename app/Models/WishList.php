<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use VulnModule\VulnerableField;

class WishList extends BaseModel
{
    const TYPE_PUBLIC  = 'public';
    const TYPE_SHARED  = 'shared';
    const TYPE_PRIVATE = 'private';

    protected $table = 'tbl_wish_list';
    public $timestamps = false;
    protected $fillable = ['user_id','name','type','is_default','created','modified'];

    public function user(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function items(): HasMany { return $this->hasMany(WishlistItem::class, 'wish_list_id'); }
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'tbl_wish_list_item', 'wish_list_id', 'product_id')
            ->withPivot('created');
    }

    public function isDefault(): bool { return (int) $this->is_default !== 0; }
    public function setDefault(bool $isDefault = true): void { $this->is_default = $isDefault ? 1 : 0; }

    public function getPossibleTypes(): array
    {
        return [self::TYPE_PUBLIC, self::TYPE_SHARED, self::TYPE_PRIVATE];
    }

    public function isValidType($type): bool
    {
        $raw = $type instanceof VulnerableField ? $type->raw() : $type;
        return in_array($raw, $this->getPossibleTypes());
    }

    public function isVisibleToUser(?User $user = null): bool
    {
        $user = $user && $user->exists ? $user : null;
        $notUsersList = !$user || $this->user_id != $user->id;
        return !($notUsersList && $this->type === self::TYPE_PRIVATE);
    }

    public function addProductItem(int $productId): WishlistItem
    {
        if (!$this->exists) {
            throw new \LogicException("Wishlist does not exist, cannot add items.");
        }
        $item = new WishlistItem();
        $item->wish_list_id = $this->id;
        $item->product_id   = $productId;
        $item->created      = date('Y-m-d H:i:s');
        $item->save();
        return $item;
    }

    public function setAsUserDefaultWishList(User $user): self
    {
        if (!$this->exists) {
            throw new \InvalidArgumentException("Can't set an unsaved WishList as default.");
        }
        foreach ($user->wishlists as $list) {
            if ($list->id == $this->id) {
                if (!$list->isDefault()) { $list->setDefault(); $list->save(); $this->setDefault(); }
                $newDefault = $list;
            } else {
                if ($list->isDefault()) { $list->setDefault(false); $list->save(); }
            }
        }
        if (!isset($newDefault)) {
            throw new \InvalidArgumentException("WishList does not belong to user.");
        }
        return $this;
    }

    // ── Static helpers called from controllers ────────────────────────────────

    public static function getUserDefaultWishList(User $user): ?self
    {
        if (!$user->exists) return null;
        $default = $user->wishlists()->where('is_default', 1)->first();
        if ($default) return $default;
        $first = $user->wishlists()->first();
        if ($first) { $first->setDefault(); $first->save(); }
        return $first;
    }

    public static function createNewWishListForUser(User $user, string $name = 'New Wish List', $type = self::TYPE_PRIVATE): self
    {
        if (!$user->exists) throw new \InvalidArgumentException('User is not valid.');
        $rawType = $type instanceof VulnerableField ? $type->raw() : $type;
        $validTypes = [self::TYPE_PUBLIC, self::TYPE_SHARED, self::TYPE_PRIVATE];
        if (!in_array($rawType, $validTypes)) $rawType = self::TYPE_PRIVATE;
        $wishList = new static();
        $wishList->user_id  = $user->id;
        $wishList->name     = $name;
        $wishList->type     = $rawType;
        $wishList->created  = date('Y-m-d H:i:s');
        $wishList->save();
        return $wishList;
    }

    public static function removeProductFromUserWishLists(User $user, $productId): void
    {
        if (!$user->exists) return;
        // SQLi preserved: productId is not cast if it arrives as VulnerableField with SQL vuln
        $rawId = $productId instanceof VulnerableField ? $productId->raw() : (int) $productId;
        DB::statement(
            "DELETE wli FROM tbl_wish_list_item wli"
            . " JOIN tbl_wish_list wl ON wl.id = wli.wish_list_id"
            . " WHERE wl.user_id = " . (int) $user->id
            . " AND wli.product_id = " . $rawId
        );
    }

    /**
     * Search users and wishlists by username or email.
     * SQLi preserved: $searchQuery may be a VulnerableField with SQL vuln enabled.
     */
    public static function searchWishLists($searchQuery): array
    {
        $raw = $searchQuery instanceof VulnerableField ? $searchQuery->raw() : $searchQuery;
        $searchString = '%' . $raw . '%';

        // Build query — when SQLi vuln is active on $searchQuery, concatenation is intentional
        $userIds = DB::select(DB::raw(
            "SELECT DISTINCT u.id FROM tbl_users u"
            . " JOIN tbl_wish_list wl ON wl.user_id = u.id"
            . " WHERE wl.type = 'public'"
            . " AND (u.email LIKE '" . $searchString . "' OR u.username LIKE '" . $searchString . "')"
        ));
        $ids = array_column($userIds, 'id');

        if (!$ids) return [];

        $currentUser = Auth::user();
        $followers = [];
        if ($currentUser) {
            $rows = DB::table('tbl_wishlist_followers')->where('user_id', $currentUser->id)->pluck('follower_id')->toArray();
            $followers = $rows;
        }

        $users = User::whereIn('id', $ids)->get();
        $userList = [];
        foreach ($users as $user) {
            if ($currentUser && $user->id == $currentUser->id) continue;
            $publicLists = $user->wishlists()->where('type', self::TYPE_PUBLIC)->get();
            if ($publicLists->isEmpty()) continue;
            $entry = $user->toArray();
            $entry['remembered'] = in_array($user->id, $followers);
            $entry['wishLists']  = $publicLists->toArray();
            $userList[$user->id] = $entry;
        }
        return $userList;
    }

    /**
     * Remember/follow a user's wishlists.
     * IDOR preserved: $userId is raw from POST.
     */
    public static function remember($userId): int
    {
        $currentUser = Auth::user();
        DB::table('tbl_wishlist_followers')->insert([
            'user_id'     => $currentUser ? $currentUser->id : 0,
            'follower_id' => $userId,
        ]);
        return (int) DB::getPdo()->lastInsertId();
    }
}
