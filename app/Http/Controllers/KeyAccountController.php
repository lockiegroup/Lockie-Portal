<?php

namespace App\Http\Controllers;

use App\Models\KeyAccount;
use App\Models\KeyAccountContact;
use App\Models\KeyAccountGift;
use App\Models\ActivityLog;
use App\Services\UnleashedService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;

class KeyAccountController extends Controller
{
    public function index(): View
    {
        $user    = auth()->user();
        $isAdmin = $user->can('key_accounts_admin');

        $accounts = $isAdmin
            ? KeyAccount::with(['user', 'contacts', 'gifts'])->orderBy('name')->get()
            : KeyAccount::with(['contacts', 'gifts'])->where('user_id', $user->id)->orderBy('name')->get();

        $years        = [now()->year - 1, now()->year];
        $customerCodes = $accounts->pluck('account_code')->all();

        $salesByYear = [];
        if (!empty($customerCodes)) {
            $unleashed = new UnleashedService(config('services.unleashed.id'), config('services.unleashed.key'));
            foreach ($years as $year) {
                $salesByYear[$year] = $unleashed->fetchSalesByCustomerCodes($customerCodes, $year);
            }
        }

        return view('key-accounts.index', compact('accounts', 'salesByYear', 'years', 'isAdmin'));
    }

    public function show(KeyAccount $keyAccount): View
    {
        $user    = auth()->user();
        $isAdmin = $user->can('key_accounts_admin');

        if (!$isAdmin && $keyAccount->user_id !== $user->id) {
            abort(403);
        }

        $keyAccount->load(['contacts.user', 'gifts']);

        return view('key-accounts.show', compact('keyAccount', 'isAdmin'));
    }

    public function storeContact(Request $request, KeyAccount $keyAccount): RedirectResponse
    {
        $user    = auth()->user();
        $isAdmin = $user->can('key_accounts_admin');

        if (!$isAdmin && $keyAccount->user_id !== $user->id) {
            abort(403);
        }

        $data = $request->validate([
            'contacted_at' => ['required', 'date'],
            'note'         => ['required', 'string', 'max:2000'],
        ]);

        $keyAccount->contacts()->create([
            'user_id'      => $user->id,
            'contacted_at' => $data['contacted_at'],
            'note'         => $data['note'],
        ]);

        ActivityLog::record('key_accounts.contact', "Logged contact for {$keyAccount->account_code}");

        return back()->with('success', 'Contact logged.');
    }

    public function destroyContact(KeyAccount $keyAccount, KeyAccountContact $contact): RedirectResponse
    {
        $user    = auth()->user();
        $isAdmin = $user->can('key_accounts_admin');

        if (!$isAdmin && $keyAccount->user_id !== $user->id) {
            abort(403);
        }

        $contact->delete();

        return back()->with('success', 'Contact entry removed.');
    }

    public function updateNotes(Request $request, KeyAccount $keyAccount): RedirectResponse
    {
        $user    = auth()->user();
        $isAdmin = $user->can('key_accounts_admin');

        if (!$isAdmin && $keyAccount->user_id !== $user->id) {
            abort(403);
        }

        $keyAccount->update(['notes' => $request->input('notes')]);

        return back()->with('success', 'Notes saved.');
    }

    public function importGifts(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls|max:5120']);

        try {
            $spreadsheet = IOFactory::load($request->file('file')->getRealPath());
            $rows        = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
            array_shift($rows); // remove header

            $user    = auth()->user();
            $isAdmin = $user->can('key_accounts_admin');

            $imported = 0;
            $skipped  = 0;

            foreach ($rows as $row) {
                $code        = trim((string) ($row[0] ?? ''));
                $recipient   = trim((string) ($row[1] ?? ''));
                $rawDate     = $row[2] ?? null;
                $description = trim((string) ($row[3] ?? ''));

                if (empty($code) || empty($recipient) || empty($description)) {
                    $skipped++;
                    continue;
                }

                // Parse date — handles Excel serial number, date string, or /Date()/ format
                if (is_numeric($rawDate)) {
                    try {
                        $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($rawDate)->format('Y-m-d');
                    } catch (\Throwable) {
                        $skipped++;
                        continue;
                    }
                } else {
                    $parsed = strtotime((string) $rawDate);
                    if (!$parsed) { $skipped++; continue; }
                    $date = date('Y-m-d', $parsed);
                }

                $account = KeyAccount::where('account_code', $code)->first();
                if (!$account || (!$isAdmin && $account->user_id !== $user->id)) {
                    $skipped++;
                    continue;
                }

                $account->gifts()->create([
                    'recipient'   => $recipient,
                    'gifted_at'   => $date,
                    'description' => $description,
                ]);
                $imported++;
            }

            ActivityLog::record('key_accounts.gifts_import', "Imported {$imported} gift(s)");

            return back()->with('success', "Imported {$imported} gift(s). Skipped {$skipped} rows.");
        } catch (\Throwable $e) {
            return back()->withErrors(['file' => 'Could not read file: ' . $e->getMessage()]);
        }
    }
}
