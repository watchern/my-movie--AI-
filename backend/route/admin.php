<?php
use think\facade\Route;
use app\controller\admin\AdminLoginController;
use app\controller\admin\AdminDashboardController;
use app\controller\admin\AdminUserController;
use app\controller\admin\AdminVideoController;

// 管理端API路由
Route::group('admin/api', function () {

    // 登录
    Route::post('login', [AdminLoginController::class, 'login']);
    Route::post('logout', [AdminLoginController::class, 'logout']);

    // 视频管理
    Route::get('video/list', [AdminVideoController::class, 'list']);
    Route::post('video/save', [AdminVideoController::class, 'save']);
    Route::post('video/delete', [AdminVideoController::class, 'delete']);
    Route::get('video/categories', [AdminVideoController::class, 'categories']);
    Route::post('video/saveCategory', [AdminVideoController::class, 'saveCategory']);
    Route::post('video/deleteCategory', [AdminVideoController::class, 'deleteCategory']);

    // 资源站点
    Route::get('video/sourceSites', [AdminVideoController::class, 'sourceSites']);
    Route::post('video/saveSourceSite', [AdminVideoController::class, 'saveSourceSite']);
    Route::post('video/collect', [AdminVideoController::class, 'collect']);

    // 用户管理
    Route::get('user/list', [AdminUserController::class, 'list']);
    Route::get('user/detail', [AdminUserController::class, 'detail']);
    Route::post('user/updateVip', [AdminUserController::class, 'updateVip']);

    // 卡密管理
    Route::get('user/cardList', [AdminUserController::class, 'cardList']);
    Route::post('user/generateCard', [AdminUserController::class, 'generateCard']);
    Route::post('user/deleteCard', [AdminUserController::class, 'deleteCard']);

    // 仪表盘统计
    Route::get('dashboard/stats', [AdminDashboardController::class, 'stats']);

})->allowCrossDomain();
