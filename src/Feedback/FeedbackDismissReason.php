<?php

namespace FirstlightUI\Feedback;

enum FeedbackDismissReason: string
{
    case Timeout = 'timeout';
    case Manual = 'manual';
    case Action = 'action';
}
