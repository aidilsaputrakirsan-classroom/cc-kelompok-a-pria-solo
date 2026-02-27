<div class="container">
	<!-- Nav tabs -->
	<ul class="nav nav-tabs">
	  <li class="nav-item">
		<a class="nav-link active" data-bs-toggle="tab" href="#informasi">Informasi</a>
	  </li>
	  <li class="nav-item">
		<a class="nav-link" data-bs-toggle="tab" href="#diskusi">Diskusi</a>
	  </li>
	</ul>

	<!-- Tab panes -->
	<div class="tab-content">
		<!-- Tab Informasi -->
		<div class="tab-pane container active" id="informasi">
			<div class="card border-{{ $style }}" @if ($style!= 'none')style="border-top:2px solid;" @endif>
				<div class="card-header with-border">
					<h3 class="card-title">{{ $title }}</h3>

					<div class="card-tools">
						{!! $tools !!}
					</div>
				
				</div>
				<!-- /.box-header -->
				
				<!-- form start -->
				<div class="form-horizontal">

					<div class="card-body">
					
						@isset($progress_project)
						<div class="progress">
						  <div class="progress-bar bg-@if($progress_project < 50)warning @elseif($progress_project < 100)primary @elseif($progress_project == 100)success @endif" style="width:{{ $progress_project }}%">{{ $progress_project }}%</div>
						</div>
						@endisset

						<div class="row">

							@foreach($fields as $field)
								{!! $field->render() !!}
							@endforeach
						</div>

					</div>
					<!-- /.box-body -->
				</div>
			</div>
		</div>

		<!-- Tab Diskusi -->
		<div class="tab-pane container fade" id="diskusi">
			<div class="card border-{{ $style }}" @if ($style!= 'none')style="border-top:2px solid;" @endif>
				<div class="card-header with-border">
					<h3 class="card-title">{{ $title }}</h3>
					
					<div class="card-tools">
						{!! $tools !!}
					</div>
				
				</div>
				<!-- /.box-header -->
				
				<!-- form start -->
				<div class="form-horizontal">
					<div class="card-body">
					
						@isset($progress_project)
						<div class="progress">
						  <div class="progress-bar bg-@if($progress_project < 50)warning @elseif($progress_project < 100)primary @elseif($progress_project == 100)success @endif" style="width:{{ $progress_project }}%">{{ $progress_project }}%</div>
						</div>
						@endisset

						@include('projects.diskusi')
					</div>
					<!-- /.box-body -->
				</div>
			</div>
		</div>

	</div>
</div>


<!-- Modal Process -->
<div class="modal fade" id="followupModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fs-5" id="exampleModalLabel">FollowUp Project</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
	  {!! $form_process !!}
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>