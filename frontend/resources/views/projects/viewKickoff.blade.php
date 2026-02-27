<div class='container px-2 py-2 bg-grey'>
	<span class='badge bg-info'>Waktu  : {{ $item->waktu }}</span>
	<span class='badge bg-info'>Lokasi : {{ $item->lokasi }}</span>
</div>

<div class='container px-4 py-4 bg-white'>
	<div class="row">
		<div class="card col-md-3">
			<div class="card-header bg-secondary"><b style="color: white;">Peserta KickOff</b></div>
			<div class="card-body">{!! $item->peserta !!}</div>
		</div>
		<div class="card col-md-9">
			<div class="card-header bg-secondary"><b style="color: white;">Summary KickOff</b></div>
			<div class="card-body">{!! $item->summary !!}</div>
		</div>
	</div>
</div>

<div class='container px-2 py-2 bg-grey'>
	<a href="{{ $url_prefix }}/{{ $item->id_rso }}"><button type="button" class="btn btn-info">Kembali ke Detail Projects</button></a>
</div>