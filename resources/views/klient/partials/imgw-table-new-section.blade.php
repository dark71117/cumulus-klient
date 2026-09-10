@php
    $regionLabel = $regionLabel ?? 'Województwo / region';
@endphp
<table id="{{ $id }}" class="imgw-datatable display nowrap" style="width:100%">
    <thead>
        <tr>
            <th>{{ $regionLabel }}</th>
            <th>Miejscowość</th>
            <th>Temp. [°C]</th>
            <th>Temp. odcz. [°C]</th>
            <th>Zachmurzenie</th>
            <th>Zjawisko</th>
            <th>Widoczność [km]</th>
            <th>Wiatr/Porywy [km/h]</th>
        </tr>
    </thead>
    <tbody>
    @foreach($rows as $row)
        @include('klient.partials.imgw-table-new-row', ['row' => $row])
    @endforeach
    </tbody>
</table>
