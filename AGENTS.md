<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.5. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.
- Activate the `deploying-to-cloud` skill whenever deploying to Laravel Cloud, configuring Cloud environments or resources, using the Cloud CLI, or troubleshooting Cloud deployments.

=== herd rules ===

# Laravel Herd

- The application is served by Laravel Herd at `https?://[kebab-case-project-dir].test`. Use the `get-absolute-url` tool to generate valid URLs. Never run commands to serve the site. It is always available.
- Use the `herd` CLI to manage services, PHP versions, and sites (e.g. `herd sites`, `herd services:start <service>`, `herd php:list`). Run `herd list` to discover all available commands.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, use `php artisan make:test --pest {name} --no-interaction` to create a feature test, and add `--unit` to create a unit test. Most tests should be feature tests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

# Pest

- Pest is the standard for all new tests in this project. Use Pest's `test()` or `it()` syntax, following nearby tests, rather than PHPUnit class-style tests. Pest uses PHPUnit underneath.
- Create tests with `php artisan make:test --pest {name} --no-interaction`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/pest` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

</laravel-boost-guidelines>

## Follow-up reminder: payments demo

Recorded 8 October 2026. The user wants to revisit this once they say the current branch is looking good or ready to wrap up. At that point, remind them about the Payments domain idea before starting new feature work. This is a reminder, not authorization to implement it now.

Idea: find completed/processed bookings and create payments handled by their own jobs on a dedicated `payments` queue, calling an internal fake payment processor. Suggested design to discuss: trigger on booking completion (after any required signoff), with a periodic recovery check for missing payments; persist payment amount, currency, status, and an idempotency key before sending; simulate successes, declines, delays, and lost responses without duplicate charges. Display payment status separately from booking completion. The user sees this as a way to make the demo more interesting.

## Project memory: provider idempotency

Recorded 8 October 2026.

This demo models an external provider that supports idempotent requests. A provider can complete an operation while the caller loses the response through a timeout, dropped connection, or worker crash. Retrying with the same key allows a supporting provider to return the existing result instead of performing the operation again.

### Current demo behaviour

- `provider_requests` is client-side storage: one logical outgoing operation per provider and booking, shared across retries. `ProviderRequestClient` saves the key and business payload before HTTP, then sends the persisted values. New requests receive a UUID saved once before HTTP. Requests are found by workflow step, provider, and operation so retries reuse the stored UUID. Existing deterministic keys remain unchanged for compatibility. Future operations such as cancellation need their own request/key, not the confirmation key; cancellation itself is not implemented.
- `provider_requests.booking_workflow_step_id` directly links each request to its workflow step, with a unique foreign key. Use `BookingWorkflowStep::providerRequest()` and `ProviderRequest::workflowStep()` rather than rebuilding a match from the booking reference in the UI. New client requests save the link before HTTP; retries retain it. The migration backfills unambiguous legacy matches and leaves unmatched records unlinked. Deleting a workflow nulls the link and preserves the request history.
- `demo_provider_operations` is the dummy provider’s separate persistent memory. Only the demo endpoints read/write it. A unique `(provider, idempotency_key)` constraint scopes keys to each provider; processing uses a transaction and `lockForUpdate()`.
- A succeeded provider operation replays its saved confirmation. Different business input or operation with the same key returns HTTP 409. Pre-confirmation simulated failures can be attempted again; a later success replaces the failure response.
- Client records store only received responses. An actual connection exception or simulated lost response leaves status `unknown` and no response payload. A subsequent retry uses the same saved key and updates the client record from the returned response. The detail panel never reads demo provider storage.
- `simulate_lost_response: true` saves success on the dummy provider before returning a special simulated HTTP 503. The client treats this simulation marker as a missing response, not as knowledge of provider success. The workflow and activity explain that the outcome is unknown; retries retrieve the saved confirmation. This is not an actual dropped connection.
- The booking form exposes “Simulate lost provider response”, off by default. All three clients receive the setting. Simulation controls are excluded from business payload matching. Signoff is local and has no provider request.
- The separation migration copies legacy provider-side records into `demo_provider_operations`, preserves their keys and confirmations, and clears the old client response payloads to `unknown`: historical provider storage does not prove the client received a response.
- `provider_requests` and `demo_provider_operations` store the latest operation state. Client-side `provider_request_attempts` records each new HTTP attempt separately: start/end time, duration, outcome, received HTTP status/raw body, and transport error. Each queued provider attempt links to its exact `workflow_step_attempt_started` activity; signoff has no API attempt. Historical attempts are not backfilled because their responses are unavailable. Expandable activity details use only client records and preserve their open state during polling.

### Commercial examples and limits

- [Stripe: Idempotent requests](https://docs.stripe.com/api/idempotent_requests): clients supply an `Idempotency-Key`. Once endpoint execution begins, Stripe saves the resulting status code and response body and replays them for retries, including some failure responses such as HTTP 500. Validation failures and concurrent-request conflicts are not saved in the same way. Keys can be removed after at least 24 hours; reuse after removal can create a new operation. Different parameters with the same retained key are rejected.
- [PayPal: Idempotency](https://developer.paypal.com/api/rest/reference/idempotency/): supported endpoints accept `PayPal-Request-Id`. Its documented example captures a payment successfully despite a timeout; retrying with the same ID retrieves the capture status without capturing payment again. PayPal returns the latest status, not necessarily the original response. Endpoint support and key retention vary.

Do not describe idempotency as a universal commercial API guarantee. Verify each real provider's supported endpoints, key scope, retention period, payload-matching rules, concurrent-request handling, and retry/error semantics. Our demo deliberately retries simulated failures while preserving successes; it does not exactly reproduce Stripe's error replay policy and currently has no key-expiry policy. Sending a key or storing it only in our application cannot prevent duplicate external operations unless the provider honours it. Without that support, recovery may require looking up the operation using a client reference or reconciling its outcome before retrying.

## Project memory: queued status and dispatch

The stuck-queued dispatch gap was reproduced and fixed on 8 October 2026. `WorkflowCoordinator` now atomically claims a pending workflow step and dispatches its job inside the workflow database connection’s transaction, using `beforeCommit()` so database queue insertion happens within that transaction. This guarantee relies on the database queue and workflow writes using the same database connection (the current configuration). Reassess before switching to Redis, SQS, or a separate queue database; a transactional outbox would be needed for equivalent cross-system guarantees. Tests force job insertion failure and verify rollback to pending, subsequent scheduling, and rollback of both writes even with queue `after_commit` enabled. `bookings:recover` now rechecks pending steps and recalculates unfinished booking statuses every minute when the scheduler is running.
