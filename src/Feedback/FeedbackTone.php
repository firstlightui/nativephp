<?php

namespace FirstlightUI\Feedback;

enum FeedbackTone: string
{
    case Default = 'default';
    case Success = 'success';
    case Warning = 'warning';
    case Danger = 'danger';
}
