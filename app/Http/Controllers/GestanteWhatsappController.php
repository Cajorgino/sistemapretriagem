<?php

namespace App\Http\Controllers;

use App\Http\Resources\GestanteResource;
use App\Models\Gestante;
use App\Models\GestanteWhatsapp;
use App\Services\GestanteWhatsappMessageRecorder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GestanteWhatsappController extends Controller
{
    public function __construct(
        private GestanteWhatsappMessageRecorder $whatsAppMessageRecorder
    ) {}

    /**
     * GET /api/gestante-whatsapp — listagem resumida (última mensagem por gestante), com JOIN em gestantes.
     */
    public function index(Request $request)
    {
        $query = $this->buildHistoricoResumoQuery($request);
        $this->applyOrdering($request, $query);

        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);
        $paginator = $query->paginate($perPage)->withQueryString();

        return response()->json([
            'data' => collect($paginator->items())->map(function ($g) {
                $ultima = $this->castUltimaData($g->ultima_data);

                return [
                    'gestante' => (new GestanteResource($g))->resolve(),
                    'ultima_mensagem' => $g->ultima_mensagem,
                    'tipo' => $g->ultimo_tipo,
                    'tempo_atendimento' => $g->ultimo_tempo_atendimento !== null ? (int) $g->ultimo_tempo_atendimento : null,
                    'tempo_atendimento_formatado' => $this->formatTempo($g->ultimo_tempo_atendimento !== null ? (int) $g->ultimo_tempo_atendimento : null),
                    'ultima_data' => $ultima?->toIso8601String(),
                ];
            }),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Página web: histórico de atendimento (mesma consulta da API, renderizada em Blade).
     */
    public function historicoPage(Request $request): View
    {
        $query = $this->buildHistoricoResumoQuery($request);
        $this->applyOrdering($request, $query);

        $rows = $query->paginate(15)->withQueryString();

        return view('historico_whatsapp.index', [
            'rows' => $rows,
            'sort' => $request->query('sort', 'ultima_data'),
            'direction' => strtolower((string) $request->query('direction', 'desc')) === 'asc' ? 'asc' : 'desc',
        ]);
    }

    /**
     * GET /api/gestante-whatsapp/{gestante} — histórico completo da gestante (PK da tabela gestantes).
     */
    public function show(Gestante $gestante)
    {
        $mensagens = GestanteWhatsapp::query()
            ->where('gestante_id', $gestante->id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        return response()->json([
            'gestante' => new GestanteResource($gestante),
            'mensagens' => $mensagens->map(fn (GestanteWhatsapp $m) => [
                'id' => $m->id,
                'mensagem' => $m->mensagem,
                'tipo' => $m->tipo,
                'tempo_atendimento' => $m->tempo_atendimento,
                'tempo_atendimento_formatado' => $m->tempo_atendimento_formatado,
                'created_at' => $m->created_at?->toIso8601String(),
            ]),
        ]);
    }

    /**
     * POST /api/gestante-whatsapp — webhook n8n/WAHA.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'telefone' => ['required', 'string', 'max:32'],
            'mensagem' => ['required', 'string'],
            'tipo' => ['required', 'in:entrada,saida'],
        ]);

        $row = $this->whatsAppMessageRecorder->record(
            $validated['telefone'],
            $validated['mensagem'],
            $validated['tipo']
        );

        return response()->json([
            'id' => $row->id,
            'gestante_id' => $row->gestante_id,
            'tempo_atendimento' => $row->tempo_atendimento,
            'tempo_atendimento_formatado' => $row->tempo_atendimento_formatado,
        ], 201);
    }

    private function buildHistoricoResumoQuery(Request $request)
    {
        $sub = DB::table('gestante_whatsapp as gw')
            ->select('gw.gestante_id', DB::raw('MAX(gw.id) as last_whatsapp_id'))
            ->whereNotNull('gw.gestante_id')
            ->groupBy('gw.gestante_id');

        $q = Gestante::query()
            ->joinSub($sub, 'lw', function ($join) {
                $join->on('gestantes.id', '=', 'lw.gestante_id');
            })
            ->join('gestante_whatsapp as gwl', 'gwl.id', '=', 'lw.last_whatsapp_id')
            ->select([
                'gestantes.*',
                'gwl.mensagem as ultima_mensagem',
                'gwl.tipo as ultimo_tipo',
                'gwl.tempo_atendimento as ultimo_tempo_atendimento',
                'gwl.created_at as ultima_data',
            ]);

        if ($request->filled('nome')) {
            $raw = (string) $request->input('nome');
            $term = '%'.addcslashes($raw, '%_\\').'%';
            $q->where(function ($sub) use ($term) {
                $sub->where('gestantes.gestante_id', 'like', $term)
                    ->orWhere('gestantes.nome', 'like', $term);
            });
        }

        if ($request->filled('cpf')) {
            $cpf = preg_replace('/\D/', '', (string) $request->input('cpf'));
            if ($cpf !== '') {
                $q->where('gestantes.cpf', 'like', '%'.$cpf.'%');
            }
        }

        if ($request->filled('telefone')) {
            $tel = preg_replace('/\D/', '', (string) $request->input('telefone'));
            if ($tel !== '') {
                $q->where('gestantes.telefone', 'like', '%'.$tel.'%');
            }
        }

        if ($request->filled('data_inicio')) {
            $q->where('gwl.created_at', '>=', Carbon::parse($request->input('data_inicio'))->startOfDay());
        }

        if ($request->filled('data_fim')) {
            $q->where('gwl.created_at', '<=', Carbon::parse($request->input('data_fim'))->endOfDay());
        }

        return $q;
    }

    private function applyOrdering(Request $request, $query): void
    {
        $sort = (string) $request->query('sort', 'ultima_data');
        $direction = strtolower((string) $request->query('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        if ($sort !== 'ultima_data') {
            $sort = 'ultima_data';
        }

        $query->orderBy($sort, $direction);
    }

    private function formatTempo(?int $seconds): ?string
    {
        if ($seconds === null) {
            return null;
        }

        $s = max(0, $seconds);
        $h = intdiv($s, 3600);
        $m = intdiv($s % 3600, 60);
        $sec = $s % 60;

        if ($h > 0) {
            return sprintf('%dh %dm %ds', $h, $m, $sec);
        }
        if ($m > 0) {
            return sprintf('%dm %ds', $m, $sec);
        }

        return sprintf('%ds', $sec);
    }

    private function castUltimaData(mixed $value): ?Carbon
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value;
        }

        return Carbon::parse((string) $value);
    }
}
