<?php

namespace App\Domain\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\XeroService;
use App\Domain\Common\Models\AccountMapping;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function getChartOfAccounts(XeroService $xero)
    {
        try {
            $accounts = $xero->getChartOfAccounts();
            return response()->json($accounts);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getMappings()
    {
        $mappings = AccountMapping::with('module')->get();
        
        // Transform the mappings to ensure module is just the name, not an object
        $mappings = $mappings->map(function ($mapping) {
            return [
                'id' => $mapping->id,
                'module' => is_object($mapping->module) ? $mapping->module->name : $mapping->module,
                'module_id' => $mapping->module_id,
                'transaction_type' => $mapping->transaction_type,
                'xero_account_code' => $mapping->xero_account_code,
                'xero_account_name' => $mapping->xero_account_name,
            ];
        });
        
        return response()->json($mappings);
    }

    public function updateMapping(Request $request)
    {
        $validated = $request->validate([
            'module' => 'required|string|max:50',
            'transaction_type' => 'nullable|string|max:50',
            'xero_account_code' => 'required|string|max:10',
            'xero_account_name' => 'nullable|string',
        ]);

        // First, ensure the module exists or create it
        $module = \App\Domain\Common\Models\Module::firstOrCreate(
            ['name' => $validated['module']],
            ['description' => 'Auto-created module for ' . $validated['module']]
        );

        // Now create or update the mapping with the module relationship
        $mapping = AccountMapping::updateOrCreate(
            [
                'module_id' => $module->id,
                'transaction_type' => $validated['transaction_type'],
            ],
            [
                'module' => $validated['module'], // Keep the module name for reference
                'xero_account_code' => $validated['xero_account_code'],
                'xero_account_name' => $validated['xero_account_name'],
            ]
        );

        if ($request->wantsJson()) {
            return response()->json($mapping);
        }

        return redirect()->route('settings.chart-of-accounts')
            ->with('success', 'Account mapping saved successfully.');
    }
} 