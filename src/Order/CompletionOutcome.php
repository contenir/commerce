<?php

declare(strict_types=1);

namespace Contenir\Commerce\Order;

enum CompletionOutcome: string
{
    case Completed        = 'completed';
    case AlreadyCompleted = 'already_completed';
    case NotPaid          = 'not_paid';

    /**
     * Another buyer completed payment for one of the works first; this
     * payment was refunded in full and the order closed (no holds policy —
     * first completed payment wins).
     */
    case RefundedRace = 'refunded_race';
}
