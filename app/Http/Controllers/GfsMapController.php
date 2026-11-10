<?php

namespace App\Http\Controllers;

use App\Services\Panel\GfsMapService;
use App\Support\GfsMapCatalog;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GfsMapController extends Controller
{
    public function __construct(private GfsMapService $maps) {}

    public function page(): string
    {
        $runs = $this->maps->runs();
        $run = $runs[0] ?? '';
        $frames = $run !== ''
            ? $this->maps->frames($run, 'Polska', 'synop3')
            : ['hours' => [], 'days' => [], 'count' => 0];

        return view('klient.partials.gfs-map', [
            'tabs' => GfsMapCatalog::tabs(),
            'boot' => [
                'runs' => $runs,
                'run' => $run,
                'region' => 'Polska',
                'product' => 'synop3',
                'lead' => $frames['hours'][0] ?? '',
                'frames' => $frames,
                'imageBase' => url('/klient/gfs/obraz'),
                'framesBase' => url('/klient/gfs/klatki'),
            ],
        ])->render();
    }

    public function frames(string $run, string $region, string $product): JsonResponse
    {
        $frames = $this->maps->frames($run, $region, $product);
        abort_if($frames === null, 404);

        return response()->json($frames);
    }

    public function image(string $run, string $region, string $product, string $hour): BinaryFileResponse
    {
        $path = $this->maps->imagePath($run, $region, $product, $hour);
        abort_if($path === null, 404);

        return response()->file($path, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
