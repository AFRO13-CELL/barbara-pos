<?php
namespace App\Http\Controllers;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('products')->orderBy('sort_order')->orderBy('name')->get();
        return view('categories.index', compact('categories'));
    }

    public function store(Request $r)
    {
        $d = $r->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('categories', 'name')->whereNull('deleted_at')],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
        Category::create(['name' => $d['name'], 'sort_order' => $d['sort_order'] ?? 0]);
        return back()->with('status', 'Category added.');
    }

    public function update(Request $r, Category $category)
    {
        $d = $r->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('categories', 'name')->ignore($category->id)->whereNull('deleted_at')],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
        $category->update(['name' => $d['name'], 'sort_order' => $d['sort_order'] ?? 0, 'is_active' => $r->boolean('is_active')]);
        return back()->with('status', 'Category saved.');
    }

    public function destroy(Category $category)
    {
        if ($category->products()->exists()) {
            return back()->withErrors(['category' => "\"{$category->name}\" still has products. Move them to another category, or untick Active to archive it."]);
        }
        $category->delete();
        return back()->with('status', 'Category deleted.');
    }
}
