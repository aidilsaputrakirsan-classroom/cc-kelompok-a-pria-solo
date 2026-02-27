<section style="background-color: #96B6C5;">
  <div class="container my-5 py-5">
    <div class="row d-flex justify-content-center">
      <div class="col-md-12 col-lg-12">
        <div class="card text-body">

          <div class="card-body col-md-12">

			<div class="row">
				<div class="d-flex align-items-center justify-content-center col-md-2">
					<img class="rounded-circle shadow-1-strong me-3" src="{{ $user->avatar }}" alt="avatar" width="60" height="60" />
					<h6 class="fw-bold">{{ $user->name }}</h6>
				</div>
				<div class="col-md-10">
						{!! $form !!}			
				</div>
			</div>
		  </div>
		  
          <hr class="my-0" />
			
		  @forelse($diskusi as $item)
		  
          <div class="card-body p-4">
            <div class="d-flex flex-start">
              <img class="rounded-circle shadow-1-strong me-3" src="{{ $host }}
				@if(is_null($item->avatar))
				{{ $def_avatar }}
				@else
				storage/{{ $item->avatar }}
				@endif" 
				alt="avatar" width="60" height="60" />
              <div>
                <h6 class="fw-bold mb-1">{{ $item->name }} @if(isset($item->role))(<small>{{ $item->role }}</small>)@endif</h6>
                <div class="d-flex align-items-center mb-3">
                  <p class="mb-0 small">
				  {{ $item->created_at }}
                  </p>
                </div>
                <p class="mb-0 small">
				{!! $item->comment !!}
                </p>
              </div>
            </div>
          </div>

		  @empty
          <div class="card-body p-4">
            <div class="d-flex flex-start">
				<div>
					<p class="mb-0 small">Belum ada diskusi</p>
				</div>
			</div>
		  </div>
		  
		  @endforelse

        </div>
      </div>
    </div>
  </div>
</section>