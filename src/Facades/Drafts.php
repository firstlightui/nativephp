<?php

namespace FirstlightUI\Facades;

use FirstlightUI\Drafts\DraftStore;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void scopeUsing(\Closure $resolver)
 * @method static bool has(string $screen, string $key)
 * @method static void flush()
 */
final class Drafts extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DraftStore::class;
    }
}
