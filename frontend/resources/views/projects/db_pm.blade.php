<div class="row">
  <table class="table table-bordered table-hover">
    <thead>
      <tr>
        <th class="table-primary border border-secondary align-middle text-center h5">WITEL</th>
        <th class="table-primary border border-secondary text-center h5">Status Delivery</th>
        <th class="table-primary border border-secondary text-center h5">Jumlah Project</th>
        <th class="table-primary border border-secondary text-center h5">Avg Waktu Delivery</th>
        <th class="table-primary border border-secondary text-center h5">RFS Paling Awal</th>
        <th class="table-primary border border-secondary text-center h5">RFS Paling Akhir</th>
      </tr>
    </thead>
    <tbody>
	@foreach($data as $item)
      <tr>
        <td class="table-white border border-secondary small text-center"><a class="link-dark" href="/projess/project-management">{{ $item->witel }}</a></td>
        <td class="table-white border border-secondary small text-center"><a class="link-dark" href="/projess/project-management">{{ $item->status_delivery }}</a></td>
        <td class="table-white border border-secondary small text-center"><a class="link-dark" href="/projess/project-management">{{ $item->jumlah_proyek }}</a></td>
        <td class="table-white border border-secondary small text-center"><a class="link-dark" href="/projess/project-management">{{ $item->rata_rata_durasi_delivery_hari }} hari</a></td>
        <td class="table-white border border-secondary small text-center"><a class="link-dark" href="/projess/project-management">{{ $item->rfs_paling_awal }}</a></td>
        <td class="table-white border border-secondary small text-center"><a class="link-dark" href="/projess/project-management">{{ $item->rfs_paling_akhir }}</a></td>
      </tr>
	@endforeach
    </tbody>
  </table>
</div>
