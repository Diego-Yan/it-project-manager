<?php

namespace App\Livewire\Itsm;

use App\Models\Sla;
use Livewire\Component;

class SlaManager extends Component
{
    public bool $showForm = false; public ?int $editingId = null;
    public string $formName = '', $formPriority = 'medium'; public int $formResponse = 30, $formResolution = 240;
    public bool $formIsActive = true;

    // [REVIEW-FIX-R2 #2 P2] 补充 formPriority 验证规则：原 rules 数组未包含 formPriority，
    // 用户可通过篡改前端提交任意字符串作为 SLA priority 值，导致 Sla::getDeadline() 查询不匹配。
    // 同时统一为 low/medium/high/critical（与 Ticket 模型和 Blade 模板一致）。
    protected $rules = [
        'formName'       => 'required|max:100',
        'formPriority'   => 'required|in:low,medium,high,critical',
        'formResponse'   => 'required|integer|min:1',
        'formResolution' => 'required|integer|min:1',
    ];

    public function save(): void
    {
        // [REVIEW-FIX] R11.1: 操作权限检查 — 路由级 middleware 不保护 Livewire action
        if (!auth()->user()->can('manage slas')) {
            session()->flash('error', __('没有 SLA 管理权限'));
            return;
        }
        $this->validate();
        $data = ['name'=>$this->formName,'priority'=>$this->formPriority,'response_minutes'=>$this->formResponse,'resolution_minutes'=>$this->formResolution,'is_active'=>$this->formIsActive];
        if ($this->editingId) { Sla::findOrFail($this->editingId)->update($data); }
        else { Sla::create($data); }
        $this->resetForm();
    }

    public function edit(int $id): void
    {
        if (!auth()->user()->can('manage slas')) {
            session()->flash('error', __('没有 SLA 管理权限'));
            return;
        }
        $s = Sla::findOrFail($id);
        $this->editingId=$id; $this->formName=$s->name; $this->formPriority=$s->priority;
        $this->formResponse=$s->response_minutes; $this->formResolution=$s->resolution_minutes;
        $this->formIsActive=$s->is_active; $this->showForm=true;
    }

    public function delete(int $id): void { if (!auth()->user()->can("manage slas")) { session()->flash("error", __("没有 SLA 管理权限")); return; } Sla::findOrFail($id)->delete(); }  // [REVIEW-FIX] R11.4: 统一错误提示
    public function resetForm(): void { $this->showForm=false; $this->editingId=null; $this->reset(['formName','formPriority','formResponse','formResolution']); $this->formPriority='medium'; $this->formResponse=30; $this->formResolution=240; $this->formIsActive=true; }

    public function render()
    {
        $slas = Sla::orderByRaw("CASE priority WHEN 'critical' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END")->get();
        return view('livewire.itsm.slas', compact('slas'))
            ->layout('layouts.app', ['title' => __('SLA 管理')]);
    }
}
