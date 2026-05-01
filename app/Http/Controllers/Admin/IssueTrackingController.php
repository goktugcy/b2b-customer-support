<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\SupportProject;
use App\Models\TicketCategory;
use App\Models\TicketCustomField;
use App\Models\TicketCustomFieldOption;
use App\Models\TicketTag;
use App\Models\TicketTracker;
use App\Services\Tickets\IssueTrackingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class IssueTrackingController extends Controller
{
    public function __construct(private readonly IssueTrackingService $issueTracking) {}

    public function index(Request $request): Response
    {
        $this->authorizeManage($request);

        return Inertia::render('Admin/IssueTracking/Index', [
            'companies' => Company::clients()->orderBy('name')->get(['id', 'public_id', 'name'])
                ->map(fn (Company $company): array => ['id' => $company->public_id, 'name' => $company->name]),
            'projects' => $this->issueTracking->projectOptions(),
            'trackers' => TicketTracker::query()
                ->with(['customFields.options'])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->map(fn (TicketTracker $tracker): array => [
                    ...$this->issueTracking->trackerPayload($tracker),
                    'description' => $tracker->description,
                    'custom_fields' => $tracker->customFields->map(fn (TicketCustomField $field): array => $this->issueTracking->customFieldPayload($field))->values(),
                ]),
            'categories' => $this->issueTracking->categoryOptions(),
            'tags' => $this->issueTracking->tagOptions(),
            'customFieldTypes' => collect(TicketCustomField::TYPES)
                ->map(fn (string $type): array => ['value' => $type, 'label' => Str::headline($type)])
                ->values(),
        ]);
    }

    public function storeProject(Request $request): RedirectResponse
    {
        $this->authorizeManage($request);

        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,public_id'],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(['active', 'disabled'])],
            'is_default' => ['boolean'],
        ]);
        $company = Company::query()->where('public_id', $validated['company_id'])->firstOrFail();
        $slug = $this->uniqueSlug(SupportProject::class, $validated['name'], ['company_id' => $company->id]);

        if ($validated['is_default'] ?? false) {
            SupportProject::query()->where('company_id', $company->id)->update(['is_default' => false]);
        }

        SupportProject::create([
            'company_id' => $company->id,
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'is_default' => $validated['is_default'] ?? false,
        ]);

        return back()->with('success', 'Project created.');
    }

    public function updateProject(Request $request, SupportProject $project): RedirectResponse
    {
        $this->authorizeManage($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(['active', 'disabled'])],
            'is_default' => ['boolean'],
        ]);

        if (($validated['is_default'] ?? false) && ! $project->is_default) {
            SupportProject::query()->where('company_id', $project->company_id)->update(['is_default' => false]);
        }

        $project->update($validated + ['slug' => $project->slug]);

        return back()->with('success', 'Project updated.');
    }

    public function storeTracker(Request $request): RedirectResponse
    {
        $this->authorizeManage($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:1000'],
            'color' => ['required', 'string', 'max:32'],
            'status' => ['required', Rule::in(['active', 'disabled'])],
            'is_default' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
        $slug = $this->uniqueSlug(TicketTracker::class, $validated['name']);

        if ($validated['is_default'] ?? false) {
            TicketTracker::query()->update(['is_default' => false]);
        }

        TicketTracker::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'color' => $validated['color'],
            'status' => $validated['status'],
            'is_default' => $validated['is_default'] ?? false,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return back()->with('success', 'Tracker created.');
    }

    public function updateTracker(Request $request, TicketTracker $tracker): RedirectResponse
    {
        $this->authorizeManage($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:1000'],
            'color' => ['required', 'string', 'max:32'],
            'status' => ['required', Rule::in(['active', 'disabled'])],
            'is_default' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        if (($validated['is_default'] ?? false) && ! $tracker->is_default) {
            TicketTracker::query()->update(['is_default' => false]);
        }

        $tracker->update([
            ...$validated,
            'is_default' => $validated['is_default'] ?? false,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return back()->with('success', 'Tracker updated.');
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $this->authorizeManage($request);

        $validated = $request->validate([
            'project_id' => ['required', 'exists:support_projects,public_id'],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(['active', 'disabled'])],
        ]);
        $project = SupportProject::query()->where('public_id', $validated['project_id'])->firstOrFail();

        TicketCategory::create([
            'support_project_id' => $project->id,
            'name' => $validated['name'],
            'slug' => $this->uniqueSlug(TicketCategory::class, $validated['name'], ['support_project_id' => $project->id]),
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
        ]);

        return back()->with('success', 'Category created.');
    }

    public function updateCategory(Request $request, TicketCategory $category): RedirectResponse
    {
        $this->authorizeManage($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(['active', 'disabled'])],
        ]);

        $category->update($validated + ['slug' => $category->slug]);

        return back()->with('success', 'Category updated.');
    }

    public function storeTag(Request $request): RedirectResponse
    {
        $this->authorizeManage($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:40'],
            'color' => ['required', 'string', 'max:32'],
        ]);

        TicketTag::create([
            'name' => $validated['name'],
            'slug' => $this->uniqueSlug(TicketTag::class, $validated['name']),
            'color' => $validated['color'],
        ]);

        return back()->with('success', 'Tag created.');
    }

    public function updateTag(Request $request, TicketTag $tag): RedirectResponse
    {
        $this->authorizeManage($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:40'],
            'color' => ['required', 'string', 'max:32'],
        ]);

        $slug = Str::slug($validated['name']);
        $exists = TicketTag::query()
            ->where('slug', $slug)
            ->whereKeyNot($tag->id)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['name' => 'Tag name already exists.']);
        }

        $tag->update([
            'name' => $validated['name'],
            'slug' => $slug,
            'color' => $validated['color'],
        ]);

        return back()->with('success', 'Tag updated.');
    }

    public function destroyTag(Request $request, TicketTag $tag): RedirectResponse
    {
        $this->authorizeManage($request);

        $tag->delete();

        return back()->with('success', 'Tag deleted.');
    }

    public function storeCustomField(Request $request): RedirectResponse
    {
        $this->authorizeManage($request);

        $validated = $request->validate([
            'tracker_id' => ['required', 'exists:ticket_trackers,public_id'],
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in(TicketCustomField::TYPES)],
            'is_required' => ['boolean'],
            'validation_regex' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'disabled'])],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'options' => ['nullable'],
        ]);
        $tracker = TicketTracker::query()->where('public_id', $validated['tracker_id'])->firstOrFail();

        DB::transaction(function () use ($validated, $tracker): void {
            $field = TicketCustomField::create([
                'ticket_tracker_id' => $tracker->id,
                'name' => $validated['name'],
                'slug' => $this->uniqueSlug(TicketCustomField::class, $validated['name'], ['ticket_tracker_id' => $tracker->id]),
                'type' => $validated['type'],
                'is_required' => $validated['is_required'] ?? false,
                'validation_regex' => $validated['validation_regex'] ?? null,
                'status' => $validated['status'],
                'sort_order' => $validated['sort_order'] ?? 0,
                'settings' => [],
            ]);

            $this->syncOptions($field, $validated['options'] ?? null);
        });

        return back()->with('success', 'Custom field created.');
    }

    public function updateCustomField(Request $request, TicketCustomField $customField): RedirectResponse
    {
        $this->authorizeManage($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in(TicketCustomField::TYPES)],
            'is_required' => ['boolean'],
            'validation_regex' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'disabled'])],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'options' => ['nullable'],
        ]);

        DB::transaction(function () use ($customField, $validated): void {
            $customField->update([
                'name' => $validated['name'],
                'type' => $validated['type'],
                'is_required' => $validated['is_required'] ?? false,
                'validation_regex' => $validated['validation_regex'] ?? null,
                'status' => $validated['status'],
                'sort_order' => $validated['sort_order'] ?? 0,
            ]);

            $this->syncOptions($customField, $validated['options'] ?? null);
        });

        return back()->with('success', 'Custom field updated.');
    }

    public function destroyCustomField(Request $request, TicketCustomField $customField): RedirectResponse
    {
        $this->authorizeManage($request);

        $customField->delete();

        return back()->with('success', 'Custom field deleted.');
    }

    private function authorizeManage(Request $request): void
    {
        abort_unless($request->user()?->can('issue_tracking.manage') === true, 403);
    }

    /**
     * @param  class-string  $model
     */
    private function uniqueSlug(string $model, string $name, array $where = []): string
    {
        $base = Str::slug($name) ?: Str::lower(Str::random(8));
        $slug = $base;
        $suffix = 2;

        while ($this->slugExists($model, $slug, $where)) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    /**
     * @param  class-string  $model
     */
    private function slugExists(string $model, string $slug, array $where): bool
    {
        $query = $model::query()->where('slug', $slug);

        foreach ($where as $column => $value) {
            $query->where($column, $value);
        }

        return $query->exists();
    }

    private function syncOptions(TicketCustomField $field, string|array|null $options): void
    {
        if (! in_array($field->type, ['single_select', 'multi_select'], true)) {
            $field->options()->delete();

            return;
        }

        $normalized = $this->issueTracking->normalizeManagementOptions($options);
        $field->options()->delete();

        foreach ($normalized as $option) {
            TicketCustomFieldOption::create([
                'ticket_custom_field_id' => $field->id,
                ...$option,
            ]);
        }
    }
}
