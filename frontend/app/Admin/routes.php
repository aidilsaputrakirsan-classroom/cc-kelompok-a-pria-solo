<?php

use Illuminate\Routing\Router;

Admin::routes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
    'as'            => config('admin.route.prefix') . '.',
], function (Router $router) {

    $router->get('/', 'HomeController@index')->name('home');

	// Auto Drafting
	$router->get('draft-doc', 'AutoDraftingController@listDrafts')->name('rso.autodraft.list');
	$router->get('rso/{id}/autodraft', 'AutoDraftingController@formDrafting')->name('rso.autodraft');
	$router->get('rso/{id}/autodraft/init', 'AutoDraftingController@initDraft')->name('rso.autodraft.init');

	// 2. Action: Generate & Download
	$router->post('rso/autodraft/generate', 'AutoDraftingController@generateDocument')->name('rso.autodraft.generate');

	// 3. Auto Draft CRUD API
	$router->get('api/autodraft/list', 'AutoDraftingController@apiListDrafts')->name('api.autodraft.list');
	$router->get('api/autodraft/{id_rso}', 'AutoDraftingController@apiGetDraft')->name('api.autodraft.get');
	$router->post('api/autodraft/save', 'AutoDraftingController@apiSaveDraft')->name('api.autodraft.save');
	$router->delete('api/autodraft/{id_rso}', 'AutoDraftingController@apiDeleteDraft')->name('api.autodraft.delete');

	// 4. API Helpers (Dropdown & Alamat)
	$router->get('api/company-address', 'AutoDraftingController@getCompanyAddress')->name('api.company.address');

	// AI Projess - Document Review Features
	// Main Landing Page
	$router->get('validasi-dokumen', 'AiAdvanceReviewController@index')->name('ai.validasi-dokumen');
	$router->get('test-hello', 'AiAdvanceReviewController@index')->name('test.route'); // Keep for backward compatibility
	
	// History/Riwayat Review
	$router->get('riwayat-review', 'AiControllers\RiwayatController@index')->name('ai.riwayat.index');
	$router->delete('riwayat-review/{id}', 'AiControllers\RiwayatController@destroy')->name('ai.riwayat.destroy');
	$router->delete('riwayat-review/review/{id}', 'AiControllers\RiwayatController@destroyReview')->name('ai.riwayat.destroy-review');
	
	// Review Overview - Show all reviews for a ticket
	$router->get('tickets/{ticketNumber}/advance-reviews', 'AiControllers\AdvanceReviewOverviewController@showReviews')->name('tickets.advance-reviews');
	$router->get('api/tickets/{ticketNumber}/advance-reviews/data', 'AiControllers\AdvanceReviewOverviewController@getOverviewData')->name('api.tickets.advance-reviews.data');
	
	// Ground Truth Validation
	$router->get('validate-ground-truth/{ticket_number}', 'AiControllers\GroundTruthController@show')->name('ai.validate-ground-truth');
	$router->post('validate-ground-truth/{ticket_number}/save', 'AiControllers\GroundTruthController@save')->name('ai.validate-ground-truth.save');
	$router->post('validate-ground-truth/{ticket_number}/complete', 'AiControllers\GroundTruthController@complete')->name('ai.validate-ground-truth.complete');
	$router->get('pdf/ground-truth/{ticket_number}/{doc_type}/{filename}', 'AiControllers\GroundTruthController@servePDF')->name('ai.pdf.ground-truth');
	
	// Advance Review Result
	$router->get('advance-result/{ticketNumber}/{docType}', 'AiControllers\AdvanceResultController@show')->name('ai.advance-result.show');
	$router->get('api/advance-result/{ticketNumber}/{docType}/data', 'AiControllers\AdvanceResultController@getAdvanceResultData')->name('api.advance-result.data');
	$router->get('pdf/advance/{ticketNumber}/{docType}/{filename}', 'AiControllers\AdvanceResultController@servePDF')->name('ai.pdf.advance');
	
	// Basic Review Result
	$router->get('basic-result/{ticket}', 'AiControllers\BasicResultController@showResultDetail')->name('ai.basic-result.show');
	$router->get('api/basic-result/{ticket}/issues', 'AiControllers\BasicResultController@getTicketIssues')->name('api.basic-result.issues');
	$router->get('pdf/basic/{ticketNumber}/{docType}/{filename}', 'AiControllers\BasicResultController@servePDF')->name('ai.pdf.basic');
	
	// File Upload
	$router->post('api/advance-upload', 'AiControllers\AdvanceUploadController@upload')->name('api.advance-upload');
	
	// Review Submission
	$router->post('api/review/submit', 'AiControllers\ReviewSubmissionController@submit')->name('api.review.submit');
	// Note: getStatus route moved to public API routes (/api/review/status/{ticketNumber})
	
	// Ticket Notes
	$router->get('api/tickets/{ticketNumber}/notes', 'AiControllers\TicketNoteController@getNotes')->name('api.tickets.notes');
	$router->post('api/tickets/{ticketNumber}/notes', 'AiControllers\TicketNoteController@saveNotes')->name('api.tickets.notes.save');
	
	// API - Get Company Names (for dropdowns)
	$router->get('api/companies', 'AiControllers\API\ApiGatewayController@getAllCompanyNames')->name('api.companies');

	// Kalkulator AKI
	$router->get('investasi', 'InvestasiController@form')->name('investasi.form');
	$router->post('investasi', 'InvestasiController@save')->name('investasi.store');
	$router->get('investasi/kembali', function () {
		return redirect()->route('investasi.form')->withInput();
	})->name('investasi.kembali');
	
	// Projects Controller-Model routes
	$router->resource('projects', ProjectsController::class);
	$router->get('projects/{ID_RSO}/diskusi', 'ProjectsController@diskusi');
	$router->get('projects/noOBL', 'ProjectsController@noOBL');
	
	// Pre Sales Summary routes
	$router->get('pre-sales', 'PreSalesController@index')->name('pre-sales.index');
	$router->get('pre-sales/list', 'PreSalesController@list')->name('pre-sales.list');
	$router->get('pre-sales/detail/{id}', 'PreSalesController@detail')->name('pre-sales.detail');
	$router->post('pre-sales/process-task/{id}', 'PreSalesController@processTask')->name('pre-sales.process-task');
	$router->get('pre-sales/hasil-rapat/{id}', 'HasilRapatController@create')->name('pre-sales.hasil-rapat');
	$router->post('pre-sales/hasil-rapat/{id}/update', 'HasilRapatController@update')->name('pre-sales.hasil-rapat.update');
	
	// OBL Controller-Model routes
	// $router->resource('obl', obl::class);
	$router->get('obl/list', 'oblController@listObl');

	// Kickoff routes
	// $router->get('kickoff/projects/{ID_RSO}', 'KickoffController@form');
	$router->get('projects/{ID_RSO}/kickoff', 'KickoffController@form');
	$router->post('kickoff/projects/save', 'KickoffController@save');
	$router->get('projects/{ID_RSO}/kickoff/{id}', 'KickoffController@view');
	
	// Document OBL routes
	$router->resource('document', DocumentController::class);
	$router->get('projects/{ID_RSO}/document', 'DocumentController@input');
	$router->get('projects/{ID_RSO}/document/{id}', 'DocumentController@view');
	$router->delete('projects/{ID_RSO}/document/{id}', 'DocumentController@deleteDoc');
	$router->get('projects/{ID_RSO}/document/{id}/edit', 'DocumentController@editDoc');
	$router->get('projects/{ID_RSO}/document/{id}/diskusi', 'DocumentController@diskusi');
	$router->post('projects/documents/create', 'DocumentController@createDoc');
	$router->post('projects/documents/save', 'DocumentController@saveDoc');
	$router->get('drafting/document/{id}/{p}', 'DocumentController@drafting');
	
	// Projects Management Routes
	$router->resource('project-management', ProjectsMgmtController::class);
	
	// Projess Tasks Routes - Custom routes must be defined BEFORE resource route
	$router->get('projess-tasks/manage-order', 'ProjessTaskController@manageOrder')->name('projess-tasks.manage-order');
	$router->post('projess-tasks/update-order', 'ProjessTaskController@updateOrder')->name('projess-tasks.update-order');
	$router->get('projess-tasks/tree-view', 'ProjessTaskController@treeView')->name('projess-tasks.tree-view');
	$router->get('projess-tasks/{id}/proceed', 'ProjessTaskController@proceed')->name('projess-tasks.proceed');
	$router->get('projess-tasks/{id}/return', 'ProjessTaskController@returnTask')->name('projess-tasks.return');
	$router->resource('projess-tasks', ProjessTaskController::class);

	// Service Operation Routes
	$router->get('OwnChanel', 'OwnchanelController@grid');

	// Search Project-OBL
	$router->get('search', 'SearchController@search');
	$router->get('searchPost', 'SearchController@searchPost');
	
	// Data Controller Routes
	$router->get('data/update/projects', 'DataController@updateProjects');
	$router->get('data/update/obl', 'DataController@updateObl');
	$router->get('export/projects', 'DataController@exportProjects');
	
	// Diskusi routes
	$router->post('diskusi/save', 'DiskusiController@save');
	
	// Workflow routes
	$router->post('workflow/projects/followup', 'WorkflowController@followupProject');
	// $router->get('workflow/projects/{ID_RSO}/{statusp}/process', 'WorkflowController@processProject');
	// $router->get('workflow/projects/{ID_RSO}/{statusp}/return', 'WorkflowController@returnProject');
	// $router->get('workflow/projects/{ID_RSO}/{statusp}/drop', 'WorkflowController@dropProject');
	// $router->get('workflow/document/{id}/process', 'WorkflowController@processDoc');
	// $router->get('workflow/document/{id}/return', 'WorkflowController@returnDoc');
	$router->post('workflow/document/followup', 'WorkflowController@followupDoc');

	// Tes Google Sheet API
	$router->get('tesGsheet', 'DataController@getGoogleSheetValues');
	
	// Tes OBL Document 
	$router->resource('obl', oblController::class);

	// Coder AI Chat
	$router->get('coder', 'CoderController@index')->name('coder.index');
	$router->post('api/coder/chat', 'CoderController@chat')->name('api.coder.chat');
	$router->post('api/coder/upload-file', 'CoderController@uploadFile')->name('api.coder.upload-file');

	// Pairing Documents (Document Comparison Feature)
	$router->get('api/tickets/{ticketNumber}/pairing-documents/available', 'AiControllers\PairingDocumentsController@getAvailableDocuments')->name('pairing-documents.available');
	$router->get('tickets/{ticketNumber}/pairing-documents/compare', 'AiControllers\PairingDocumentsController@showComparison')->name('pairing-documents.compare');
	$router->get('pdf/pairing/{ticketNumber}/{documentId}', 'AiControllers\PairingDocumentsController@servePDF')->name('pairing-documents.serve-pdf');

});


Route::group([
    'namespace' => config('admin.route.namespace'),
	'prefix' => 'app'
], function (Router $router) {
	// List semua project SME
    $router->get('sme/projects', 'ApiController@index');
    
    // Detail project SME
    $router->get('sme/projects/{id}', 'ApiController@show');

	// Create project
	$router->post('create/project/{segmen?}', 'ApiController@store');
});

