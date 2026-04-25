<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ClassificationRule;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function categories()
    {
        return Inertia::render('Settings/Categories', [
            'categories' => Category::withCount('transactions', 'classificationRules')
                ->orderBy('type')->orderBy('sort_order')->get(),
        ]);
    }

    public function storeCategory(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:DEBIT,CREDIT',
            'color' => 'nullable|string|max:20',
            'icon' => 'nullable|string|max:50',
        ]);

        Category::create($request->only(['name', 'type', 'color', 'icon']));
        return back();
    }

    public function destroyCategory(Category $category)
    {
        $category->delete();
        return back();
    }

    public function rules()
    {
        return Inertia::render('Settings/Rules', [
            'rules' => ClassificationRule::with('category:id,name,type,color')
                ->orderBy('priority')->get(),
            'categories' => Category::orderBy('type')->orderBy('name')->get(),
        ]);
    }

    public function storeRule(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'pattern' => 'required|string|max:255',
            'match_type' => 'required|in:EXACT,CONTAINS,REGEX',
            'priority' => 'nullable|integer|min:1',
        ]);

        ClassificationRule::create($request->only(['category_id', 'pattern', 'match_type', 'priority']));
        return back();
    }

    public function destroyRule(ClassificationRule $rule)
    {
        $rule->delete();
        return back();
    }

    /**
     * Approve a suggested category (convert to regular).
     */
    public function approveCategory(Category $category)
    {
        $category->update([
            'is_suggested' => false,
        ]);
        return back();
    }
}
