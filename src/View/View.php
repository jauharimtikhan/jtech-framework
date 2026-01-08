<?php

namespace Jtech\View;

class View
{
    protected BladeCompiler $compiler;
    protected ViewFinder $finder;

    // Tambahkan properti ini untuk menampung layout saat ini
    protected ?string $currentExtends = null;

    protected array $sections = [];
    protected array $sectionStack = [];

    protected array $componentStack = [];
    protected array $slots = [];
    protected array $slotStack = [];

    protected array $pushStacks = [];
    protected array $pushStackNames = [];

    public function __construct(BladeCompiler $compiler, ViewFinder $finder)
    {
        $this->compiler = $compiler;
        $this->finder   = $finder;
    }

    public function render(string $name, array $data = [])
    {
        $this->resetRenderState();
        return $this->renderInternal($name, $data);
    }

    protected function renderInternal(string $name, array $data)
    {
        // 1. Reset extends lokal untuk level rekursi ini
        $this->currentExtends = null;

        $viewPath = $this->finder->find($name);
        $compiled = $this->compiler->compile($viewPath);
        $errors = session()->get('errors', new \Illuminate\Support\MessageBag);

        // Gabungin ke data view
        $data['errors'] = $errors;
        extract($data, EXTR_SKIP);
        $__env = $this;

        ob_start();

        // 2. Saat file ini di-require, jika ada @extends,
        // dia akan mengeksekusi $__env->setExtends('layout');
        require $compiled;

        $content = ob_get_clean();

        // 3. Cek properti lokal $this->currentExtends (bukan cek ke compiler)
        if ($this->currentExtends) {
            $layout = $this->currentExtends;

            // Reset agar tidak terjadi infinite loop jika logic salah
            $this->currentExtends = null;

            // Render layout parent
            return $this->renderInternal($layout, $data);
        }

        return $content;
    }

    protected function resetRenderState(): void
    {
        $this->sections = [];
        $this->sectionStack = [];
        $this->componentStack = [];
        $this->slots = [];
        $this->slotStack = [];
        $this->pushStacks = [];
        $this->pushStackNames = [];
        $this->currentExtends = null; // Reset juga ini
    }

    // --- METHOD BARU: Dipanggil oleh file compiled ---
    public function setExtends(string $view): void
    {
        $this->currentExtends = $view;
    }

    // ... Sisanya sama persis (startSection, yieldContent, dll) ...

    public function startSection(string $name)
    {
        $this->sectionStack[] = $name;
        ob_start();
    }

    public function stopSection()
    {
        $name = array_pop($this->sectionStack);
        $this->sections[$name] = ob_get_clean();
    }

    public function yieldContent(string $name)
    {
        return $this->sections[$name] ?? '';
    }

    public function startComponent(string $view)
    {
        $this->componentStack[] = $view;
        $this->slots = [];
        $this->slotStack = [];
        ob_start();
    }

    public function startSlot(string $name)
    {
        $this->slotStack[] = $name;
        ob_start();
    }

    public function stopSlot()
    {
        $name = array_pop($this->slotStack);
        $this->slots[$name] = ob_get_clean();
    }

    public function renderComponent()
    {
        $defaultSlot = ob_get_clean();
        $view = array_pop($this->componentStack);

        return view($view, [
            'slot'  => $defaultSlot,
            'slots' => $this->slots,
        ]);
    }

    public function startPush(string $name)
    {
        $this->pushStackNames[] = $name;
        ob_start();
    }

    public function stopPush()
    {
        $name = array_pop($this->pushStackNames);
        $this->pushStacks[$name][] = ob_get_clean();
    }

    public function yieldStack(string $name)
    {
        return implode('', $this->pushStacks[$name] ?? []);
    }

    public function getCompiler()
    {
        return $this->compiler;
    }
    public function exists(string $name): bool
    {
        try {
            // Coba cari path-nya, kalau throw exception berarti gak ketemu
            $this->finder->find($name);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
