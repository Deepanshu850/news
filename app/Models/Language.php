<?php

namespace App\Models;

use Barryvdh\LaravelIdeHelper\Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * App\Models\Language
 *
 * @property int $id
 * @property string $name
 * @property string $iso_code
 * @property int $is_default
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int $front_language_status
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Category[] $Categories
 * @property-read int|null $categories_count
 *
 * @method static Builder|Language newModelQuery()
 * @method static Builder|Language newQuery()
 * @method static Builder|Language query()
 * @method static Builder|Language whereCreatedAt($value)
 * @method static Builder|Language whereId($value)
 * @method static Builder|Language whereIsDefault($value)
 * @method static Builder|Language whereIsoCode($value)
 * @method static Builder|Language whereName($value)
 * @method static Builder|Language whereUpdatedAt($value)
 * @method static Builder|Language whereFrontLanguageStatus($value)
 *
 * @mixin Eloquent
 */
class Language extends Model
{
    use HasFactory;

    protected $table = 'languages';

    protected $fillable = [
        'name',
        'iso_code',
        'front_language_status',
    ];

    public const ACTIVE = 1;

    public const DISABLE = 0;

    protected $casts = [
        'name' => 'string',
        'iso_code' => 'string',
        'is_default' => 'integer',
    ];

    /**
     * Validation rules
     */
    public static array $rules = [
        'name' => 'required|unique:languages,name|max:20',
        'iso_code' => 'required|unique:languages,iso_code|max:2|min:2',
    ];

    public function Categories(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Category::class, 'lang_id', 'id');
    }
}
