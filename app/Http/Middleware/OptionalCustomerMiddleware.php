<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\Auth\AuthService;
use Illuminate\Support\Facades\Auth;

class OptionalCustomerMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            /** @var AuthService $authService */
            $authService = app(AuthService::class);

            // If they are admin/super_admin, redirect them to admin dashboard
            if ($authService->isAdmin(Auth::user())) {
                return redirect()->route('admin.dashboard');
            }

            // If they have the customer role, ensure customer profile is created
            if (Auth::user()->hasRole('customer')) {
                if (!Auth::user()->customer) {
                    // Find or create default customer level
                    $level = \App\Models\CustomerLevel::orderBy('sort_order')->first();
                    if (!$level) {
                        $level = \App\Models\CustomerLevel::create([
                            'name' => 'Retail Outlet',
                            'discount_percentage' => 0,
                            'default_credit_limit' => 100000,
                            'is_active' => true,
                            'sort_order' => 3,
                        ]);
                    }

                    \App\Models\Customer::create([
                        'user_id' => Auth::user()->id,
                        'customer_number' => 'CUST-' . str_pad(Auth::user()->id, 4, '0', STR_PAD_LEFT),
                        'customer_level_id' => $level->id,
                        'company_name' => Auth::user()->name ?: 'New Customer Garments',
                        'gst_number' => 'GSTIN-' . strtoupper(bin2hex(random_bytes(5))),
                        'contact_person' => Auth::user()->name ?: 'Contact Person',
                        'mobile_number' => Auth::user()->mobile_number ?: '+91 99999 99999',
                        'email' => Auth::user()->email,
                        'credit_limit' => $level->default_credit_limit,
                        'outstanding_amount' => 0.0,
                        'available_credit' => $level->default_credit_limit,
                        'is_active' => true,
                        'billing_address' => 'Default billing address',
                    ]);

                    Auth::user()->load('customer');
                }
            }
        }

        return $next($request);
    }
}
