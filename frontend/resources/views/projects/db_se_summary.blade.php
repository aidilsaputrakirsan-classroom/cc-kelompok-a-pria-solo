<div class="card border-none">
	<h5 class="card-header" style="background-color: #96B6C5;">Summary Project by Status</h5>
	<div class="card-body">
		<div class="list-group list-group-flush">
			@foreach($data as $item)
			<li class="list-group-item d-flex justify-content-between align-items-start">
				<div class="ms-2 me-auto">
				<a href="/projess/projects?status_project={{ $item->status_project }}" class="list-group-item-action">{{ $item->step }}</a>
				</div>
				<span class="badge bg-{{ $colors[$item->status_project] }}">{{ $item->qty }}</span>
			</li>
			@endforeach
		</div>
	</div>
	<div class="card-footer">
	<span class="badge bg-secondary">Account Manager</span>
	<span class="badge bg-warning">Solution Engineer Witel</span>
	<span class="badge bg-primary">Solution Regional</span>
	<span class="badge bg-success">Bidding Management Regional</span>
	<span class="badge bg-dark">DROP</span>
	</div>
</div>
