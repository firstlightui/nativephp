<?php

namespace FirstlightUI\Authorization;

use Closure;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Support\Facades\Gate;

final class GateEvaluator
{
    /** @var (Closure(string, array<int, mixed>, mixed): bool)|null */
    private readonly ?Closure $resolver;

    /**
     * @param  (callable(string, array<int, mixed>, mixed): bool)|null  $resolver
     */
    public function __construct(?callable $resolver = null)
    {
        $this->resolver = $resolver === null
            ? null
            : Closure::fromCallable($resolver);
    }

    /**
     * @param  array<int, mixed>  $arguments
     */
    public function allows(string $ability, array $arguments = [], mixed $user = null): bool
    {
        if ($this->resolver !== null) {
            return (bool) ($this->resolver)($ability, $arguments, $user);
        }

        $application = Gate::getFacadeApplication();

        if (
            $application !== null
            && method_exists($application, 'bound')
            && ! $application->bound(GateContract::class)
        ) {
            return false;
        }

        if (Gate::getFacadeRoot() === null) {
            return false;
        }

        if ($user !== null) {
            return (bool) Gate::forUser($user)->allows($ability, $arguments);
        }

        return Gate::allows($ability, $arguments);
    }
}
