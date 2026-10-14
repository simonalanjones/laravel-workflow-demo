<?php

namespace App\View\Components\Bookings\Activity;

use App\Models\ProviderRequestAttempt;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AttemptDetails extends Component
{
    public ?string $responseBody;

    public string $outcomeTone;

    /**
     * Create a new component instance.
     */
    public function __construct(public ProviderRequestAttempt $attempt)
    {
        $this->responseBody = $attempt->response_body;
        if ($this->responseBody !== null && json_validate($this->responseBody)) {
            $this->responseBody = json_encode(
                json_decode($this->responseBody),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            );
        }
        $this->outcomeTone = match ($attempt->status) {
            'succeeded' => 'text-emerald-700 dark:text-emerald-300',
            'failed' => 'text-rose-700 dark:text-rose-300',
            'unknown' => 'text-amber-700 dark:text-amber-300',
            default => 'text-sky-700 dark:text-sky-300',
        };
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.bookings.activity.attempt-details');
    }
}
