<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\NotificationTemplateService;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationTemplateController extends Controller
{
    public function edit(NotificationTemplateService $t): Response
    {
        return Inertia::render('Admin/Settings/Notifications', ['templates' => $t->all(), 'variables' => ['{registration_number}', '{full_name}', '{payment_status}', '{document_status}', '{selection_status}', '{selection_date}', '{selection_time}']]);
    }

    public function update(Request $r, SettingsService $s, NotificationTemplateService $t): RedirectResponse
    {
        $data = $r->validate(['templates' => ['required', 'array'], 'templates.*' => ['required', 'string', 'max:3000']]);
        foreach ($data['templates'] as $event => $template) {
            if (array_key_exists($event, $t->all())) {
                $s->put('notifications', 'notifications.'.$event, $template);
            }
        }

return back()->with('success', 'Template notifikasi tersimpan.');
    }
}
