<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use Illuminate\Http\Request;

class AdminLogController extends Controller
{
    /**
     * Liste des logs admin
     */
    public function index(Request $request)
    {
        $query = AdminLog::with('admin');

        // Filtrer par admin
        if ($request->filled('admin_id')) {
            $query->where('admin_id', $request->admin_id);
        }

        // Filtrer par action
        if ($request->filled('action')) {
            $query->where('action', 'like', '%' . $request->action . '%');
        }

        $logs = $query
            ->latest()
            ->paginate(15)
            ->withQueryString();

        // Récupérer tous les admins pour le filtre
        $admins = \App\Models\User::where('role', 'admin')
            ->orderBy('name')
            ->get();

        return view('admin.logs.index', compact('logs', 'admins'));
    }

    /**
     * Détail d’un log
     */
    public function show(AdminLog $adminLog)
    {
        return view('admin.logs.show', compact('adminLog'));
    }

    /**
     * Obtenir la classe CSS pour le badge d'action
     */
    public static function getActionBadgeClass($action)
    {
        if (str_contains(strtolower($action), 'création') || str_contains(strtolower($action), 'creation')) {
            return 'bg-green-100 text-green-800';
        }
        if (str_contains(strtolower($action), 'suppression') || str_contains(strtolower($action), 'delete')) {
            return 'bg-red-100 text-red-800';
        }
        if (str_contains(strtolower($action), 'modification') || str_contains(strtolower($action), 'mise à jour')) {
            return 'bg-blue-100 text-blue-800';
        }
        if (str_contains(strtolower($action), 'approbation') || str_contains(strtolower($action), 'activation')) {
            return 'bg-emerald-100 text-emerald-800';
        }
        if (str_contains(strtolower($action), 'suspension') || str_contains(strtolower($action), 'blocage')) {
            return 'bg-amber-100 text-amber-800';
        }
        return 'bg-gray-100 text-gray-800';
    }

    /**
     * Obtenir le type d'action
     */
    public static function getActionType($action)
    {
        if (str_contains(strtolower($action), 'création') || str_contains(strtolower($action), 'creation')) {
            return 'Création';
        }
        if (str_contains(strtolower($action), 'suppression') || str_contains(strtolower($action), 'delete')) {
            return 'Suppression';
        }
        if (str_contains(strtolower($action), 'modification') || str_contains(strtolower($action), 'mise à jour')) {
            return 'Modification';
        }
        if (str_contains(strtolower($action), 'approbation') || str_contains(strtolower($action), 'activation')) {
            return 'Activation';
        }
        if (str_contains(strtolower($action), 'suspension') || str_contains(strtolower($action), 'blocage')) {
            return 'Restriction';
        }
        if (str_contains(strtolower($action), 'consultation') || str_contains(strtolower($action), 'affichage')) {
            return 'Consultation';
        }
        return 'Action';
    }

    /**
     * Supprimer tous les logs (danger contrôlé)
     */
    public function destroyAll()
    {
        AdminLog::truncate();

        return redirect()
            ->route('admin.logs.index')
            ->with('success', 'Tous les logs ont été supprimés');
    }
}
