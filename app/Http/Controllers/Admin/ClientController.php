<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientController extends Controller
{
    /**
     * Liste des clients
     */
    public function index(): View
    {
        $clients = User::where('role', 'client')
            ->latest()
            ->paginate(10);

        return view('admin.clients.index', compact('clients'));
    }

    /**
     * Détail d’un client
     */
    public function show(User $client): View
    {
        // Sécurité : empêcher l'accès si ce n'est pas un client
        abort_if($client->role !== 'client', 404);

        $client->load('orders');

        return view('admin.clients.show', compact('client'));
    }

    /**
     * Bloquer un client
     */
    public function block(User $client)
    {
        abort_if($client->role !== 'client', 404);

        $client->update([
            'is_active' => false
        ]);

        return redirect()
            ->route('admin.clients.index')
            ->with('success', 'Client bloqué avec succès');
    }

    /**
     * Activer un client
     */
    public function activate(User $client)
    {
        abort_if($client->role !== 'client', 404);

        $client->update([
            'is_active' => true
        ]);

        return redirect()
            ->route('admin.clients.index')
            ->with('success', 'Client activé avec succès');
    }
}
