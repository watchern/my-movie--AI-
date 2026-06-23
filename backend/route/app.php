<?php
use think\facade\Route;
use app\controller\VideoController;
use app\controller\AuthController;
use app\controller\UserController;
use app\controller\FavoriteController;
use app\controller\HistoryController;
use app\controller\CardController;
use app\controller\AdController;

// 根路径
Route::get('/', function () {
    return json([
        'code' => 200,
        'msg' => '影视系统 API 服务已启动',
        'data' => [
            'version' => '1.0.0',
            'api_base' => '/api/v1',
            'endpoints' => [
                'pretty_url' => [
                    'GET /api/v1/video/home' => '首页数据',
                    'GET /api/v1/video/list' => '视频列表',
                    'GET /api/v1/video/detail' => '视频详情',
                    'GET /api/v1/video/rank' => '排行榜',
                    'GET /api/v1/video/search' => '搜索',
                    'GET /api/v1/video/categories' => '分类列表',
                    'POST /api/v1/auth/register' => '注册',
                    'POST /api/v1/auth/login' => '登录',
                ],
                'query_url' => [
                    'GET /index.php/api/v1/video/home' => '首页数据',
                    'GET /index.php/api/v1/video/list' => '视频列表',
                    'GET /index.php/api/v1/video/detail?id=1' => '视频详情',
                    'GET /index.php/api/v1/video/rank' => '排行榜',
                    'GET /index.php/api/v1/video/search?keyword=流浪地球' => '搜索',
                    'GET /index.php/api/v1/video/categories' => '分类列表',
                    'POST /index.php/api/v1/auth/register' => '注册',
                    'POST /index.php/api/v1/auth/login' => '登录',
                ]
            ]
        ]
    ]);
});

// API路由组
Route::group('api', function () {

    // 公开接口
    Route::group('v1', function () {
        // 认证
        Route::post('auth/register', [AuthController::class, 'register']);
        Route::post('auth/login', [AuthController::class, 'login']);
        Route::post('auth/refresh', [AuthController::class, 'refresh']);

        // 视频（公开）
        Route::get('video/home', [VideoController::class, 'home']);
        Route::get('video/list', [VideoController::class, 'list']);
        Route::get('video/detail', [VideoController::class, 'detail']);
        Route::get('video/rank', [VideoController::class, 'rank']);
        Route::get('video/search', [VideoController::class, 'search']);
        Route::get('video/categories', [VideoController::class, 'categories']);
        Route::get('video/playUrl', [VideoController::class, 'playUrl']);
        Route::get('video/play/:id', [VideoController::class, 'play']);

        // 异步触发视频采集
        Route::post('video/collect/trigger', [VideoController::class, 'collectTrigger']);
        Route::get('video/collect/status', [VideoController::class, 'collectStatus']);
    });

    // 需要认证的接口
    Route::group('v1', function () {
        // 用户信息
        Route::get('user/info', [UserController::class, 'info']);

        // 收藏
        Route::get('favorite/list', [FavoriteController::class, 'list']);
        Route::post('favorite/add', [FavoriteController::class, 'add']);
        Route::post('favorite/remove', [FavoriteController::class, 'remove']);

        // 观看历史
        Route::get('history/list', [HistoryController::class, 'list']);
        Route::post('history/add', [HistoryController::class, 'add']);
        Route::post('history/clear', [HistoryController::class, 'clear']);
        Route::post('history/sync', [HistoryController::class, 'syncBatch']);

        // VIP卡密
        Route::post('card/redeem', [CardController::class, 'redeem']);

         // 广告记录
         Route::post('ad/watch', [AdController::class, 'watch']);
         Route::get('ad/status', [AdController::class, 'status']);
     })->middleware(\app\middleware\ApiAuth::class);

 })->allowCrossDomain();

 // 广告配置（公开接口，无需认证）- 独立路由组
 Route::group('api/v1', function () {
     Route::get('ad/info', [AdController::class, 'info']);
 })->allowCrossDomain();

