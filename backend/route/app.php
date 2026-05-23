<?php
use think\facade\Route;

// API路由组
Route::group('api', function () {

    // 公开接口
    Route::group('v1', function () {
        // 认证
        Route::post('auth/register', 'v1.Auth/register');
        Route::post('auth/login', 'v1.Auth/login');
        Route::post('auth/refresh', 'v1.Auth/refresh');

        // 视频（公开）
        Route::get('video/home', 'v1.Video/home');
        Route::get('video/list', 'v1.Video/list');
        Route::get('video/detail', 'v1.Video/detail');
        Route::get('video/rank', 'v1.Video/rank');
        Route::get('video/search', 'v1.Video/search');
        Route::get('video/categories', 'v1.Video/categories');
        Route::get('video/playUrl', 'v1.Video/playUrl');
    });

    // 需要认证的接口
    Route::group('v1', function () {
        // 用户信息
        Route::get('user/info', 'v1.User/info');

        // 收藏
        Route::get('favorite/list', 'v1.Favorite/list');
        Route::post('favorite/add', 'v1.Favorite/add');
        Route::post('favorite/remove', 'v1.Favorite/remove');

        // 观看历史
        Route::get('history/list', 'v1.History/list');
        Route::post('history/add', 'v1.History/add');
        Route::post('history/clear', 'v1.History/clear');

        // VIP卡密
        Route::post('card/redeem', 'v1.Card/redeem');

        // 广告记录
        Route::post('ad/watch', 'v1.Ad/watch');
    })->middleware(\app\middleware\ApiAuth::class);

})->allowCrossDomain();
