# Support Desk

Tenant-aware B2B customer support platform built with Laravel, Inertia, Vue, Sanctum, PostgreSQL, Redis queues, and Mailpit.

## Implemented Production Core

- Provider admin console and customer portal
- Tenant-scoped companies, users, roles, invitations, tickets, comments, attachments, API tokens, webhooks, and audit logs
- Company-based 24/7 SLA policies with first-response and resolution breach tracking
- Real dashboard metrics for provider operations and customer portal workspaces
- Attachment allowlist validation with file type and size limits
- Invitation revoke/resend and user role/status management
- Webhook delivery visibility, test delivery, manual retry, and secret rotation
- API v1 ticket endpoints with idempotent ticket creation and OpenAPI documentation
- Company-scoped human ticket numbers such as `#100001`, with numeric portal/admin ticket URLs and legacy public-id redirects
- Database + mail notification center with header inbox dropdown, read/unread filters, and a full notifications archive
- Personal and shared saved ticket views for admin and portal ticket lists
- Provider bulk ticket actions for status, assignment, priority, and tags
- Public knowledge base for portal/API clients plus provider-managed internal content
- Provider canned responses with ticket/requester/company variables
- Ticket comment mentions with ticket-access scoping
- Provider ticket merge and split workflows with ticket events and audit logs
- CSAT surveys sent on first resolution, with single-use token responses
- Synchronous CSV/PDF exports for ticket and CSAT reports

## Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
```

Create or update a provider admin:

```bash
php artisan support:create-provider-admin admin@example.com --password=password
```

Run the local app, queue workers, logs, and Vite:

```bash
composer run dev
```

## Operations

SLA breaches are marked by the scheduled command:

```bash
php artisan support:check-sla-breaches
```

In production, run Laravel's scheduler every minute and keep queue workers alive for the `notifications` and `webhooks` queues.

Attachment upload validation is configured with:

```env
SUPPORT_ATTACHMENT_MAX_KB=20480
SUPPORT_ATTACHMENT_ALLOWED_MIMES=text/plain,text/csv,application/pdf,image/png,image/jpeg,image/gif,application/zip,application/json,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
SUPPORT_ATTACHMENT_ALLOWED_EXTENSIONS=txt,csv,pdf,png,jpg,jpeg,gif,zip,json,docx,xlsx
```

## API

API clients are managed from the customer portal. Use bearer tokens against `/api/v1`.

The OpenAPI description is available at [docs/openapi.yaml](docs/openapi.yaml).

Ticket creation supports an optional `Idempotency-Key` header. Reusing a key with the same body returns the stored response; reusing it with a different body returns a validation error.

Default API token abilities now include ticket creation/read/comment, attachment upload, public knowledge base read, company-safe bulk ticket updates, report exports, and CSAT writes.

Webhook deliveries are signed with:

- `X-Support-Event`
- `X-Support-Delivery`
- `X-Support-Timestamp`
- `X-Support-Signature: sha256=<hmac>`

The signature payload is `<timestamp>.<json-body>` using the endpoint secret.

## Verification

```bash
php artisan test
npm run typecheck
npm run build
```

## Roadmap

The major production workflow roadmap is implemented. Future work can focus on async report scheduling, richer article approval workflows, and provider-facing API clients for operational actions.
