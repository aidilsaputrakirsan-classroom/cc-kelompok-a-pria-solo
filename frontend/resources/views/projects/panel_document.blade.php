<div class="container">
	<!-- Nav tabs -->
	<ul class="nav nav-tabs">
	  <li class="nav-item">
		<a class="nav-link active" data-bs-toggle="tab" href="#informasi">Informasi</a>
	  </li>
	  <li class="nav-item">
		<a class="nav-link" data-bs-toggle="tab" href="#diskusi">Diskusi</a>
	  </li>
	  <li class="nav-item">
		<a class="nav-link" data-bs-toggle="tab" href="#files">Files</a>
	  </li>
	  <!--
	  <li class="nav-item">
		<a class="nav-link" data-bs-toggle="tab" href="#drafting">Drafting</a>
	  </li>
	  -->
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
					
						@isset($progress)
						<div class="progress">
						  <div class="progress-bar bg-@if($progress < 50)warning @elseif($progress < 100)primary @elseif($progress == 100)success @endif" style="width:{{ $progress }}%">{{ $progress }}%</div>
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

		<!-- Tab Files -->
		<div class="tab-pane container fade" id="files">
			<div class="card border-{{ $style }}" @if ($style!= 'none')style="border-top:2px solid;" @endif>
				<div class="card-header with-border">
					<h3 class="card-title">{{ $title }}</h3>			
				</div>
				<!-- /.box-header -->
				
				<!-- form start -->
				<div class="form-horizontal">
					<div class="card-body">
					@if(count($files)>0)
					<ul class="list-group">
						@foreach($files as $file)
						<li class="list-group-item">{{ pathinfo($file)['basename'] }}</li>
						@endforeach
					</ul>						
					@else
						<i>[Belum ada Files]</i>
					@endif
					
					</div>
					<!-- /.box-body -->
				</div>
			</div>
		</div>

		<!-- Tab Drafting -->
		<!--
		<div class="tab-pane container fade" id="drafting">
			<div class="card border-{{ $style }}" @if ($style!= 'none')style="border-top:2px solid;" @endif>
				<div class="card-header with-border">
					<h3 class="card-title">{{ $title }}</h3>

					<div class="card-tools">
						{!! $tools !!}
					</div>
									
				</div>

				<div class="form-horizontal">
					<div class="card-body">
						<div class="row">
							<label class="col-sm-2 form-label">Dokumen P1</label>
							<div class="col-sm-8 show-value">
								@if (isset($project->p1_nomor))
								Nomor : {{ $project->p1_nomor }} tanggal @isset($project->p1_tanggal) {{ $project->p1_tanggal->format('d M Y') }} @endisset<br/>
								<small>{{ $project->p1_namaKontrak }}</small>
								@else
									<i>[Dokumen P1 belum ada]</i>
								@endif
							</div>
						</div>
						<hr/>
						<div class="row">
							<label class="col-sm-2 form-label">Dokumen P2</label>
							<div class="col-sm-8 show-value">
								@if (isset($doc->p2_tanggal))
								Tanggal : @isset($doc->p2_tanggal) {{ $doc->p2_tanggal->format('d M Y') }} @endisset<br/>
								Calon Mitra : <ul>@foreach($doc->p2_calon_mitra as $mitra)<li>{{ $list_mitra[$mitra] }}</li>@endforeach</ul>
								
								Dibuat : {{ $officer_obl[$doc->p2_dibuat] }}<br/>
								Diperiksa : {{ $mgr_obl[$doc->p2_diperiksa] }}</br>
								Disetujui : {{ $sm_rso[$doc->p2_disetujui] }}</br>
								@else
									<i>[Dokumen P2 belum ada]</i>
								@endif
								@if (isset($doc->p2_tanggal))<a href='/projess/drafting/document/{{ $doc->id }}/p2'><button type='button' class='btn btn-outline-primary'>Drafting Dokumen P2</button></a>
								@endif
							</div>
						</div>
						<hr/>
						<div class="row">
							<label class="col-sm-2 form-label">Dokumen P3</label>
							<div class="col-sm-8 show-value">
								@if (isset($doc->p3_tanggal))
								Nomor : {{ $doc->p3_nomor }}</br>
								Tanggal : @isset($doc->p3_tanggal) {{ $doc->p3_tanggal->format('d M Y') }} @endisset<br/>
								Oleh : {{ $mgr_obl[$doc->p3_dibuat] }}</br>
								@else
									<i>[Dokumen P3 belum ada]</i>
								@endif
								@if (isset($doc->p3_tanggal))<a href='/projess/drafting/document/{{ $doc->id }}/p3'><button type='button' class='btn btn-outline-primary'>Drafting Dokumen P3</button></a>
								@endif
							</div>
						</div>
						<hr/>
						<div class="row">
							<label class="col-sm-2 form-label">Dokumen P4</label>
							<div class="col-sm-8 show-value">
								@if (isset($doc->p4_tanggal))
								Tanggal : @isset($doc->p4_tanggal) {{ $doc->p4_tanggal->format('d M Y') }} @endisset<br/>
								Skema Bisnis : {{ $doc->p4_skema_bisnis }}<br/>
								Term of Payment : {{ $doc->p4_top }}<br/>
								Lokasi Instalasi : {{ $doc->p4_lokasi_instalasi }}<br/>
								Jangka Waktu : {{ $doc->p4_jangka_waktu }}<br/>
								SLG : {{ $doc->p4_slg }} %<br/>
								Tanggal Deadline SPH : @isset($doc->p4_tgl_sph) {{ $doc->p4_tgl_sph->format('d M Y') }} @endisset<br/>
								Peserta : <ul>@foreach($doc->list_peserta as $peserta)<li>{{ $peserta }}</li>@endforeach</ul>
								@else
									<i>[Dokumen P4 belum ada]</i>
								@endif
								@if (isset($doc->p4_tanggal))
								<a href='/projess/drafting/document/{{ $doc->id }}/p4'><button type='button' class='btn btn-outline-primary'>Drafting Dokumen P4</button></a>
								@endif
							</div>
						</div>
						<hr/>
					</div>
				</div>
			</div>
		</div>
		-->
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