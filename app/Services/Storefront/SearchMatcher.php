<?php

namespace App\Services\Storefront;

use App\Models\Product;
use App\Support\Like;
use Illuminate\Database\Eloquent\Builder;

/**
 * Turns a search term into product conditions and a relevance order, for the listing and the suggestions.
 *
 * A product matches when a live variant has exactly this SKU, or when every word of the term is found.
 * MySQL FULLTEXT cannot index words shorter than `innodb_ft_min_token_size` (3) or InnoDB stopwords
 * and shared hosting does not let us change that, so only longer non-stopwords go into MATCH ... AGAINST
 * and every other word must appear in the name (escaped LIKE). SQLite (tests) uses LIKE for all words
 * on the name and the short description.
 */
class SearchMatcher
{
    private const FULLTEXT_MIN_LENGTH = 3;

    private const LIKE_ESCAPE = Like::ESCAPE;

    /** The words InnoDB never indexes (its default stopword list). */
    private const STOPWORDS = [
        'a', 'about', 'an', 'are', 'as', 'at', 'be', 'by', 'com', 'de', 'en', 'for', 'from', 'how', 'i',
        'in', 'is', 'it', 'la', 'of', 'on', 'or', 'that', 'the', 'this', 'to', 'was', 'what', 'when',
        'where', 'who', 'will', 'with', 'und', 'www',
    ];

    /**
     * @param  Builder<Product>  $products
     */
    public function apply(Builder $products, string $term): void
    {
        $products->where(fn (Builder $match) => $match
            ->where(fn (Builder $text) => $this->matchWords($text, $this->words($term)))
            ->orWhereHas('variants', fn (Builder $variants) => $variants->whereIn('sku', $this->skuCandidates($term))));
    }

    /**
     * SKU hit first, then products whose name has every word, then (MySQL) the FULLTEXT score.
     *
     * @param  Builder<Product>  $products
     */
    public function orderByRelevance(Builder $products, string $term): void
    {
        $products->orderByRaw(
            'EXISTS (SELECT 1 FROM product_variants sv WHERE sv.product_id = products.id AND sv.sku IN (?, ?) AND sv.deleted_at IS NULL) DESC',
            $this->skuCandidates($term),
        );

        $words = $this->words($term);

        if ($words !== []) {
            $products->orderByRaw(
                '('.$this->nameLikeSql($words).') DESC',
                $this->likePatterns($words),
            );
        }

        [$fulltext] = $this->splitWords($words);

        if ($fulltext !== [] && $this->usesFulltext($products)) {
            $products->orderByRaw('MATCH(products.name, products.short_description) AGAINST (? IN BOOLEAN MODE) DESC', [$this->against($fulltext)]);
        }
    }

    /**
     * @param  Builder<Product>  $text
     * @param  list<string>  $words
     */
    private function matchWords(Builder $text, array $words): void
    {
        if ($words === []) {
            $text->whereRaw('1 = 0');

            return;
        }

        if (! $this->usesFulltext($text)) {
            foreach ($words as $word) {
                $pattern = $this->likePattern($word);
                $escape = self::LIKE_ESCAPE;

                $text->where(fn (Builder $any) => $any
                    ->whereRaw("products.name LIKE ? ESCAPE '{$escape}'", [$pattern])
                    ->orWhereRaw("products.short_description LIKE ? ESCAPE '{$escape}'", [$pattern]));
            }

            return;
        }

        [$fulltext, $plain] = $this->splitWords($words);

        if ($fulltext !== []) {
            $text->whereRaw('MATCH(products.name, products.short_description) AGAINST (? IN BOOLEAN MODE)', [$this->against($fulltext)]);
        }

        if ($plain !== []) {
            $text->whereRaw($this->nameLikeSql($plain), $this->likePatterns($plain));
        }
    }

    /**
     * SKUs are stored upper-case; the exact term is tried as typed and upper-cased so the SKU index serves
     * the lookup on every driver (MySQL compares case-insensitively anyway).
     *
     * @return array{0: string, 1: string}
     */
    private function skuCandidates(string $term): array
    {
        return [$term, mb_strtoupper($term)];
    }

    /**
     * Letters and digits only, so punctuation never reaches the boolean-mode operators.
     *
     * @return list<string>
     */
    private function words(string $term): array
    {
        return array_values(array_unique(
            preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($term), -1, PREG_SPLIT_NO_EMPTY) ?: [],
        ));
    }

    /**
     * @param  list<string>  $words
     * @return array{0: list<string>, 1: list<string>} words for FULLTEXT, words for LIKE
     */
    private function splitWords(array $words): array
    {
        $fulltext = array_values(array_filter(
            $words,
            fn (string $word) => mb_strlen($word) >= self::FULLTEXT_MIN_LENGTH && ! in_array($word, self::STOPWORDS, true),
        ));

        return [$fulltext, array_values(array_diff($words, $fulltext))];
    }

    /**
     * @param  list<string>  $fulltext
     */
    private function against(array $fulltext): string
    {
        return implode(' ', array_map(fn (string $word) => "+{$word}*", $fulltext));
    }

    /**
     * @param  list<string>  $words
     */
    private function nameLikeSql(array $words): string
    {
        $escape = self::LIKE_ESCAPE;

        return implode(' AND ', array_fill(0, count($words), "products.name LIKE ? ESCAPE '{$escape}'"));
    }

    /**
     * @param  list<string>  $words
     * @return list<string>
     */
    private function likePatterns(array $words): array
    {
        return array_map(fn (string $word) => $this->likePattern($word), $words);
    }

    private function likePattern(string $word): string
    {
        return '%'.Like::escape($word).'%';
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function usesFulltext(Builder $query): bool
    {
        return in_array($query->getConnection()->getDriverName(), ['mysql', 'mariadb'], true);
    }
}
