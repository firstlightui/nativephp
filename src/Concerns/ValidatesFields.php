<?php

namespace FirstlightUI\Concerns;

use FirstlightUI\Media\MediaValidation;
use FirstlightUI\Media\MediaValue;
use FirstlightUI\Validation\ErrorSummary;
use FirstlightUI\Validation\FieldErrorBag;
use FirstlightUI\Validation\ValidationAlert;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory as ValidatorFactory;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use Illuminate\View\View;
use Native\Mobile\Edge\Concerns\ValidatesInput;
use Native\Mobile\Edge\Element;
use Throwable;

trait ValidatesFields
{
    use ValidatesInput {
        validate as private validateInput;
        validateOnly as private validateOnlyInput;
        addError as private addInputError;
        view as private inputView;
        partial as private inputPartial;
        fromView as private inputFromView;
        fromViewPartial as private inputFromViewPartial;
        validationData as private inputValidationData;
    }

    /** Keys whose messages changed during the current interaction; null outside one. */
    private ?array $failedValidationKeys = null;

    /**
     * The last interaction's alert. Every render until the next interaction
     * republishes it (NativePHP may render a frame more than once); native
     * renderers act once per revision.
     */
    private ?ValidationAlert $validationAlert = null;

    /**
     * @param  array<string, mixed>|class-string|null  $rules
     * @param  array<string, string>  $messages
     * @param  array<string, string>  $attributes
     * @return array<string, mixed>
     */
    public function validate(array|string|null $rules = null, array $messages = [], array $attributes = []): array
    {
        [$rules, $messages, $attributes] = $this->resolveValidation($rules, $messages, $attributes);

        try {
            return $this->validateInput($rules, $messages, $attributes);
        } catch (ValidationException $exception) {
            $this->recordFailedValidation($this->getErrorBag()->keys());

            throw $exception;
        }
    }

    /**
     * @param  array<string, mixed>|class-string|null  $rules
     * @param  array<string, string>  $messages
     * @param  array<string, string>  $attributes
     * @return array<string, mixed>
     */
    public function validateOnly(string $field, array|string|null $rules = null, array $messages = [], array $attributes = []): array
    {
        [$rules, $messages, $attributes] = $this->resolveValidation($rules, $messages, $attributes);

        try {
            return $this->validateOnlyInput($field, $rules, $messages, $attributes);
        } catch (ValidationException $exception) {
            $this->recordFailedValidation([$field]);

            throw $exception;
        }
    }

    public function addError(string $field, string $message): void
    {
        $this->addInputError($field, $message);
        $this->recordFailedValidation([$field]);
    }

    public function hasError(string $field): bool
    {
        return $this->getErrorBag()->has($field);
    }

    /** Keep upstream's exception bookkeeping while recording accessibility alerts. */
    public function __invokeInteraction(string $method, array $parameters): mixed
    {
        try {
            return parent::__invokeInteraction($method, $parameters);
        } catch (ValidationException $exception) {
            $this->storeValidationException($exception);

            return null;
        }
    }

    protected function dispatchUiEvent(array $event): void
    {
        $outer = $this->failedValidationKeys;
        $this->failedValidationKeys = [];

        if ($outer === null) {
            $this->validationAlert = null;
        }

        try {
            parent::dispatchUiEvent($event);
        } catch (ValidationException $exception) {
            // Deferred listeners and drag-end callbacks bypass __invokeInteraction().
            $this->storeValidationException($exception);
        } finally {
            $failed = $this->failedValidationKeys;
            $this->failedValidationKeys = $outer;
        }

        if ($outer === null) {
            $this->queueValidationAlert($failed, $event);
        } else {
            // A nested dispatch (for example to a child owner) reports to the outermost one.
            $this->recordFailedValidation($failed);
        }
    }

    /**
     * A press or keyboard submit is a form submission: announce every
     * current message and move screen-reader focus to the first invalid
     * field. Property synchronization (blur, live, debounce, change) only
     * announces the keys it just failed, so focus is never pulled back.
     */
    protected function queueValidationAlert(array $failedKeys, array $event): void
    {
        $bag = $this->getErrorBag();
        $failedKeys = array_values(array_filter(
            array_unique($failedKeys),
            fn (string $key): bool => $bag->has($key),
        ));

        if ($failedKeys === []) {
            return;
        }

        $submission = in_array((int) ($event['type'] ?? -1), [0, 4], true);

        $this->validationAlert = ValidationAlert::next(
            ErrorSummary::announcement($bag, $submission ? null : $failedKeys),
            movesFocus: $submission,
            keys: $submission ? null : $failedKeys,
        );
    }

    /** Record hand-thrown messages without replacing a bag already updated by upstream validation. */
    protected function storeValidationException(ValidationException $exception): void
    {
        if ($exception !== $this->recordedValidationException) {
            $this->errorBag = $exception->validator->errors();
            $this->recordFailedValidation($this->errorBag->keys());
        }

        $this->recordedValidationException = null;
    }

    /** @param  list<string>  $keys */
    private function recordFailedValidation(array $keys): void
    {
        if ($this->failedValidationKeys !== null) {
            array_push($this->failedValidationKeys, ...$keys);
        }
    }

    protected function view(string $name, array $data = []): Element
    {
        return $this->withFieldErrorBag(function () use ($name, $data): Element {
            $data['errorBag'] = $this->getErrorBag();

            return $this->inputView($name, $data);
        }, publishesAlert: true);
    }

    protected function partial(string $name, array $data = []): Element
    {
        return $this->withFieldErrorBag(function () use ($name, $data): Element {
            $data['errorBag'] = $this->getErrorBag();

            return $this->inputPartial($name, $data);
        });
    }

    protected function fromView(View $view): Element
    {
        return $this->withFieldErrorBag(function () use ($view): Element {
            $view->with('errorBag', $this->getErrorBag());

            return $this->inputFromView($view);
        }, publishesAlert: true);
    }

    protected function fromViewPartial(View $view): Element
    {
        return $this->withFieldErrorBag(function () use ($view): Element {
            $view->with('errorBag', $this->getErrorBag());

            return $this->inputFromViewPartial($view);
        });
    }

    /**
     * @param  array<string, mixed>|class-string|null  $rules
     * @param  array<string, string>  $messages
     * @param  array<string, string>  $attributes
     * @return array{0: array<string, mixed>, 1: array<string, string>, 2: array<string, string>}
     */
    protected function resolveValidation(array|string|null $rules, array $messages, array $attributes): array
    {
        if (is_string($rules)) {
            $source = $this->makeValidationSource($rules);
            $rules = $source->rules();

            if ($messages === [] && method_exists($source, 'messages')) {
                $messages = $source->messages();
            }

            if ($attributes === [] && method_exists($source, 'attributes')) {
                $attributes = $source->attributes();
            }
        } elseif ($rules === null) {
            $rules = method_exists($this, 'rules')
                ? $this->rules()
                : ($this->rules ?? []);
        }

        if ($messages === []) {
            $messages = method_exists($this, 'messages')
                ? $this->messages()
                : ($this->messages ?? []);
        }

        if ($attributes === []) {
            $attributes = method_exists($this, 'validationAttributes')
                ? $this->validationAttributes()
                : ($this->validationAttributes ?? []);
        }

        return [$rules, $messages, $attributes];
    }

    protected function makeValidationSource(string $class): object
    {
        if (function_exists('app')) {
            try {
                return app($class);
            } catch (Throwable) {
            }
        }

        return new $class;
    }

    /**
     * @param  array<string, mixed>  $rules
     * @param  array<string, string>  $messages
     * @param  array<string, string>  $attributes
     */
    protected function makeValidator(array $rules, array $messages, array $attributes): Validator
    {
        $data = $this->validationData();

        if (function_exists('app')) {
            try {
                $application = app();

                if (is_object($application) && method_exists($application, 'bound') && $application->bound('validator')) {
                    return $application->make('validator')->make($data, $rules, $messages, $attributes);
                }
            } catch (Throwable) {
            }
        }

        return $this->standaloneValidatorFactory()->make($data, $rules, $messages, $attributes);
    }

    /** @return array<string, mixed> */
    protected function validationData(): array
    {
        return array_map(
            fn (mixed $value): mixed => $value instanceof MediaValue
                ? MediaValidation::toUploadedFile($value)
                : $value,
            $this->inputValidationData(),
        );
    }

    /**
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    protected function withFieldErrorBag(callable $callback, bool $publishesAlert = false): mixed
    {
        if (! $publishesAlert) {
            return FieldErrorBag::using($this->getErrorBag(), $callback);
        }

        // Each render claims the announcement and focus afresh.
        $alert = $this->validationAlert === null ? null : clone $this->validationAlert;

        return FieldErrorBag::using($this->getErrorBag(), $callback, $alert);
    }

    protected function standaloneValidatorFactory(): ValidatorFactory
    {
        $translator = new Translator(new ArrayLoader, 'en');
        $translator->addLines([
            'validation.accepted' => 'The :attribute must be accepted.',
            'validation.date' => 'The :attribute is not a valid date.',
            'validation.email' => 'The :attribute must be a valid email address.',
            'validation.integer' => 'The :attribute must be an integer.',
            'validation.numeric' => 'The :attribute must be a number.',
            'validation.required' => 'The :attribute field is required.',
            'validation.min.numeric' => 'The :attribute must be at least :min.',
            'validation.min.string' => 'The :attribute must be at least :min characters.',
        ], 'en');

        return new ValidatorFactory($translator);
    }
}
