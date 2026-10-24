<?php

namespace FirstlightUI\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeScreenCommand extends Command
{
    protected $signature = 'make:firstlight-screen
                            {name : The screen class (e.g. EditProfile, Settings/Notifications)}
                            {--form : Add a validated field and a Save button using validate() and submit()}
                            {--list : Add a paginated List using refreshList() and loadMoreList()}
                            {--destroy : Add authorized row deletion through Confirmation Dialog (implies --list)}
                            {--test : Also create a Pest test that uses Native::test()}
                            {--force : Overwrite existing files}';

    protected $description = 'Create a Firstlight NativeComponent screen, its Blade view, and optionally a Pest test';

    public function __construct(private readonly Filesystem $files)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $segments = explode('/', str_replace('\\', '/', trim($this->argument('name'), '/\\')));
        $class = Str::studly(array_pop($segments));
        $segments = array_map(Str::studly(...), $segments);
        $namespace = implode('\\', ['App\\NativeComponents', ...$segments]);
        $viewName = implode('.', ['native', ...array_map(Str::kebab(...), $segments), Str::kebab($class)]);

        $form = (bool) $this->option('form');
        $destroy = (bool) $this->option('destroy');
        $list = $destroy || (bool) $this->option('list');

        $files = [
            $this->laravel->path(implode('/', ['NativeComponents', ...$segments, $class.'.php'])) => $this->classSource($namespace, $class, $viewName, $form, $list, $destroy),
            $this->laravel->resourcePath('views/'.str_replace('.', '/', $viewName).'.blade.php') => $this->viewSource($class, $form, $list, $destroy),
        ];

        if ($this->option('test')) {
            $files[$this->laravel->basePath(implode('/', ['tests/Feature/NativeComponents', ...$segments, $class.'Test.php']))] = $this->testSource($namespace, $class, $form, $list);
        }

        foreach (array_keys($files) as $path) {
            if ($this->files->exists($path) && ! $this->option('force')) {
                $this->components->error("{$path} already exists. Use --force to overwrite.");

                return self::FAILURE;
            }
        }

        foreach ($files as $path => $contents) {
            $this->files->ensureDirectoryExists(dirname($path));
            $this->files->put($path, $contents);
            $this->components->twoColumnDetail('Created', ltrim(Str::after($path, $this->laravel->basePath()), '/'));
        }

        $this->newLine();
        $this->line('  Register it in routes/mobile.php:');
        $this->line("  <comment>Route::native('/".Str::kebab($class)."', \\{$namespace}\\{$class}::class);</comment>");

        return self::SUCCESS;
    }

    private function classSource(string $namespace, string $class, string $viewName, bool $form, bool $list, bool $destroy): string
    {
        $imports = ['FirstlightUI\\NativeComponent', 'Illuminate\\View\\View'];
        $members = [];

        if ($form) {
            $members[] = <<<'PHP'
                public string $name = '';

                /** @var array<string, string> */
                protected array $rules = [
                    'name' => 'required',
                ];

                public function save(): void
                {
                    $this->submit(
                        action: function (): void {
                            // Persist the validated public properties.
                        },
                        successMessage: 'Saved',
                    );
                }
            PHP;
        }

        if ($list) {
            $imports[] = 'FirstlightUI\\Pagination\\ListPage';
            $imports[] = 'Illuminate\\Pagination\\LengthAwarePaginator';
            $members[] = <<<'PHP'
                public function mount(): void
                {
                    $this->reload();
                }

                public function reload(): void
                {
                    $this->refreshList(fn (ListPage $page) => $this->listQuery($page));
                }

                public function loadMore(): void
                {
                    $this->loadMoreList(fn (ListPage $page) => $this->listQuery($page));
                }

                public function open(int|string $id): void
                {
                    // Navigate to the row, for example $this->navigate("/items/{$id}").
                }

                protected function listQuery(ListPage $page): object
                {
                    // TODO: Replace with your query. Rows must expose a stable ->id and display ->title, for example:
                    // return Post::query()->latest()->paginate(perPage: 20, page: $page->page);
                    return new LengthAwarePaginator([], 0, 20, $page->page);
                }
            PHP;
        }

        if ($destroy) {
            $members[] = <<<'PHP'
                public function requestDeletion(int|string $id): void
                {
                    $this->requestDestructiveListAction($id, 'delete');
                }

                public function cancelDeletion(): void
                {
                    $this->cancelDestructiveListAction();
                }

                public function deleteItem(): void
                {
                    $this->confirmDestructiveListAction(
                        destroy: fn (mixed $item) => $item->delete(),
                        ability: 'delete',
                        successMessage: 'Deleted',
                    );
                }
            PHP;
        }

        $members[] = <<<PHP
                public function render(): View
                {
                    return view('{$viewName}');
                }
            PHP;

        sort($imports);
        $uses = implode("\n", array_map(fn (string $import): string => "use {$import};", $imports));
        $body = implode("\n\n", $members);

        return <<<PHP
            <?php

            namespace {$namespace};

            {$uses}

            class {$class} extends NativeComponent
            {
            {$body}
            }

            PHP;
    }

    private function viewSource(string $class, bool $form, bool $list, bool $destroy): string
    {
        $title = Str::headline($class);
        $sections = [<<<BLADE
                <native:text class="text-2xl font-bold">{$title}</native:text>
            BLADE];

        if ($form) {
            $sections[] = <<<'BLADE'
                <firstlight:text-field label="Name" native:model="name" required />

                <firstlight:button variant="primary" :loading="$submitting" @press="save">
                    Save
                </firstlight:button>
            BLADE;
        }

        if ($list) {
            $row = $destroy
                ? <<<'BLADE'
                            <firstlight:list-item
                                :headline="$item->title"
                                :disabled="$this->denies('delete', $item)"
                                @press="open({{ $item->id }})"
                                :trailing-actions="[
                                    ['method' => 'requestDeletion('.$item->id.')', 'label' => 'Delete', 'role' => 'destructive'],
                                ]"
                            />
                BLADE
                : <<<'BLADE'
                            <firstlight:list-item :headline="$item->title" @press="open({{ $item->id }})" />
                BLADE;

            $sections[] = <<<BLADE
                <firstlight:list separator @refresh="reload" @endReached="loadMore">
                    @foreach (\$this->listItems as \$item)
                        {{-- TODO: Replace ->title and ->id with your row's display text and stable key. --}}
            {$row}
                    @endforeach
                </firstlight:list>
            BLADE;
        }

        if ($destroy) {
            $sections[] = <<<'BLADE'
                <firstlight:confirmation-dialog
                    :visible="$confirmingListDestruction"
                    title="Delete item?"
                    message="This action cannot be undone."
                    confirm-label="Delete"
                    tone="destructive"
                    @press="deleteItem"
                    @dismiss="cancelDeletion"
                />
            BLADE;
        }

        $body = implode("\n\n", $sections);

        return <<<BLADE
            <native:column class="w-full h-full p-4 gap-4">
            {$body}
            </native:column>

            BLADE;
    }

    private function testSource(string $namespace, string $class, bool $form, bool $list): string
    {
        $title = Str::headline($class);
        $tests = [<<<PHP
            it('renders', function () {
                Native::test({$class}::class)
                    ->assertSee('{$title}');
            });
            PHP];

        if ($form) {
            $tests[] = <<<PHP
                it('requires a name before saving', function () {
                    Feedback::fake();

                    Native::test({$class}::class)
                        ->tap('Save')
                        ->assertFieldError('name')
                        ->assertNoFeedbackSent()
                        ->input('name', 'Ada')
                        ->tap('Save')
                        ->assertNoFieldError('name')
                        ->assertFeedbackSent('Saved', FeedbackTone::Success);
                });
                PHP;
        }

        if ($list) {
            $tests[] = <<<PHP
                it('renders one List Item per row', function () {
                    // Seed rows for listQuery(), then assert them by headline:
                    // ->assertListItem('First post')->pressListItem('First post')
                    Native::test({$class}::class)
                        ->assertListItemCount(0);
                });
                PHP;
        }

        $imports = ["{$namespace}\\{$class}", 'FirstlightUI\\Testing\\ScreenAssertions', 'Native\\Mobile\\Testing\\Native'];

        if ($form) {
            $imports[] = 'FirstlightUI\\Facades\\Feedback';
            $imports[] = 'FirstlightUI\\Feedback\\FeedbackTone';
        }

        sort($imports);
        $uses = implode("\n", array_map(fn (string $import): string => "use {$import};", $imports));
        $body = implode("\n\n", $tests);

        return <<<PHP
            <?php

            {$uses}

            beforeEach(fn () => ScreenAssertions::register());

            {$body}

            PHP;
    }
}
