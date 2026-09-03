<?php

namespace App\Http\Controllers;

use App\Services\Panel\AnalysisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalizaController extends Controller
{
    public function query(Request $request): JsonResponse
    {
        $service = AnalysisService::fromSource($this->source($request));
        if ((string) $request->input('mode') === 'stats') {
            $stats = $service->stats(
                (int) $request->input('station', 0),
                (string) $request->input('from', ''),
                (string) $request->input('to', '')
            );

            return response()->json([
                'ok' => true,
                'html' => view('klient.partials.analiza-stats', ['stats' => $stats])->render(),
            ]);
        }
        $data = $service->hour($request->input('termin'));
        if (isset($data['error'])) {
            return response()->json(['ok' => false], 500);
        }

        return response()->json([
            'ok' => true,
            'html' => view('klient.partials.analiza-hour', ['rows' => $data['rows']])->render(),
            'meta' => [
                'termin' => $data['termin'],
                'prev' => $data['prev'],
                'next' => $data['next'],
                'latest' => $data['latest'],
            ],
        ]);
    }

    public function page(): string
    {
        $source = $this->source(request());
        $data = AnalysisService::fromSource($source)->hour();
        if (isset($data['error'])) {
            return view('klient.partials.critical')->render();
        }

        return view('klient.partials.analiza', [
            'data' => $data,
            'source' => $source,
            'mode' => 'hour',
        ])->render();
    }

    private function source(Request $request): string
    {
        return (string) $request->input('source', 'ogimet') === 'imgw' ? 'imgw' : 'ogimet';
    }
}
