<?php

return [
    'common' => [
        'greeting' => 'Hola :name,',
        'signature' => '— El equipo de :community',
        'cta_fallback' => 'Abrir en el navegador',
        'manage_preferences' => 'Administrar preferencias de notificaciones',
    ],
    'invite' => [
        'subject' => 'Te invitaron a unirte a :community en :platform',
        'preview' => ':inviter te invitó a unirte a :community.',
        'intro' => ':inviter quiere que te unas a :community en :platform.',
        'cta' => 'Revisar invitación',
        'footer' => 'La invitación vence el :expiry.',
        'push' => [
            'title' => 'Invitación a :community',
            'body' => ':inviter te invitó a unirte. Toca para revisarla.',
        ],
    ],
    'approval' => [
        'subject' => 'Bienvenido a :community en :platform',
        'preview' => 'Tu solicitud para unirte a :community fue aprobada.',
        'intro' => '¡Buenas noticias! Ya puedes acceder a :community. Ingresa para saludar.',
        'cta' => 'Visitar la comunidad',
        'footer' => '¿Necesitas ayuda? Responde este correo o escribe a los moderadores.',
        'push' => [
            'title' => '¡Acceso concedido! 🎉',
            'body' => 'Ya puedes entrar a :community. Toca para comenzar.',
        ],
    ],
    'new_reply' => [
        'subject' => ':actor respondió tu publicación en :community',
        'preview' => '" :excerpt "',
        'intro' => ':actor respondió a **:post** en :community.',
        'cta' => 'Leer la conversación',
        'footer' => 'Sigue la conversación y comparte tu voz.',
        'push' => [
            'title' => ':actor respondió en :community',
            'body' => 'Respondieron a " :post ". Toca para leer.',
        ],
    ],
    'mention' => [
        'subject' => 'Te mencionaron en :community',
        'preview' => ':actor te mencionó: " :excerpt "',
        'intro' => ':actor te mencionó en :context dentro de :community.',
        'cta' => 'Ver mención',
        'footer' => 'Únete a la conversación para que sepan que lo viste.',
        'push' => [
            'title' => 'Mención de :actor',
            'body' => 'Te nombraron en :community. Toca para responder.',
        ],
    ],
    'purchase_receipt' => [
        'subject' => 'Recibo :number — Membresía de :community',
        'preview' => 'Recibimos tu pago de :amount :currency.',
        'intro' => 'Gracias por apoyar a :community. Este es el resumen de tu compra.',
        'items_heading' => 'Resumen del pedido',
        'total_label' => 'Total',
        'cta' => 'Administrar facturación',
        'footer' => '¿Dudas de pago? Responde este mensaje o escribe a :support.',
        'push' => [
            'title' => 'Pago recibido',
            'body' => 'Confirmamos tu pago para :community.',
        ],
    ],
    'reminder' => [
        'subject' => 'Recordatorio: :event el :date',
        'preview' => 'Sucede pronto en :community.',
        'intro' => 'Te recordamos que **:event** comienza el :date para :community.',
        'cta' => 'Ver detalles del evento',
        'footer' => 'Agrégalo a tu calendario para no olvidarlo.',
        'push' => [
            'title' => ':event comienza pronto',
            'body' => 'Tu evento en :community inicia el :date.',
        ],
    ],
    'digest' => [
        'subject' => ':community — Destacados de :period',
        'preview' => 'Lo mejor de :period en tu comunidad.',
        'intro' => 'Esto es lo que ocurrió en :community durante :period.',
        'cta' => 'Ponte al día',
        'footer' => 'Mantén el ritmo y comparte una actualización.',
        'highlights_heading' => 'Destacados',
        'empty_highlights' => 'Fue un :period tranquilo. ¿Por qué no iniciar una conversación?',
        'push' => [
            'title' => 'Resumen de :community',
            'body' => 'Tus destacados de :period están listos. Toca para verlos.',
        ],
    ],
];
