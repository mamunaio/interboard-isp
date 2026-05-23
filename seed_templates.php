<?php

use App\Modules\Notification\Models\NotificationTemplate;
use App\Modules\Tenant\Models\Tenant;

$tenant = Tenant::first();

$templates = [
    [
        'event_name' => 'invoice.created',
        'channel' => 'email',
        'subject' => 'New Invoice: {{ invoice_number }}',
        'body' => "Dear {{ customer_name }},\n\nYour new invoice {{ invoice_number }} for the amount of {{ amount }} BDT has been generated.\nDue date: {{ due_date }}.\n\nThank you for choosing Interboard.",
        'is_active' => true,
    ],
    [
        'event_name' => 'invoice.created',
        'channel' => 'sms',
        'subject' => null,
        'body' => "Dear {{ customer_name }}, your new invoice {{ invoice_number }} of {{ amount }} BDT is generated. Due: {{ due_date }}. - Interboard",
        'is_active' => true,
    ],
    [
        'event_name' => 'invoice.paid',
        'channel' => 'email',
        'subject' => 'Payment Confirmation: {{ invoice_number }}',
        'body' => "Dear {{ customer_name }},\n\nWe have received your payment of {{ amount }} BDT for invoice {{ invoice_number }}.\nYour account is up to date.\n\nThank you!",
        'is_active' => true,
    ],
    [
        'event_name' => 'invoice.paid',
        'channel' => 'sms',
        'subject' => null,
        'body' => "We have received {{ amount }} BDT for your invoice {{ invoice_number }}. Thank you for your payment. - Interboard",
        'is_active' => true,
    ],
    [
        'event_name' => 'ticket.created',
        'channel' => 'email',
        'subject' => 'Support Ticket #{{ ticket_id }} Opened',
        'body' => "Dear {{ customer_name }},\n\nWe have received your support request. Ticket #{{ ticket_id }} has been created.\nOur team will review and get back to you shortly.\n\nBest Regards,\nSupport Team",
        'is_active' => true,
    ],
    [
        'event_name' => 'ticket.resolved',
        'channel' => 'email',
        'subject' => 'Support Ticket #{{ ticket_id }} Resolved',
        'body' => "Dear {{ customer_name }},\n\nYour support ticket #{{ ticket_id }} has been marked as resolved.\nIf you have further issues, please let us know.\n\nBest Regards,\nSupport Team",
        'is_active' => true,
    ],
    [
        'event_name' => 'subscription.expiring',
        'channel' => 'sms',
        'subject' => null,
        'body' => "Reminder: Your internet subscription will expire on {{ expiry_date }}. Please renew to avoid disconnection. - Interboard",
        'is_active' => true,
    ],
    [
        'event_name' => 'customer.welcome',
        'channel' => 'email',
        'subject' => 'Welcome to Interboard ISP',
        'body' => "Dear {{ customer_name }},\n\nWelcome to Interboard! We are thrilled to have you on board.\nYour customer code is {{ customer_code }}.\n\nIf you need any help, contact our support team.",
        'is_active' => true,
    ],
];

foreach ($templates as $template) {
    NotificationTemplate::firstOrCreate(
        [
            'tenant_id' => $tenant ? $tenant->id : null,
            'event_name' => $template['event_name'],
            'channel' => $template['channel'],
        ],
        $template
    );
}

echo "Demo notification templates generated successfully.\n";
