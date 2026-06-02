<?php

namespace App\Http\Controllers;

use App\Models\Analise;
use App\Models\Consulta;
use App\Models\Gestante;

class DashboardController extends Controller
{
    public function index()
    {
        $totalGestantes = Gestante::count();
        $totalConsultas = Consulta::count();
        $chdConfirmadas = Consulta::where('chd_confirmada', true)->count();
        $chdNegativas = $totalConsultas > 0 ? $totalConsultas - $chdConfirmadas : 0;

        $gestantesComConsulta = Gestante::has('consultas')->count();
        $taxaGestantesComConsulta = $totalGestantes > 0
            ? round(100 * $gestantesComConsulta / $totalGestantes, 0)
            : 0;

        $pctChdNasConsultas = $totalConsultas > 0
            ? round(100 * $chdConfirmadas / $totalConsultas, 1)
            : 0.0;

        $mediaIdadeGestacional = Consulta::query()->avg('idade_gestacional');
        $mediaIdadeGestacional = $mediaIdadeGestacional !== null
            ? round((float) $mediaIdadeGestacional, 1)
            : null;

        $consultasComDiabetes = Consulta::where('diabetes_gestacional', true)->count();
        $consultasComHipertensao = Consulta::where('hipertensao', true)->count();

        $consultasRecentes = Consulta::query()
            ->with('gestante')
            ->orderByDesc('data_consulta')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        return view('dashboard', [
            'totalGestantes' => $totalGestantes,
            'totalConsultas' => $totalConsultas,
            'chdConfirmadas' => $chdConfirmadas,
            'chdNegativas' => $chdNegativas,
            'gestantesComConsulta' => $gestantesComConsulta,
            'taxaGestantesComConsulta' => $taxaGestantesComConsulta,
            'pctChdNasConsultas' => $pctChdNasConsultas,
            'mediaIdadeGestacional' => $mediaIdadeGestacional,
            'consultasComDiabetes' => $consultasComDiabetes,
            'consultasComHipertensao' => $consultasComHipertensao,
            'consultasRecentes' => $consultasRecentes,
            'analise' => Analise::first(),
            'hoje' => now()->format('d/m/Y'),
        ]);
    }
}
