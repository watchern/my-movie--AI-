<?php
use think\facade\Route;
use app\controller\admin\AdminController;
use app\controller\admin\LoginController;
use app\controller\admin\DashboardController;
use app\controller\admin\UserController;
use app\controller\admin\VideoController;
use app\controller\admin\ConfigController;
use app\controller\admin\BannerController;
use app\controller\admin\CollectSourceController;

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
    Route::get('video/collectProgress', [VideoController::class, 'collectProgress']);
    Route::post('video/collectProcessNext', [VideoController::class, 'collectProcessNext']);
    Route::post('video/collectReset', [VideoController::class, 'collectReset']);

    // 剧集管理
    Route::get('video/episodes', [VideoController::class, 'episodes']);
    Route::post('video/saveEpisode', [VideoController::class, 'saveEpisode']);
    Route::post('video/deleteEpisode', [VideoController::class, 'deleteEpisode']);

    // 用户管理
    Route::get('user/list', [UserController::class, 'list']);
    Route::get('user/detail', [UserController::class, 'detail']);
    Route::post('user/addUser', [UserController::class, 'addUser']);
    Route::post('user/updateVip', [UserController::class, 'updateVip']);
    Route::post('user/resetPassword', [UserController::class, 'resetPassword']);

    // 卡密管理
    Route::get('user/cardList', [UserController::class, 'cardList']);
    Route::post('user/generateCard', [UserController::class, 'generateCard']);
    Route::post('user/deleteCard', [UserController::class, 'deleteCard']);
    Route::post('user/disableCard', [UserController::class, 'disableCard']);

    // 登录日志
    Route::get('user/loginLogs', [UserController::class, 'loginLogs']);

    // 观看历史
    Route::get('user/watchHistory', [UserController::class, 'watchHistory']);

    // 收藏记录
    Route::get('user/favorites', [UserController::class, 'favorites']);

    // 系统配置
    Route::get('config/list', [ConfigController::class, 'list']);
    Route::post('config/save', [ConfigController::class, 'save']);
    Route::get('config/vipLogs', [ConfigController::class, 'vipLogs']);

    // 管理员管理
    Route::get('admin/list', [AdminController::class, 'list']);
    Route::post('admin/add', [AdminController::class, 'add']);
    Route::post('admin/update', [AdminController::class, 'update']);
    Route::post('admin/delete', [AdminController::class, 'delete']);
    Route::get('admin/logs', [AdminController::class, 'logs']);

    // 仪表盘统计
    Route::get('dashboard/stats', [DashboardController::class, 'stats']);

    // 轮播图管理
    Route::get('banner/list', [BannerController::class, 'list']);
    Route::post('banner/save', [BannerController::class, 'save']);
    Route::post('banner/delete', [BannerController::class, 'delete']);
    Route::post('banner/updateStatus', [BannerController::class, 'updateStatus']);
    Route::get('banner/up/:id', [BannerController::class, 'up']);
    Route::get('banner/down/:id', [BannerController::class, 'down']);
    Route::get('banner/videoOptions', [BannerController::class, 'getVideoOptions']);

    // 资源采集站点管理
    Route::get('collectSource/list', [CollectSourceController::class, 'list']);
    Route::post('collectSource/add', [CollectSourceController::class, 'add']);
    Route::post('collectSource/edit', [CollectSourceController::class, 'edit']);
    Route::post('collectSource/delete', [CollectSourceController::class, 'delete']);
    Route::post('collectSource/test', [CollectSourceController::class, 'test']);
    Route::post('collectSource/toggleStatus', [CollectSourceController::class, 'toggleStatus']);

})->allowCrossDomain();
