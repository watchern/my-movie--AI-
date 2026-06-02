<?php
use think\facade\Route;
use app\controller\admin\LoginController;
use app\controller\admin\DashboardController;
use app\controller\admin\UserController;
use app\controller\admin\VideoController;
use app\controller\admin\ConfigController;

// 管理端API路由
Route::group('admin/api', function () {

    // 登录
    Route::post('login', [LoginController::class, 'login']);
    Route::post('logout', [LoginController::class, 'logout']);

    // 视频管理
    Route::get('video/list', [VideoController::class, 'list']);
    Route::post('video/save', [VideoController::class, 'save']);
    Route::post('video/delete', [VideoController::class, 'delete']);
    Route::post('video/updateStatus', [VideoController::class, 'updateStatus']);
    Route::get('video/categories', [VideoController::class, 'categories']);
    Route::post('video/saveCategory', [VideoController::class, 'saveCategory']);
    Route::post('video/deleteCategory', [VideoController::class, 'deleteCategory']);

    // 资源站点
    Route::get('video/sourceSites', [VideoController::class, 'sourceSites']);
    Route::post('video/saveSourceSite', [VideoController::class, 'saveSourceSite']);
    Route::post('video/collect', [VideoController::class, 'collect']);

    // 用户管理
    Route::get('user/list', [UserController::class, 'list']);
    Route::get('user/detail', [UserController::class, 'detail']);
    Route::post('user/addUser', [UserController::class, 'addUser']);
    Route::post('user/updateVip', [UserController::class, 'updateVip']);

    // 卡密管理
    Route::get('user/cardList', [UserController::class, 'cardList']);
    Route::post('user/generateCard', [UserController::class, 'generateCard']);
    Route::post('user/deleteCard', [UserController::class, 'deleteCard']);
    Route::post('user/disableCard', [UserController::class, 'disableCard']);

    // 登录日志
    Route::get('user/loginLogs', [UserController::class, 'loginLogs']);

    // 系统配置
    Route::get('config/list', [ConfigController::class, 'list']);
    Route::post('config/save', [ConfigController::class, 'save']);
    Route::get('config/vipLogs', [ConfigController::class, 'vipLogs']);

    // 仪表盘统计
    Route::get('dashboard/stats', [DashboardController::class, 'stats']);

})->allowCrossDomain();
