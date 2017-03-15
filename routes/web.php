<?php


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// 기본 홈
Route::get('/index', ['as' => 'index', 'uses' => 'WelcomeController@index']);

Route::get('/', 'ThemeController@index');

// 로그인 후 리다이렉트 되는 페이지
Route::get('/home', 'HomeController@index');

// 인증이 필요한 라우트 그룹
Route::group(['middleware' => 'auth'], function() {
    Route::put('users/selected_update',
        ['as' => 'users.selectedUpdate', 'uses' => 'Admin\UsersController@selectedUpdate']);
    Route::resource('users', 'Admin\UsersController');

    Route::get('user/edit', ['as' => 'user.edit', 'uses' => 'User\UserController@edit']);
    Route::put('user/update', ['as' => 'user.update', 'uses' => 'User\UserController@update']);
    Route::get('user/password_confirm', ['as' => 'user.getPasswordConfirm', 'uses' => 'User\UserController@getPasswordConfirm']);
    Route::post('user/password_confirm', ['as' => 'user.postPasswordConfirm', 'uses' => 'User\UserController@postPasswordConfirm']);
});

// 인증에 관련한 라우트들
Auth::routes();
