<?php

Route::fallback(function(){
    return \Falcomnl\LaravelApiController\Http\Controllers\Api\ApiController::responseRouteNotFound();
})->name('fallback');
