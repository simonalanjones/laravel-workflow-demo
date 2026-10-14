<?php

namespace App\View\Components\Bookings\Activity;

use App\Models\BookingActivity;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Entry extends Component
{
    public bool $isFailure;

    public bool $isSuccess;

    /**
     * Create a new component instance.
     */
    public function __construct(
        public BookingActivity $activity,
        public bool $isLast = false,
    ) {
        $this->isFailure = str_contains($activity->type, 'failed');

        $this->isSuccess = in_array($activity->type, [
            'workflow_item_completed',
            'workflow_item_approved',
            'booking_processed',
        ], true);
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.bookings.activity.entry');
    }
}
