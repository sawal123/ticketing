<?php

namespace App\Http\Controllers\Dashboard;

use Storage;
use Illuminate\Http\Request;
use App\Models\PaymentGateway;
use App\Http\Controllers\Controller;

class PaymentGatewayController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $payment = PaymentGateway::paginate(20);
        // dd($payment);
        return view('backend.content.payment.payment-gateaway', compact('payment'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'payment'     => 'required|string|max:100',
            'biaya'       => 'required|numeric',
            'biaya_type'  => 'required|in:rupiah,persen',
            'icon'        => 'nullable|image|mimes:png,jpg,jpeg,svg|max:2048',
            'is_active'   => 'required|boolean',
        ]);

        // Handle icon upload
        if ($request->hasFile('icon')) {
            $iconPath = $request->file('icon')->store('payment-icons', 'public');
            $validated['icon'] = $iconPath;
        }

        PaymentGateway::create($validated);

        return redirect()->back()->with('success', 'Payment Gateway berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(PaymentGateway $paymentGateway)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PaymentGateway $paymentGateway)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PaymentGateway $paymentGateway)
    {
        // dd($paymentGateway);
        $paymentGateway->payment = $request->payment;
        $paymentGateway->biaya = $request->biaya;
        $paymentGateway->biaya_type = $request->biaya_type;
        $paymentGateway->is_active = $request->is_active;

        if ($request->hasFile('icon')) {
            $paymentGateway->icon = $request->file('icon')->store('icon_payment', 'public');
        }

        $paymentGateway->save();

        return redirect()->back()->with('success', 'Data berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PaymentGateway $paymentGateway)
    {
        // $gateway = PaymentGateway::findOrFail($id);

        // Jika ada file icon, bisa hapus juga (opsional)
        if ($paymentGateway->icon && Storage::disk('public')->exists($paymentGateway->icon)) {
            \Storage::disk('public')->delete($paymentGateway->icon);
        }

        $paymentGateway->delete();

        return redirect()->back()->with('success', 'Data berhasil dihapus.');
    }
}
