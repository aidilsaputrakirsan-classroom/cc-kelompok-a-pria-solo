<div class="row">
  <table class="table table-bordered table-hover">
    <thead>
      <tr>
        <th class="table-white border border-secondary align-middle text-center h5" rowspan="2">WITEL</th>
        <th class="table-warning border border-secondary text-center h5" colspan="5">Simple OBL</th>
        <th class="table-primary border border-secondary text-center h5" colspan="7">Multi OBL</th>
        <th class="table-success border border-secondary text-center h5" colspan="3">Negosiasi</th>
        <th class="table-danger border border-secondary text-center h5" colspan="5">Wait KB</th>
        <th class="table-success border border-secondary text-center h5" colspan="3">Kontrak Layanan</th>
        <th class="table-success border border-secondary text-center h5" colspan="4">Close SM</th>
        <th class="table-success border border-secondary text-center h5" colspan="2">Closing</th>
      </tr>
      <tr>
        <th class="table-warning border border-secondary text-center small">Start</th>
        <th class="table-warning border border-secondary text-center small">P2</th>
        <th class="table-warning border border-secondary text-center small">P3</th>
        <th class="table-warning border border-secondary text-center small">P4</th>
        <th class="table-warning border border-secondary text-center small">SPH</th>
        <th class="table-primary border border-secondary text-center small">Start</th>
        <th class="table-primary border border-secondary text-center small">Contest</th>
        <th class="table-primary border border-secondary text-center small">P2</th>
        <th class="table-primary border border-secondary text-center small">P3</th>
        <th class="table-primary border border-secondary text-center small">P4</th>
        <th class="table-primary border border-secondary text-center small">SPH</th>
        <th class="table-primary border border-secondary text-center small">Skoring</th>
        <th class="table-success border border-secondary text-center small">Nego</th>
        <th class="table-success border border-secondary text-center small">P6-P7</th>
        <th class="table-success border border-secondary text-center small">SKM</th>
        <th class="table-danger border border-secondary text-center small">RAB<br/>Final</th>
        <th class="table-danger border border-secondary text-center small">Draft<br/>KB</th>
        <th class="table-success border border-secondary text-center small">Review<br/>KB</th>
        <th class="table-danger border border-secondary text-center small">Sirkulir<br/>KB</th>
        <th class="table-danger border border-secondary text-center small">Input<br/>Quote</th>
        <th class="table-success border border-secondary text-center small">Draft<br/>P8-KL</th>
        <th class="table-success border border-secondary text-center small">Review<br/>KL</th>
        <th class="table-success border border-secondary text-center small">Review<br/>Legal-Mitra</th>
        <th class="table-success border border-secondary text-center small">Verifikasi<br/>Dok OBL</th>
        <th class="table-success border border-secondary text-center small">Sirkulir<br/>Internal</th>
        <th class="table-success border border-secondary text-center small">Sirkulir<br/>Mitra</th>
        <th class="table-success border border-secondary text-center small">Close<br/>SM</th>
        <th class="table-danger border border-secondary text-center small">Input<br/>Order</th>
        <th class="table-success border border-secondary text-center small">OBL<br/>Done</th>
      </tr>
    </thead>
    <tbody>
	@foreach($data as $item)
      <tr>
        <td class="table-white border border-secondary small"><a class="link-dark" href="/projess/document?2d06a1562feb25f0e3c248bfb424b01e={{ $item->Witel }}">{{ $item->Witel }}</a></td>
        <td class="bg-warning bg-opacity-10 border border-secondary text-center small"><a class="link-dark" href="/projess/document?2d06a1562feb25f0e3c248bfb424b01e={{ $item->Witel }}&status_doc=130">{{ $item->s_start }}</a></td>
        <td class="bg-warning bg-opacity-10 border border-secondary text-center small"><a class="link-dark" href="/projess/document?2d06a1562feb25f0e3c248bfb424b01e={{ $item->Witel }}&status_doc=131">{{ $item->s_p2 }}</a></td>
        <td class="bg-warning bg-opacity-10 border border-secondary text-center small"><a class="link-dark" href="/projess/document?2d06a1562feb25f0e3c248bfb424b01e={{ $item->Witel }}&status_doc=132">{{ $item->s_p3 }}</a></td>
        <td class="bg-warning bg-opacity-10 border border-secondary text-center small"><a class="link-dark" href="/projess/document?2d06a1562feb25f0e3c248bfb424b01e={{ $item->Witel }}&status_doc=133">{{ $item->s_p4 }}</a></td>
        <td class="bg-warning bg-opacity-10 border border-secondary text-center small"><a class="link-dark" href="/projess/document?2d06a1562feb25f0e3c248bfb424b01e={{ $item->Witel }}&status_doc=134">{{ $item->s_sph }}</a></td>
        <td class="bg-primary bg-opacity-10 border border-secondary text-center small"><a class="link-dark" href="/projess/document?2d06a1562feb25f0e3c248bfb424b01e={{ $item->Witel }}&status_doc=140">{{ $item->m_start }}</a></td>
        <td class="bg-primary bg-opacity-10 border border-secondary text-center small"><a class="link-dark" href="/projess/document?2d06a1562feb25f0e3c248bfb424b01e={{ $item->Witel }}&status_doc=141">{{ $item->m_contest }}</a></td>
        <td class="bg-primary bg-opacity-10 border border-secondary text-center small"><a class="link-dark" href="/projess/document?2d06a1562feb25f0e3c248bfb424b01e={{ $item->Witel }}&status_doc=142">{{ $item->m_p2 }}</a></td>
        <td class="bg-primary bg-opacity-10 border border-secondary text-center small"><a class="link-dark" href="/projess/document?2d06a1562feb25f0e3c248bfb424b01e={{ $item->Witel }}&status_doc=143">{{ $item->m_p3 }}</a></td>
        <td class="bg-primary bg-opacity-10 border border-secondary text-center small"><a class="link-dark" href="/projess/document?2d06a1562feb25f0e3c248bfb424b01e={{ $item->Witel }}&status_doc=144">{{ $item->m_p4 }}</a></td>
        <td class="bg-primary bg-opacity-10 border border-secondary text-center small"><a class="link-dark" href="/projess/document?2d06a1562feb25f0e3c248bfb424b01e={{ $item->Witel }}&status_doc=145">{{ $item->m_sph }}</a></td>
        <td class="bg-primary bg-opacity-10 border border-secondary text-center small"><a class="link-dark" href="/projess/document?2d06a1562feb25f0e3c248bfb424b01e={{ $item->Witel }}&status_doc=146">{{ $item->m_skoring }}</a></td>
        <td class="bg-success bg-opacity-10 border border-secondary text-center small"><a class="link-dark" href="/projess/document?2d06a1562feb25f0e3c248bfb424b01e={{ $item->Witel }}&status_doc=150">{{ $item->nego }}</a></td>
        <td class="bg-success bg-opacity-10 border border-secondary text-center small"><a class="link-dark" href="/projess/document?2d06a1562feb25f0e3c248bfb424b01e={{ $item->Witel }}&status_doc=155">{{ $item->p6 }}</a></td>
        <td class="bg-success bg-opacity-10 border border-secondary text-center small"><a class="link-dark" href="/projess/document?2d06a1562feb25f0e3c248bfb424b01e={{ $item->Witel }}&status_doc=160">{{ $item->skm }}</a></td>
        <td class="bg-danger bg-opacity-10 border border-secondary text-center small"><a class="link-dark" href="/projess/document?2d06a1562feb25f0e3c248bfb424b01e={{ $item->Witel }}&status_doc=170">{{ $item->rab_final }}</a></td>
        <td class="bg-danger bg-opacity-10 border border-secondary text-center small"><a class="link-dark" href="/projess/document?2d06a1562feb25f0e3c248bfb424b01e={{ $item->Witel }}&status_doc=212">{{ $item->draft_kb }}</a></td>
        <td class="bg-success bg-opacity-10 border border-secondary text-center small"><a class="link-dark" href="/projess/document?2d06a1562feb25f0e3c248bfb424b01e={{ $item->Witel }}&status_doc=214">{{ $item->review_kb }}</a></td>
        <td class="bg-danger bg-opacity-10 border border-secondary text-center small"><a class="link-dark" href="/projess/document?2d06a1562feb25f0e3c248bfb424b01e={{ $item->Witel }}&status_doc=216">{{ $item->sirkulir_kb }}</a></td>
        <td class="bg-danger bg-opacity-10 border border-secondary text-center small"><a class="link-dark" href="/projess/document?2d06a1562feb25f0e3c248bfb424b01e={{ $item->Witel }}&status_doc=218">{{ $item->input_quote }}</a></td>
        <td class="bg-success bg-opacity-10 border border-secondary text-center small"><a class="link-dark" href="/projess/document?2d06a1562feb25f0e3c248bfb424b01e={{ $item->Witel }}&status_doc=220">{{ $item->draft_kl }}</a></td>
        <td class="bg-success bg-opacity-10 border border-secondary text-center small"><a class="link-dark" href="/projess/document?2d06a1562feb25f0e3c248bfb424b01e={{ $item->Witel }}&status_doc=221">{{ $item->review_kl }}</a></td>
        <td class="bg-success bg-opacity-10 border border-secondary text-center small"><a class="link-dark" href="/projess/document?2d06a1562feb25f0e3c248bfb424b01e={{ $item->Witel }}&status_doc=222">{{ $item->review_kl_mitra }}</a></td>
        <td class="bg-success bg-opacity-10 border border-secondary text-center small"><a class="link-dark" href="/projess/document?2d06a1562feb25f0e3c248bfb424b01e={{ $item->Witel }}&status_doc=223">{{ $item->verifikasi_dok }}</a></td>
        <td class="bg-success bg-opacity-10 border border-secondary text-center small"><a class="link-dark" href="/projess/document?2d06a1562feb25f0e3c248bfb424b01e={{ $item->Witel }}&status_doc=224">{{ $item->sirkulir_internal }}</a></td>
        <td class="bg-success bg-opacity-10 border border-secondary text-center small"><a class="link-dark" href="/projess/document?2d06a1562feb25f0e3c248bfb424b01e={{ $item->Witel }}&status_doc=225">{{ $item->sirkulir_mitra }}</a></td>
        <td class="bg-success bg-opacity-10 border border-secondary text-center small"><a class="link-dark" href="/projess/document?2d06a1562feb25f0e3c248bfb424b01e={{ $item->Witel }}&status_doc=226">{{ $item->closing_sm }}</a></td>
        <td class="bg-danger bg-opacity-10 border border-secondary text-center small"><a class="link-dark" href="/projess/document?2d06a1562feb25f0e3c248bfb424b01e={{ $item->Witel }}&status_doc=227">{{ $item->input_order }}</a></td>
        <td class="bg-success bg-opacity-10 border border-secondary text-center small"><a class="link-dark" href="/projess/document?2d06a1562feb25f0e3c248bfb424b01e={{ $item->Witel }}&status_doc=228">{{ $item->obl_done }}</a></td>
      </tr>
	@endforeach
    </tbody>
  </table>
</div>
