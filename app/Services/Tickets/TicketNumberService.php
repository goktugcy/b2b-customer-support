<?php

namespace App\Services\Tickets;

use App\Models\CompanyTicketCounter;
use Illuminate\Support\Facades\DB;

class TicketNumberService
{
    public const START = 100001;

    public function nextForCompany(int $companyId): int
    {
        return DB::transaction(function () use ($companyId): int {
            CompanyTicketCounter::query()->upsert([
                [
                    'company_id' => $companyId,
                    'next_number' => self::START,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ], ['company_id'], ['company_id']);

            $counter = CompanyTicketCounter::query()
                ->where('company_id', $companyId)
                ->lockForUpdate()
                ->firstOrFail();

            $number = $counter->next_number;

            $counter->forceFill(['next_number' => $number + 1])->save();

            return $number;
        });
    }
}
