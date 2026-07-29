<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use App\Services\SecureImageStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentGatewayController extends Controller
{
    public function __construct(private SecureImageStorage $images) {}

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
            'payment' => 'required|string|max:100',
            'category' => 'required|string',
            'biaya' => 'required|numeric',
            'biaya_type' => 'required|in:rupiah,persen',
            'icon' => SecureImageStorage::rules(),
            'is_active' => 'required|boolean',
        ]);

        // Generate slug dari field 'payment'
        $validated['slug'] = Str::slug($validated['payment']);
        // Handle icon upload
        if ($request->hasFile('icon')) {
            $validated['icon'] = $this->images->store($request->file('icon'), 'payment-icons');
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
        $request->validate([
            'icon' => SecureImageStorage::rules(),
        ]);

        // dd($paymentGateway);
        $paymentGateway->payment = $request->payment;
        $paymentGateway->biaya = $request->biaya;
        $paymentGateway->biaya_type = $request->biaya_type;
        $paymentGateway->is_active = $request->is_active;

        $oldIcon = null;
        if ($request->hasFile('icon')) {
            $oldIcon = $paymentGateway->icon;
            $paymentGateway->icon = $this->images->store($request->file('icon'), 'payment-icons');
        }

        $paymentGateway->save();
        $this->images->delete('payment-icons', $oldIcon);
        $this->images->delete('icon_payment', $oldIcon);

        return redirect()->back()->with('success', 'Data berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PaymentGateway $paymentGateway)
    {
        // $gateway = PaymentGateway::findOrFail($id);

        $this->images->delete('payment-icons', $paymentGateway->icon);
        $this->images->delete('icon_payment', $paymentGateway->icon);
        $paymentGateway->delete();

        return redirect()->back()->with('success', 'Data berhasil dihapus.');
    }
}
