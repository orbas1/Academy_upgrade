<?php

namespace App\Http\Controllers;

use App\Http\Requests\LocaleSwitchRequest;
use App\Support\Localization\LocaleManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class LocalizationController extends Controller
{
    public function __construct(private readonly LocaleManager $localeManager)
    {
    }

    public function __invoke(LocaleSwitchRequest $request): Response|RedirectResponse
    {
        $locale = $request->input('locale');

        $this->localeManager->apply($locale);
        $this->localeManager->queuePersistentCookie($locale);

        if ($request->expectsJson()) {
            return response([
                'message' => __('locale.updated'),
                'locale' => $this->localeManager->current(),
            ], 200, ['Content-Language' => $locale]);
        }

        return redirect()->back()->with('status', __('locale.updated'));
    }
}
