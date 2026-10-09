<?php
namespace App\Http\Controllers;
use App\Models\{Brand, Category, Color, Product, Size};
use App\Services\CatalogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    private function lists(): array
    {
        return [
            'categories' => Category::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->pluck('name'),
            'sizes' => Size::orderBy('sort_order')->orderBy('name')->pluck('name'),
            'colors' => Color::orderBy('name')->pluck('name'),
        ];
    }

    private function variationRules(bool $canCost): array
    {
        return [
            'size' => 'nullable|string|max:40', 'color' => 'nullable|string|max:40',
            'material' => 'nullable|string|max:60', 'style' => 'nullable|string|max:60',
            'gender' => 'nullable|string|max:30', 'item_condition' => 'nullable|string|max:30',
            'cost_price' => $canCost ? 'required|numeric|min:0' : 'nullable|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0|lt:selling_price',
            'min_stock' => 'nullable|integer|min:0',
        ];
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $query = Product::query()->with(['category', 'brand'])
            ->withCount('variations')->withSum('variations', 'stock_qty')
            ->withMin('variations', 'selling_price')->withMax('variations', 'selling_price');
        if ($request->boolean('archived')) $query->onlyTrashed();
        if ($request->filled('category')) $query->where('category_id', $request->category);
        if ($q !== '') {
            $like = "%{$q}%";
            $query->where(function ($w) use ($like) {
                $w->where('name', 'like', $like)
                  ->orWhereHas('category', fn ($c) => $c->where('name', 'like', $like))
                  ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', $like))
                  ->orWhereHas('variations', fn ($v) => $v->where('sku', 'like', $like)->orWhere('barcode', 'like', $like)
                        ->orWhere('size', 'like', $like)->orWhere('color', 'like', $like));
            });
        }
        $products = $query->latest()->paginate(20)->withQueryString();
        $categories = Category::orderBy('name')->get();
        return view('products.index', compact('products', 'categories'));
    }

    public function create()
    {
        return view('products.create', $this->lists());
    }

    public function store(Request $r)
    {
        $canCost = $r->user()->can_do('costs.view');
        $d = $r->validate(array_merge($this->variationRules($canCost), [
            'name' => 'required|string|max:150', 'category_id' => 'required|exists:categories,id',
            'brand' => 'nullable|string|max:100', 'description' => 'nullable|string|max:2000',
            'image' => 'nullable|image|max:2048',
            'sku' => 'nullable|string|max:60|unique:product_variations,sku',
            'barcode' => 'nullable|string|max:60|unique:product_variations,barcode',
            'stock_qty' => 'nullable|integer|min:0',
        ]));
        $d['cost_price'] = $d['cost_price'] ?? 0;

        $product = DB::transaction(function () use ($r, $d) {
            $brandId = !empty($d['brand']) ? Brand::firstOrCreate(['name' => trim($d['brand'])])->id : null;
            $product = Product::create([
                'category_id' => $d['category_id'], 'brand_id' => $brandId, 'name' => $d['name'],
                'description' => $d['description'] ?? null,
                'image_path' => $r->hasFile('image') ? $r->file('image')->store('products', 'public') : null,
            ]);
            CatalogService::createVariation($product, $d);
            return $product;
        });
        return redirect()->route('products.edit', $product)->with('status', 'Product saved. You can add more sizes and colors below.');
    }

    public function edit(Product $product)
    {
        $product->load(['variations', 'brand']);
        return view('products.edit', array_merge($this->lists(), ['product' => $product]));
    }

    public function update(Request $r, Product $product)
    {
        $d = $r->validate([
            'name' => 'required|string|max:150', 'category_id' => 'required|exists:categories,id',
            'brand' => 'nullable|string|max:100', 'description' => 'nullable|string|max:2000',
            'image' => 'nullable|image|max:2048',
        ]);
        $product->fill([
            'name' => $d['name'], 'category_id' => $d['category_id'], 'description' => $d['description'] ?? null,
            'brand_id' => !empty($d['brand']) ? Brand::firstOrCreate(['name' => trim($d['brand'])])->id : null,
        ]);
        if ($r->hasFile('image')) $product->image_path = $r->file('image')->store('products', 'public');
        $product->save();
        return back()->with('status', 'Product updated.');
    }

    public function archive(Product $product)
    {
        $product->delete();
        return redirect()->route('products.index')->with('status', "\"{$product->name}\" archived. Its sales history is kept.");
    }

    public function restore(int $id)
    {
        $product = Product::onlyTrashed()->findOrFail($id);
        $product->restore();
        return redirect()->route('products.edit', $product)->with('status', 'Product restored.');
    }

    public function duplicate(Product $product)
    {
        $copy = DB::transaction(function () use ($product) {
            $copy = $product->replicate(['image_path']);
            $copy->name = $product->name . ' (Copy)';
            $copy->save();
            foreach ($product->variations as $v) {
                CatalogService::createVariation($copy, [
                    'size' => $v->size, 'color' => $v->color, 'material' => $v->material, 'style' => $v->style,
                    'gender' => $v->gender, 'item_condition' => $v->item_condition,
                    'cost_price' => $v->cost_price, 'selling_price' => $v->selling_price,
                    'discount_price' => $v->discount_price, 'min_stock' => $v->min_stock, 'stock_qty' => 0,
                ]);
            }
            return $copy;
        });
        return redirect()->route('products.edit', $copy)->with('status', 'Product duplicated with zero stock. Adjust the name and prices.');
    }
}
