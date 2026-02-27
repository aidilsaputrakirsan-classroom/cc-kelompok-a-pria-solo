<?php

namespace App\Admin\Controllers;
use OpenAdmin\Admin\Admin;
use OpenAdmin\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use App\Services\PDFService;
use Illuminate\Support\Facades\Log;

class AiAdvanceReviewController extends Controller
{
    public function index(Content $content)
    {
        Admin::css(asset('css/doc-review-landing-page.css'));
        Admin::css(asset('css/file-upload.css'));
        Admin::js(asset('js/file-upload-handler.js'));
        Admin::js(asset('js/doc-review-landing-page.js'));

        return $content
            ->title('Document Review')
            ->description('Review your documents')
            ->body(view('advance-reviews.document-review-main-page', ['isOpenAdmin' => true]));
    }
}