<?php

namespace App\Services\Tickets;

use App\Enums\CompanyType;
use App\Models\Company;
use App\Models\SupportProject;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketCustomField;
use App\Models\TicketCustomFieldValue;
use App\Models\TicketTag;
use App\Models\TicketTracker;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class IssueTrackingService
{
    public function defaultTracker(): TicketTracker
    {
        return TicketTracker::query()
            ->where('is_default', true)
            ->first()
            ?? TicketTracker::query()->firstOrCreate(
                ['slug' => 'support'],
                [
                    'name' => 'Support',
                    'description' => 'Default support issue tracker.',
                    'color' => '#2563eb',
                    'status' => 'active',
                    'is_default' => true,
                    'sort_order' => 0,
                ],
            );
    }

    public function defaultProjectForCompany(Company|int $company): SupportProject
    {
        $companyId = $company instanceof Company ? $company->id : $company;

        return SupportProject::query()
            ->where('company_id', $companyId)
            ->where('is_default', true)
            ->first()
            ?? SupportProject::query()->firstOrCreate(
                ['company_id' => $companyId, 'slug' => 'general'],
                [
                    'name' => 'General',
                    'description' => 'Default support project.',
                    'status' => 'active',
                    'is_default' => true,
                ],
            );
    }

    public function resolveProject(?string $publicId, Company $company): SupportProject
    {
        if (! $publicId) {
            return $this->defaultProjectForCompany($company);
        }

        return SupportProject::query()
            ->where('company_id', $company->id)
            ->where('public_id', $publicId)
            ->firstOrFail();
    }

    public function resolveTracker(?string $publicId): TicketTracker
    {
        if (! $publicId) {
            return $this->defaultTracker();
        }

        return TicketTracker::query()
            ->where('public_id', $publicId)
            ->firstOrFail();
    }

    public function resolveCategory(?string $publicId, SupportProject $project): ?TicketCategory
    {
        if (! $publicId) {
            return null;
        }

        return TicketCategory::query()
            ->where('support_project_id', $project->id)
            ->where('public_id', $publicId)
            ->firstOrFail();
    }

    /**
     * @param  array<int, string|null>  $tagNames
     */
    public function syncTags(Ticket $ticket, array $tagNames): void
    {
        $tagIds = collect($tagNames)
            ->map(fn ($name) => $this->normalizeTagName($name))
            ->filter()
            ->unique(fn (string $name): string => Str::slug($name))
            ->take(20)
            ->map(function (string $name): int {
                $slug = $this->slug($name);

                return TicketTag::query()->firstOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $name,
                        'color' => '#64748b',
                    ],
                )->id;
            })
            ->values()
            ->all();

        $ticket->tags()->sync($tagIds);
    }

    /**
     * @param  array<string, mixed>|array<int, array<string, mixed>>  $rawValues
     */
    public function syncCustomFields(Ticket $ticket, TicketTracker $tracker, array $rawValues): void
    {
        $fields = $tracker->customFields()
            ->where('status', 'active')
            ->with('options')
            ->get();

        $values = $this->normalizeCustomFieldInput($rawValues);
        $errors = [];
        $normalized = [];

        foreach ($fields as $field) {
            $key = $field->public_id;
            $raw = $values[$field->public_id] ?? $values[$field->slug] ?? null;
            $isBlank = $raw === null || $raw === '' || $raw === [] || ($raw === false && $field->type !== 'boolean');

            if ($field->is_required && $isBlank) {
                $errors["custom_fields.$key"] = "{$field->name} is required.";

                continue;
            }

            if ($isBlank) {
                $normalized[$field->id] = null;

                continue;
            }

            try {
                $normalized[$field->id] = $this->normalizeCustomFieldValue($ticket, $field, $raw);
            } catch (ValidationException $exception) {
                $errors["custom_fields.$key"] = Arr::first(Arr::flatten($exception->errors()));
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        foreach ($fields as $field) {
            $value = $normalized[$field->id] ?? null;

            if ($value === null || $value === '' || $value === []) {
                TicketCustomFieldValue::query()
                    ->where('ticket_id', $ticket->id)
                    ->where('ticket_custom_field_id', $field->id)
                    ->delete();

                continue;
            }

            TicketCustomFieldValue::query()->updateOrCreate(
                [
                    'ticket_id' => $ticket->id,
                    'ticket_custom_field_id' => $field->id,
                ],
                ['value' => ['value' => $value]],
            );
        }
    }

    public function optionsForCompany(Company $company): array
    {
        return [
            'projects' => $this->projectOptions($company),
            'trackers' => $this->trackerOptions(),
            'categories' => $this->categoryOptions($company),
            'tags' => $this->tagOptions(),
            'customFields' => $this->customFieldOptions(),
        ];
    }

    public function projectOptions(?Company $company = null): array
    {
        return SupportProject::query()
            ->with('company:id,public_id,name')
            ->when($company, fn ($query) => $query->where('company_id', $company->id))
            ->orderBy('name')
            ->get()
            ->map(fn (SupportProject $project): array => $this->projectPayload($project))
            ->all();
    }

    public function trackerOptions(): array
    {
        return TicketTracker::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (TicketTracker $tracker): array => $this->trackerPayload($tracker))
            ->all();
    }

    public function categoryOptions(?Company $company = null): array
    {
        return TicketCategory::query()
            ->with('supportProject.company:id,public_id,name')
            ->when($company, fn ($query) => $query->whereHas('supportProject', fn ($project) => $project->where('company_id', $company->id)))
            ->orderBy('name')
            ->get()
            ->map(fn (TicketCategory $category): array => $this->categoryPayload($category))
            ->all();
    }

    public function tagOptions(): array
    {
        return TicketTag::query()
            ->orderBy('name')
            ->get()
            ->map(fn (TicketTag $tag): array => $this->tagPayload($tag))
            ->all();
    }

    public function customFieldOptions(?TicketTracker $tracker = null): array
    {
        return TicketCustomField::query()
            ->with('options')
            ->when($tracker, fn ($query) => $query->where('ticket_tracker_id', $tracker->id))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (TicketCustomField $field): array => $this->customFieldPayload($field))
            ->all();
    }

    public function projectPayload(?SupportProject $project): ?array
    {
        if (! $project) {
            return null;
        }

        return [
            'id' => $project->public_id,
            'name' => $project->name,
            'company_id' => $project->company?->public_id,
            'company' => $project->company?->name,
            'status' => $project->status,
            'is_default' => $project->is_default,
        ];
    }

    public function trackerPayload(?TicketTracker $tracker): ?array
    {
        if (! $tracker) {
            return null;
        }

        return [
            'id' => $tracker->public_id,
            'name' => $tracker->name,
            'color' => $tracker->color,
            'status' => $tracker->status,
            'is_default' => $tracker->is_default,
        ];
    }

    public function categoryPayload(?TicketCategory $category): ?array
    {
        if (! $category) {
            return null;
        }

        return [
            'id' => $category->public_id,
            'name' => $category->name,
            'project_id' => $category->supportProject?->public_id,
            'project' => $category->supportProject?->name,
            'company_id' => $category->supportProject?->company?->public_id,
            'status' => $category->status,
        ];
    }

    public function tagPayload(TicketTag $tag): array
    {
        return [
            'id' => $tag->public_id,
            'name' => $tag->name,
            'color' => $tag->color,
        ];
    }

    public function customFieldPayload(TicketCustomField $field, mixed $value = null): array
    {
        return [
            'id' => $field->public_id,
            'tracker_id' => $field->tracker?->public_id,
            'name' => $field->name,
            'slug' => $field->slug,
            'type' => $field->type,
            'is_required' => $field->is_required,
            'validation_regex' => $field->validation_regex,
            'status' => $field->status,
            'sort_order' => $field->sort_order,
            'options' => $field->options->map(fn ($option): array => [
                'value' => $option->value,
                'label' => $option->label,
            ])->values(),
            'value' => $value,
        ];
    }

    public function customFieldValuesPayload(Ticket $ticket): array
    {
        $values = $ticket->customFieldValues
            ->keyBy('ticket_custom_field_id')
            ->map(fn (TicketCustomFieldValue $value) => $value->value['value'] ?? null);
        $fields = $ticket->tracker?->customFields ?? collect();

        return $fields
            ->map(fn (TicketCustomField $field): array => $this->customFieldPayload($field, $values[$field->id] ?? null))
            ->values()
            ->all();
    }

    public function normalizeManagementOptions(string|array|null $options): array
    {
        $items = is_array($options)
            ? $options
            : preg_split('/\r\n|\r|\n|,/', (string) $options);

        return collect($items)
            ->map(fn ($option) => is_array($option) ? ($option['label'] ?? $option['value'] ?? null) : $option)
            ->map(fn ($option) => trim((string) $option))
            ->filter()
            ->unique(fn (string $option): string => $this->slug($option))
            ->values()
            ->map(fn (string $option, int $index): array => [
                'value' => $this->slug($option),
                'label' => $option,
                'sort_order' => $index,
            ])
            ->all();
    }

    private function normalizeTagName(?string $name): ?string
    {
        $normalized = trim((string) $name);

        return $normalized === '' ? null : Str::limit($normalized, 40, '');
    }

    private function slug(string $value): string
    {
        return Str::slug($value) ?: Str::lower(Str::random(8));
    }

    private function normalizeCustomFieldInput(array $rawValues): array
    {
        if (! array_is_list($rawValues)) {
            return $rawValues;
        }

        return collect($rawValues)
            ->filter(fn ($item): bool => is_array($item) && isset($item['id']))
            ->mapWithKeys(fn (array $item): array => [$item['id'] => $item['value'] ?? null])
            ->all();
    }

    private function normalizeCustomFieldValue(Ticket $ticket, TicketCustomField $field, mixed $raw): mixed
    {
        $value = match ($field->type) {
            'text', 'textarea' => trim((string) $raw),
            'number' => $this->numberValue($field, $raw),
            'date' => $this->dateValue($field, $raw),
            'boolean' => filter_var($raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
            'single_select' => $this->selectValue($field, $raw),
            'multi_select' => $this->multiSelectValue($field, $raw),
            'user' => $this->userValue($ticket, $raw),
            'project' => $this->projectValue($ticket, $raw),
            'category' => $this->categoryValue($ticket, $raw),
            default => throw ValidationException::withMessages(['type' => 'Unsupported custom field type.']),
        };

        if (is_string($value) && $field->validation_regex && @preg_match($field->validation_regex, '') !== false && ! preg_match($field->validation_regex, $value)) {
            throw ValidationException::withMessages(['value' => "{$field->name} is invalid."]);
        }

        return $value;
    }

    private function numberValue(TicketCustomField $field, mixed $raw): int|float
    {
        if (! is_numeric($raw)) {
            throw ValidationException::withMessages(['value' => "{$field->name} must be numeric."]);
        }

        return $raw + 0;
    }

    private function dateValue(TicketCustomField $field, mixed $raw): string
    {
        $timestamp = strtotime((string) $raw);

        if (! $timestamp) {
            throw ValidationException::withMessages(['value' => "{$field->name} must be a date."]);
        }

        return date('Y-m-d', $timestamp);
    }

    private function selectValue(TicketCustomField $field, mixed $raw): string
    {
        $value = (string) $raw;
        $allowed = $field->options->pluck('value')->all();

        if (! in_array($value, $allowed, true)) {
            throw ValidationException::withMessages(['value' => "{$field->name} has an invalid option."]);
        }

        return $value;
    }

    private function multiSelectValue(TicketCustomField $field, mixed $raw): array
    {
        $values = collect(is_array($raw) ? $raw : [$raw])
            ->map(fn ($value): string => (string) $value)
            ->filter()
            ->unique()
            ->values();
        $allowed = $field->options->pluck('value')->all();

        if ($values->contains(fn (string $value): bool => ! in_array($value, $allowed, true))) {
            throw ValidationException::withMessages(['value' => "{$field->name} has invalid options."]);
        }

        return $values->all();
    }

    private function userValue(Ticket $ticket, mixed $raw): string
    {
        $publicId = (string) $raw;
        $exists = User::query()
            ->where('public_id', $publicId)
            ->where('status', 'active')
            ->where(function ($query) use ($ticket): void {
                $query->where('company_id', $ticket->company_id)
                    ->orWhereHas('company', fn ($company) => $company->where('type', CompanyType::Provider->value));
            })
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages(['value' => 'Selected user is invalid.']);
        }

        return $publicId;
    }

    private function projectValue(Ticket $ticket, mixed $raw): string
    {
        $publicId = (string) $raw;
        $exists = SupportProject::query()
            ->where('company_id', $ticket->company_id)
            ->where('public_id', $publicId)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages(['value' => 'Selected project is invalid.']);
        }

        return $publicId;
    }

    private function categoryValue(Ticket $ticket, mixed $raw): string
    {
        $publicId = (string) $raw;
        $exists = TicketCategory::query()
            ->where('public_id', $publicId)
            ->whereHas('supportProject', fn ($project) => $project->where('company_id', $ticket->company_id))
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages(['value' => 'Selected category is invalid.']);
        }

        return $publicId;
    }
}
