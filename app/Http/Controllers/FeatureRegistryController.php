<?php

namespace App\Http\Controllers;

use App\Models\FeatureRegistry;
use Illuminate\Http\Request;

class FeatureRegistryController extends Controller
{
    public function index()
    {
        $features = FeatureRegistry::where('user_id', auth()->id())
            ->orderBy('feature_name')
            ->get();
        return view('feature-registry.index', compact('features'));
    }

    public function create()
    {
        return view('feature-registry.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'feature_name' => 'required|string|max:255',
            'code_areas' => 'required|string',
            'endpoints' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        FeatureRegistry::create([
            'user_id' => auth()->id(),
            'feature_name' => $validated['feature_name'],
            'code_areas' => array_map('trim', explode(',', $validated['code_areas'])),
            'endpoints' => !empty($validated['endpoints'])
                ? array_map('trim', explode(',', $validated['endpoints']))
                : [],
            'description' => $validated['description'],
        ]);

        return redirect()->route('feature-registry.index')
            ->with('success', 'Feature added to registry.');
    }

    public function edit(FeatureRegistry $featureRegistry)
    {
        return view('feature-registry.edit', compact('featureRegistry'));
    }

    public function update(Request $request, FeatureRegistry $featureRegistry)
    {
        $validated = $request->validate([
            'feature_name' => 'required|string|max:255',
            'code_areas' => 'required|string',
            'endpoints' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $featureRegistry->update([
            'feature_name' => $validated['feature_name'],
            'code_areas' => array_map('trim', explode(',', $validated['code_areas'])),
            'endpoints' => !empty($validated['endpoints'])
                ? array_map('trim', explode(',', $validated['endpoints']))
                : [],
            'description' => $validated['description'],
        ]);

        return redirect()->route('feature-registry.index')
            ->with('success', 'Feature updated.');
    }

    public function destroy(FeatureRegistry $featureRegistry)
    {
        $featureRegistry->delete();
        return redirect()->route('feature-registry.index')
            ->with('success', 'Feature deleted.');
    }
}