<?php

namespace Tests\Unit;

use App\Support\ImgwMap;
use Carbon\Carbon;
use Tests\TestCase;

class ImgwMapTest extends TestCase
{
    public function test_cloud_icon_uses_phenomenon_then_cloud_cover(): void
    {
        $this->assertSame('w61.png', ImgwMap::cloudIcon('61', 8));
        $this->assertSame('w01.png', ImgwMap::cloudIcon('N', 2));
        $this->assertSame('w05.png', ImgwMap::cloudIcon('N', 8));
        $this->assertSame('w01.png', ImgwMap::cloudIcon('N', 99));
        $this->assertSame('', ImgwMap::cloudIcon('N', -99));
    }

    public function test_latest_by_station_keeps_newest_row(): void
    {
        $older = (object) ['idStacji' => 1, 'termin' => '2026-08-15 18:00:00', 'temp' => 10];
        $newer = (object) ['idStacji' => 1, 'termin' => '2026-08-15 19:00:00', 'temp' => 20];
        $other = (object) ['idStacji' => 2, 'termin' => '2026-08-15 18:00:00', 'temp' => 8];

        $latest = ImgwMap::latestByStation([$older, $other, $newer]);
        $this->assertCount(2, $latest);
        $byId = [];
        foreach ($latest as $row) {
            $byId[(int) $row->idStacji] = $row;
        }
        $this->assertSame(20, $byId[1]->temp);
        $this->assertSame(8, $byId[2]->temp);
    }

    public function test_hour_without_measurement_shows_bd_not_copied_icons(): void
    {
        $wroclaw18 = $this->mapRow(12424, 'Wrocław', '2026-08-15 18:00:00', 10, 190, 480);
        $wroclaw19 = $this->mapRow(12424, 'Wrocław', '2026-08-15 19:00:00', 20, 190, 480);
        $krakow19 = $this->mapRow(1, 'Kraków', '2026-08-15 19:00:00', 8, 120, 80);

        $frames = ImgwMap::frames(
            [$wroclaw19, $krakow19],
            [$wroclaw18, $wroclaw19, $krakow19],
            '2026-08-15 19:00:00',
            ['geo_lat' => 51.1, 'geo_lon' => 17.0]
        );

        $this->assertCount(2, $frames);
        $this->assertSame('18:00', $frames[0]['hour']);
        $this->assertSame('19:00', $frames[1]['hour']);
        $this->assertSame('15.08.2026', $frames[0]['date']);
        $this->assertSame('15.08.2026', $frames[1]['date']);
        $byHour = [];
        foreach ($frames as $frame) {
            $byHour[$frame['hour']] = $frame;
        }
        $this->assertCount(2, $byHour['18:00']['points']);
        $this->assertCount(2, $byHour['19:00']['points']);
        $temps18 = array_column($byHour['18:00']['points'], 'temp', 'name');
        $temps19 = array_column($byHour['19:00']['points'], 'temp', 'name');
        $this->assertSame('10', $temps18['Wrocław']);
        $this->assertSame('BD', $temps18['Kraków']);
        $this->assertSame('20', $temps19['Wrocław']);
        $this->assertSame('8', $temps19['Kraków']);
        $krakow18 = null;
        foreach ($byHour['18:00']['points'] as $point) {
            if ($point['name'] === 'Kraków') {
                $krakow18 = $point;
            }
        }
        $this->assertNotNull($krakow18);
        $this->assertTrue($krakow18['missing']);
        $this->assertSame('', $krakow18['icon']);
    }

    public function test_frame_keeps_date_when_hour_crosses_midnight(): void
    {
        $late = $this->mapRow(12424, 'Wrocław', '2026-08-15 23:00:00', 18, 190, 480);
        $early = $this->mapRow(12424, 'Wrocław', '2026-08-16 00:00:00', 17, 190, 480);
        $frames = ImgwMap::frames([$early], [$late, $early], '2026-08-16 00:00:00', [
            'geo_lat' => 51.1,
            'geo_lon' => 17.0,
        ]);

        $this->assertCount(2, $frames);
        $this->assertSame('23:00', $frames[0]['hour']);
        $this->assertSame('15.08.2026', $frames[0]['date']);
        $this->assertSame('0:00', $frames[1]['hour']);
        $this->assertSame('16.08.2026', $frames[1]['date']);
    }

    public function test_playback_keeps_sparse_hours_within_limit(): void
    {
        $stale = $this->mapRow(12424, 'Wrocław', '2025-11-05 14:00:00', 1, 190, 480);
        $now = $this->mapRow(12424, 'Wrocław', '2026-08-15 15:00:00', 20, 190, 480);
        $frames = ImgwMap::frames([$now, $stale], [$now, $stale], '2026-08-15 15:00:00', [
            'geo_lat' => 51.1,
            'geo_lon' => 17.0,
        ]);
        $this->assertCount(2, $frames);
        $this->assertSame('14:00', $frames[0]['hour']);
        $this->assertSame('05.11.2025', $frames[0]['date']);
        $this->assertSame('15:00', $frames[1]['hour']);
        $this->assertSame('15.08.2026', $frames[1]['date']);
    }

    public function test_playback_keeps_only_last_48_hour_entries(): void
    {
        $max = Carbon::parse('2026-08-15 15:00:00');
        $rows = [];
        for ($i = 0; $i < 50; $i++) {
            $rows[] = $this->mapRow(
                12424,
                'Wrocław',
                $max->copy()->subHours($i)->format('Y-m-d H:i:s'),
                10,
                190,
                480
            );
        }
        $frames = ImgwMap::frames($rows, $rows, '2026-08-15 15:00:00', [
            'geo_lat' => 51.1,
            'geo_lon' => 17.0,
        ]);
        $oldest = $max->copy()->subHours(23);
        $this->assertCount(24, $frames);
        $this->assertSame($oldest->format('G').':00', $frames[0]['hour']);
        $this->assertSame($oldest->format('d.m.Y'), $frames[0]['date']);
        $this->assertSame('15:00', $frames[23]['hour']);
        $this->assertSame('15.08.2026', $frames[23]['date']);

        $long = ImgwMap::frames($rows, $rows, '2026-08-15 15:00:00', [
            'geo_lat' => 51.1,
            'geo_lon' => 17.0,
            'mapaOkresy' => 48,
        ]);
        $this->assertCount(48, $long);
    }

    public function test_playback_limit_clamps_to_allowed_range(): void
    {
        $this->assertSame(24, ImgwMap::playbackLimit([]));
        $this->assertSame(24, ImgwMap::playbackLimit(['mapaOkresy' => 0]));
        $this->assertSame(12, ImgwMap::playbackLimit(['mapaOkresy' => 12]));
        $this->assertSame(48, ImgwMap::playbackLimit(['mapaOkresy' => 48]));
        $this->assertSame(12, ImgwMap::playbackLimit(['mapaOkresy' => 3]));
        $this->assertSame(48, ImgwMap::playbackLimit(['mapaOkresy' => 99]));
    }

    public function test_point_tooltip_prefers_phenomenon_then_clouds(): void
    {
        $rain = $this->mapRow(12424, 'Wrocław', '2026-08-15 19:00:00', 20, 190, 480, 'słaby deszcz', 'duże');
        $cloud = $this->mapRow(12424, 'Wrocław', '2026-08-15 18:00:00', 18, 190, 480, '', 'małe');
        $rainFrames = ImgwMap::frames([$rain], [$rain], '2026-08-15 19:00:00', [
            'geo_lat' => 51.1,
            'geo_lon' => 17.0,
        ]);
        $cloudFrames = ImgwMap::frames([$cloud], [$cloud], '2026-08-15 18:00:00', [
            'geo_lat' => 51.1,
            'geo_lon' => 17.0,
        ]);
        $this->assertSame('słaby deszcz', $rainFrames[0]['points'][0]['text']);
        $this->assertSame('zachmurzenie małe', $cloudFrames[0]['points'][0]['text']);
    }

    private function mapRow(
        int $id,
        string $name,
        string $termin,
        float $temp,
        int $x,
        int $y,
        string $zjawiskoTXT = '',
        string $zachmurzenieTXT = ''
    ): object {
        return (object) [
            'idStacji' => $id,
            'nazwaStacji' => $name,
            'termin' => $termin,
            'temp' => $temp,
            'pozX' => $x,
            'pozY' => $y,
            'zjawiskoIkona' => 'N',
            'zachmurzenie' => 2,
            'zjawiskoTXT' => $zjawiskoTXT,
            'zachmurzenieTXT' => $zachmurzenieTXT,
        ];
    }
}
