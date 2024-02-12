<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use App\Models\Scopes\AuthorNotStaffUsers;

/**
 * App\Models\Author
 *
 * @property int $id
 * @property string $name
 * @property string $status  N => New(Waiting activation), A=>Active, I=>Inactive, B=>Banned
 * @property string $membership_mark  N => No membership, S=>Silver Membership, G=>Gold Membership
 * @property string $email
 * @property string|null $email_verified_at
 * @property string $password
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $phone
 * @property string|null $website
 * @property string|null $last_logged
 * @property string|null $remember_token
 * @property string $created_at
 * @property string|null $updated_at
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection|\Spatie\MediaLibrary\MediaCollections\Models\Media[] $media
 * @property-read int|null $media_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Page[] $pages
 * @property-read int|null $pages_count
 * @method static \Database\Factories\AuthorFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|Author getByEmail($email = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Author getById($id)
 * @method static \Illuminate\Database\Eloquent\Builder|Author getByMembershipMark($membershipMark)
 * @method static \Illuminate\Database\Eloquent\Builder|Author getByName($name = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Author getByStatus($status)
 * @method static \Illuminate\Database\Eloquent\Builder|Author newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Author newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Author query()
 * @method static \Illuminate\Database\Eloquent\Builder|Author whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Author whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Author whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Author whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Author whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Author whereLastLogged($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Author whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Author whereMembershipMark($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Author whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Author wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Author wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Author whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Author whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Author whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Author whereWebsite($value)
 * @mixin \Eloquent
 */
class Author extends Model implements HasMedia
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    public $timestamps = false;
    use HasFactory;
    use InteractsWithMedia;

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new AuthorNotStaffUsers);
    }

    protected $fillable
        = [
            'name',
            'email',
            'password',
            'status',
            'first_name',
            'last_name',
            'phone',
            'website',
            'has_debts',
            'subscriber_id',
            'updated_at',
        ];

    protected $hidden
        = [
            'laravel_through_key',
            'created_at',
            'updated_at'
        ];

    private static $authorStatusLabelValueArray
        = [
            'N' => 'New(Waiting activation)',
            'A' => 'Active',
            'I' => 'Inactive',
            'B' => 'Banned'
        ];

    public static function getAuthorStatusValueArray($keyReturn = true): array
    {
        if ( ! $keyReturn) {
            return self::$authorStatusLabelValueArray;
        }
        $resArray = [];
        foreach (self::$authorStatusLabelValueArray as $key => $value) {
            if ($keyReturn) {
                $resArray[] = ['key' => $key, 'label' => $value];
            }
        }

        return $resArray;
    }

    public static function getAuthorStatusLabel(string $status): string
    {
        if ( ! empty(self::$authorStatusLabelValueArray[$status])) {
            return self::$authorStatusLabelValueArray[$status];
        }

        return self::$authorStatusLabelValueArray[0];
    }

    public function scopeGetById($query, $id)
    {
        return $query->where(with(new Author)->getTable() . '.id', $id);
    }

    public function scopeGetByName($query, $name = null)
    {
        if (empty($name)) {
            return $query;
        }

        return $query->where(with(new Author)->getTable() . '.name', 'like', '%' . $name . '%');
    }

    public function scopeGetByEmail($query, $email = null)
    {
        if (empty($email)) {
            return $query;
        }

        return $query->where(with(new Author)->getTable() . '.email', 'like', '%' . $email . '%');
    }

    public function scopeGetByStatus($query, $status)
    {
        if (empty($status)) {
            return $query;
        }

        return $query->where(with(new Author)->getTable() . '.status', $status);
    }

    public function scopeGetByMembershipMark($query, $membershipMark)
    {
        if (empty($membershipMark)) {
            return $query;
        }

        return $query->where(with(new Author)->getTable() . '.membership_mark', $membershipMark);
    }

    public function pages()
    {
        return $this->hasMany('App\Models\Page', 'creator_id', 'id');
    }

    public static function getAuthorValidationRulesArray(int $authorId = null, array $skipFieldsArray = []): array
    {
        $validationRulesArray = [
            'name'             => 'required|max:100|unique:' . with(new Author)->getTable(),
            'email'            => 'required|email|max:100|unique:' . with(new Author)->getTable(),
            'status'           => 'required|in:' . getValueLabelKeys(Author::getAuthorStatusValueArray(false)),
            'password'         => 'required|min:6|max:15',
            'confirm_password' => 'required|min:6|max:15|same:password',
            'first_name'       => 'required|max:50',
            'last_name'        => 'required|max:50',
            'phone'            => 'nullable|max:100',
            'website'          => 'nullable|max:100',
            'notes'            => 'nullable',
        ];

        foreach ($skipFieldsArray as $nextField) {
            if ( ! empty($validationRulesArray[$nextField])) {
                unset($validationRulesArray[$nextField]);
            }
        }

        return $validationRulesArray;
    }

    public static function getValidationMessagesArray(): array
    {
        return [
            'name.required'       => 'Name is required',
            'email.required'      => 'Email is required',
            'email.email'         => 'Email is in invalid format',
            'status.required'     => 'Status is required',
            'first_name.required' => 'First name is required',
            'last_name.required'  => 'Last name is required',
        ];
    }

}
