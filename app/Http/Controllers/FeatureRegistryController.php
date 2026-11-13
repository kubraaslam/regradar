<?php

namespace App\Http\Controllers;

use App\Models\FeatureRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FeatureRegistryController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $all = FeatureRegistry::visibleTo($user)
            ->with('user')
            ->orderByRaw('repository IS NULL')   // scoped entries first, unscoped last
            ->orderBy('repository')
            ->orderBy('feature_name')
            ->get();

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'repository' => (string) $request->query('repository', ''),
            'owner' => $user->canViewAll() ? (string) $request->query('owner', '') : '',
        ];

        // Filtered in memory: code areas and endpoints are stored as JSON, where
        // slashes are escaped, so a database LIKE would miss paths such as
        // Illuminate/Routing.
        $features = $all->filter(function (FeatureRegistry $f) use ($filters) {
            if ($filters['repository'] === '__all') {
                if ($f->repository !== null) {
                    return false;
                }
            } elseif ($filters['repository'] !== '' && $f->repository !== $filters['repository']) {
                return false;
            }

            if ($filters['owner'] !== '' && (string) $f->user_id !== $filters['owner']) {
                return false;
            }

            if ($filters['q'] === '') {
                return true;
            }

            $haystack = implode("\n", array_merge(
                [$f->feature_name, $f->description, $f->repository],
                $f->code_areas ?? [],
                $f->endpoints ?? [],
            ));

            return Str::contains($haystack, $filters['q'], ignoreCase: true);
        })->values();

        return view('feature-registry.index', [
            'features' => $features,
            'total' => $all->count(),
            'filters' => $filters,
            'filtered' => collect($filters)->filter(fn($v) => $v !== '')->isNotEmpty(),
            'options' => [
                'repositories' => $all->pluck('repository')->filter()->unique()->sort()->values(),
                'hasUnscoped' => $all->contains(fn($f) => $f->repository === null),
                'owners' => $user->canViewAll()
                    ? $all->pluck('user')->filter()->unique('id')->sortBy('name')->values()
                    : collect(),
            ],
        ]);
    }

    public function create()
    {
        return view('feature-registry.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);

        FeatureRegistry::create(['user_id' => auth()->id()] + $this->attributes($validated));

        return redirect()->route('feature-registry.index')
            ->with('success', 'Feature added to registry.');
    }

    public function edit(Request $request, FeatureRegistry $featureRegistry)
    {
        $this->authorizeOwner($request, $featureRegistry);

        return view('feature-registry.edit', compact('featureRegistry'));
    }

    public function update(Request $request, FeatureRegistry $featureRegistry)
    {
        $this->authorizeOwner($request, $featureRegistry);

        $featureRegistry->update($this->attributes($this->validated($request)));

        return redirect()->route('feature-registry.index')
            ->with('success', 'Feature updated.');
    }

    public function destroy(Request $request, FeatureRegistry $featureRegistry)
    {
        $this->authorizeOwner($request, $featureRegistry);

        $featureRegistry->delete();

        return redirect()->route('feature-registry.index')
            ->with('success', 'Feature deleted.');
    }

    /**
     * Previously any QA engineer could edit or delete any other user's entry by
     * changing the ID in the URL. Owners and admins only.
     */
    protected function authorizeOwner(Request $request, FeatureRegistry $featureRegistry): void
    {
        abort_unless($request->user()->owns($featureRegistry), 403, 'You can only change your own registry entries.');
    }

    /**
     * Mirrors docs/feature-registry.schema.json, so the form cannot store an
     * entry the documented schema would reject.
     */
    protected function validated(Request $request): array
    {
        $validated = $request->validate([
            'repository' => ['nullable', 'string', 'max:255', 'regex:#^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$#'],
            'feature_name' => 'required|string|max:255',
            'code_areas' => 'required|string',
            'endpoints' => 'nullable|string',
            'description' => 'nullable|string',
        ], [
            'repository.regex' => 'Repository must be in the form owner/name, for example laravel/framework.',
        ]);

        // "required" accepts ", ," which splits to nothing. The schema requires
        // at least one code area, so check after splitting.
        if ($this->splitList($validated['code_areas']) === []) {
            throw ValidationException::withMessages([
                'code_areas' => 'Enter at least one code area.',
            ]);
        }

        return $validated;
    }

    /**
     * Normalise form input into stored attributes. Comma separated fields become
     * arrays with blanks dropped, so a trailing comma does not store an empty
     * code area that would match every diff.
     */
    protected function attributes(array $validated): array
    {
        return [
            'repository' => filled($validated['repository'] ?? null) ? strtolower(trim($validated['repository'])) : null,
            'feature_name' => trim($validated['feature_name']),
            'code_areas' => $this->splitList($validated['code_areas']),
            'endpoints' => $this->splitList($validated['endpoints'] ?? ''),
            'description' => $validated['description'] ?? null,
        ];
    }

    protected function splitList(?string $value): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $value)), fn($v) => $v !== ''));
    }
}
