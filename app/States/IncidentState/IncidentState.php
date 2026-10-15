<?php

namespace App\States\IncidentState;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

abstract class IncidentState extends State
{
    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Reported::class)
            ->allowTransition(Reported::class, NeedsRCA::class)
            ->allowTransition(Reported::class, NeedsFeedback::class)
            ->allowTransition(NeedsRCA::class, PendingHODApproval::class)
            ->allowTransition(NeedsFeedback::class, PendingHODApproval::class)
            // Loops back if HOD rejects/amends:
            ->allowTransition(PendingHODApproval::class, NeedsRCA::class)
            ->allowTransition(PendingHODApproval::class, NeedsFeedback::class)
            // QA Processing and Closure:
            ->allowTransition(PendingHODApproval::class, Closed::class);
    }
}