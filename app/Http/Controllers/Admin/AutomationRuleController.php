<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AutomationRule;
use App\Models\AutomationRuleExecution;
use App\Models\Company;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Automation\AutomationRuleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AutomationRuleController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('automation.manage'), 403);

        return Inertia::render('Admin/Automation/Index', [
            'rules' => AutomationRule::query()
                ->with('company')
                ->orderBy('priority')
                ->latest()
                ->get()
                ->map(fn (AutomationRule $rule): array => $this->rulePayload($rule)),
            'executions' => AutomationRuleExecution::query()
                ->with(['rule', 'ticket.company'])
                ->latest('executed_at')
                ->limit(30)
                ->get()
                ->map(fn (AutomationRuleExecution $execution): array => [
                    'id' => $execution->id,
                    'rule' => $execution->rule?->name,
                    'ticket' => $execution->ticket?->displayId(),
                    'ticket_subject' => $execution->ticket?->subject,
                    'company' => $execution->ticket?->company?->name,
                    'trigger' => $execution->trigger,
                    'status' => $execution->status,
                    'error_message' => $execution->error_message,
                    'executed_at' => $execution->executed_at?->toISOString(),
                ]),
            'companies' => Company::query()->clients()->orderBy('name')->get(['public_id', 'name'])->map(fn (Company $company): array => [
                'id' => $company->public_id,
                'name' => $company->name,
            ]),
            'providerUsers' => User::query()
                ->whereHas('company', fn ($query) => $query->where('type', 'provider'))
                ->orderBy('name')
                ->get(['public_id', 'name'])
                ->map(fn (User $user): array => ['id' => $user->public_id, 'name' => $user->name]),
            'triggers' => AutomationRuleService::TRIGGERS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('automation.manage'), 403);

        $validated = $this->validated($request);

        AutomationRule::create($validated);

        return back()->with('success', 'Automation rule created.');
    }

    public function update(Request $request, AutomationRule $automationRule): RedirectResponse
    {
        abort_unless($request->user()->can('automation.manage'), 403);

        $automationRule->update($this->validated($request, partial: true));

        return back()->with('success', 'Automation rule updated.');
    }

    public function destroy(Request $request, AutomationRule $automationRule): RedirectResponse
    {
        abort_unless($request->user()->can('automation.manage'), 403);

        $automationRule->delete();

        return back()->with('success', 'Automation rule deleted.');
    }

    public function preview(Request $request, AutomationRuleService $automation): JsonResponse
    {
        abort_unless($request->user()->can('automation.manage'), 403);

        $validated = $request->validate([
            'ticket_id' => ['required', 'exists:tickets,public_id'],
            'trigger' => ['required', Rule::in(AutomationRuleService::TRIGGERS)],
            'conditions' => ['nullable'],
            'actions' => ['nullable'],
        ]);

        $ticket = Ticket::query()->where('public_id', $validated['ticket_id'])->firstOrFail();
        $rule = new AutomationRule([
            'trigger' => $validated['trigger'],
            'conditions' => $this->jsonValue($validated['conditions'] ?? []),
            'actions' => $this->jsonValue($validated['actions'] ?? []),
        ]);

        return response()->json($automation->preview($rule, $ticket, $validated['trigger']));
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $validated = $request->validate([
            'name' => [$partial ? 'sometimes' : 'required', 'string', 'max:160'],
            'company_id' => ['nullable', 'exists:companies,public_id'],
            'trigger' => [$partial ? 'sometimes' : 'required', Rule::in(AutomationRuleService::TRIGGERS)],
            'conditions' => ['nullable'],
            'actions' => [$partial ? 'sometimes' : 'required'],
            'enabled' => ['boolean'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:100000'],
        ]);

        if (array_key_exists('company_id', $validated)) {
            $validated['company_id'] = $validated['company_id']
                ? Company::query()->where('public_id', $validated['company_id'])->firstOrFail()->id
                : null;
        }

        if (array_key_exists('conditions', $validated)) {
            $validated['conditions'] = $this->jsonValue($validated['conditions'] ?? []);
        }

        if (array_key_exists('actions', $validated)) {
            $validated['actions'] = $this->jsonValue($validated['actions'] ?? []);
        }

        return $validated;
    }

    private function jsonValue(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    private function rulePayload(AutomationRule $rule): array
    {
        return [
            'id' => $rule->public_id,
            'company_id' => $rule->company?->public_id,
            'company' => $rule->company?->name ?? 'All companies',
            'name' => $rule->name,
            'trigger' => $rule->trigger,
            'conditions' => $rule->conditions ?? [],
            'actions' => $rule->actions ?? [],
            'enabled' => $rule->enabled,
            'priority' => $rule->priority,
            'last_run_at' => $rule->last_run_at?->toISOString(),
        ];
    }
}
