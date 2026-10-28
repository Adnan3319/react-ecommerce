<?php

namespace App\Models;

use App\Support\Phone;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A message sent from the storefront contact form. `mobile` is stored in the normalized form (01XXXXXXXXX).
 */
class ContactMessage extends Model
{
    use HasFactory;

    public const MAX_NAME_LENGTH = 80;

    public const MAX_MESSAGE_LENGTH = 1000;

    protected $fillable = [
        'name',
        'email',
        'mobile',
        'message',
        'is_read',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
        ];
    }

    /**
     * @param  Builder<ContactMessage>  $query
     */
    #[Scope]
    protected function unread(Builder $query): void
    {
        $query->where('is_read', false);
    }

    /**
     * Name, mobile or email contains the term (LIKE wildcards in the term match literally).
     *
     * @param  Builder<ContactMessage>  $query
     */
    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $pattern = '%'.addcslashes($term, '%_\\').'%';
        $phone = Phone::searchTerm($term);

        $query->where(function (Builder $query) use ($pattern, $phone): void {
            $query->where('name', 'like', $pattern)->orWhere('email', 'like', $pattern);

            if ($phone !== null) {
                $query->orWhere('mobile', 'like', '%'.addcslashes($phone, '%_\\').'%');
            }
        });
    }
}
