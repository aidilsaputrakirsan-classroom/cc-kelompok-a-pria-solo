<?php

namespace App\Admin\Actions;

use Illuminate\Database\Eloquent\Model;
use OpenAdmin\Admin\Actions\RowAction;

class diskusiAction extends RowAction
{
    public $name = 'Diskusi';

    public $icon = 'icon-comments';

    public function handle(Model $model)
    {
        // $model ...

        // return $this->response()->success('Success message.')->refresh();
    }

	public function href()
	{
		return env('APP_URL') . config('admin.route.prefix') . '/projects/' . $this->getKey() . '/diskusi';
	}
}