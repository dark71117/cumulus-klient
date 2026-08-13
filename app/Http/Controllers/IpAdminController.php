<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\IpLog;
use App\Models\IpRange;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class IpAdminController extends Controller
{
    public function index(Request $request): View
    {
        return $this->adminView($request);
    }

    public function saveActive(Request $request): View|RedirectResponse
    {
        $customer = Client::query()->findOrFail((int) $request->input('IpAdmin.id'));
        $customer->aktywny = (int) $request->input('IpAdmin.aktywny', 0);
        if ($request->filled('IpAdmin.haslo')) {
            $customer->haslo = Hash::make($request->input('IpAdmin.haslo'));
            $customer->auth_key = bin2hex(random_bytes(16));
        }
        $customer->save();

        return $this->saved($request, 'Pomyślnie zapisano dane');
    }

    public function storeIp(Request $request): View|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'id_klient' => ['required', 'integer'],
            'opis' => ['required', 'string', 'min:3', 'max:255'],
            'ip1' => ['required', 'ip'],
            'ip2' => ['required', 'ip'],
        ]);
        if ($validator->fails()) {
            if ($request->ajax()) {
                return $this->adminView($request)->withErrors($validator);
            }

            return redirect()->route('klient.ipadmin')->withErrors($validator)->withInput();
        }
        IpRange::query()->create($validator->validated());

        return $this->saved($request, 'Pomyślnie zapisano dane');
    }

    public function destroyIp(Request $request, int $id): View|RedirectResponse
    {
        IpRange::query()->where('id', $id)->delete();

        return $this->saved($request, 'Usunięto zakres IP');
    }

    public function journal(Request $request, int $id): View
    {
        $customer = Client::query()->findOrFail($id);
        $journal = IpLog::query()->where('id_klient', $id)->orderByDesc('czas')->get();
        $view = $request->ajax() ? 'klient.partials.ipjournal' : 'klient.ipjournal';

        return view($view, compact('customer', 'journal'));
    }

    private function saved(Request $request, string $message): View|RedirectResponse
    {
        if ($request->ajax()) {
            session()->now('success', $message);

            return $this->adminView($request);
        }

        return redirect()->route('klient.ipadmin')->with('success', $message);
    }

    private function adminView(Request $request): View
    {
        $users = Client::query()->orderBy('nazwa')->pluck('nazwa', 'id')->all();
        $customerId = (int) $request->session()->get('ipadminCustomerId', 0);
        if ($request->filled('IpAdmin.id')) {
            $customerId = (int) $request->input('IpAdmin.id');
        }
        $customer = $customerId ? Client::query()->find($customerId) : null;
        $ipAddresses = $customerId ? IpRange::query()->where('id_klient', $customerId)->get() : collect();
        $request->session()->put('ipadminCustomerId', $customerId);
        $view = $request->ajax() ? 'klient.partials.ipadmin' : 'klient.ipadmin';

        return view($view, compact('users', 'customerId', 'customer', 'ipAddresses'));
    }
}
