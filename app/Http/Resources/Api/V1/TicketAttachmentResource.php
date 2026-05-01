<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketAttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'filename' => $this->original_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'visibility' => $this->visibility->value,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
