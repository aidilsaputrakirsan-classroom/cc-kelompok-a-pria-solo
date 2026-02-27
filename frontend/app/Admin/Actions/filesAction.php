<?php

namespace App\Admin\Actions;

use Illuminate\Database\Eloquent\Model;
use OpenAdmin\Admin\Actions\RowAction;
use Illuminate\Support\Facades\File;

class filesAction extends RowAction
{
    public $name = 'Files';

    public $icon = 'icon-file';

    public function handle(Model $model)
    {
        // $model ...

        // return $this->response()->success('Success message.')->refresh();
    }

	public function href()
	{
		$dirPath=storage_path('admin/docs/'.$this->getKey());

		//check if the directory exists
		if(!File::isDirectory($dirPath)){
			//make the directory because it doesn't exists
			File::makeDirectory($dirPath);
		}

		return env('APP_URL') . config('admin.route.prefix') . '/media?path=%2Fdocs%2F' . $this->getKey() . '&fn=selectFile';
	}
}