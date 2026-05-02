<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Ticket;
use App\Services\Csat\CsatService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CsatController extends Controller
{
    public function resend(Request $request, Company $company, Ticket $ticket, CsatService $csat): RedirectResponse
    {
        abort_unless($request->user()->can('csat.view'), 403);
        abort_unless($ticket->company_id === $company->id, 404);

        $csat->resendForTicket($ticket, $request->user());

        return back()->with('success', 'CSAT survey sent.');
    }
}
