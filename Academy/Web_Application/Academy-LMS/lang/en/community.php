<?php

return [
    'common' => [
        'greeting' => 'Hi :name,',
        'signature' => '— The :community Team',
        'cta_fallback' => 'Open in browser',
        'manage_preferences' => 'Manage notification preferences',
    ],
    'invite' => [
        'subject' => 'You are invited to join :community on :platform',
        'preview' => ':inviter invited you to join :community.',
        'intro' => ':inviter invited you to collaborate inside :community on :platform.',
        'cta' => 'Review invitation',
        'footer' => 'This invitation expires on :expiry.',
        'push' => [
            'title' => 'Invitation to :community',
            'body' => ':inviter invited you to join. Tap to review.',
        ],
    ],
    'approval' => [
        'subject' => 'Welcome to :community on :platform',
        'preview' => 'Your request to join :community has been approved.',
        'intro' => 'Great news—your request to join :community has been approved. Jump back in to say hello.',
        'cta' => 'Visit the community',
        'footer' => 'Need help getting started? Reply to this email or reach out to the community moderators.',
        'push' => [
            'title' => 'You are in! 🎉',
            'body' => 'Your access to :community is live. Tap to get started.',
        ],
    ],
    'new_reply' => [
        'subject' => ':actor replied to your post in :community',
        'preview' => '" :excerpt "',
        'intro' => ':actor just replied to **:post** in :community.',
        'cta' => 'Read the conversation',
        'footer' => 'Keep the thread going and let the community hear from you.',
        'push' => [
            'title' => ':actor replied in :community',
            'body' => 'They responded to " :post ". Tap to read it.',
        ],
    ],
    'mention' => [
        'subject' => 'You were mentioned in :community',
        'preview' => ':actor mentioned you: " :excerpt "',
        'intro' => ':actor mentioned you in :context within :community.',
        'cta' => 'View mention',
        'footer' => 'Join the conversation so people know you saw it.',
        'push' => [
            'title' => 'Mentioned by :actor',
            'body' => 'Someone pulled you into the conversation in :community.',
        ],
    ],
    'purchase_receipt' => [
        'subject' => 'Receipt :number — :community membership',
        'preview' => 'We received your payment of :amount :currency.',
        'intro' => 'Thanks for supporting :community. Here is a summary of your purchase.',
        'items_heading' => 'Order summary',
        'total_label' => 'Total',
        'cta' => 'Manage billing preferences',
        'footer' => 'Have billing questions? Reply to this message or email :support.',
        'push' => [
            'title' => 'Payment received',
            'body' => 'Your membership payment to :community is confirmed.',
        ],
    ],
    'reminder' => [
        'subject' => 'Reminder: :event on :date',
        'preview' => 'Happening soon in :community.',
        'intro' => 'Just a quick reminder that **:event** starts on :date for :community.',
        'cta' => 'View event details',
        'footer' => 'Add it to your calendar so you don’t miss it.',
        'push' => [
            'title' => ':event starts soon',
            'body' => 'Your event in :community begins on :date.',
        ],
    ],
    'digest' => [
        'subject' => ':community — :period highlights',
        'preview' => 'Top activity this :period from your community.',
        'intro' => 'Here’s what you missed in :community this :period.',
        'cta' => 'Catch up now',
        'footer' => 'Keep your streak going—stop by and share an update.',
        'highlights_heading' => 'Highlights',
        'empty_highlights' => 'It was a quiet :period. Why not start a conversation?',
        'push' => [
            'title' => ':community digest',
            'body' => 'Your :period highlights are ready. Tap to review.',
        ],
    ],
];
