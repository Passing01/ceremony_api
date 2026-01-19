<?php

namespace App\Http\Controllers;

use App\Models\Template;
use App\Models\UserCredit;
use Illuminate\Http\Request;

class CreditController extends Controller
{
    public function purchase(Request $request)
    {
        $request->validate([
            'template_id' => 'required|exists:templates,id',
            'pack_size' => 'integer|min:1', // Usually 2 per pack as per spec, but flexible
            // 'payment_token' => 'required' // In real app verify receipt here
        ]);

        $user = $request->user();
        
        $credit = UserCredit::firstOrCreate(
            ['user_id' => $user->id, 'template_id' => $request->template_id],
            ['remaining_uses' => 0]
        );

        $usesToAdd = $request->pack_size ?? 2;
        $credit->increment('remaining_uses', $usesToAdd);

        return response()->json([
            'message' => 'Crédits ajoutés avec succès',
            'credits' => $credit
        ]);
    }
    
    public function balance(Request $request)
    {
        $credits = UserCredit::where('user_id', $request->user()->id)
                    ->with('template')
                    ->get();
                    
        return response()->json($credits);
    }
}
