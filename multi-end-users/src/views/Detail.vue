<template>
  <div class="page">
    <!-- 左侧导航（大屏幕 >= 500px） -->
    <van-sidebar
      v-model="activeSidebar"
      class="sidebar-nav"
      @change="onSidebarChange"
    >
      <van-sidebar-item title="首页" />
      <van-sidebar-item title="搜索" />
      <van-sidebar-item title="排行榜" />
      <van-sidebar-item title="影视详情" />
      <van-sidebar-item title="我的" />
    </van-sidebar>

    <!-- 右侧内容区域 -->
    <div class="content-wrapper">
      <van-nav-bar :title="detail.title" left-arrow @click-left="goBack" />

      <div v-if="loading" class="loading-wrapper">
        <van-loading>加载中...</van-loading>
      </div>
      <div v-else-if="detail.id" class="content-scroll">
        <!-- 视频播放器 -->
        <div class="player-wrapper">
          <div id="xgplayer-container" class="xgplayer-container"></div>
          <!-- 暂停广告 -->
          <div
            v-if="showPauseAd && !isPlaying && !showEndAd"
            class="ad-overlay"
            @click="clickAd"
          >
            <div class="ad-image-wrapper">
              <img :src="adConfig.image" alt="广告" class="ad-image" />
              <div class="ad-tip">广告</div>
            </div>
            <van-icon name="cross" class="ad-close" @click.stop="closeAd" />
          </div>
          <!-- 结束广告 -->
          <div
            v-if="showEndAd && !isPlaying"
            class="ad-overlay"
            @click="clickEndAd"
          >
            <div class="ad-image-wrapper">
              <img :src="endAdConfig.image" alt="结束广告" class="ad-image" />
              <div class="ad-tip">结束广告</div>
            </div>
            <van-icon name="cross" class="ad-close" @click.stop="closeEndAd" />
          </div>
        </div>

        <!-- 视频信息和选集 -->
        <div class="content">
          <!-- 标题 -->
          <div class="title-section">
            <div class="title-row">
              <h2>
                {{ detail.title }}
                <span v-if="currentSource">- {{ currentSource.name }}</span>
              </h2>
            </div>
            <!-- 操作按钮 -->
            <div class="action-buttons">
              <!-- 播放源选择 -->
              <div
                class="action-btn"
                v-if="sourceSites.length > 0"
                @click="showSourcePicker = true"
              >
                <van-icon name="apps-o" size="24" />
                <span>换源</span>
              </div>
              <!-- 收藏 -->
              <div class="action-btn" @click="toggleFav">
                <van-icon
                  :name="isFavorited ? 'star' : 'star-o'"
                  size="24"
                  :color="isFavorited ? '#ff976a' : '#666'"
                />
                <span>{{ isFavorited ? "已收藏" : "收藏" }}</span>
              </div>
              <!-- 分享 -->
              <div class="action-btn" @click="handleShare">
                <van-icon name="share-o" size="24" />
                <span>分享</span>
              </div>
            </div>
          </div>

          <!-- 选集列表 -->
          <div class="episode-section" v-if="episodes.length">
            <div class="section-title">选集</div>
            <div class="episode-list">
              <div
                v-for="ep in episodes"
                :key="ep.id"
                class="episode-item"
                :class="{ active: ep.id === currentSource.id }"
                @click="selectSource(ep)"
              >
                {{ ep.name }}
              </div>
            </div>
          </div>

          <!-- 视频详情信息 -->
          <div class="video-info">
            <div class="sub-info">
              <span>{{ detail.release_year }}</span>
              <span>{{ detail.region }}</span>
              <span>{{ detail.type_name }}</span>
              <span v-if="detail.is_vip" class="vip">VIP</span>
            </div>
            <div class="meta">
              <span>播放 {{ formatCount(detail.play_count) }}</span>
              <span>评分 {{ detail.rating }}</span>
            </div>
            <div class="tags">
              <van-tag
                type="primary"
                size="mini"
                v-for="t in (detail.tags || '').split(',')"
                :key="t"
                >{{ t }}</van-tag
              >
            </div>
            <div class="description">
              {{ detail.description || "暂无简介" }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 底部导航栏（小屏幕 < 500px） -->
    <van-tabbar v-model="activeTab" class="bottom-tabbar" @change="onTabChange">
      <van-tabbar-item icon="wap-home">首页</van-tabbar-item>
      <van-tabbar-item icon="chart-trending-o">排行榜</van-tabbar-item>
      <van-tabbar-item icon="user-o">我的</van-tabbar-item>
    </van-tabbar>

    <!-- 快捷登录弹窗 -->
    <QuickLogin ref="quickLoginRef" @success="onLoginSuccess" />

    <!-- 播放源选择弹窗 -->
    <van-popup
      v-model:show="showSourcePicker"
      position="center"
      round
      closeable
    >
      <div class="source-picker">
        <div class="picker-header">
          <span>选择播放源</span>
        </div>
        <div class="source-list">
          <div
            v-for="site in sourceSites"
            :key="site.id"
            class="source-item"
            :class="{ active: site.id === currentSourceSite?.id }"
            @click="selectSourceSite(site)"
          >
            <div class="source-name">{{ site.name }}</div>
            <div class="source-info">{{ site.episode_count }}集</div>
            <van-icon
              v-if="site.id === currentSourceSite?.id"
              name="success"
              size="18"
              color="#1989fa"
            />
          </div>
        </div>
      </div>
    </van-popup>

    <!-- 选集选择弹窗 -->
    <van-popup
      v-model:show="showEpisodePicker"
      position="center"
      round
      closeable
    >
      <div class="source-picker">
        <div class="picker-header">
          <span>选择剧集</span>
        </div>
        <div class="source-list episode-grid">
          <div
            v-for="ep in episodes"
            :key="ep.id"
            class="source-item"
            :class="{ active: ep.id === currentSource?.id }"
            @click="selectSource(ep)"
          >
            <div class="source-name">{{ ep.name }}</div>
            <van-icon
              v-if="ep.id === currentSource?.id"
              name="success"
              size="18"
              color="#1989fa"
            />
          </div>
        </div>
      </div>
    </van-popup>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick, computed } from "vue";
import { useRouter, useRoute } from "vue-router";
import { get, post } from "@/utils/request";
import { useUserStore } from "@/stores/user";
import { useHistoryStore } from "@/stores/history";
import { useSafeBack } from "@/utils/router";
import Hls from "hls.js";
import QuickLogin from "@/components/QuickLogin.vue";
import Player from "xgplayer";
import "xgplayer/dist/index.min.css";

const router = useRouter();
const route = useRoute();
const userStore = useUserStore();
const historyStore = useHistoryStore();
const { safeBack } = useSafeBack();
const quickLoginRef = ref(null);
const activeSidebar = ref(3);
const activeTab = ref(0);

const playerRef = ref(null);
const detail = ref({});
const sourceSites = ref([]);
const currentSourceSite = ref(null);
const episodes = ref([]);
const currentSource = ref(null);
const isFavorited = ref(false);
const loading = ref(true);
const showSourcePicker = ref(false);
const showEpisodePicker = ref(false);
const showPauseAd = ref(false);
const showEndAd = ref(false);
const isPlaying = ref(false);
const isFullscreen = ref(false);
const isPip = ref(false);
const danmakuEnabled = ref(true);
const skipIntroEnabled = ref(true);
const autoPlayNextEnabled = ref(true);
const playbackRate = ref(1);

// 片头片尾时间设置（单位：秒）
const introStart = ref(0);
const introEnd = ref(90); // 默认跳过前90秒的片头
const outroStart = ref(0); // 片尾开始时间，会根据视频时长动态计算
const outroDuration = ref(60); // 默认跳过最后60秒的片尾

// 倍速选项
const speedOptions = [0.5, 0.75, 1, 1.25, 1.5, 2];

// 暂停广告 Mock 配置
const adConfig = {
  image: "https://picsum.photos/seed/pause_ad/600/300",
  link: "https://www.example.com/pause",
};

// 结束广告 Mock 配置（视频播放结束后显示）
const endAdConfig = {
  image: "https://picsum.photos/seed/end_ad/600/300",
  link: "https://www.example.com/end",
};

let historyTimer = null;
let skipTimer = null;
let isAutoPlayingNext = false; // 防抖标志，防止自动播放下一集重复触发
let lastEndedTime = 0; // 上一次触发ended的时间，用于防抖
let isNearEnd = false; // 是否接近视频结尾（最后5秒内）- 通过timeupdate事件检测
let playerInitAttempts = 0; // 播放器初始化尝试次数，防止无限重试
const MAX_PLAYER_INIT_ATTEMPTS = 3; // 最大尝试次数
let lastCheckedSourceUrl = ""; // 上一次检查的视频源URL，避免重复检查同一个URL
let sourceCheckFailed = false; // 标记当前视频源检查是否失败

// 检查视频源是否可访问
// 使用 video 元素测试视频源是否可访问，避免 CORS 问题
const checkVideoSource = (url) => {
  return new Promise((resolve) => {
    // 创建一个临时 video 元素来测试
    const video = document.createElement('video');
    video.src = url;
    video.muted = true;  // 静音避免自动播放策略阻止
    
    const timeout = setTimeout(() => {
      video.remove();
      resolve(false);
    }, 5000);  // 5秒超时
    
    // 视频可以加载
    video.onloadeddata = () => {
      clearTimeout(timeout);
      video.remove();
      resolve(true);
    };
    
    // 视频加载失败
    video.onerror = () => {
      clearTimeout(timeout);
      video.remove();
      resolve(false);
    };
    
    // 尝试加载视频
    video.load();
  });
};
// 下一集插件 - 挂载到 CONTROLS_LEFT
class NextEpisodePlugin extends Player.Plugin {
  static get pluginName() {
    return "NextEpisodePlugin";
  }

  static get defaultConfig() {
    return {
      position: "CONTROLS_LEFT",
    };
  }

  afterCreate() {
    this.isSwitching = false;
    // 使用 setTimeout 确保 DOM 已经渲染
    setTimeout(() => {
      this.render();
    }, 500);
  }

  playNext() {
    if (this.isSwitching) return;

    const episodesVal = episodes.value;
    const currentSourceVal = currentSource.value;

    if (!currentSourceVal || episodesVal.length === 0) return;

    const idx = episodesVal.findIndex((e) => e.id === currentSourceVal.id);
    if (idx < episodesVal.length - 1) {
      this.isSwitching = true;
      selectSource(episodesVal[idx + 1]);
    }
  }

  onSourceSwitched() {
    setTimeout(() => {
      this.isSwitching = false;
    }, 300);
  }

  render() {
    // 先尝试使用 this.root（xgplayer 3.x 的标准方式）
    let container = this.root;

    // 如果 this.root 不存在，尝试手动查找控件容器
    if (!container) {
      container =
        this.player.root?.querySelector(".xgplayer-controls-left") ||
        this.player.root?.querySelector(".xg-left-grid") ||
        this.player.root?.querySelector(".xgplayer-controls");
    }

    if (!container) {
      console.log("[NextEpisodePlugin] container not found, retrying...");
      setTimeout(() => this.render(), 200);
      return;
    }

    // 检查按钮是否已经存在
    if (container.querySelector(".xgplayer-next-episode")) {
      console.log("[NextEpisodePlugin] button already exists");
      return;
    }

    const btn = document.createElement("button");
    btn.className = "xgplayer-next-episode";
    btn.innerHTML =
      '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 15 12 5 21 5 3"></polygon><line x1="19" y1="5" x2="19" y2="19"></line></svg>';
    btn.onclick = () => this.playNext();
    // btn.style.display = "flex";
    // btn.style.alignItems = "center";
    // btn.style.justifyContent = "center";
    // btn.style.width = "32px";
    // btn.style.height = "32px";
    // btn.style.marginRight = "8px";
    // btn.style.padding = "4px";
    // btn.style.background = "rgba(255, 255, 255, 0.2)";
    // btn.style.border = "none";
    // btn.style.borderRadius = "4px";
    // btn.style.cursor = "pointer";
    // btn.style.color = "#fff";
    // btn.style.transition = "background 0.3s";

    container.appendChild(btn);
    console.log("[NextEpisodePlugin] button added successfully");
  }
}

// 旋转插件 - 挂载到 CONTROLS_RIGHT
class RotatePlugin extends Player.Plugin {
  static get pluginName() {
    return "RotatePlugin";
  }

  static get defaultConfig() {
    return {
      position: "CONTROLS_RIGHT",
    };
  }

  afterCreate() {
    this.rotation = 0;
    this.render();
  }

  rotate() {
    this.rotation = (this.rotation + 90) % 360;
    const video = this.player.video;
    if (video) {
      video.style.transform = `rotate(${this.rotation}deg)`;
    }
  }

  render() {
    if (!this.root) {
      setTimeout(() => this.render(), 100);
      return;
    }

    const btn = document.createElement("button");
    btn.className = "xgplayer-rotate";
    btn.innerHTML =
      '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"></polyline><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path></svg>';
    btn.onclick = () => this.rotate();

    this.root.appendChild(btn);
  }
}

// 设置面板插件 - 挂载到 CONTROLS_RIGHT
class SettingsPlugin extends Player.Plugin {
  static get pluginName() {
    return "SettingsPlugin";
  }

  static get defaultConfig() {
    return {
      position: "CONTROLS_RIGHT",
    };
  }

  afterCreate() {
    this.panelVisible = false;
    this.render();
  }

  render() {
    if (!this.root) {
      setTimeout(() => this.render(), 100);
      return;
    }

    const btn = document.createElement("button");
    btn.className = "xgplayer-settings-btn";
    btn.innerHTML =
      '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>';
    btn.onclick = (e) => {
      e.stopPropagation();
      this.togglePanel();
    };

    this.root.appendChild(btn);
  }

  togglePanel() {
    this.panelVisible = !this.panelVisible;

    if (this.panelVisible) {
      this.createPanel();
    } else {
      this.destroyPanel();
    }
  }

  createPanel() {
    if (!this.root) return;

    let panel = this.player.root.querySelector(".xgplayer-settings-panel");
    if (panel) {
      panel.style.display = "block";
      return;
    }

    panel = document.createElement("div");
    panel.className = "xgplayer-settings-panel";

    panel.innerHTML = `
      <div class="settings-item">
        <span class="settings-label">自动跳过片头片尾</span>
        <label class="settings-switch">
          <input type="checkbox" ${skipIntroEnabled.value ? "checked" : ""} />
          <span class="settings-slider"></span>
        </label>
      </div>
      <div class="settings-item">
        <span class="settings-label">自动播放下一集</span>
        <label class="settings-switch">
          <input type="checkbox" ${autoPlayNextEnabled.value ? "checked" : ""} />
          <span class="settings-slider"></span>
        </label>
      </div>
      <div class="settings-item">
        <span class="settings-label">倍速</span>
        <div class="settings-speed">
          <select class="speed-select">
            ${speedOptions
              .map(
                (speed) =>
                  `<option value="${speed}" ${playbackRate.value === speed ? "selected" : ""}>${speed}x</option>`,
              )
              .join("")}
          </select>
        </div>
      </div>
    `;

    this.root.appendChild(panel);

    // 绑定事件
    const skipSwitch = panel.querySelector(
      ".settings-item:nth-child(1) .settings-switch input",
    );
    const autoNextSwitch = panel.querySelector(
      ".settings-item:nth-child(2) .settings-switch input",
    );
    const speedSelect = panel.querySelector(".speed-select");

    skipSwitch.addEventListener("change", (e) => {
      skipIntroEnabled.value = e.target.checked;
      this.notifyChange();
    });

    autoNextSwitch.addEventListener("change", (e) => {
      autoPlayNextEnabled.value = e.target.checked;
      this.notifyChange();
    });

    speedSelect.addEventListener("change", (e) => {
      playbackRate.value = parseFloat(e.target.value);
      this.player.playbackRate = playbackRate.value;
      this.notifyChange();
    });

    // 点击其他地方关闭面板
    setTimeout(() => {
      document.addEventListener("click", this.handleOutsideClick);
    }, 0);
  }

  destroyPanel() {
    const panel = this.player.root.querySelector(".xgplayer-settings-panel");
    if (panel) {
      panel.style.display = "none";
    }
    document.removeEventListener("click", this.handleOutsideClick);
  }

  handleOutsideClick = (e) => {
    const panel = this.player.root.querySelector(".xgplayer-settings-panel");
    const btn = this.player.root.querySelector(".xgplayer-settings-btn");
    if (panel && btn && !panel.contains(e.target) && !btn.contains(e.target)) {
      this.togglePanel();
    }
  };

  notifyChange() {
    if (this.player.skipIntroPlugin) {
      this.player.skipIntroPlugin.setEnabled(skipIntroEnabled.value);
    }
  }

  destroy() {
    this.destroyPanel();
    super.destroy();
  }
}

// 跳过片头片尾插件
class SkipIntroPlugin extends Player.Plugin {
  static get pluginName() {
    return "SkipIntroPlugin";
  }

  afterCreate() {
    this.enabled = skipIntroEnabled.value;
    this.introStart = introStart.value;
    this.introEnd = introEnd.value;
    this.outroDuration = outroDuration.value;
    this.outroStart = 0;
    this.skipTimer = null;
    this.bindEvents();
  }

  bindEvents() {
    this.player.on("timeupdate", () => {
      this.onTimeUpdate();
    });

    this.player.on("loadedmetadata", () => {
      this.calculateOutro();
    });
  }

  calculateOutro() {
    const duration = this.player.duration;
    if (duration > 0) {
      this.outroStart = duration - this.outroDuration;
    }
  }

  onTimeUpdate() {
    if (!this.enabled) return
    
    // 只有在视频正在播放时才自动跳过，暂停时不跳过
    if (this.player.paused) return
    
    const currentTime = this.player.currentTime
    const duration = this.player.duration
    
    if (duration > 0 && this.outroStart === 0) {
      this.outroStart = duration - this.outroDuration
    }
    
    // 自动跳过片头 - 但要确保 introEnd 不超过视频时长
    if (currentTime > this.introStart && currentTime < this.introEnd) {
      // 确保 introEnd 不超过视频时长
      const targetTime = Math.min(this.introEnd, duration - 1)
      if (currentTime < targetTime) {
        if (this.skipTimer) clearTimeout(this.skipTimer)
        this.skipTimer = setTimeout(() => {
          // 再次检查是否正在播放
          if (!this.player.paused) {
            this.player.currentTime = targetTime
          }
        }, 500)
      }
    }
    
    // 注意：不再自动跳过片尾，因为这会与自动播放下一集功能冲突
    // 用户可以通过设置面板关闭自动跳过片头片尾功能
  }

  setEnabled(enabled) {
    this.enabled = enabled;
  }

  isEnabled() {
    return this.enabled;
  }

  destroy() {
    if (this.skipTimer) clearTimeout(this.skipTimer);
    super.destroy();
  }
}

// 左侧导航切换
const onSidebarChange = (index) => {
  if (index === 0) router.push("/");
  else if (index === 1) router.push("/search");
  else if (index === 2) router.push("/rank");
  else if (index === 3) return;
  else if (index === 4) router.push("/user");
};

// 底部导航切换
const onTabChange = (index) => {
  if (index === 0) router.push("/");
  else if (index === 1) router.push("/rank");
  else if (index === 2) router.push("/user");
};

// 是否有下一集
const hasNextEpisode = computed(() => {
  if (!currentSource.value || episodes.value.length === 0) return false;
  const idx = episodes.value.findIndex((e) => e.id === currentSource.value.id);
  return idx < episodes.value.length - 1;
});

const loadDetail = async () => {
  loading.value = true;
  try {
    const params = { id: route.params.id };
    if (route.query.episode_id) {
      params.episode_id = route.query.episode_id;
      params.id = null;
    }
    const res = await get("/video/detail", params);
    detail.value = res.data || {};
    sourceSites.value = res.data?.source_sites || [];
    currentSourceSite.value = res.data?.current_source_site || null;
    episodes.value = res.data?.episodes || [];
    isFavorited.value = res.data?.is_favorited || false;

    if (episodes.value.length > 0) {
      let targetSource = episodes.value[0];
      if (res.data?.current_episode_id) {
        targetSource =
          episodes.value.find((ep) => ep.id === res.data.current_episode_id) ||
          targetSource;
      }
      selectSource(targetSource);
    }
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
};

const switchSourceSite = async (site) => {
  if (site.id === currentSourceSite.value?.id) return;

  currentSourceSite.value = site;
  currentSource.value = null;

  try {
    const res = await get("/video/detail", {
      id: detail.value.id,
      source_site_id: site.id,
    });
    episodes.value = res.data?.episodes || [];
    if (episodes.value.length > 0) {
      selectSource(episodes.value[0]);
    }
  } catch (e) {
    console.error("切换资源站失败", e);
  }
};

const selectSourceSite = (site) => {
  showSourcePicker.value = false;
  switchSourceSite(site);
};

const handleShare = async () => {
  const shareText = `${detail.value.title}\n${window.location.href}`;

  try {
    await navigator.clipboard.writeText(shareText);
    const { showDialog } = await import("vant");
    showDialog({
      title: "分享提示",
      message: "分享内容已复制，快去发送给好友吧！",
      confirmButtonText: "知道了",
      confirmButtonColor: "#1989fa",
    });
  } catch (e) {
    console.error("复制失败", e);
    const { showToast } = await import("vant");
    showToast("复制失败，请手动复制");
  }
};

const selectSource = (source) => {
  // 重置所有状态标志，防止切换视频源时的误触发
  isNearEnd = false;
  isAutoPlayingNext = false;
  playerInitAttempts = 0;
  lastCheckedSourceUrl = ""; // 重置已检查的URL
  sourceCheckFailed = false; // 重置失败标志
  showEndAd.value = false; // 切换视频时隐藏结束广告

  if (historyTimer) {
    clearTimeout(historyTimer);
    historyTimer = null;
  }

  currentSource.value = source;

  if (detail.value.id && source) {
    historyTimer = setTimeout(() => {
      addHistoryRecord(source);
    }, 2000);
  }

  outroStart.value = 0;

  // 使用播放器更新视频源方式切换，而不是销毁重建
  const player = playerRef.value;
  if (player && source?.play_url) {
    // 先暂停当前播放
    player.pause();

    // 等待一小段时间确保播放器状态稳定后再切换
    setTimeout(() => {
      if (playerRef.value && source?.play_url) {
        // 使用 src 方式更新视频源
        playerRef.value.src = source.play_url;
        playerRef.value.play();

        // 重置下一集插件的状态
        if (playerRef.value.nextEpisodePlugin) {
          playerRef.value.nextEpisodePlugin.onSourceSwitched();
        }
      }
    }, 100);
  } else {
    initPlayer(source);
  }
};

const initPlayer = (source) => {
  if (!source?.play_url) {
    console.log("[Player] initPlayer: no play_url");
    return;
  }

  // 检查尝试次数，防止无限重试
  if (playerInitAttempts >= MAX_PLAYER_INIT_ATTEMPTS) {
    console.log("[Player] initPlayer: max attempts reached, stopping");
    return;
  }
  playerInitAttempts++;
  console.log("[Player] initPlayer: attempt", playerInitAttempts);

  nextTick(() => {
    const container = document.getElementById("xgplayer-container");
    if (!container) {
      console.log("[Player] initPlayer: container not found");
      return;
    }

    // 如果已经有播放器，先销毁
    if (playerRef.value) {
      console.log("[Player] initPlayer: destroying existing player");
      destroyPlayer();
    }

    const isM3U8 = source.play_url.includes(".m3u8");

    const config = {
      id: "xgplayer-container",
      url: source.play_url,
        mini: true, //开启小窗插件
      poster: detail.value.cover,
      autoplay: true,
      controls: true,
      width: "100%",
      height: "100%",
      lang: "zh-cn",
      playsinline: true,
      pictureInPicture: true,
      pipMode: ["mini", "float", "fullscreen"],
      screenFull: true,
      playbackRate: playbackRate.value,
      videoInit: {
        hls: isM3U8
          ? {
              enableWorker: true,
              lowLatencyMode: false,
            }
          : undefined,
      },
      danmaku: {
        enable: danmakuEnabled.value,
        fontSize: 24,
        opacity: 1,
        speed: 1,
        showBottom: true,
        showTop: true,
      },
      plugins: [
        NextEpisodePlugin,
        SettingsPlugin,
        SkipIntroPlugin,
        RotatePlugin,
      ],
      nextEpisodePlugin: {
        episodes,
        currentSource,
        onEpisodeChange: selectSource,
      },
      settingsPlugin: {
        skipIntroEnabled: skipIntroEnabled.value,
        autoPlayNextEnabled: autoPlayNextEnabled.value,
        playbackRate: playbackRate.value,
        speedOptions,
        onSettingsChange: onSettingsChange,
      },
      skipIntroPlugin: {
        enabled: skipIntroEnabled.value,
        introStart: introStart.value,
        introEnd: introEnd.value,
        outroDuration: outroDuration.value,
      },
      //  playnext: {
      //   urlList: ['./a.mp4']
      // }
    };

    console.log(
      "[Player] initPlayer: creating new player with url:",
      source.play_url,
    );

    const player = new Player(config);
    playerRef.value = player;
    player.emit("resourceReady", [
      {
        name: "超清",
        definition: "1080p",
        url: "http://ltjd.yiyuntech.net:9088/hls/40/index.m3u8",
      },
      {
        name: "高清",
        definition: "720p",
        url: "//sf1-cdn-tos.huoshanstatic.com/obj/media-fe/xgplayer_doc_video/mp4/xgplayer-demo-480p.mp4",
      },
      {
        name: "标清",
        definition: "480p",
        url: "//sf1-cdn-tos.huoshanstatic.com/obj/media-fe/xgplayer_doc_video/mp4/xgplayer-demo-360p.mp4",
      },
    ]);
    if(config.playnext){      
    player.on(Events.PLAYNEXT, () => {
      // TODO
      console.log('DO-PLAY-NEXT')
    })
    }

    // 视频加载成功后重置尝试次数，并尝试播放
    player.on("loadedmetadata", () => {
      console.log("[Player] loadedmetadata: video loaded successfully");
      playerInitAttempts = 0;
      // 手动调用 play 确保自动播放生效
      player.play().catch(err => {
        console.log("[Player] play() failed:", err);
      });
    });

    // 视频加载失败时的处理
    player.on("error", (err) => {
      console.log("[Player] error:", err);
      // 不自动重试，让用户手动选择
    });

    player.on("pause", onPause);
    player.on("play", onPlay);
    player.on("play", () => {
      // 新视频开始播放时，重置防抖标志
      if (isAutoPlayingNext) {
        isAutoPlayingNext = false;
      }
      // 重置尝试次数
      playerInitAttempts = 0;
    });
    player.on("fullscreenchange", onFullscreenChange);
    player.on("pipChange", onPipChange);

    // 监听视频播放结束事件，自动播放下一集
    player.on("ended", async () => {
      // 如果正在自动播放下一集，跳过
      if (isAutoPlayingNext) {
        console.log(
          "[AutoPlayNext] ended event ignored, isAutoPlayingNext is true",
        );
        return;
      }

      // 如果之前检查过视频源且失败，不再重复检查
      if (sourceCheckFailed) {
        console.log(
          "[AutoPlayNext] ended event ignored, sourceCheckFailed is true",
        );
        return;
      }

      // 如果不是接近结尾触发的ended，忽略（通过timeupdate事件检测）
      // 注意：移除了 isPlaying.value 的检查，因为首次加载时 isPlaying 可能还未设置为 true
      if (!isNearEnd) {
        console.log(
          "[AutoPlayNext] ended event ignored, isNearEnd is false",
        );
        return;
      }

      // 防抖：如果1秒内已经触发过，忽略
      const now = Date.now();
      if (now - lastEndedTime < 1000) {
        console.log(
          "[AutoPlayNext] ended event ignored, debounce check failed",
        );
        return;
      }
      lastEndedTime = now;

      // 重置接近结尾标志
      isNearEnd = false;

      console.log("[AutoPlayNext] ended event accepted");

      const episodesVal = episodes.value;
      const currentSourceVal = currentSource.value;

      if (!currentSourceVal || episodesVal.length === 0) {
        console.log("[AutoPlayNext] no episodes or current source, skip");
        return;
      }

      const idx = episodesVal.findIndex((e) => e.id === currentSourceVal.id);
      if (idx >= episodesVal.length - 1) {
        console.log("[AutoPlayNext] no next episode");
        return;
      }

      // 显示结束广告
      console.log("[AutoPlayNext] showing end ad");
      showEndAd.value = true;
      showPauseAd.value = false;

      if (!autoPlayNextEnabled.value) {
        console.log("[AutoPlayNext] autoPlayNext is disabled");
        return;
      }

      const nextEpisode = episodesVal[idx + 1];
      if (!nextEpisode?.play_url) {
        console.log("[AutoPlayNext] next episode has no play_url");
        return;
      }

      // 如果已经检查过这个URL，不再重复检查
      if (lastCheckedSourceUrl === nextEpisode.play_url) {
        console.log("[AutoPlayNext] already checked this URL, skip");
        return;
      }

      // 立即设置防抖标志，防止重复触发检查
      isAutoPlayingNext = true;
      lastCheckedSourceUrl = nextEpisode.play_url;

      // 检查下一集视频源是否可访问
      console.log(
        "[AutoPlayNext] checking video source:",
        nextEpisode.play_url,
      );
      const isSourceAvailable = await checkVideoSource(nextEpisode.play_url);

      if (!isSourceAvailable) {
        console.log(
          "[AutoPlayNext] video source is not available, marking as failed",
        );
        sourceCheckFailed = true;
        isAutoPlayingNext = false; // 重置标志，允许用户手动操作
        return;
      }

      console.log(
        "[AutoPlayNext] video source is available, playing next episode",
      );

      // 重置失败标志
      sourceCheckFailed = false;

      console.log("[AutoPlayNext] switching to episode", idx + 2);
      selectSource(nextEpisode);
      showEndAd.value = false;
    });

    // 监听时间更新，检测是否接近视频结尾
    player.on("timeupdate", () => {
      isNearEnd = false;
      const video = player.video;
      if (!video) return false;

      const currentTime = video.currentTime;
      const duration = video.duration;
      console.log("currentTime", currentTime, "duration", duration);

      // 如果视频时长有效且当前时间在最后5秒内
      if (duration > 0 && currentTime >= duration - 2) {
        isNearEnd = true;
      } else {
        isNearEnd = false;
      }
    });
  });
};

const onSettingsChange = (settings) => {
  skipIntroEnabled.value = settings.skipIntroEnabled;
  autoPlayNextEnabled.value = settings.autoPlayNextEnabled;
  playbackRate.value = settings.playbackRate;

  if (playerRef.value && playerRef.value.skipIntroPlugin) {
    playerRef.value.skipIntroPlugin.setEnabled(settings.skipIntroEnabled);
  }
};

const destroyPlayer = () => {
  if (playerRef.value) {
    // 先移除 video 元素的事件监听器，防止销毁后事件触发
    const video = playerRef.value.video;
    if (video) {
      const clone = video.cloneNode(false);
      video.parentNode?.replaceChild(clone, video);
    }

    // 清空容器内容
    const container = document.getElementById("xgplayer-container");
    if (container) {
      container.innerHTML = "";
    }

    playerRef.value.destroy();
    playerRef.value = null;
  }
};

const addHistoryRecord = (source) => {
  if (!source || !detail.value.id) return;

  let episodeName = source.name || "";
  if (!episodeName && source.id) {
    const found = episodes.value.find((e) => e.id === source.id);
    if (found) {
      episodeName = found.name || "";
    }
  }
  if (!episodeName) {
    episodeName = source.sort_order ? `第${source.sort_order}集` : "";
  }

  historyStore.addHistory({
    video_id: detail.value.id,
    episode_id: source.id,
    episode_name: episodeName,
    title: detail.value.title,
    cover_url: detail.value.cover,
    last_position: 0,
    progress: 0,
  });
};

const toggleFav = async () => {
  if (!userStore.isLogin) {
    quickLoginRef.value?.open();
    return;
  }
  try {
    const res = await post(
      isFavorited.value ? "/favorite/remove" : "/favorite/add",
      {
        video_id: detail.value.id,
      },
    );
    isFavorited.value = !isFavorited.value;
    const { showToast } = await import("vant");
    showToast(isFavorited.value ? "收藏成功" : "已取消收藏");
  } catch (e) {
    console.error("收藏操作失败", e);
  }
};

const onLoginSuccess = async () => {
  try {
    const res = await get("/favorite/check", { video_id: route.params.id });
    isFavorited.value = res.data?.is_favorited || false;
  } catch (e) {
    console.error("检查收藏状态失败", e);
  }
};

const onPlay = () => {
  isPlaying.value = true;
  showPauseAd.value = false;
  showEndAd.value = false;
};

const onPause = () => {
  console.log("onPause");
  isPlaying.value = false;
  // 暂停时显示广告（仅在不是结束广告时）
  if (!showEndAd.value) {
    showPauseAd.value = true;
  }
};

const closeAd = () => {
  showPauseAd.value = false;
};

const clickAd = () => {
  window.open(adConfig.link, "_blank");
};

const closeEndAd = () => {
  showEndAd.value = false;
};

const clickEndAd = () => {
  window.open(endAdConfig.link, "_blank");
};

const onFullscreenChange = (e) => {
  isFullscreen.value = e.fullscreen;
};

const onPipChange = (e) => {
  console.log("onPipChange");
  isPip.value = e.pip;
};

const toggleFullscreen = () => {
  const player = playerRef.value;
  if (player) {
    if (isFullscreen.value) {
      player.exitFullscreen();
    } else {
      player.requestFullscreen();
    }
  }
};

const togglePip = () => {
  const player = playerRef.value;
  if (player) {
    if (isPip.value) {
      player.exitPictureInPicture();
    } else {
      player.requestPictureInPicture();
    }
  }
};

const toggleDanmaku = () => {
  danmakuEnabled.value = !danmakuEnabled.value;

  const player = playerRef.value;
  if (player && player.danmaku) {
    if (danmakuEnabled.value) {
      player.danmaku.show();
    } else {
      player.danmaku.hide();
    }
  }
};

const formatCount = (count) => {
  if (count >= 10000) return (count / 10000).toFixed(1) + "万";
  return count;
};

const goBack = () => safeBack("/");

onMounted(() => loadDetail());

onBeforeUnmount(() => {
  if (historyTimer) clearTimeout(historyTimer);
  if (skipTimer) clearTimeout(skipTimer);

  destroyPlayer();
});
</script>

<style lang="scss" scoped>
.page {
  display: flex;
  min-height: 100vh;
  overflow-x: hidden;
  width: 100%;

  @media (min-width: 500px) {
    gap: 8px;
  }
}

.content-wrapper {
  flex: 1;
  min-height: 100vh;
  background: #f5f5f5;
  overflow-x: hidden;
}

.sidebar-nav {
  display: none;
  width: 100px;
  background: white;
  box-shadow: 2px 0 8px rgba(0, 0, 0, 0.1);
  flex-shrink: 0;

  @media (min-width: 500px) {
    display: block;
  }

  :deep(.van-sidebar-item) {
    height: 46px;
    line-height: 46px;
    padding: 0 12px;
    font-size: 14px;

    &.van-sidebar-item--select {
      color: #1989fa;
      font-weight: 500;
    }
  }
}

.bottom-tabbar {
  display: none;

  @media (max-width: 499px) {
    display: flex;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 999;
  }
}

.content-scroll {
  padding: 12px;
}

.loading-wrapper {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 60vh;
  padding-top: 20px;
}

.player-wrapper {
  position: relative;
  background: #000;
  width: 100%;
  aspect-ratio: 16/9;

  .xgplayer-container {
    width: 100%;
    height: 100%;

    :deep(video) {
      transition: transform 0.3s ease;
      transform-origin: center center;
    }
  }
}

.content {
  :deep(.title-section) {
    background: white;
    padding: 16px;
    border-radius: 8px;
    margin-top: 12px;

    .title-row {
      h2 {
        font-size: 18px;
        font-weight: 600;
        margin: 0 0 12px 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }
    }

    .action-buttons {
      display: flex;
      gap: 24px;
      justify-content: space-around;

      .action-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
        cursor: pointer;
        flex: 1;

        span {
          font-size: 13px;
          color: #666;
        }
      }
    }
  }

  :deep(.episode-section) {
    margin-top: 12px;
    background: white;
    padding: 16px;
    border-radius: 8px;

    .section-title {
      font-size: 16px;
      font-weight: 600;
      margin-bottom: 12px;
    }

    .episode-list {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 8px;

      .episode-item {
        padding: 8px;
        background: #f5f5f5;
        border-radius: 6px;
        text-align: center;
        font-size: 13px;
        color: #333;
        cursor: pointer;

        &.active {
          background: #1989fa;
          color: white;
        }
      }
    }
  }

  :deep(.source-site-section) {
    display: none;
  }

  :deep(.video-info) {
    margin-top: 12px;
    background: white;
    padding: 16px;
    border-radius: 8px;

    .sub-info {
      display: flex;
      gap: 12px;
      font-size: 13px;
      color: #666;
      margin-bottom: 8px;

      .vip {
        color: #ff6a00;
        font-weight: 500;
      }
    }

    .meta {
      font-size: 13px;
      color: #999;
      margin-bottom: 12px;

      span {
        margin-right: 12px;
      }
    }

    .tags {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      margin-bottom: 12px;
    }

    .description {
      font-size: 14px;
      color: #666;
      line-height: 1.8;
    }
  }
}

.source-picker {
  width: 33vw;
  min-width: 280px;
  max-width: 360px;
  max-height: 33vh;
  min-height: 200px;
  padding: 16px;

  .picker-header {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid #eee;

    span {
      font-size: 16px;
      font-weight: 600;
    }
  }

  .source-list {
    max-height: calc(33vh - 80px);
    overflow-y: auto;
  }

  .source-item {
    display: flex;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f5f5f5;
    cursor: pointer;

    &:last-child {
      border-bottom: none;
    }

    &.active {
      color: #1989fa;
    }

    .source-name {
      flex: 1;
      font-size: 15px;
    }

    .source-info {
      font-size: 13px;
      color: #999;
      margin-right: 8px;
    }
  }

  .episode-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;

    .source-item {
      padding: 8px;
      justify-content: center;
    }
  }
}

.ad-overlay {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 60%;
  max-width: 300px;
  background: transparent;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  z-index: 10;

  .ad-image-wrapper {
    position: relative;
    width: 100%;
    border-radius: 8px;
    overflow: hidden;

    .ad-image {
      width: 100%;
      display: block;
    }

    .ad-tip {
      position: absolute;
      bottom: 6px;
      right: 6px;
      font-size: 10px;
      color: rgba(255, 255, 255, 0.9);
      background: rgba(0, 0, 0, 0.6);
      padding: 2px 6px;
      border-radius: 4px;
    }
  }

  .ad-close {
    position: absolute;
    top: -8px;
    right: -8px;
    font-size: 20px;
    color: white;
    cursor: pointer;
    background: rgba(0, 0, 0, 0.7);
    border-radius: 50%;
    padding: 6px;
    line-height: 1;

    &:hover {
      background: rgba(0, 0, 0, 0.9);
    }
  }
}

// 下一集按钮样式
:deep(.xgplayer-controls-left) .xgplayer-next-episode,
:deep(.xgplayer-next-episode) {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  margin-right: 8px;
  padding: 4px;
  background: rgba(255, 255, 255, 0);
  border: none;
  border-radius: 4px;
  cursor: pointer;
  color: #fff;
  transition: background 0.3s;

  &:hover {
    background: rgba(255, 255, 255, 0.4);
  }

  svg {
    width: 18px;
    height: 18px;
  }
}

// 设置按钮样式
:deep(.xgplayer-controls-right) .xgplayer-settings-btn,
:deep(.xgplayer-settings-btn) {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  margin-left: 8px;
  padding: 4px;
  background: rgba(255, 255, 255, 0.2);
  border: none;
  border-radius: 4px;
  cursor: pointer;
  color: #fff;
  transition: background 0.3s;

  &:hover {
    background: rgba(255, 255, 255, 0.4);
  }

  svg {
    width: 18px;
    height: 18px;
  }
}

// 旋转按钮样式
:deep(.xgplayer-controls-right) .xgplayer-rotate,
:deep(.xgplayer-rotate) {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  margin-left: 8px;
  padding: 4px;
  background: rgba(255, 255, 255, 0.2);
  border: none;
  border-radius: 4px;
  cursor: pointer;
  color: #fff;
  transition: background 0.3s;

  &:hover {
    background: rgba(255, 255, 255, 0.4);
  }

  svg {
    width: 18px;
    height: 18px;
  }
}

// 设置面板样式
:deep(.xgplayer-settings-panel) {
  position: absolute;
  top: 100%;
  right: 0;
  margin-top: 8px;
  background: rgba(0, 0, 0, 0.9);
  border-radius: 8px;
  padding: 12px;
  min-width: 200px;
  z-index: 100;
  display: none;

  .settings-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 0;

    &:not(:last-child) {
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .settings-label {
      font-size: 14px;
      color: #fff;
    }
  }

  .settings-switch {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 24px;

    input {
      opacity: 0;
      width: 0;
      height: 0;
    }

    .settings-slider {
      position: absolute;
      cursor: pointer;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: #444;
      transition: 0.3s;
      border-radius: 24px;

      &:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: 0.3s;
        border-radius: 50%;
      }
    }

    input:checked + .settings-slider {
      background-color: #1989fa;
    }

    input:checked + .settings-slider:before {
      transform: translateX(20px);
    }
  }

  .settings-speed {
    .speed-select {
      background: rgba(255, 255, 255, 0.1);
      color: #fff;
      border: none;
      border-radius: 4px;
      padding: 4px 8px;
      font-size: 14px;
      cursor: pointer;
      outline: none;

      option {
        background: #333;
        color: #fff;
      }
    }
  }
}
</style>
