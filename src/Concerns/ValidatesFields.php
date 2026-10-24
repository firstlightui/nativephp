<?php

namespace FirstlightUI\Concerns;

use FirstlightUI\Media\MediaValidation;
use FirstlightUI\Media\MediaValue;
use FirstlightUI\Validation\ErrorSummary;
use FirstlightUI\Validation\FieldErrorBag;
use FirstlightUI\Validation\ValidationAlert;
use Illuminate\Support\MessageBag;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory as ValidatorFactory;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use Illuminate\View\View;
use Native\Mobile\Edge\Element;
use ReflectionClass;
use ReflectionProperty;
use Throwable;

trait ValidatesFields
{
    protected ?MessageBag $errorBag = null;

    /** Keys whose messages changed during the current interaction; null outside one. */
    private ?array $failedValidationKeys = null;

    /** Published once with the next screen frame, then cleared. */
    private ?ValidationAlert $pendingValidationAlert = null;

    /**
     * @param  array<string, mixed>|class-string|null  $rules
     * @param  array<string, string>  $messages
     * @param  array<string, string>  $attributes
     * @return array<string, mixed>
     */
    public function validate(array|string|null $rules = null, array $messages = [], array $attributes = []): array
    {
        [$rules, $messages, $attributes] = $this->resolveValidation($rules, $messages, $attributes);
        $validator = $this->makeFieldValidator($rules, $messages, $attributes);

        try {
            $validated = $validator->validate();
        } catch (ValidationException $exception) {
            $this->errorBag = $exception->validator->errors();
            $this->recordFailedValidation($this->errorBag->keys());

            throw $exception;
        }

        $this->resetValidation(array_keys($rules));

        return $validated;
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
        $fieldRules = array_key_exists($field, $rules) ? [$field => $rules[$field]] : [];
        $validator = $this->makeFieldValidator($fieldRules, $messages, $attributes);

        try {
            $validated = $validator->validate();
        } catch (ValidationException $exception) {
            $this->replaceFieldErrors($field, $exception->validator->errors());
            $this->recordFailedValidation([$field]);

            throw $exception;
        }

        $this->resetValidation($field);

        return $validated;
    }

    public function addError(string $field, string $message): void
    {
        $this->getErrorBag()->add($field, $message);
        $this->recordFailedValidation([$field]);
    }

    public function resetValidation(string|array|null $field = null): void
    {
        if ($field === null) {
            $this->errorBag = new MessageBag;

            return;
        }

        foreach ((array) $field as $key) {
            $this->getErrorBag()->forget($key);
        }
    }

    public function getErrorBag(): MessageBag
    {
        return $this->errorBag ??= new MessageBag;
    }

    public function hasError(string $field): bool
    {
        return $this->getErrorBag()->has($field);
    }

    protected function dispatchUiEvent(array $event): void
    {
        $outer = $this->failedValidationKeys;
        $this->failedValidationKeys = [];

        try {
            parent::dispatchUiEvent($event);
        } catch (ValidationException $exception) {
            $this->storeValidationException($exception);
        } finally {
            $failed = $this->failedValidationKeys;
            $this->failedValidationKeys = $outer;
        }

        $this->queueValidationAlert($failed, $event);
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

        $this->pendingValidationAlert = ValidationAlert::next(
            ErrorSummary::announcement($bag, $submission ? null : $failedKeys),
            movesFocus: $submission,
            keys: $submission ? null : $failedKeys,
        );
    }

    /** Replace the bag with a thrown exception's messages, such as `ValidationException::withMessages()`. */
    protected function storeValidationException(ValidationException $exception): void
    {
        $this->errorBag = $exception->validator->errors();
        $this->recordFailedValidation($this->errorBag->keys());
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

            return parent::view($name, $data);
        }, publishesAlert: true);
    }

    protected function fromView(View $view): Element
    {
        return $this->withFieldErrorBag(function () use ($view): Element {
            $view->with('errorBag', $this->getErrorBag());

            return parent::fromView($view);
        }, publishesAlert: true);
    }

    protected function fromViewPartial(View $view): Element
    {
        return $this->withFieldErrorBag(function () use ($view): Element {
            $view->with('errorBag', $this->getErrorBag());

            return parent::fromViewPartial($view);
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
    protected function makeFieldValidator(array $rules, array $messages, array $attributes): Validator
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
        $data = [];

        foreach ((new ReflectionClass($this))->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $value = $property->isInitialized($this)
                ? $property->getValue($this)
                : null;

            $data[$property->getName()] = $value instanceof MediaValue
                ? MediaValidation::toUploadedFile($value)
                : $value;
        }

        return $data;
    }

    protected function replaceFieldErrors(string $field, MessageBag $incoming): void
    {
        $bag = $this->getErrorBag();
        $bag->forget($field);

        foreach ($incoming->get($field) as $message) {
            $bag->add($field, $message);
        }
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

        $alert = $this->pendingValidationAlert;
        $this->pendingValidationAlert = null;

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
