<?php

namespace App\View\Components\Bookings\Workflow;

use App\Models\BookingWorkflow;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Row extends Component
{
    public ?string $taskTone;

    public ?string $taskMessage;

    /**
     * Create a new component instance.
     */
    public function __construct(
        public BookingWorkflow $workflow,
        public array $progress,
        public int $position,
        public ?int $selectedWorkflowId = null,
    ) {
        $this->taskTone = match ($workflow->status) {
            'completed' => 'text-emerald-700 dark:text-emerald-300',
            'failed' => 'text-red-600 dark:text-red-400',
            'awaiting_approval' => 'text-amber-700 dark:text-amber-300',
            'processing' => 'text-indigo-600 dark:text-indigo-400',
            default => 'text-slate-500 dark:text-gray-400',
        };

        $this->taskMessage = $workflow->status === 'completed'
            ? match ($workflow->type) {
                'property_confirmation' => 'Property availability confirmed',
                'flight_confirmation' => 'Flight confirmed with provider',
                'vehicle_confirmation' => 'Vehicle confirmed with provider',
                default => $workflow->message,
            }
        : $workflow->message;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.bookings.workflow.row');
    }
}
