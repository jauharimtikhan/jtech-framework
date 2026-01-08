<?php

namespace Jtech\View;

class BladeCompiler
{
    protected array $customDirectives = [];

    // OPTIMASI 1: Constructor Promotion (PHP 8)
    public function __construct(
        protected string $cachePath
    ) {
        $this->cachePath = rtrim($cachePath, '/');
    }

    public function compile(string $viewPath): string
    {
        $compiledPath = $this->cachePath . '/' . md5($viewPath) . '.php';

        // Cek cache: Kalau file gak ada ATAU file view asli lebih baru dari cache
        if (!file_exists($compiledPath) || filemtime($compiledPath) < filemtime($viewPath)) {
            $contents = file_get_contents($viewPath);

            // Compile konten
            $compiled = $this->compileString($contents);

            file_put_contents($compiledPath, $compiled);
        }

        return $compiledPath;
    }

    protected function compileString(string $value): string
    {
        // Chain method biar lebih clean, urutan tetap penting
        return $this->compileCustomDirectives(
            $this->compileStacks(
                $this->compileComponents(
                    $this->compileSlots(
                        $this->compileStatements(
                            $this->compileEchos(
                                $this->compileSections(
                                    $this->compileExtends($value)
                                )
                            )
                        )
                    )
                )
            )
        );
    }

    /* =====================================================
     |  EXTENDS
     ===================================================== */

    protected function compileExtends(string $value): string
    {
        return preg_replace(
            '/@extends\([\'"](.+?)[\'"]\)/',
            '<?php $__env->setExtends(\'$1\'); ?>',
            $value
        );
    }

    /* =====================================================
     |  SECTIONS
     ===================================================== */

    protected function compileSections(string $value): string
    {
        // OPTIMASI 2: Pakai Array Replacement.
        // 1x panggil preg_replace lebih cepat daripada 3x panggil.
        return preg_replace(
            [
                '/@section\([\'"](.+?)[\'"]\)/',
                '/@endsection/',
                '/@yield\([\'"](.+?)[\'"]\)/',
            ],
            [
                '<?php $__env->startSection(\'$1\'); ?>',
                '<?php $__env->stopSection(); ?>',
                '<?php echo $__env->yieldContent(\'$1\'); ?>',
            ],
            $value
        );
    }

    /* =====================================================
     |  ECHO
     ===================================================== */

    protected function compileEchos(string $value): string
    {
        return preg_replace(
            [
                '/\{\{\s*(.+?)\s*\}\}/', // Escaped echo {{ }}
                '/\{!!\s*(.+?)\s*!!\}/', // Raw echo {!! !!}
            ],
            [
                '<?php echo htmlspecialchars($1, ENT_QUOTES, \'UTF-8\'); ?>',
                '<?php echo $1; ?>',
            ],
            $value
        );
    }

    /* =====================================================
     |  STATEMENTS (IF, FOREACH, INCLUDE, ETC)
     ===================================================== */

    protected function compileStatements(string $value): string
    {
        // Regex Sakti untuk handle kurung di dalam kurung (1 level nesting)
        // Penjelasan: [^()]+(?:\([^()]*\)[^()]*)*
        // Artinya: Cari teks biasa, kalau ketemu kurung buka, pastikan ada kurung tutupnya.
        $expression = '([^()]+(?:\([^()]*\)[^()]*)*)';

        $patterns = [
            // Conditionals (Updated Regex)
            "/@if\s*\($expression\)/"      => '<?php if ($1): ?>',
            "/@elseif\s*\($expression\)/"  => '<?php elseif ($1): ?>',
            '/@else/'                      => '<?php else: ?>',
            '/@endif/'                     => '<?php endif; ?>',

            // Loops (Updated Regex - jaga2 kalau ada logic di dalam foreach)
            "/@foreach\s*\($expression\)/" => '<?php foreach ($1): ?>',
            '/@endforeach/'                => '<?php endforeach; ?>',
            "/@for\s*\($expression\)/"     => '<?php for ($1): ?>',
            '/@endfor/'                    => '<?php endfor; ?>',

            // Includes (Updated Regex)
            "/@include\s*\($expression\)/" => '<?php echo view($1, get_defined_vars()); ?>',

            // PHP Native
            '/@php/'                       => '<?php ',
            '/@endphp/'                    => ' ?>',

            // Error Handling (Cukup regex sederhana karena jarang ada nesting di string key)
            '/@error\([\'"](.+?)[\'"]\)/'  => '<?php if($errors->has(\'$1\')): $message = $errors->first(\'$1\'); ?>',
            '/@enderror/'                  => '<?php endif; ?>',

            // Auth Helpers
            '/@auth/'                      => '<?php if(auth()->check()): ?>',
            '/@endauth/'                   => '<?php endif; ?>',
            '/@guest/'                     => '<?php if(!auth()->check()): ?>',
            '/@endguest/'                  => '<?php endif; ?>',
            '/@csrf/'                      => '<?php echo \'<input type="hidden" name="_token" value="\' . csrf_token() . \'">\'; ?>'
        ];

        foreach ($patterns as $pattern => $replace) {
            $value = preg_replace($pattern, $replace, $value);
        }

        return $value;
    }

    /* =====================================================
     |  COMPONENT & SLOT
     ===================================================== */

    protected function compileSlots(string $value): string
    {
        return preg_replace(
            [
                '/<x-slot\s+name="(.+?)">/',
                '/<\/x-slot>/'
            ],
            [
                '<?php $__env->startSlot(\'$1\'); ?>',
                '<?php $__env->stopSlot(); ?>'
            ],
            $value
        );
    }

    protected function compileComponents(string $value): string
    {
        // Regex komponen ini cukup "greedy", hati-hati kalau ada nested component
        // Tapi untuk simple usage ini sudah cukup.
        return preg_replace_callback(
            '/<x-([\w\-]+)(.*?)>(.*?)<\/x-\1>/s',
            function ($m) {
                $name = str_replace('-', '.', $m[1]);
                $attributes = $m[2]; // Todo: Parse attributes if needed
                $slotContent = $m[3];

                // Heredoc syntax biar rapi outputnya
                return <<<PHP
                <?php \$__env->startComponent('components.$name'); ?>
                {$slotContent}
                <?php echo \$__env->renderComponent(); ?>
                PHP;
            },
            $value
        );
    }

    /* =====================================================
     |  STACKS
     ===================================================== */

    protected function compileStacks(string $value): string
    {
        return preg_replace(
            [
                '/@push\([\'"](.+?)[\'"]\)/',
                '/@endpush/',
                '/@stack\([\'"](.+?)[\'"]\)/'
            ],
            [
                '<?php $__env->startPush(\'$1\'); ?>',
                '<?php $__env->stopPush(); ?>',
                '<?php echo $__env->yieldStack(\'$1\'); ?>'
            ],
            $value
        );
    }

    /* =====================================================
     |  CUSTOM DIRECTIVES
     ===================================================== */

    public function directive(string $name, callable $handler): void
    {
        $this->customDirectives[$name] = $handler;
    }

    protected function compileCustomDirectives(string $value): string
    {
        if (empty($this->customDirectives)) {
            return $value;
        }

        foreach ($this->customDirectives as $name => $handler) {
            $value = preg_replace_callback(
                "/@$name\s*\((.*?)\)/",
                fn($m) => $handler($m[1]),
                $value
            );
        }

        return $value;
    }
}
