<?php
use think\facade\Route;

// 管理端API路由
Route::group('admin/api', function () {

    // 登录
    Route::post('login', 'admin.Login/login');
    Route::post('logout', 'admin.Login/logout');

    // 视频管理
    Route::get('video/list', 'admin.Video/list');
    Route::post('video/save', 'admin.Video/save');
    Route::post('video/delete', 'admin.Video/delete');
    Route::get('video/categories', 'admin.Video/categories');
    Route::post('video/saveCategory', 'admin.Video/saveCategory');
    Route::post('video/deleteCategory', 'admin.Video/deleteCategory');

    // 资源站点
    Route::get('video/sourceSites', 'admin.Video/sourceSites');
    Route::post('video/saveSourceSite', 'admin.Video/saveSourceSite');
    Route::post('video/collect', 'admin.Video/collect');

    // 用户管理
    Route::get('user/list', 'admin.User/list');
    Route::get('user/detail', 'admin.User/detail');
    Route::post('user/updateVip', 'admin.User/updateVip');

    // 卡密管理
    Route::get('user/cardList', 'admin.User/cardList');
    Route::post('user/generateCard', 'admin.User/generateCard');
    Route::post('user/deleteCard', 'admin.User/deleteCard');

    // 仪表盘统计
    Route::get('dashboard/stats', 'admin.Dashboard/stats');

})->allowCrossDomain();
