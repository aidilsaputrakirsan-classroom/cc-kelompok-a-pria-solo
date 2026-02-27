<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use \App\Admin\Controllers\WorkflowController;
use \App\Models\document;

class tesComm extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'projess:tes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tes Command';

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
		$list = document::where('id_rso', '=', '24SME10120')->get();
		
		foreach($list as $item) {
			$doc = document::findOrFail($item->id);
			echo $doc->id_obl . " - " . $doc->status_doc . PHP_EOL ;
		}
		
        return 0;
    }
}
