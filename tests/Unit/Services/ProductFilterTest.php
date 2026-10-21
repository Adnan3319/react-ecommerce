<?php

use App\Services\Storefront\ProductFilter;

it('parses every valid parameter', function () {
    $filter = ProductFilter::fromQuery([
        'q' => '  summer   dress ',
        'category' => 'Fashion',
        'min' => '500',
        'max' => '2500.50',
        'in_stock' => '1',
        'sort' => 'price_desc',
        'f' => ['Size' => 'M,l', 'color' => ['Pink']],
    ]);

    expect($filter->q)->toBe('summer dress')
        ->and($filter->category)->toBe('fashion')
        ->and($filter->minPrice)->toBe(50000)
        ->and($filter->maxPrice)->toBe(250050)
        ->and($filter->inStock)->toBeTrue()
        ->and($filter->sort)->toBe('price_desc')
        ->and($filter->heads)->toBe(['size' => ['m', 'l'], 'color' => ['pink']]);
});

it('uses safe defaults for an empty query', function () {
    $filter = ProductFilter::fromQuery([]);

    expect($filter->q)->toBeNull()
        ->and($filter->category)->toBeNull()
        ->and($filter->minPrice)->toBeNull()
        ->and($filter->maxPrice)->toBeNull()
        ->and($filter->inStock)->toBeFalse()
        ->and($filter->sort)->toBe('newest')
        ->and($filter->heads)->toBe([])
        ->and($filter->isNarrowed())->toBeFalse();
});

it('ignores invalid values', function (array $query) {
    $filter = ProductFilter::fromQuery($query);

    expect($filter->minPrice)->toBeNull()
        ->and($filter->maxPrice)->toBeNull()
        ->and($filter->sort)->toBe('newest')
        ->and($filter->heads)->toBe([])
        ->and($filter->q)->toBeNull();
})->with([
    'words as prices' => [['min' => 'cheap', 'max' => 'lots']],
    'negative price' => [['min' => '-5']],
    'too many decimals' => [['max' => '10.999']],
    'out of range price' => [['max' => '99999999999']],
    'array price' => [['min' => ['1']]],
    'unknown sort' => [['sort' => 'random']],
    'array sort' => [['sort' => ['price_asc']]],
    'head filter not an array' => [['f' => 'size=m']],
    'empty head values' => [['f' => ['size' => ',,']]],
    'overlong key' => [['f' => [str_repeat('a', 51) => 'm']]],
    'array term' => [['q' => ['x']]],
    'blank term' => [['q' => "  \n "]],
]);

it('swaps a reversed price range', function () {
    $filter = ProductFilter::fromQuery(['min' => '900', 'max' => '100']);

    expect($filter->minPrice)->toBe(10000)
        ->and($filter->maxPrice)->toBe(90000);
});

it('caps the search term, the heads and the values per head', function () {
    $heads = array_combine(
        array_map(fn (int $i) => "head{$i}", range(1, 8)),
        array_fill(0, 8, implode(',', array_map(fn (int $i) => "v{$i}", range(1, 30)))),
    );

    $filter = ProductFilter::fromQuery(['q' => str_repeat('x', 150), 'f' => $heads]);

    expect(mb_strlen($filter->q))->toBe(100)
        ->and($filter->heads)->toHaveCount(5)
        ->and($filter->heads['head1'])->toHaveCount(20);
});

it('treats an in-stock flag that is not true as off', function (mixed $value) {
    expect(ProductFilter::fromQuery(['in_stock' => $value])->inStock)->toBeFalse();
})->with(['0', 'no', '', 'banana']);

it('drops the search term and category on a category page', function () {
    $filter = ProductFilter::fromQuery(['q' => 'dress', 'category' => 'fashion', 'min' => '100'])->forCategoryPage();

    expect($filter->q)->toBeNull()
        ->and($filter->category)->toBeNull()
        ->and($filter->minPrice)->toBe(10000);
});

it('keeps only heads and values the category has', function () {
    $filter = ProductFilter::fromQuery(['f' => ['size' => 'm,xxl', 'material' => 'wool', 'color' => 'pink']])
        ->restrictedTo(['size' => ['s', 'm'], 'color' => ['blue']]);

    expect($filter->heads)->toBe(['size' => ['m']]);
});

it('reports a narrowed listing for any filter or sort', function (array $query) {
    expect(ProductFilter::fromQuery($query)->isNarrowed())->toBeTrue();
})->with([
    'price' => [['min' => '10']],
    'stock' => [['in_stock' => '1']],
    'head' => [['f' => ['size' => 'm']]],
    'sort' => [['sort' => 'price_asc']],
]);

it('exposes taka strings for the active price range', function () {
    $array = ProductFilter::fromQuery(['min' => '1650.5'])->toArray();

    expect($array['min'])->toBe('1650.50')
        ->and($array['max'])->toBeNull();
});

describe('search term', function () {
    it('uses a term of two or more characters', function () {
        $filter = ProductFilter::fromQuery(['q' => 'dr']);

        expect($filter->searchTerm())->toBe('dr')
            ->and($filter->hasShortTerm())->toBeFalse();
    });

    it('treats a one-character term as too short', function () {
        $filter = ProductFilter::fromQuery(['q' => ' m ']);

        expect($filter->searchTerm())->toBeNull()
            ->and($filter->hasShortTerm())->toBeTrue()
            ->and($filter->toArray()['q_too_short'])->toBeTrue();
    });

    it('has no term and no short-term flag without q', function () {
        $filter = ProductFilter::fromQuery([]);

        expect($filter->searchTerm())->toBeNull()
            ->and($filter->hasShortTerm())->toBeFalse();
    });
});

describe('best match sort', function () {
    it('is the default while a usable term is set', function () {
        expect(ProductFilter::fromQuery(['q' => 'dress'])->sort)->toBe('best_match');
    });

    it('is newest without a usable term', function (array $query) {
        expect(ProductFilter::fromQuery($query)->sort)->toBe('newest');
    })->with([
        'no term' => [[]],
        'short term' => [['q' => 'd']],
        'best match requested without a term' => [['sort' => 'best_match']],
    ]);

    it('yields to an explicit sort', function () {
        expect(ProductFilter::fromQuery(['q' => 'dress', 'sort' => 'price_asc'])->sort)->toBe('price_asc');
    });

    it('does not narrow the listing by itself but a price sort does', function () {
        expect(ProductFilter::fromQuery(['q' => 'dress'])->isNarrowed())->toBeFalse()
            ->and(ProductFilter::fromQuery(['q' => 'dress', 'sort' => 'price_asc'])->isNarrowed())->toBeTrue();
    });

    it('becomes newest on a category page', function () {
        expect(ProductFilter::fromQuery(['q' => 'dress'])->forCategoryPage()->sort)->toBe('newest');
    });
});
