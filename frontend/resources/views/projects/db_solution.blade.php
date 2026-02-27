<div class="row">
  <table class="table table-bordered table-hover">
    <thead>
      <tr>
        <th class="table-white border border-secondary align-middle text-center h5" rowspan="2">WITEL</th>
        <th class="table-secondary border border-secondary text-center h5" colspan="6">Account Mgr / Support</th>
        <th class="table-warning border border-secondary text-center h5" colspan="6">Solution Engineer</th>
        <th class="table-primary border border-secondary text-center h5" colspan="3">Solution Regional</th>
        <th class="table-success border border-secondary text-center h5" colspan="3">Bidding Regional</th>
      </tr>
      <tr>
        <th class="table-secondary border border-secondary text-center small"><span class="badge bg-primary">3-</span> Req<br/>Need Revision</th>
        <th class="table-secondary border border-secondary text-center small"><span class="badge bg-primary">5+</span> Collecting<br/>Doc & P1</th>
        <th class="table-secondary border border-secondary text-center small"><span class="badge bg-primary">11</span> Draft<br/>KB</th>
        <th class="table-secondary border border-secondary text-center small"><span class="badge bg-primary">12</span> Sirkulir<br/>KB</th>
        <th class="table-secondary border border-secondary text-center small"><span class="badge bg-primary">13</span> Input<br/>Quote</th>
        <th class="table-secondary border border-secondary text-center small"><span class="badge bg-primary">14</span> Input<br/>Order</th>
        <th class="table-warning border border-secondary text-center small"><span class="badge bg-primary">1</span> Req<br/>for Proposal</th>
        <th class="table-warning border border-secondary text-center small"><span class="badge bg-primary">2</span> Req<br/>In Progress</th>
        <th class="table-warning border border-secondary text-center small"><span class="badge bg-primary">3+</span> RAB<br/>Created</th>
        <th class="table-warning border border-secondary text-center small"><span class="badge bg-primary">5-</span> RAB<br/>Revision</th>
        <th class="table-warning border border-secondary text-center small"><span class="badge bg-primary">7-</span> Post KickOff<br/>Need Revision</th>
        <th class="table-warning border border-secondary text-center small"><span class="badge bg-primary">10</span> RAB<br/>Final</th>
        <th class="table-primary border border-secondary text-center small"><span class="badge bg-primary">4</span> Review<br/>Proposal</th>
        <th class="table-primary border border-secondary text-center small"><span class="badge bg-primary">6</span> Wait<br/>for KickOff</th>
        <th class="table-primary border border-secondary text-center small"><span class="badge bg-primary">7+</span> Need OBL<br/>UnVerified</th>
        <th class="table-success border border-secondary text-center small"><span class="badge bg-primary">8</span> Verified<br/>(>> OBL)</th>
        <th class="table-success border border-secondary text-center small"><span class="badge bg-primary">9</span> InProgress<br/>OBL</th>
        <th class="table-success border border-secondary text-center small"><span class="badge bg-primary">15</span> OBL<br/>Done</th>
      </tr>
    </thead>
    <tbody>
	@foreach($data as $item)
      <tr>
        <td class="table-white border border-secondary small"><a class="link-dark" href="/projess/projects?Witel={{ $item->Witel }}">{{ $item->Witel }}</a></td>
        <td class="bg-secondary bg-opacity-10 border border-secondary text-center small"><a class="link-dark" href="/projess/projects?Witel={{ $item->Witel }}&status_project=20">{{ $item->qty_req_rev }}</a></td>
        <td class="bg-secondary bg-opacity-10 border border-secondary text-center small"><a class="link-dark" href="/projess/projects?Witel={{ $item->Witel }}&status_project=70">{{ $item->qty_coll_doc }}</a></td>
        <td class="bg-secondary bg-opacity-10 border border-secondary text-center small"><a class="link-dark" href="/projess/projects?Witel={{ $item->Witel }}&status_project=212">{{ $item->qty_draft_kb }}</a></td>
        <td class="bg-secondary bg-opacity-10 border border-secondary text-center small"><a class="link-dark" href="/projess/projects?Witel={{ $item->Witel }}&status_project=216">{{ $item->qty_sirkulir_kb }}</a></td>
        <td class="bg-secondary bg-opacity-10 border border-secondary text-center small"><a class="link-dark" href="/projess/projects?Witel={{ $item->Witel }}&status_project=218">{{ $item->qty_input_quote }}</a></td>
        <td class="bg-secondary bg-opacity-10 border border-secondary text-center small"><a class="link-dark" href="/projess/projects?Witel={{ $item->Witel }}&status_project=227">{{ $item->qty_input_order }}</a></td>
        <td class="bg-warning bg-opacity-10 bg-opacity-10 border border-secondary text-center small"><a class="link-dark" href="/projess/projects?Witel={{ $item->Witel }}&status_project=10">{{ $item->qty_req_prop }}</a></td>
        <td class="bg-warning bg-opacity-10 border border-secondary text-center small"><a class="link-dark" href="/projess/projects?Witel={{ $item->Witel }}&status_project=15">{{ $item->qty_req_progress }}</a></td>
        <td class="bg-warning bg-opacity-10 border border-secondary text-center small"><a class="link-dark" href="/projess/projects?Witel={{ $item->Witel }}&status_project=30">{{ $item->qty_rab_created }}</a></td>
        <td class="bg-warning bg-opacity-10 border border-secondary text-center small"><a class="link-dark" href="/projess/projects?Witel={{ $item->Witel }}&status_project=60">{{ $item->qty_rab_rev }}</a></td>
        <td class="bg-warning bg-opacity-10 border border-secondary text-center small"><a class="link-dark" href="/projess/projects?Witel={{ $item->Witel }}&status_project=100">{{ $item->qty_post_ko_rev }}</a></td>
        <td class="bg-warning bg-opacity-10 border border-secondary text-center small"><a class="link-dark" href="/projess/projects?Witel={{ $item->Witel }}&status_project=170">{{ $item->qty_finalisasi_sph }}</a></td>
        <td class="bg-primary bg-opacity-10 border border-secondary text-center small"><a class="link-dark" href="/projess/projects?Witel={{ $item->Witel }}&status_project=50">{{ $item->qty_rev_prop }}</a></td>
        <td class="bg-primary bg-opacity-10 border border-secondary text-center small"><a class="link-dark" href="/projess/projects?Witel={{ $item->Witel }}&status_project=90">{{ $item->qty_wait_ko }}</a></td>
        <td class="bg-primary bg-opacity-10 border border-secondary text-center small"><a class="link-dark" href="/projess/projects?Witel={{ $item->Witel }}&status_project=120&is_verified=0">{{ $item->qty_post_ko_obl }}</a></td>
        <td class="bg-success bg-opacity-10 border border-secondary text-center small"><a class="link-dark" href="/projess/projects?Witel={{ $item->Witel }}&status_project=120&is_verified=1">{{ $item->qty_post_ko_obl_verified }}</a></td>
        <td class="bg-success bg-opacity-10 border border-secondary text-center small">{{ $item->qty_obl_inprogress }}</td>
        <td class="bg-success bg-opacity-10 border border-secondary text-center small"><a class="link-dark" href="/projess/projects?Witel={{ $item->Witel }}&status_project=222">{{ $item->qty_obl_done }}</a></td>
      </tr>
	@endforeach
    </tbody>
  </table>
</div>
