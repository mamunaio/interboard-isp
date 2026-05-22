<?php

declare(strict_types=1);

namespace App\Modules\Notification\Http\Livewire;

use App\Core\Tenant\TenantManager;
use App\Modules\Notification\Models\NotificationTemplate;
use Livewire\Component;

class NotificationTemplateManager extends Component
{
    public ?int $tenantId = null;

    public $templates = [];
    public $editingTemplateId = null;
    public $editEventName = '';
    public $editChannel = 'sms';
    public $editSubject = '';
    public $editBody = '';
    public $editIsActive = true;
    
    public bool $isModalOpen = false;

    public function mount(): void
    {
        $this->tenantId = app(TenantManager::class)->getTenantId();
        $this->loadTemplates();
    }

    public function loadTemplates(): void
    {
        $this->templates = NotificationTemplate::where('tenant_id', $this->tenantId)->get();
    }

    public function openModal(?int $id = null): void
    {
        $this->resetValidation();
        
        if ($id) {
            $template = NotificationTemplate::findOrFail($id);
            $this->editingTemplateId = $template->id;
            $this->editEventName = $template->event_name;
            $this->editChannel = $template->channel;
            $this->editSubject = $template->subject ?? '';
            $this->editBody = $template->body;
            $this->editIsActive = $template->is_active;
        } else {
            $this->editingTemplateId = null;
            $this->editEventName = '';
            $this->editChannel = 'sms';
            $this->editSubject = '';
            $this->editBody = '';
            $this->editIsActive = true;
        }
        
        $this->isModalOpen = true;
    }

    public function closeModal(): void
    {
        $this->isModalOpen = false;
    }

    public function saveTemplate(): void
    {
        $this->validate([
            'editEventName' => 'required|string',
            'editChannel' => 'required|string|in:sms,email,whatsapp',
            'editBody' => 'required|string',
        ]);

        NotificationTemplate::updateOrCreate(
            [
                'tenant_id' => $this->tenantId,
                'event_name' => $this->editEventName,
                'channel' => $this->editChannel,
            ],
            [
                'subject' => $this->editChannel === 'email' ? $this->editSubject : null,
                'body' => $this->editBody,
                'is_active' => $this->editIsActive,
            ]
        );

        $this->closeModal();
        $this->loadTemplates();
        session()->flash('message', 'Notification Template saved successfully.');
    }

    public function render()
    {
        return view('notification::livewire.template-manager');
    }
}
