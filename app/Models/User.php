<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use VulnModule\VulnerableField;

/**
 * @property int $id
 * @property string $username
 * @property string $password
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $user_phone
 * @property string $email
 * @property string|null $oauth_provider
 * @property string|null $oauth_uid
 * @property string $created_on
 * @property string|null $last_login
 * @property int $active
 * @property string|null $recover_passw
 * @property string|null $rest_token
 * @property string|null $photo
 * @property string|null $credit_card
 * @property string|null $credit_card_expires
 * @property int|null $credit_card_cvv
 */
class User extends BaseModel implements AuthenticatableContract
{
    use Authenticatable;

    protected $table = 'tbl_users';
    public $timestamps = false;
    protected $primaryKey = 'id';

    protected $hidden = ['password', 'recover_passw', 'rest_token'];

    protected $fillable = [
        'username', 'password', 'first_name', 'last_name', 'user_phone',
        'email', 'oauth_provider', 'oauth_uid', 'created_on', 'last_login',
        'active', 'recover_passw', 'rest_token', 'photo',
        'credit_card', 'credit_card_expires', 'credit_card_cvv',
    ];

    public function wishlists(): HasMany
    {
        return $this->hasMany(WishList::class, 'user_id');
    }

    public function wishlistFollowers(): HasMany
    {
        return $this->hasMany(WishlistFollower::class, 'user_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'tbl_users_roles', 'user_id', 'role_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(File::class, 'user_id');
    }

    public static function checkExistingUser(array $dataUser): bool
    {
        if (!empty($dataUser['username']) && static::where('username', $dataUser['username'])->exists()) {
            return true;
        }
        if (!empty($dataUser['email']) && static::where('email', $dataUser['email'])->exists()) {
            return true;
        }
        return false;
    }

    /** @param array|string $dataUser array of fields, or username string (legacy AuthController compat) */
    public static function registerUser($dataUser, string $password = ''): void
    {
        if (is_string($dataUser)) {
            $dataUser = ['username' => $dataUser, 'password' => $password];
        }
        // NOTE: MD5 hashing preserved from PHPixie auth (intentionally weak)
        $dataUser['password'] = md5((string) $dataUser['password']);
        $dataUser['created_on'] = $dataUser['last_login'] = date('Y-m-d H:i:s');
        $allowed = ['first_name', 'last_name', 'email', 'password', 'username', 'created_on', 'last_login'];
        static::create(array_intersect_key($dataUser, array_flip($allowed)));
    }

public static function checkLoginUser(string $login): string
    {
        if (preg_match("/[a-z0-9_-]+(\.[a-z0-9_-]+)*@([0-9a-z][0-9a-z-]*[0-9a-z]\.)+([a-z]{2,4})/i", $login)) {
            $user = static::where('email', $login)->first();
            if ($user) {
                return $user->username;
            }
        }
        return $login;
    }

    public static function loadUserModel(string $login): ?self
    {
        return static::where('username', $login)->first();
    }

    public static function saveOAuthUser(string $username, string $oauth_uid, string $oauth_provider): self
    {
        $user = new static();
        $user->username = $username;
        $user->oauth_provider = $oauth_provider;
        $user->oauth_uid = $oauth_uid;
        $user->created_on = date('Y-m-d H:i:s');
        $user->save();
        return $user;
    }

    public static function getEmailData($email): ?array
    {
        $rawEmail = $email instanceof VulnerableField ? $email->raw() : $email;
        $user = static::where('email', $rawEmail)->first();
        if ($user) {
            $host = isset($_SERVER['HTTP_HOST']) ? 'http://' . $_SERVER['HTTP_HOST'] : 'http://hackazon.com';
            return [
                'to'      => $rawEmail,
                'from'    => 'RobotHackazon@hackazon.com',
                'subject' => 'recovering password',
                'text'    => 'Hello, ' . $user->username . ".\nRecovering link is here "
                    . $host . '/user/recover?recover=' . static::getTempPassword($user),
            ];
        }
        return null;
    }

    private static function getTempPassword(self $user): string
    {
        $chars = array_merge(range('a', 'z'), range('A', 'Z'), range('0', '9'));
        $password = '';
        for ($i = 0; $i < 32; $i++) {
            $password .= $chars[rand(0, count($chars) - 1)];
        }
        $user->recover_passw = md5($password);
        $user->save();
        return $password;
    }

    public static function checkRecoverPass(string $username, string $recover_passw): bool
    {
        $user = static::loadUserModel($username);
        return $user && md5($recover_passw) === $user->recover_passw;
    }

    public static function getUserByRecoveryPass(string $recover_passw): ?self
    {
        return static::where('recover_passw', md5($recover_passw))->first();
    }

    public static function changeUserPassword(string $username, string $new_passw): bool
    {
        $user = static::loadUserModel($username);
        if ($user) {
            $user->password = md5($new_passw);
            $user->recover_passw = null;
            $user->save();
            return true;
        }
        return false;
    }

    public function getRoles(bool $refresh = false): array
    {
        return $this->roles->pluck('name')->toArray();
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles());
    }

    public function getPublicData(): ?array
    {
        if (!$this->exists) {
            return null;
        }
        $allowed = ['id', 'username', 'first_name', 'last_name', 'email', 'photo', 'user_phone', 'created_on'];
        $result = $this->only($allowed);
        $result['photoUrl'] = $this->getPhotoPath();
        return $result;
    }

    public function getPhotoPath(): ?string
    {
        if (!$this->exists) {
            return null;
        }
        if (isset($this->photo) && is_numeric($this->photo)) {
            $photoObj = File::where('id', $this->photo)->where('user_id', $this->id)->first();
            if ($photoObj) {
                return preg_replace('#.*?([^\\\\/]{2}[\\\\/][^\\\\/]+)$#', '$1', $photoObj->path);
            }
        }
        return $this->photo;
    }
}
