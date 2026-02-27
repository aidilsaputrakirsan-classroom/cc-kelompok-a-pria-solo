<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use \App\Admin\Controllers\WorkflowController;

class updateStatusDoc extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'projess:updateStatusDoc';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update masal status Document';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
		$wf = new WorkflowController();
		
		$sql = "
SELECT 
	xx.id_rso, AVG(xx.stat_doc) AS avg_skor
FROM
(
SELECT
	a.id_rso, a.id_obl,
	(case when a.status_doc = '170' then 1 ELSE 0 END) AS stat_doc
FROM
	0_document a
) xx	
GROUP BY 
	xx.id_rso
		";
		
		$list = DB::select(DB::raw($sql));
		
		foreach($list as $doc) {
			if ($doc->avg_skor == 1) {
				$result = $wf->updateProjectOblDone($doc->id_rso);
				echo $result . PHP_EOL;
			}
		}
		
        return 0;
    }
}
