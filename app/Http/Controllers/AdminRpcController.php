<?php

namespace App\Http\Controllers;

use App\Support\PanelOptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AdminRpcController extends Controller
{
    public function show()
    {
        $rpcOptions = PanelOptions::rpc();

        return view('admin.rpc', compact('rpcOptions'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'rpc_activation' => 'boolean',
            'rpc_id' => 'required|string|max:100',
            'rpc_details' => 'required|string|max:255',
            'rpc_state' => 'required|string|max:255',
            'rpc_large_text' => 'required|string|max:255',
            'rpc_small_text' => 'required|string|max:255',
            'rpc_button1' => 'nullable|string|max:50',
            'rpc_button1_url' => 'nullable|url|max:200',
            'rpc_button2' => 'nullable|string|max:50',
            'rpc_button2_url' => 'nullable|url|max:200',
        ]);

        PanelOptions::rpc()->update($validated);
        Cache::forever('launcher_options_version', (int) Cache::get('launcher_options_version', 1) + 1);

        return redirect()->route('admin.rpc')->with('success', __('messages.flash.rpc_updated'));
    }
}
