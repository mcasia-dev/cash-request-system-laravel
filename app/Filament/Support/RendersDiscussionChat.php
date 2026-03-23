<?php

namespace App\Filament\Support;

use Illuminate\Support\Facades\Auth;

trait RendersDiscussionChat
{
    protected function canViewDiscussionChat($record): bool
    {
        $currentUserId = Auth::id();

        if (!$currentUserId) {
            return false;
        }

        return $record->discussions()
            ->where(function ($query) use ($currentUserId): void {
                $query
                    ->where('sender_id', $currentUserId)
                    ->orWhere('recipient_id', $currentUserId);
            })
            ->exists();
    }

    protected function renderDiscussionChatHtml($record): string
    {
        $currentUserId = Auth::id();

        if (!$currentUserId) {
            return '<div style="padding:12px;border:1px dashed #374151;border-radius:12px;color:#9ca3af;">No messages yet.</div>';
        }

        $messages = $record->discussions()
            ->with(['sender', 'recipient'])
            ->where(function ($query) use ($currentUserId): void {
                $query
                    ->where('sender_id', $currentUserId)
                    ->orWhere('recipient_id', $currentUserId);
            })
            ->orderBy('id')
            ->get();

        if ($messages->isEmpty()) {
            return '<div style="padding:12px;border:1px dashed #374151;border-radius:12px;color:#9ca3af;">No messages yet.</div>';
        }

        $html = '<div style="display:flex;flex-direction:column;gap:10px;padding:4px 0;">';

        foreach ($messages as $message) {
            $isMine = $currentUserId !== null && (int) $message->sender_id === (int) $currentUserId;

            $align = $isMine ? 'flex-end' : 'flex-start';
            $bubbleColor = $isMine ? '#2563eb' : '#1f2937';
            $textColor = '#f9fafb';
            $sender = e($message->sender?->name ?? 'System');
            $recipient = e($message->recipient?->name ?? 'All Concerned');
            $type = e(str($message->type)->replace('_', ' ')->title()->toString());
            $remarks = nl2br(e((string) $message->remarks));
            $time = e(optional($message->created_at)->format('M d, Y h:i A'));

            $html .= '<div style="display:flex;justify-content:' . $align . ';">';
            $html .= '<div style="max-width:min(75%,760px);display:flex;flex-direction:column;gap:6px;">';
            $html .= '<div style="font-size:12px;color:#9ca3af;">' . $sender . ' to ' . $recipient . ' • ' . $type . '</div>';
            $html .= '<div style="background:' . $bubbleColor . ';color:' . $textColor . ';padding:12px 14px;border-radius:16px;line-height:1.45;word-break:break-word;">' . $remarks . '</div>';
            $html .= '<div style="font-size:11px;color:#9ca3af;">' . $time . '</div>';
            $html .= '</div>';
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }
}
