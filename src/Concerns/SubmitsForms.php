<?php

namespace FirstlightUI\Concerns;

use FirstlightUI\Facades\Feedback;
use Illuminate\Validation\ValidationException;

trait SubmitsForms
{
    public bool $submitting = false;

    public function submit(
        callable $action,
        ?string $successMessage = null,
        bool $validate = true,
    ): bool {
        if ($this->submitting) {
            return false;
        }

        $this->submitting = true;

        try {
            if ($validate) {
                $this->validate();
            }

            $action();

            if ($successMessage !== null && trim($successMessage) !== '') {
                Feedback::success($successMessage)->send();
            }

            return true;
        } catch (ValidationException) {
            return false;
        } finally {
            $this->submitting = false;
        }
    }
}
