<ul>
@foreach($list as $item)
<li>
<a href="/{{ $prefix }}/document/{{ $item->id_doc }}">[ {{ $item->id_obl }} ] {{ $item->LAYANAN }}</a> <span class="badge bg-primary rounded-pill"> {{ $item->step }}</span> 
@if(is_null($item->NO_QUOTE))<a href="/{{ $prefix }}/document/{{ $item->id_doc }}/edit"><span class="badge bg-warning rounded-pill">Input Quote</a> @else <span class="badge bg-primary rounded-pill">{{ $item->NO_QUOTE }}@endif</span> 
@if(is_null($item->NO_ORDER))<a href="/{{ $prefix }}/document/{{ $item->id_doc }}/edit"><span class="badge bg-warning rounded-pill">Input Order</a> @else <span class="badge bg-primary rounded-pill">{{ $item->NO_ORDER }}@endif</span>
</li>
@endforeach
</ul>