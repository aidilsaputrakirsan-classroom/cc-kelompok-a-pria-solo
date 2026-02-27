<div class="container">
	<!-- Nav tabs -->
	<ul class="nav nav-tabs">
	  <li class="nav-item">
		<a class="nav-link active" data-bs-toggle="tab" href="#inprogress">TOP In Progress</a>
	  </li>
	  <li class="nav-item">
		<a class="nav-link" data-bs-toggle="tab" href="#win">TOP WIN Projects</a>
	  </li>
	</ul>

	<!-- Tab panes -->
	<div class="tab-content">
		<!-- Tab In Progress -->
		<div class="tab-pane container active" id="inprogress">
			<div class="card border-none">			
				<div class="card-body">
					<ol class="list-group list-group-numbered">
						@foreach($data_inprogress as $item)
					  <li class="list-group-item d-flex justify-content-between align-items-start">
						<div class="ms-2 me-auto">
						  <div class="fw-bold"><a class="link-dark" href="/projess/projects/{{ $item->ID_RSO }}">{{ $item->Customer }}</a> ({{ number_format($item->Nilai_Project_Total/1000000,2,',','.') }} Juta)</div>
							<small>{{ $item->Nama_Project }}</small>
						</div>
						<span class="badge bg-{{ $colors[$item->status_project] }}">{{ $item->step }}</span>
						@if ($item->is_verified == "1")&nbsp;<span class="badge bg-success">VERIFIED</span>@endif
						@if ($item->is_ngtma == "1")&nbsp;<span class="badge bg-info">NGTMA</span>@endif
						@if ($item->is_ibl == "1")&nbsp;<span class="badge bg-info">IBL</span>@endif
					  </li>
						@endforeach
					</ol>
				</div>
			</div>
		</div>

		<!-- Tab Top WIN -->
		<div class="tab-pane container fade" id="win">
			<div class="card border-none">				
				<div class="card-body">
					<ol class="list-group list-group-numbered">
						@foreach($data_win as $item)
					  <li class="list-group-item d-flex justify-content-between align-items-start">
						<div class="ms-2 me-auto">
						  <div class="fw-bold"><a class="link-dark" href="/projess/projects/{{ $item->ID_RSO }}">{{ $item->Customer }}</a> ({{ number_format($item->Nilai_Project_Total/1000000,2,',','.') }} Juta)</div>
							<small>{{ $item->Nama_Project }}</small>
						</div>
						<span class="badge bg-{{ $colors[$item->status_project] }}">{{ $item->step }}</span>
						@if ($item->is_verified == "1")&nbsp;<span class="badge bg-success">VERIFIED</span>@endif
						@if ($item->is_ngtma == "1")&nbsp;<span class="badge bg-info">NGTMA</span>@endif
						@if ($item->is_ibl == "1")&nbsp;<span class="badge bg-info">IBL</span>@endif
					  </li>
						@endforeach
					</ol>
				</div>
			</div>
		</div>

	</div>
</div>
