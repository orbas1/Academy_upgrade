<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Billing;

use App\Http\Controllers\Controller;
use App\Services\Billing\Exceptions\StripeWebhookException;
use App\Services\Billing\StripeWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Throwable;

class StripeWebhookController extends Controller
{
    public function __construct(private readonly StripeWebhookService $webhooks)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $event = $this->webhooks->constructEvent(
                $request->getContent(),
                $request->header('Stripe-Signature')
            );

            $this->webhooks->handle($event);

            return response()->json(['received' => true], HttpResponse::HTTP_OK);
        } catch (StripeWebhookException $exception) {
            report($exception);

            return response()->json(
                ['message' => $exception->getMessage()],
                HttpResponse::HTTP_BAD_REQUEST
            );
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(
                ['message' => 'Unexpected error processing Stripe webhook.'],
                HttpResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
