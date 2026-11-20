<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use App\Support\Photo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

/** Settings (§8.14): profile, utang defaults, inventory, language + payment methods. */
class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $business = $request->user()->business;
        $business->ensurePaymentMethods();

        return Inertia::render('Settings/Index', [
            'paymentMethods' => $business->paymentMethods()->get()->map(
                fn (PaymentMethod $m) => $this->methodPayload($m)
            ),
        ]);
    }

    public function update(Request $request)
    {
        $business = $request->user()->business;

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'type' => 'required|string|max:120',
            'location' => 'required|string|max:120',
            'default_due_days' => 'required|integer|min:1|max:365',
            'default_reminder_tone' => 'required|in:gentle,firm',
            'auto_remind' => 'boolean',
            'low_stock_threshold' => 'required|integer|min:0|max:1000',
            'language' => 'required|in:english,filipino',
        ]);

        $business->update($data);

        return redirect()->to('/settings')->with('success', __('Settings saved.'));
    }

    /** Upsert one payment method: label/account info + optional QR image. */
    public function savePaymentMethod(Request $request, PaymentMethod $method)
    {
        abort_unless($method->business_id === $request->user()->business->id, 403);

        $data = $request->validate([
            'label' => 'required|string|max:40',
            'account_name' => 'nullable|string|max:120',
            'account_number' => 'nullable|string|max:60',
            'is_active' => 'nullable|boolean',
            'qr' => 'nullable|image|max:4096', // 4MB QR snapshot
        ]);

        $method->update([
            'label' => $data['label'],
            'account_name' => $data['account_name'] ?? null,
            'account_number' => $data['account_number'] ?? null,
        ]);

        if (array_key_exists('is_active', $data)) {
            $method->update(['is_active' => (bool) $data['is_active']]);
        }

        if ($request->hasFile('qr')) {
            if ($method->qr_path) {
                Storage::disk('private')->delete($method->qr_path);
            }
            $method->update([
                'qr_path' => $request->file('qr')->store('payment-qr', 'private'),
            ]);
        }

        return redirect()->to('/settings')->with('success', __(':label saved.', ['label' => $method->label]));
    }

    /** Add a custom online wallet method. */
    public function addPaymentMethod(Request $request)
    {
        $business = $request->user()->business;
        $business->ensurePaymentMethods();

        $data = $request->validate(['label' => 'required|string|max:40']);

        $sort = (int) $business->paymentMethods()->max('sort') + 1;
        $method = $business->paymentMethods()->create([
            'code' => 'custom-'.Str::lower(Str::slug($data['label'])).'-'.$sort,
            'label' => $data['label'],
            'sort' => $sort,
        ]);

        return redirect()->to('/settings')->with('success', __(':label added.', ['label' => $method->label]));
    }

    /** Only custom methods are removable. */
    public function destroyPaymentMethod(Request $request, PaymentMethod $method)
    {
        abort_unless($method->business_id === $request->user()->business->id, 403);

        if (! str_starts_with($method->code, 'custom-')) {
            return back()->with('error', __('Default payment methods cannot be deleted.'));
        }

        if ($method->qr_path) {
            Storage::disk('private')->delete($method->qr_path);
        }
        $method->delete();

        return redirect()->to('/settings')->with('success', __(':label deleted.', ['label' => $method->label]));
    }

    private function methodPayload(PaymentMethod $m): array
    {
        return [
            'id' => $m->id,
            'code' => $m->code,
            'label' => $m->label,
            'account_name' => $m->account_name,
            'account_number' => $m->account_number,
            'is_active' => $m->is_active,
            'is_default' => ! str_starts_with($m->code, 'custom-'),
            'qr_url' => Photo::signedUrl($m->qr_path, 30),
        ];
    }
}
