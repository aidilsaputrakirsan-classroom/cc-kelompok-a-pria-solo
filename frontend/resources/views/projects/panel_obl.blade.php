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
						@include('projects.diskusi')
					</div>
					<!-- /.box-body -->
				</div>
			</div>
		</div>

	</div>
</div>
