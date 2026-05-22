<?php

declare(strict_types=1);

namespace App\Modules\CRM\Enums;

enum LeadStatus: string
{
    case NEW = 'new';
    case CONTACTED = 'contacted';
    case SURVEY_PENDING = 'survey_pending';
    case PROPOSAL_SENT = 'proposal_sent';
    case WON = 'won';
    case LOST = 'lost';

    public function label(): string
    {
        return match($this) {
            self::NEW => 'New',
            self::CONTACTED => 'Contacted',
            self::SURVEY_PENDING => 'Site Survey',
            self::PROPOSAL_SENT => 'Proposal Sent',
            self::WON => 'Won / Converted',
            self::LOST => 'Lost',
        };
    }
}
