# Laravel workflow demo

A hands-on Laravel demo of resilient background workflows: coordinate travel bookings across multiple providers, recover from failures, and retry safely without duplicate confirmations.

Watch each step update live, approve bookings that need operations signoff, and explore how Laravel queues coordinate a travel booking across multiple providers, handle failures, and safely retry when a confirmation response never arrives.

Built with Laravel 13, Livewire 4 and PHP 8.4, this project demonstrates queues, workflow dependencies, retries and idempotent provider requests.

![Laravel workflow demo landing page showing an example booking workflow and lost-response scenario](screenshots/app_landing_page.png)

## What you can explore

- **Background processing** — a queue worker confirms the different parts of a booking.
- **Workflow dependencies** — steps wait until their prerequisites are complete.
- **Live progress** — booking status and activity update automatically.
- **Retries and recovery** — see automatic retries, manually retry failed steps, and recover pending work.
- **Human approval** — optionally require operations signoff before the booking completes.
- **Provider responses** — expand activity entries to inspect individual HTTP attempts.
- **Lost responses** — discover why an unknown outcome does not necessarily mean a provider failed.

The providers are simulated inside the application. No real travel reservations or payments are made.

## Quick start

Use PHP 8.4 or later with SQLite support, Composer, and Node.js/npm compatible with the project's locked dependencies. Laravel Herd is optional; you can also use PHP's built-in development server.

From a fresh checkout:

```sh
composer install
cp .env.example .env
php artisan key:generate --no-interaction
touch database/database.sqlite
php artisan migrate --no-interaction
npm ci
npm run build
```

Only copy `.env.example` if you do not already have a local `.env`.

Choose one of the web server options below and set `APP_URL` in your local `.env` to match. The queue worker calls the simulated providers through this URL, so it must also be reachable from CLI PHP.

Keep these settings:

```dotenv
QUEUE_CONNECTION=database
CACHE_STORE=database
```

Leave `DB_QUEUE_CONNECTION` unset. This keeps queue inserts and workflow updates on the same database connection, so claiming a workflow step and adding its queue job can commit or roll back together.

### Serve the website

**With Herd:** link or park the project in Herd and set `APP_URL` to the site's URL. Herd serves the website, so you do not need to run `php artisan serve`.

**Without Herd (macOS/Linux, or Linux through WSL on Windows):** set this in `.env`:

```dotenv
APP_URL=http://127.0.0.1:8000
```

Then start the development server from the project directory:

```sh
PHP_CLI_SERVER_WORKERS=4 php artisan serve --host=127.0.0.1 --port=8000 --tries=1 --no-reload
```

Multiple workers let the website and simulated provider endpoints handle requests concurrently, which is useful when simulating slow responses. Laravel requires `--no-reload` to honour the worker setting; restart the server after changing `.env`. If port 8000 is already occupied, choose another port and update `APP_URL` to match.

PHP's multiple-worker development server is not supported on native Windows. Use WSL for the command above, or a local web server such as Herd. These server instructions are for local development.

### Run the background processes

With either server option, start a queue worker in a separate terminal:

```sh
php artisan queue:work --queue=default --timeout=60 --no-interaction
```

Start the scheduler in another terminal:

```sh
php artisan schedule:work
```

Leave the web server, worker and scheduler running while exploring the demo. The worker processes jobs; the scheduler checks for pending work and stale booking statuses every minute. Press Ctrl+C in each terminal to stop its process.

For frontend development, run `npm run dev` instead of rebuilding assets after every change. Restart long-running workers after backend code changes.

## Try a booking

Open your site's `/bookings` page, create a booking, then open its details to follow the workflow. Use the site address configured in `APP_URL`, whether you chose Herd or the development server.

![Booking details showing workflow progress and activity history](screenshots/booking_detail_dark.png)

Start with a successful booking, then try the failure and delay controls:

| Try this | What to look for |
| --- | --- |
| Successful booking | Provider steps complete, then wait for operations approval if signoff is enabled. |
| Disable signoff | The booking completes once all provider steps finish. |
| Failing booking with a flight | The flight provider repeatedly fails. Automatic retries eventually leave the step failed. |
| Random failures | Provider attempts may fail, then succeed on a later retry. |
| Simulate network delay | Responses take an additional 1–5 seconds, making progress easier to follow. |
| Simulate lost provider response | The provider completes the operation, but the application initially records an unknown outcome. A retry retrieves the saved confirmation. |

Manual retry does not remove the deliberately failing flight scenario: the provider will continue to fail.

### Why the lost-response example matters

A provider can complete a request even when the caller never receives its response. Retrying blindly could perform the same operation twice.

This demo saves an idempotency key before sending a request and reuses it on retries. The simulated provider recognises that key and returns its saved confirmation rather than repeating a successful operation.

Client and provider records are kept separate, so the interface shows only what the application actually received. The simulation uses a special HTTP 503 response to represent a lost response; it does not drop a real connection.

Real providers have their own idempotency rules. Sending a key only prevents duplicates when the provider supports and honours it.

## How it works

A coordinator finds workflow steps that are ready and queues their jobs. Each step has a processor responsible for its work, while PHP attributes describe workflow dependencies.

The application uses a database queue, Livewire for the reactive interface, and Laravel Telescope for debugging.

## Running tests

```sh
php artisan test --compact
```

## Scope

This is a learning application, not a production booking platform. Payments and cancellation are not implemented.

Scheduled recovery checks pending work and recalculates unfinished booking statuses. It does not automatically retry failed steps, approve operations signoff, or guarantee exactly-once execution.

## License

This project is licensed under the [MIT License](LICENSE).
