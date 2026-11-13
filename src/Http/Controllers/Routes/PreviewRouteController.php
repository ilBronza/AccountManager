<?php

namespace IlBronza\AccountManager\Http\Controllers\Routes;

use IlBronza\AccountManager\Models\Route;
use IlBronza\AccountManager\Routing\RoutePreview;
use Illuminate\Http\Request;

class PreviewRouteController
{
    public function __invoke(Request $request, RoutePreview $preview)
    {
        $id = $request->post('route');
        abort_unless(is_scalar($id) && ctype_digit((string) $id), 404);

        $model = config('accountmanager.models.route.class', Route::class);
        $record = $model::query()->where('is_registered', true)->findOrFail($id);
        $url = $preview->exampleUrl($record);

        if ($url === null) {
            return response()->view('accountmanager::routes.preview-unavailable', ['route' => $record], 422);
        }

        return redirect()->to($url, 303);
    }
}
