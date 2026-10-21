<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Services\Product\ProductPayload;
use App\Services\Product\ProductService;
use App\Support\Money;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * 20 realistic products each for Kitchenware and Fashion so the storefront can be reviewed locally.
 * Images are the photos already shipped under public/images (kitchen photos for Kitchenware, the category
 * photo for Fashion): replace them from the admin.
 * DatabaseSeeder only calls this in the local environment. Safe to run repeatedly (matched by slug).
 */
class ProductSampleSeeder extends Seeder
{
    private const SIZES_CLOTHING = ['S', 'M', 'L', 'XL'];

    /** Photos under public/images used as product images, per root category slug. */
    private const PHOTOS = [
        'kitchenware' => ['sliders/1.jpg', 'sliders/2.jpg', 'sliders/3.jpg', 'categories/kitchenware.jpg'],
        'fashion' => ['categories/fashion.jpg'],
    ];

    public function __construct(private readonly ProductService $products) {}

    public function run(): void
    {
        foreach (['kitchenware' => $this->kitchenware(), 'fashion' => $this->fashion()] as $slug => $items) {
            $category = Category::query()->where('slug', $slug)->firstOrFail();

            foreach ($items as $position => $item) {
                $this->seed($category, $item, $position);
            }
        }
    }

    /**
     * @param  array{name: string, short: string, price: int, sale?: int, heads?: array<string, list<string>>, featured?: bool, stock?: int}  $item
     */
    private function seed(Category $category, array $item, int $position): void
    {
        $slug = Str::slug($item['name']);

        if (Product::withTrashed()->where('slug', $slug)->exists()) {
            return;
        }

        $price = Money::toMinor((string) $item['price']);
        $sale = isset($item['sale']) ? Money::toMinor((string) $item['sale']) : null;
        $stock = $item['stock'] ?? 25;
        $heads = $item['heads'] ?? [];

        $this->products->create(new ProductPayload(
            attributes: [
                'category_id' => $category->id,
                'name' => $item['name'],
                'slug' => $slug,
                'short_description' => $item['short'],
                'description' => $item['short']."\n\nCash on Delivery available across Bangladesh.",
                'status' => 'active',
                'is_featured' => $item['featured'] ?? false,
            ],
            mainImage: $this->photo($category->slug, $position),
            hasVariants: $heads !== [],
            simple: ['sku' => null, 'price' => $price, 'sale_price' => $sale, 'stock' => $stock],
            heads: array_map(
                fn (string $name, array $values) => ['id' => null, 'name' => $name, 'values' => array_map(fn (string $value) => ['id' => null, 'value' => $value], $values)],
                array_keys($heads),
                $heads,
            ),
            variants: array_map(
                fn (array $values, int $index) => [
                    'id' => null,
                    'values' => $values,
                    'sku' => null,
                    'price' => $price,
                    'sale_price' => $sale,
                    // Every fourth combination is sold out so the stock states show up.
                    'stock' => $stock === 0 || ($index % 4 === 3) ? 0 : $stock,
                    'image_ref' => null,
                    'status' => 'active',
                ],
                $this->combinations($heads),
                array_keys($this->combinations($heads)),
            ),
        ));
    }

    /**
     * @param  array<string, list<string>>  $heads
     * @return list<list<string>>
     */
    private function combinations(array $heads): array
    {
        $result = [[]];

        foreach ($heads as $values) {
            $next = [];

            foreach ($result as $combination) {
                foreach ($values as $value) {
                    $next[] = [...$combination, $value];
                }
            }

            $result = $next;
        }

        return $heads === [] ? [] : $result;
    }

    /**
     * A shipped photo as an upload, so it goes through the normal media pipeline (preset, thumb, webp).
     */
    private function photo(string $categorySlug, int $position): UploadedFile
    {
        $photos = self::PHOTOS[$categorySlug];
        $file = $photos[$position % count($photos)];

        return new UploadedFile(public_path('images/'.$file), basename($file), null, null, true);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function kitchenware(): array
    {
        return [
            ['name' => 'Non-Stick Fry Pan 28cm', 'short' => 'Durable granite-coated fry pan with a heat-resistant handle, suitable for gas and induction.', 'price' => 1850, 'sale' => 1590, 'featured' => true],
            ['name' => 'Stainless Steel Cooking Pot Set (3 Pcs)', 'short' => 'Three thick-bottom pots with glass lids for everyday curries, rice and soup.', 'price' => 4200, 'featured' => true],
            ['name' => 'Cast Iron Kadai 10 Inch', 'short' => 'Pre-seasoned cast iron kadai that holds heat evenly for deep frying and bhuna.', 'price' => 3200],
            ['name' => 'Pressure Cooker 5 Litre', 'short' => 'Aluminium pressure cooker with safety valve and a gasket that seals tightly.', 'price' => 3900, 'sale' => 3499, 'featured' => true],
            ['name' => 'Glass Food Storage Container Set (5 Pcs)', 'short' => 'Leak-proof borosilicate glass containers with snap-lock lids, safe for microwave and freezer.', 'price' => 1650],
            ['name' => 'Airtight Storage Jar', 'short' => 'BPA-free airtight jar that keeps rice, lentils and flour fresh and pest-free.', 'price' => 650, 'heads' => ['Size' => ['2 kg', '5 kg', '10 kg']]],
            ['name' => 'Stainless Steel Spice Rack 12 Jar', 'short' => 'Rotating spice rack with twelve labelled jars to keep the counter tidy.', 'price' => 1450],
            ['name' => 'Borosilicate Glass Water Jug 1.5 Litre', 'short' => 'Heat-resistant glass jug with a leak-free lid, for water, juice and tea.', 'price' => 780],
            ['name' => 'Tempered Glass Drinking Set (6 Pcs)', 'short' => 'Six clear tumblers that stay sturdy through daily use and dishwashing.', 'price' => 1250],
            ['name' => 'Ceramic Dinner Set 24 Pcs', 'short' => 'Elegant 24-piece dinner set for six: plates, bowls and side plates with a glossy glaze.', 'price' => 5800, 'sale' => 4999, 'featured' => true],
            ['name' => 'Bamboo Chopping Board', 'short' => 'Knife-friendly bamboo board with a juice groove and a hanging hole.', 'price' => 650, 'heads' => ['Size' => ['Small', 'Medium', 'Large']]],
            ['name' => 'Chef Knife Set with Block (5 Pcs)', 'short' => 'Stainless steel knives with ergonomic handles and a wooden block.', 'price' => 2800],
            ['name' => 'Silicone Spatula Set (6 Pcs)', 'short' => 'Heat-resistant silicone utensils that are gentle on non-stick surfaces.', 'price' => 720],
            ['name' => 'Electric Kettle 1.8 Litre', 'short' => 'Fast-boil stainless steel kettle with auto shut-off and boil-dry protection.', 'price' => 2100],
            ['name' => 'Rolling Board and Pin Set', 'short' => 'Smooth wooden board and pin for rolling out ruti, paratha and pitha.', 'price' => 1100],
            ['name' => 'Stainless Steel Tiffin Carrier 3 Layer', 'short' => 'Leak-resistant three-tier tiffin carrier with a carry handle for office and school.', 'price' => 1350],
            ['name' => 'Insulated Thermos Flask 1 Litre', 'short' => 'Double-wall vacuum flask that keeps drinks hot for 12 hours or cold for 24.', 'price' => 1650, 'heads' => ['Color' => ['Silver', 'Black']], 'featured' => true],
            ['name' => 'Dish Drying Rack 2-Tier', 'short' => 'Rust-resistant two-tier rack with a cutlery holder and a drip tray.', 'price' => 1890],
            ['name' => 'Non-Stick Roti Tawa 12 Inch', 'short' => 'Flat non-stick tawa that heats evenly for soft rotis and parathas.', 'price' => 990, 'stock' => 0],
            ['name' => 'Stainless Steel Mixing Bowl Set (5 Pcs)', 'short' => 'Nesting mixing bowls in five sizes, easy to clean and store.', 'price' => 1550],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fashion(): array
    {
        $clothing = ['Size' => self::SIZES_CLOTHING];

        return [
            ['name' => 'Women\'s Cotton Kurti', 'short' => 'Soft breathable cotton kurti with a relaxed fit, ideal for daily wear.', 'price' => 1450, 'heads' => [...$clothing, 'Color' => ['Pink', 'Blue']], 'featured' => true],
            ['name' => 'Printed Georgette Saree', 'short' => 'Lightweight georgette saree with a printed border and matching blouse piece.', 'price' => 3200, 'sale' => 2790, 'featured' => true],
            ['name' => 'Embroidered Salwar Kameez 3-Piece', 'short' => 'Three-piece set with hand-finished embroidery, dupatta included.', 'price' => 3590, 'heads' => $clothing],
            ['name' => 'Summer Floral Midi Dress', 'short' => 'Flowy floral midi dress in breathable fabric for warm days.', 'price' => 2200, 'sale' => 1890, 'heads' => $clothing, 'featured' => true],
            ['name' => 'Men\'s Cotton Panjabi', 'short' => 'Classic cotton panjabi with a mandarin collar, perfect for Eid and Friday prayers.', 'price' => 2450, 'heads' => [...$clothing, 'Color' => ['White', 'Navy']]],
            ['name' => 'Men\'s Slim Fit Polo Shirt', 'short' => 'Pique cotton polo with a ribbed collar and a slim, comfortable fit.', 'price' => 990, 'heads' => [...$clothing, 'Color' => ['Black', 'Green']]],
            ['name' => 'Men\'s Casual Denim Jeans', 'short' => 'Stretch denim jeans with a straight cut and a durable stitch.', 'price' => 2300, 'heads' => ['Size' => ['30', '32', '34', '36']]],
            ['name' => 'Women\'s Palazzo Pants', 'short' => 'Wide-leg palazzo pants in soft viscose with an elastic waist.', 'price' => 890, 'heads' => $clothing],
            ['name' => 'Cotton Hijab', 'short' => 'Soft, breathable cotton hijab that drapes easily and stays in place.', 'price' => 450, 'heads' => ['Color' => ['Black', 'Maroon', 'Grey']]],
            ['name' => 'Premium Silk Scarf', 'short' => 'Smooth silk-blend scarf with a subtle sheen, for work or an evening out.', 'price' => 790],
            ['name' => 'Leather Ladies Handbag', 'short' => 'Structured faux-leather handbag with a zip closure and an inner pocket.', 'price' => 3250, 'heads' => ['Color' => ['Black', 'Brown']], 'featured' => true],
            ['name' => 'Canvas Tote Bag', 'short' => 'Sturdy cotton canvas tote with a reinforced handle for books and groceries.', 'price' => 890],
            ['name' => 'Men\'s Leather Wallet', 'short' => 'Slim bi-fold wallet with card slots and a coin pocket.', 'price' => 1190, 'sale' => 990],
            ['name' => 'Leather Belt', 'short' => 'Genuine leather belt with a brushed metal buckle.', 'price' => 850, 'heads' => ['Size' => ['32', '34', '36', '38']]],
            ['name' => 'Women\'s Flat Sandals', 'short' => 'Cushioned flat sandals with a non-slip sole and an adjustable strap.', 'price' => 1450, 'heads' => ['Size' => ['36', '37', '38', '39', '40']]],
            ['name' => 'Men\'s Formal Shoes', 'short' => 'Polished lace-up formal shoes with a padded insole for long days.', 'price' => 3900, 'heads' => ['Size' => ['40', '41', '42', '43', '44']]],
            ['name' => 'Unisex Fleece Hoodie', 'short' => 'Warm brushed-fleece hoodie with a kangaroo pocket and a drawstring hood.', 'price' => 1990, 'heads' => [...$clothing, 'Color' => ['Charcoal', 'Olive']]],
            ['name' => 'Cotton T-Shirt Pack of 3', 'short' => 'Three everyday crew-neck tees in 100% combed cotton.', 'price' => 1290, 'heads' => $clothing],
            ['name' => 'Sports Cap', 'short' => 'Lightweight adjustable cap with a curved brim and a breathable lining.', 'price' => 450, 'heads' => ['Color' => ['Black', 'White', 'Red']], 'stock' => 0],
            ['name' => 'Laptop Backpack', 'short' => 'Water-resistant backpack with a padded laptop sleeve and multiple compartments.', 'price' => 2150, 'heads' => ['Color' => ['Black', 'Navy']]],
        ];
    }
}
