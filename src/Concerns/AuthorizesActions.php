<?php

namespace FirstlightUI\Concerns;

use FirstlightUI\Authorization\GateEvaluator;
use FirstlightUI\Facades\Feedback;

trait AuthorizesActions
{
    public function allows(string $ability, mixed ...$arguments): bool
    {
        return $this->actionGateEvaluator()->allows(
            $ability,
            $arguments,
            $this->gateUser(),
        );
    }

    public function denies(string $ability, mixed ...$arguments): bool
    {
        return ! $this->allows($ability, ...$arguments);
    }

    public function authorize(string $ability, mixed ...$arguments): bool
    {
        if ($this->allows($ability, ...$arguments)) {
            return true;
        }

        Feedback::danger('This action is unauthorized.')->send();

        return false;
    }

    protected function gateUser(): mixed
    {
        return null;
    }

    protected function actionGateEvaluator(): GateEvaluator
    {
        return new GateEvaluator;
    }
}
