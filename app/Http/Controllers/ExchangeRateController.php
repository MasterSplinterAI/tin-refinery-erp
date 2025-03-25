<?php

namespace App\Http\Controllers;

use App\Domain\ExchangeRate\Models\ExchangeRate;
use App\Domain\ExchangeRate\Services\ExchangeRateService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExchangeRateController extends Controller
{
    private ExchangeRateService $exchangeRateService;

    public function __construct(ExchangeRateService $exchangeRateService)
    {
        $this->exchangeRateService = $exchangeRateService;
    }

    public function index()
    {
        $rates = ExchangeRate::orderBy('date', 'desc')
            ->paginate(10);

        $latestRate = $this->exchangeRateService->getLatestRate();

        return Inertia::render('ExchangeRates/Index', [
            'rates' => $rates,
            'latestRate' => $latestRate
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'rate' => 'required|numeric|min:0',
            'date' => 'required|date',
        ]);

        $rate = $this->exchangeRateService->createManualRate(
            $validated['rate'],
            new \DateTime($validated['date'])
        );

        return redirect()->back()->with('success', 'Exchange rate added successfully');
    }

    public function convert(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'from_currency' => 'required|in:USD,COP',
            'to_currency' => 'required|in:USD,COP',
            'date' => 'nullable|date',
        ]);

        $date = $validated['date'] ? new \DateTime($validated['date']) : null;
        $amount = $validated['amount'];

        if ($validated['from_currency'] === 'USD' && $validated['to_currency'] === 'COP') {
            $result = $this->exchangeRateService->convertUsdToCop($amount, $date);
        } else {
            $result = $this->exchangeRateService->convertCopToUsd($amount, $date);
        }

        $rate = $date ? 
            $this->exchangeRateService->getRateForDate($date)?->rate : 
            $this->exchangeRateService->getLatestRate()?->rate;

        return back()->with('conversion', [
            'result' => $result,
            'rate_used' => $rate
        ]);
    }
} 