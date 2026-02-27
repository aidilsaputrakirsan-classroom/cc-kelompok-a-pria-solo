<ul>
@foreach($list as $item)
<li><a href="/{{ $prefix }}/projects/{{ $item->id_rso }}/kickoff/{{ $item->id }}"><span class="badge bg-primary rounded-pill">KickOff {{ $item->waktu }}</span></a></li>
@endforeach
</ul>