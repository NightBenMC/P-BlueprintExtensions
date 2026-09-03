@php
    $settings = app()->make('Pterodactyl\Contracts\Repository\SettingsRepositoryInterface');
    $blueprint = app()->make('Pterodactyl\BlueprintFramework\Libraries\ExtensionLibrary\Client\BlueprintClientLibrary', ['settings' => $settings]);

    $animSpeed = $blueprint->dbGet('fileflow', 'anim_speed') ?? '0.04';
    $animStagger = $blueprint->dbGet('fileflow', 'anim_stagger') ?? '0.005';
    $rowGap = $blueprint->dbGet('fileflow', 'row_gap') ?? '6';
    $fileDepth = $blueprint->dbGet('fileflow', 'max_depth') ?? '10';
    $skipShortcut = $blueprint->dbGet('fileflow', 'skip_shortcut') ?? 'x';
    $customIcons = $blueprint->dbGet('fileflow', 'custom_icons');

    if (empty($customIcons)) $customIcons = '{}';
@endphp<style>
    :root {
        --ff-anim-speed: {{ $animSpeed }}s;
        --ff-stagger: {{ $animStagger }}s;
    }
    /* ── Animations ── */
    @keyframes ff-fadeIn {
        0% { opacity: 0; transform: translateY(-6px); }
        100% { opacity: 1; transform: translateY(0); }
    }
    .ff-fade-in {
        animation: ff-fadeIn var(--ff-anim-speed, 0.02s) ease-out both;
        transition: none !important;
    }
    .ff-file-row:not(.ff-fade-in) {
        opacity: 0 !important;
    }

    @keyframes ff-serverFade {
        0% { opacity: 0; transform: translateY(10px); }
        100% { opacity: 1; transform: translateY(0); }
    }
    body.ff-dash-page a[href*="/server/"]:not(.ff-done):not(.ff-animating) {
        opacity: 0 !important;
    }
    a[href*="/server/"].ff-animating {
        animation-name: ff-serverFade !important;
        animation-duration: 0.25s !important;
        animation-timing-function: cubic-bezier(0.4, 0, 0.2, 1) !important;
        animation-fill-mode: forwards !important;
    }
    a[href*="/server/"].ff-done {
        opacity: 1 !important;
        transform: none !important;
        animation: none !important;
    }

    @media (max-width: 768px) {
        .ff-ico {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            margin-right: 8px !important;
            width: 24px !important;
            height: 24px !important;
            flex-shrink: 0 !important;
        }
        .ff-ico svg, .ff-ico img {
            width: 20px !important;
            height: 20px !important;
            object-fit: contain !important;
        }
        .ff-txt-wrap {
            opacity: 1 !important;
            display: inline-flex !important;
            align-items: center !important;
        }
        .ff-txt { opacity: 1 !important; }
    }

    /* ── Sidebar (Refined Client UI) ── */
    @media (min-width: 769px) {
        body { transition: padding-left 0.25s ease-in-out !important; }
        body.ff-srv-view {
            padding-left: 90px !important;
        }
        body.ff-srv-view.ff-sidebar-expanded {
            padding-left: 215px !important;
        }

        body.ff-srv-view nav:not([class*="SubNavigation"]),
        body.ff-srv-view header,
        body.ff-srv-view [class*="Navigation__Container"],
        body.ff-srv-view [class*="TopNavigation"] {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            z-index: 4000 !important;
            box-sizing: border-box !important;
            background: #111827 !important;
            height: 60px !important;
            display: flex !important;
            align-items: center !important;
            border-bottom: 1px solid rgba(255,255,255,0.05) !important;
            padding-left: 90px !important;
        }
        body.ff-srv-view [class*="TopNavigation"] h1,
        body.ff-srv-view [class*="TopNavigation"] a,
        body.ff-srv-view [class*="Navigation__Container"] a,
        body.ff-srv-view [class*="TopNavigation"] div[class*="Branding"] {
            margin-left: 0 !important;
        }

        /* Sidebar Container */
        div[class*="SubNavigation"], nav[class*="SubNavigation"] {
            position: fixed !important;
            left: 0 !important;
            top: 0 !important;
            width: 75px !important;
            height: 100vh !important;
            z-index: 5000 !important;
            background: #0d1117 !important;
            border-right: 1px solid rgba(255,255,255,0.05) !important;
            border-top: none !important;
            border-left: none !important;
            border-bottom: none !important;
            border-radius: 0 !important;
            transition: width 0.25s ease-in-out, transform 0.3s ease-in-out !important;
            display: flex !important;
            flex-direction: column !important;
            padding: 20px 0 !important;
            box-sizing: border-box !important;
            overflow-x: hidden !important;
            overflow-y: auto !important;
            transform: translateX(calc(-100% - 30px));
        }
        div[class*="SubNavigation"]::-webkit-scrollbar, nav[class*="SubNavigation"]::-webkit-scrollbar { width: 4px; }
        div[class*="SubNavigation"]::-webkit-scrollbar-thumb, nav[class*="SubNavigation"]::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 2px; }

        div[class*="SubNavigation"]:hover, nav[class*="SubNavigation"]:hover {
            width: 200px !important;
            transform: translateX(0);
        }

        body.ff-srv-view div[class*="SubNavigation"],
        body.ff-srv-view nav[class*="SubNavigation"] {
            transform: translateX(0);
        }

        div[class*="SubNavigation"] > div, nav[class*="SubNavigation"] > div {
            display: flex !important;
            flex-direction: column !important;
            width: 100% !important;
            gap: 6px !important;
        }

        div[class*="SubNavigation"] a, nav[class*="SubNavigation"] a {
            display: flex !important;
            align-items: center !important;
            justify-content: flex-start !important;
            margin: 0 12px !important;
            width: 50px !important;
            min-height: 50px !important;
            border-radius: 14px !important;
            background: rgba(255,255,255,0.02) !important;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
            color: rgba(255,255,255,0.5) !important;
            text-decoration: none !important;
            flex-shrink: 0 !important;
            position: relative !important;
            overflow: hidden !important;
            border: 1px solid rgba(255,255,255,0.03) !important;
            padding: 0 !important;
        }
        div[class*="SubNavigation"]:hover a, nav[class*="SubNavigation"]:hover a {
            width: 176px !important;
        }
        div[class*="SubNavigation"] a:hover, nav[class*="SubNavigation"] a:hover {
            background: rgba(255,255,255,0.06) !important;
            color: #fff !important;
            border-color: rgba(255,255,255,0.1) !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2) !important;
        }
        div[class*="SubNavigation"] a.active,
        div[class*="SubNavigation"] a[aria-current="page"],
        nav[class*="SubNavigation"] a.active {
            background: rgba(59, 130, 246, 0.1) !important;
            color: #3b82f6 !important;
            border-color: rgba(59, 130, 246, 0.2) !important;
            box-shadow: inset 0 0 10px rgba(59, 130, 246, 0.1) !important;
        }

        /* Icon wrapper */
        .ff-ico {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            flex-shrink: 0 !important;
            width: 50px !important;
            height: 50px !important;
            min-width: 50px !important;
            transition: transform 0.2s;
        }
        div[class*="SubNavigation"] a:hover .ff-ico, nav[class*="SubNavigation"] a:hover .ff-ico {
            transform: scale(1.05);
        }

        /* Text label */
        .ff-txt-wrap {
            flex: 1; overflow: hidden; display: flex; align-items: center;
            opacity: 0; transition: opacity 0.2s ease 0.1s; padding-right: 15px;
        }
        div[class*="SubNavigation"]:hover .ff-txt-wrap, nav[class*="SubNavigation"]:hover .ff-txt-wrap {
            opacity: 1;
        }
        .ff-txt { font-size: 13px; font-weight: 600; white-space: nowrap; pointer-events: none; color: inherit; }

        @keyframes ff-marquee {
            0%, 20% { transform: translateX(0); }
            50%, 70% { transform: translateX(min(0px, calc(-100% + 110px))); }
            100% { transform: translateX(0); }
        }
        div[class*="SubNavigation"]:hover a:hover .ff-txt { animation: ff-marquee 4s linear infinite; }

    }

    /* ── Robust File Search UI ── */
    .ff-search-bar {
        display: flex; align-items: center; gap: 8px;
        padding: 6px 16px; margin-bottom: 8px;
        max-width: 1280px; width: 100%; box-sizing: border-box;
        margin-left: auto; margin-right: auto;
    }
    .ff-searching-active section > *:not(.ff-search-bar):not(.ff-search-results):not(.ff-search-status),
    .ff-searching-active [class*="FileManagerContainer"] > *:not(.ff-search-bar):not(.ff-search-results):not(.ff-search-status),
    .ff-searching-active [class*="ContentContainer"] > *:not(.ff-search-bar):not(.ff-search-results):not(.ff-search-status),
    .ff-searching-active #file-manager-container > *:not(.ff-search-bar):not(.ff-search-results):not(.ff-search-status) {
        display: none !important;
    }
    .ff-searching-active .ff-search-bar {
        display: flex !important;
    }
    .ff-searching-active .ff-search-results {
        display: block !important;
    }
    @media (max-width: 640px) {
        .ff-search-bar { max-width: 100%; padding: 6px 12px; }
    }
    .ff-search-input {
        flex: 1; min-width: 0; padding: 8px 12px; border-radius: 6px; width: 100%; box-sizing: border-box;
        border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.04);
        color: #e2e8f0; font-size: 13px; outline: none;
        transition: border-color 0.15s;
    }
    .ff-search-results {
        margin-bottom: 8px; padding: 0 16px; max-width: 1280px;
        margin-left: auto; margin-right: auto;
    }
    @media (max-width: 640px) {
        .ff-search-results { max-width: 100%; padding: 0 12px; }
    }
    .ff-search-result-row { background: rgba(255,255,255,0.03); border-radius: 4px; margin-bottom: 1px; transition: background 0.15s; }
    .ff-search-result-row:hover { background: rgba(255,255,255,0.08); }
    .ff-search-result-link { display: flex; align-items: center; gap: 10px; padding: 8px 16px; color: #cbd5e1; text-decoration: none; font-size: 13px; }
    .ff-search-result-link:hover { color: #f1f5f9; }
    .ff-search-result-icon { flex-shrink: 0; display: flex; align-items: center; }
    .ff-search-result-path { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ff-search-result-size { flex-shrink: 0; color: rgba(255,255,255,0.35); font-size: 12px; margin-left: 12px; }

    /* ── Quick Commands Modal ── */
    .ff-cmd-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; background: #3b82f6; color: #fff; border-radius: 8px; border: none; font-size: 13px; font-weight: 600; cursor: pointer; margin: 16px 0; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); transition: all 0.2s; }
    .ff-cmd-btn:hover { background: #2563eb; transform: translateY(-1px); }
    .ff-cmd-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 70000; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(8px); }
    .ff-cmd-modal { background: #0d1117; width: 1000px; max-width: 95vw; height: 600px; border-radius: 16px; border: 1px solid rgba(255,255,255,0.1); display: flex; overflow: hidden; }
    .ff-cmd-col { flex: 1; padding: 24px; display: flex; flex-direction: column; border-right: 1px solid rgba(255,255,255,0.05); }
    .ff-cmd-col:last-child { border-right: none; }
    .ff-cmd-col-title { font-size: 16px; font-weight: 700; color: #fff; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
    .ff-cmd-list { flex: 1; overflow-y: auto; }
    .ff-cmd-item { padding: 12px; background: rgba(255,255,255,0.03); border-radius: 8px; margin-bottom: 8px; cursor: pointer; display: flex; align-items: center; transition: background 0.2s; border: 1px solid transparent; }
    .ff-cmd-item:hover { background: rgba(255,255,255,0.06); }
    .ff-cmd-item.active { border-color: #3b82f6; background: rgba(59, 130, 246, 0.1); }
    .ff-cmd-name { font-weight: 600; color: #fff; font-size: 14px; }
    .ff-cmd-string { font-size: 11px; color: rgba(255,255,255,0.3); font-family: monospace; display: block; margin-top: 2px; }
    .ff-cmd-act-btn.del { margin-left: auto; color: #ef4444; background: none; border: none; cursor: pointer; opacity: 0.4; transition: opacity 0.2s; }
    .ff-cmd-act-btn.del:hover { opacity: 1; }
    .ff-form-group { margin-bottom: 15px; }
    .ff-form-label { display: block; font-size: 11px; color: rgba(255,255,255,0.4); text-transform: uppercase; margin-bottom: 6px; font-weight: 700; }
    .ff-form-input { width: 100%; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; padding: 10px 14px; color: #fff; font-size: 13px; outline: none; }
    .ff-form-input:focus { border-color: #3b82f6; }

    /* ── File Preview Modal ── */
    .ff-preview-overlay {
        position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.85); z-index: 50000;
        display: flex; align-items: center; justify-content: center; cursor: pointer; animation: ff-fadeIn 0.15s ease-out both;
    }
    .ff-preview-content {
        position: relative; max-width: 90vw; max-height: 85vh; display: flex; align-items: center; justify-content: center;
        cursor: default; background: rgba(0,0,0,0.4); padding: 20px; border-radius: 12px; backdrop-filter: blur(4px); border: 1px solid rgba(255,255,255,0.1);
    }
    .ff-preview-content img, .ff-preview-content video {
        max-width: 80vw; max-height: 70vh; border-radius: 4px; box-shadow: 0 8px 32px rgba(0,0,0,0.6); object-fit: contain; background: #000;
    }
    .ff-preview-close { position: absolute; top: -36px; right: 0; background: none; border: none; color: #fff; font-size: 28px; cursor: pointer; opacity: 0.8; transition: opacity 0.15s; padding: 4px 8px; line-height: 1; }
    .ff-preview-close:hover { opacity: 1; }
    .ff-preview-name { position: absolute; bottom: -32px; left: 0; right: 0; text-align: center; color: rgba(255,255,255,0.7); font-size: 13px; font-weight: 500; }
    .ff-preview-loading { color: rgba(255,255,255,0.6); font-size: 14px; padding: 40px; }

</style>


<script id="ff-icon-config" type="application/json">{!! $customIcons !!}</script>


<script>
(function() {
    if (window._ffLoaded) return;
    window._ffLoaded = true;
    window._ffBusy = false;
    var lastUrl = "";
    var updateTimer = null;
    var searchDebounceTimer = null;
    var queryId = 0;
    var _ffInSearchUpdate = false;

    console.log('[FileFlow] Extension Loaded v1.4.9');
    var pathIcons = {
        'console': '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 17 10 11 4 5"/><line x1="12" y1="19" x2="20" y2="19"/></svg>',
        'files': '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/></svg>',
        'databases': '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>',
        'schedules': '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
        'users': '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>',
        'backups': '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>',
        'network': '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="8" rx="2" ry="2"/><rect x="2" y="14" width="20" height="8" rx="2" ry="2"/><line x1="6" y1="6" x2="6.01" y2="6"/><line x1="6" y1="18" x2="6.01" y2="18"/></svg>',
        'startup': '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',
        'settings': '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>',
        'activity': '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>',
    };
        var fallbackIcon = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>';


    function getCsrfToken() { return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''; }
    function getServerUuid() {
        var parts = location.pathname.split('/');
        var idx = parts.indexOf('server');
        if (idx !== -1 && idx + 1 < parts.length) {
            return parts[idx + 1];
        }
        return '';
    }
    function isFilesPage() {
        var path = location.pathname;
        return path.includes('/server/') && path.includes('/files') && !path.includes('/files/edit') && !path.includes('/files/new');
    }
    function getCookie(n) {
        var m = document.cookie.match(new RegExp('(^|;)\\s*' + n + '\\s*=\\s*([^;]+)'));
        return m ? decodeURIComponent(m[2]) : '';
    }
    var previewExts = {
        image: ['png', 'jpg', 'jpeg', 'gif', 'bmp', 'webp', 'svg', 'ico'],
        video: ['mp4', 'webm', 'ogg', 'mov']
    };
function getFileExt(name) {
        var parts = name.split('.');
        return parts.length > 1 ? parts[parts.length - 1].toLowerCase() : '';
    }

function isPreviewable(ext) {
        return previewExts.image.indexOf(ext) !== -1 || previewExts.video.indexOf(ext) !== -1;
    }

function getCurrentDir() {
        var hash = location.hash;
        if (hash && hash.startsWith('#')) {
            var dir = decodeURIComponent(hash.substring(1));
            if (dir) return dir.startsWith('/') ? dir : '/' + dir;
        }
        var params = new URLSearchParams(location.search);
        var dirParam = params.get('directory');
        if (dirParam) return dirParam.startsWith('/') ? dirParam : '/' + dirParam;
        return '/';
    }

function showPreview(fileName, uuid, fullPath) {
        if (document.querySelector('.ff-preview-overlay')) return;
        var ext = getFileExt(fileName);
        var isVideo = previewExts.video.indexOf(ext) !== -1;
        var dir = getCurrentDir();
        var filePath = fullPath || (dir === '/' ? '/' + fileName : dir + '/' + fileName);

        var overlay = document.createElement('div');
        overlay.className = 'ff-preview-overlay';
        overlay.innerHTML = '<div class="ff-preview-content"><div class="ff-preview-loading">Loading preview...</div></div>';

        var closePreview = function() {
            overlay.remove();
            document.body.style.overflow = '';
        };

        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) closePreview();
        });
        document.body.appendChild(overlay);
        document.body.style.overflow = 'hidden';

        var content = overlay.querySelector('.ff-preview-content');

        fetch('/api/client/servers/' + uuid + '/files/download?file=' + encodeURIComponent(filePath), {
            headers: { 'Accept': 'application/json', 'X-XSRF-TOKEN': getCookie('XSRF-TOKEN') },
            credentials: 'same-origin'
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var url = data.attributes ? data.attributes.url : data.url;
            if (!url) { content.innerHTML = '<div class="ff-preview-loading">Failed to load preview.</div>'; return; }

            if (isVideo) {
                content.innerHTML = '<button class="ff-preview-close">&times;</button>' +
                    '<video src="' + url + '" controls autoplay></video>' +
                    '<div class="ff-preview-name">' + fileName + '</div>';
            } else {
                content.innerHTML = '<button class="ff-preview-close">&times;</button>' +
                    '<img src="' + url + '" alt="' + fileName + '" />' +
                    '<div class="ff-preview-name">' + fileName + '</div>';
            }
            content.querySelector('.ff-preview-close').addEventListener('click', closePreview);
        })
        .catch(function() {
            content.innerHTML = '<div class="ff-preview-loading">Failed to load preview.</div>';
        });

        var escHandler = function(e) {
            if (e.key === 'Escape') { closePreview(); document.removeEventListener('keydown', escHandler); }
        };
        document.addEventListener('keydown', escHandler);
    }

function showMediaPage(fileName, serverUuid, filePath) {
    var container = document.querySelector('section') ||
                    document.querySelector('div[class*="ContentContainer"]') ||
                    document.querySelector('div[class*="FileManagerContainer"]') ||
                    document.querySelector('[class*="FileManagerContainer"]');
    if (!container) return;

    // Remove any existing media page
    var existing = document.querySelector('.ff-media-page');
    if (existing) existing.remove();

    // Make sure container is relative so our absolute child aligns correctly
    container.style.position = 'relative';

    var mediaPage = document.createElement('div');
    mediaPage.className = 'ff-media-page';
    mediaPage.style.cssText = 'position:absolute; top:0; left:0; right:0; bottom:0; background:#0d1117; z-index:10000; display:flex; flex-direction:column; padding:24px; box-sizing:border-box; animation:ff-fadeIn 0.2s ease-out;';

    var ext = getFileExt(fileName);
    var isVideo = previewExts.video.indexOf(ext) !== -1;

    mediaPage.innerHTML = '<div class="ff-media-header" style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; padding-bottom:15px; border-bottom:1px solid rgba(255,255,255,0.05);">' +
        '<div style="display:flex; align-items:center; gap:15px;">' +
            '<button class="ff-media-back" style="display:inline-flex; align-items:center; gap:6px; color:#3b82f6; background:none; border:none; cursor:pointer; font-size:14px; font-weight:600; padding:0; outline:none;">' +
                '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>' +
                'Back to files' +
            '</button>' +
            '<span style="color:rgba(255,255,255,0.3);">|</span>' +
            '<span style="font-family:monospace; font-size:14px; color:#fff; font-weight:600;">' + escapeHtml(filePath) + '</span>' +
        '</div>' +
    '</div>' +
    '<div class="ff-media-body" style="flex:1; display:flex; justify-content:center; align-items:center; background:#0b0e14; border:1px solid rgba(255,255,255,0.05); border-radius:12px; padding:20px; box-sizing:border-box; overflow:hidden; position:relative;">' +
        '<div class="ff-preview-loading" style="color: rgba(255,255,255,0.6); font-size: 14px;">Loading preview...</div>' +
    '</div>';

    container.appendChild(mediaPage);

    var closeMediaPage = function() {
        mediaPage.remove();
    };

    mediaPage.querySelector('.ff-media-back').addEventListener('click', closeMediaPage);

    var xsrf = getCookie('XSRF-TOKEN');
    var csrf = getCsrfToken();
    var cleanPath = '/' + filePath.replace(/^\/+/, '');

    function loadMediaBlob() {
        return fetch('/api/client/servers/' + serverUuid + '/files/contents?file=' + encodeURIComponent(cleanPath), {
            headers: {
                'Accept': 'text/plain, */*',
                'X-XSRF-TOKEN': xsrf,
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
        .then(function(res) {
            if (!res.ok) throw new Error('Panel contents status ' + res.status);
            return res.blob();
        })
        .catch(function() {
            return fetch('/api/client/servers/' + serverUuid + '/files/download?file=' + encodeURIComponent(cleanPath), {
                headers: {
                    'Accept': 'application/json',
                    'X-XSRF-TOKEN': xsrf,
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var url = data.attributes ? data.attributes.url : data.url;
                if (!url) throw new Error('No URL returned');
                return fetch(url).then(function(r) {
                    if (!r.ok) throw new Error('Wings fetch status ' + r.status);
                    return r.blob();
                });
            });
        });
    }

    loadMediaBlob()
    .then(function(blob) {
        var mimeType = isVideo ? 'video/' + (ext === 'mov' ? 'mp4' : ext) : 'image/' + (ext === 'jpg' ? 'jpeg' : ext);
        var typedBlob = new Blob([blob], { type: mimeType });
        var mediaUrl = URL.createObjectURL(typedBlob);

        var header = mediaPage.querySelector('.ff-media-header');
        var btnWrap = document.createElement('div');
        btnWrap.innerHTML = '<a href="' + mediaUrl + '" download="' + escapeHtml(fileName) + '" class="ff-cmd-btn" style="margin:0; padding:8px 16px; background:rgba(255,255,255,0.05); color:#fff; border:1px solid rgba(255,255,255,0.1); border-radius:6px; font-size:13px; text-decoration:none; display:inline-flex; align-items:center; gap:8px;">' +
            '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>' +
            'Download' +
            '</a>';
        header.appendChild(btnWrap);

        var body = mediaPage.querySelector('.ff-media-body');
        body.innerHTML = '';

        if (isVideo) {
            body.innerHTML = '<video src="' + mediaUrl + '" controls autoplay preload="auto" style="max-width:100%; max-height:100%; border-radius:4px; box-shadow:0 8px 32px rgba(0,0,0,0.6); object-fit:contain; background:#000;"></video>';
        } else {
            body.innerHTML = '<img src="' + mediaUrl + '" alt="' + escapeHtml(fileName) + '" style="max-width:100%; max-height:100%; border-radius:4px; box-shadow:0 8px 32px rgba(0,0,0,0.6); object-fit:contain; background:#000;" />';
        }
    })
    .catch(function(err) {
        console.error('[FileFlow] Media load error:', err);
        mediaPage.querySelector('.ff-media-body').innerHTML = '<div class="ff-preview-loading" style="color: #ef4444;">Failed to load media preview.</div>';
    });
}

function setupFileSearch() {
        if (!isFilesPage()) return;

        var attempt = 0;
        function tryMount() {
            if (!isFilesPage()) return;

            var section = document.querySelector('section') ||
                          document.querySelector('div[class*="ContentContainer"]') ||
                          document.querySelector('div[class*="PageContent"]') ||
                          document.querySelector('div[class*="FileManagerContainer"]') ||
                          document.querySelector('[class*="FileManagerContainer"]') ||
                          document.querySelector('#file-manager-container') ||
                          document.querySelector('#app');
            if (!section) {
                if (attempt < 30) {
                    attempt++;
                    setTimeout(tryMount, 100);
                }
                return;
            }

            var searchBar = document.querySelector('.ff-search-bar');
            var resultsContainer = document.querySelector('.ff-search-results');
            if (searchBar && resultsContainer) {
                if (section.contains(searchBar)) {
                    return;
                }
                if (document.activeElement === searchBar.querySelector('.ff-search-input')) {
                    return; // Currently typing, don't interrupt
                }
                searchBar.remove();
                resultsContainer.remove();
            }

            var serverUuid = getServerUuid();
            if (!serverUuid) return;

            searchBar = document.createElement('div');
            searchBar.className = 'ff-search-bar';
            searchBar.style.cssText = 'margin-bottom: 6px; width: 100%;';
            searchBar.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.4)" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>' +
                '<input type="text" class="ff-search-input" placeholder="Search files (use * for wildcards)..." />';

            var statusBar = document.createElement('div');
            statusBar.className = 'ff-search-status';
            statusBar.style.cssText = 'margin-bottom: 12px; padding: 0 4px; font-size: 13px; color: rgba(255,255,255,0.6); font-weight: 500; text-align: center; width: 100%; min-height: 18px; display: none;';

            resultsContainer = document.createElement('div');
            resultsContainer.className = 'ff-search-results';
            resultsContainer.style.display = 'none';

            // Always prepend as the very first element inside the main container
            section.prepend(searchBar);
            searchBar.after(statusBar);
            statusBar.after(resultsContainer);

            var input = searchBar.querySelector('.ff-search-input');

            input.addEventListener('input', function() {
                if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
                searchDebounceTimer = setTimeout(function() {
                    executeSearch();
                }, 500);
            });

        function updateStatusText(text) {
            if (text && input.value.trim().length > 0) {
                statusBar.textContent = text;
                statusBar.style.display = 'block';
            } else {
                statusBar.textContent = '';
                statusBar.style.display = 'none';
            }
        }

        function executeSearch() {
            var query = input.value.trim();
            queryId++;
            var thisQueryId = queryId;

            if (window._ffStreamReader) {
                window._ffStreamReader.cancel();
                window._ffStreamReader = null;
            }

            if (query.length < 1) {
                updateStatusText('');
                resultsContainer.innerHTML = '';
                resultsContainer.style.display = 'none';
                resultsContainer.dataset.page = '1';
                document.body.classList.remove('ff-searching-active');
                return;
            }

            resultsContainer.dataset.page = '1';
            document.body.classList.add('ff-searching-active');

            var clientResults = scanCurrentDirectory(query);
            renderSearchResults(clientResults, resultsContainer);

            var url = '/api/client/extensions/fileflow/servers/' + serverUuid + '/search?q=' + encodeURIComponent(query) + '&depth={{ $fileDepth }}';
            var serverResults = [];
            var scannedCount = 0;

            // 1-second client-side ticker: always update the count display
            if (window._ffCountTimer) clearInterval(window._ffCountTimer);
            window._ffCountTimer = setInterval(function() {
                if (thisQueryId !== queryId) { clearInterval(window._ffCountTimer); return; }
                var merged = mergeClientServerResults(clientResults, serverResults);
                var totalScanned = scannedCount > 0 ? scannedCount : merged.length;
                updateStatusText(merged.length + ' files found from ' + totalScanned + ' (' + query + ')');
            }, 1000);

            fetch(url, {
                headers: {
                    'Accept': 'application/json, application/x-ndjson, */*',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).then(function(response) {
                if (!response.ok) {
                    console.error('[FileFlow] Search request failed:', response.status);
                    clearInterval(window._ffCountTimer);
                    updateStatusText('ERROR ' + response.status);
                    renderSearchResults(clientResults, resultsContainer);
                    return;
                }
                var reader = response.body.getReader();
                window._ffStreamReader = reader;
                var decoder = new TextDecoder();
                var buffer = '';

                function processChunk({done, value}) {
                    if (thisQueryId !== queryId) {
                        clearInterval(window._ffCountTimer);
                        return;
                    }
                    if (done) {
                        clearInterval(window._ffCountTimer);
                        var merged = mergeClientServerResults(clientResults, serverResults);
                        var totalScanned = scannedCount > 0 ? scannedCount : merged.length;
                        updateStatusText(merged.length + ' files found from ' + totalScanned + ' (' + query + ')');
                        renderSearchResults(merged, resultsContainer);
                        return;
                    }
                    buffer += decoder.decode(value, {stream: true});
                    var lines = buffer.split('\n');
                    buffer = lines.pop();

                    lines.forEach(function(line) {
                        if (!line.trim()) return;
                        try {
                            var data = JSON.parse(line);
                            if (data.match) {
                                serverResults.push(data.match);
                                scannedCount = data.scanned || scannedCount;
                                var merged = mergeClientServerResults(clientResults, serverResults);
                                updateStatusText(merged.length + ' files found from ' + scannedCount + ' (' + query + ')');
                                renderSearchResults(merged, resultsContainer);
                            } else if (data.progress || data.final) {
                                scannedCount = data.scanned || scannedCount;
                                var merged = mergeClientServerResults(clientResults, serverResults);
                                updateStatusText(merged.length + ' files found from ' + scannedCount + ' (' + query + ')');
                                if (data.final) {
                                    clearInterval(window._ffCountTimer);
                                    renderSearchResults(merged, resultsContainer);
                                }
                            } else if (data.error) {
                                console.warn('[FileFlow] Stream error:', data.error);
                            }
                        } catch(e) {}
                    });
                    return reader.read().then(processChunk);
                }
                return reader.read().then(processChunk);
            }).catch(function(err) {
                clearInterval(window._ffCountTimer);
                console.error('[FileFlow] Fetch failed:', err);
                updateStatusText('ERROR 500');
                renderSearchResults(clientResults, resultsContainer);
            });
        }
        }

        tryMount();
    }

function renderSearchResults(results, container) {
        _ffInSearchUpdate = true;
        container.innerHTML = '';
        if (results.length === 0) {
            container.innerHTML = '<div style="padding:12px;color:rgba(255,255,255,0.4);text-align:center;">No files found</div>';
            container.style.display = 'block';
            document.body.classList.add('ff-searching-active');
            _ffInSearchUpdate = false;
            return;
        }

        document.body.classList.add('ff-searching-active');
        container.style.display = 'block';

        var serverShort = getServerUuid();

        var perPage = 50;
        var currentPage = container.dataset.page ? parseInt(container.dataset.page) : 1;
        var start = (currentPage - 1) * perPage;
        var pageResults = results.slice(start, start + perPage);

        pageResults.forEach(function(file, i) {
            var row = document.createElement('div');
            row.className = 'ff-search-result-row ff-fade-in';
            row.style.animationDelay = (Math.min(i, 15) * 0.02) + 's';

            var icon = file.is_file
                ? fallbackIcon
                : pathIcons['files'];

            var href = '';

            if (file.is_file) {
                var ext = getFileExt(file.name);
                var isEditable = !!file.path.match(/\.(txt|yml|yaml|json|properties|conf|cfg|log|xml|html|css|js|php|java|py|sh|md|toml|ini|env|sk|bat|ps1|sql|htaccess|ptero)$/i);

                if (isEditable) {
                    var pathForEdit = file.path.startsWith('/') ? file.path : '/' + file.path;
                    href = '/server/' + serverShort + '/files/edit#' + pathForEdit.split('/').map(encodeURIComponent).join('/');
                } else {
                    var dirPath = file.path.substring(0, file.path.lastIndexOf('/')) || '/';
                    href = '/server/' + serverShort + '/files#' + dirPath.split('/').map(encodeURIComponent).join('/');
                }
            } else {
                href = '/server/' + serverShort + '/files#' + file.path.split('/').map(encodeURIComponent).join('/');
            }

            var sizeText = '';
            if (file.is_file && file.size !== undefined) {
                if (file.size < 1024) sizeText = file.size + ' B';
                else if (file.size < 1048576) sizeText = (file.size / 1024).toFixed(1) + ' KB';
                else sizeText = (file.size / 1048576).toFixed(1) + ' MB';
            }

            row.innerHTML = '<a href="' + href + '" class="ff-search-result-link">' +
                '<span class="ff-search-result-icon">' + icon + '</span>' +
                '<span class="ff-search-result-path">' + escapeHtml(file.path) + '</span>' +
                (sizeText ? '<span class="ff-search-result-size">' + sizeText + '</span>' : '') +
                '</a>';

            var link = row.querySelector('a');
            link.onclick = function(e) {
                if (file.is_file && isPreviewable(getFileExt(file.name))) {
                    e.preventDefault();
                    showMediaPage(file.name, serverShort, file.path);
                    return;
                }

                // Clear search UI state
                clearSearchState();

                // Handle directory or non-openable file parent directory navigation manually if it uses hash
                if (href.indexOf('#') !== -1) {
                    e.preventDefault();
                    window.location.href = href;
                    window.dispatchEvent(new HashChangeEvent('hashchange'));
                }
            };

            container.appendChild(row);
        });

        _ffInSearchUpdate = false;

        if (results.length > perPage) {
            var pagination = document.createElement('div');
            pagination.style.cssText = 'display:flex;justify-content:center;gap:8px;padding:12px;margin-top:8px;';
            var totalPages = Math.ceil(results.length / perPage);
            for (var p = 1; p <= totalPages; p++) {
                var btn = document.createElement('button');
                btn.textContent = p;
                btn.style.cssText = 'padding:4px 10px;border-radius:4px;border:1px solid rgba(255,255,255,0.1);background:' + (p === currentPage ? '#3b82f6' : 'rgba(255,255,255,0.05)') + ';color:#fff;cursor:pointer;';
                (function(page) {
                    btn.onclick = function() {
                        container.dataset.page = page;
                        renderSearchResults(results, container);
                        window.scrollTo(0, 0);
                    };
                })(p);
                pagination.appendChild(btn);
            }
            container.appendChild(pagination);
        }
    }

function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

function scanCurrentDirectory(query) {
        var results = [];
        var q = query.toLowerCase();
        var fileRows = document.querySelectorAll('form[method="GET"] input[name="json"]');
        if (fileRows.length > 0) {
            fileRows.forEach(function(input) {
                if (input.closest('.ff-search-results')) return;
                try {
                    var data = JSON.parse(input.value);
                    if (data.attributes && data.attributes.name) {
                        var name = data.attributes.name;
                        if (name.startsWith('.')) return;
                        if (name.toLowerCase().indexOf(q) !== -1) {
                            results.push({
                                name: name,
                                path: (getCurrentDir() === '/' ? '/' : getCurrentDir() + '/') + name,
                                is_file: !data.attributes.is_directory,
                                size: data.attributes.size
                            });
                        }
                    }
                } catch(e) {}
            });
        }
        return results;
    }

function mergeClientServerResults(clientResults, serverResults) {
        var paths = {};
        var merged = [];
        clientResults.forEach(function(f) { paths[f.path] = true; merged.push(f); });
        serverResults.forEach(function(f) { if (!paths[f.path]) { paths[f.path] = true; merged.push(f); } });
        return merged;
    }


    function clearSearchState() {
        if (window._ffStreamReader) {
            try { window._ffStreamReader.cancel(); } catch(err) {}
            window._ffStreamReader = null;
        }
        if (window._ffCountTimer) {
            clearInterval(window._ffCountTimer);
            window._ffCountTimer = null;
        }

        var searchBar = document.querySelector('.ff-search-bar');
        if (searchBar) {
            var input = searchBar.querySelector('.ff-search-input');
            var countSpan = searchBar.querySelector('.ff-search-count');
            if (input) input.value = '';
            if (countSpan) countSpan.textContent = '';
        }
        var statusBar = document.querySelector('.ff-search-status');
        if (statusBar) {
            statusBar.textContent = '';
            statusBar.style.display = 'none';
        }

        var resultsContainer = document.querySelector('.ff-search-results');
        if (resultsContainer) {
            resultsContainer.innerHTML = '';
            resultsContainer.style.display = 'none';
        }

        document.body.classList.remove('ff-searching-active');
    }

    function triggerUpdate() {
        var isSrv = location.pathname.includes('/server/');

        if (!isFilesPage()) {
            clearSearchState();
        }

        if (isSrv) {
            document.body.classList.add('ff-server-page', 'ff-srv-view');
            try { setupSidebar(); } catch(e) {}
            try { setupQuickCommands(); } catch(e) {}
            if (isFilesPage()) {
                try { setupFileFeatures(); } catch(e) {}
                try { setupFileSearch(); } catch(e) {}
            }
        }

        if (updateTimer) clearTimeout(updateTimer);
        updateTimer = setTimeout(() => {
            try {
                if (isSrv) {
                    document.body.classList.add('ff-server-page', 'ff-srv-view');
                    try { setupSidebar(); } catch(e) { console.error('setupSidebar failed', e); }
                    try { setupQuickCommands(); } catch(e) { console.error('setupQuickCommands failed', e); }
                    if (isFilesPage()) {
                        try { setupFileFeatures(); } catch(e) { console.error('setupFileFeatures failed', e); }
                        try { setupFileSearch(); } catch(e) { console.error('setupFileSearch failed', e); }
                    } else {
                        clearSearchState();
                    }
                } else {
                    clearSearchState();
                    if (document.body.classList.contains('ff-srv-view')) {
                        setTimeout(() => {
                            if (!location.pathname.includes('/server/')) {
                                document.body.classList.remove('ff-srv-view', 'ff-server-page');
                            }
                        }, 300);
                    }
                    animateServerCards();
                }
            } catch(e) { console.error('triggerUpdate failed', e); }
        }, 100);
    }
function setupSidebar() {
        var subNav = document.querySelector('div[class*="SubNavigation"]') || document.querySelector('nav[class*="SubNavigation"]');
        var mobileNav = document.querySelector('div[class*="MobileNavigation"]') || document.querySelector('nav[class*="MobileNavigation"]');

        if (subNav && window.innerWidth >= 769) {
            if (!subNav._ffEvt) {
                subNav._ffEvt = true;
                subNav.addEventListener('mouseenter', () => {
                    document.body.classList.add('ff-sidebar-expanded');
                });
                subNav.addEventListener('mouseleave', () => {
                    document.body.classList.remove('ff-sidebar-expanded');
                });
            }
        }

        var navs = [subNav, mobileNav].filter(n => !!n);
        if (navs.length === 0) return;

        navs.forEach(nav => {
            nav.querySelectorAll('a').forEach(function(link) {
                var hasIco = link.querySelector('.ff-ico');
                var hasTxt = link.querySelector('.ff-txt-wrap');

                if (link.dataset.ffDone && (!hasIco || !hasTxt)) {
                    delete link.dataset.ffDone;
                }

                if (!link.dataset.ffDone) {
                    var text = "";
                    Array.from(link.childNodes).forEach(node => {
                        if (node.nodeType === 3 && node.textContent.trim()) {
                            text = node.textContent.trim();
                        } else if (node.nodeType === 1 && !node.classList.contains('ff-ico') && !node.classList.contains('ff-txt-wrap')) {
                            var t = node.textContent.trim();
                            if (t) text = t;
                        }
                    });

                    if (!text) return; // Not ready yet, retry on next tick

                    link.dataset.ffDone = '1';
                    var href = (link.getAttribute('href') || '').toLowerCase();
                    var iconHtml = null;
                    var keys = Object.keys(pathIcons).sort((a,b) => b.length - a.length);
                    for (var k of keys) { if (href.includes('/'+k) || href.endsWith(k) || (href.includes(k) && k.length > 3)) { iconHtml = pathIcons[k]; break; } }
                    if (!iconHtml && /\/server\/[a-zA-Z0-9-]+\/?$/.test(href)) iconHtml = pathIcons['console'];
                    if (!iconHtml) iconHtml = fallbackIcon;

                    Array.from(link.childNodes).forEach(node => {
                        if (node.nodeType === 3) {
                            link.removeChild(node);
                        } else if (node.nodeType === 1 && !node.classList.contains('ff-ico') && !node.classList.contains('ff-txt-wrap')) {
                            node.style.setProperty('display', 'none', 'important');
                        }
                    });

                    if (!hasIco) {
                        var icoSpan = document.createElement('span'); icoSpan.className = 'ff-ico'; icoSpan.innerHTML = iconHtml;
                        link.appendChild(icoSpan);
                    }
                    if (!hasTxt) {
                        var wrap = document.createElement('span'); wrap.className = 'ff-txt-wrap';
                        var txt = document.createElement('span'); txt.className = 'ff-txt'; txt.textContent = text;
                        wrap.appendChild(txt); link.appendChild(wrap);
                    }
                }
                var h = link.getAttribute('href');
                var active = (h === location.pathname || h === location.pathname + '/');
                if (!active && h !== '/' && location.pathname.startsWith(h) && h.split('/').length > 3) active = true;
                link.classList.toggle('active', active);
            });
        });
    }

    function animateServerCards() {
        if (!['/', ''].includes(location.pathname)) {
            document.body.classList.remove('ff-dash-page');
            window._ffInitialDashAnimDone = false;
            return;
        }
        document.body.classList.add('ff-dash-page');
        var allLinks = document.querySelectorAll('a[href*="/server/"]');
        if (allLinks.length === 0) return;

        var dashboardLinks = Array.from(allLinks).filter(l => !l.closest('nav, [class*="Sidebar"], [class*="SubNavigation"], [class*="MobileNavigation"]'));
        if (dashboardLinks.length === 0) return;

        if (window._ffInitialDashAnimDone) {
            dashboardLinks.forEach(l => {
                if (!l.classList.contains('ff-done')) {
                    l.classList.add('ff-done');
                }
            });
            return;
        }

        var unAnimatedLinks = dashboardLinks.filter(l => !l.classList.contains('ff-done') && !l.classList.contains('ff-animating'));
        if (unAnimatedLinks.length === 0) return;

        var cardStagger = 0.03;
        unAnimatedLinks.forEach((link, i) => {
            link.style.setProperty('animation-delay', (i * cardStagger) + 's', 'important');
            link.classList.add('ff-animating');

            setTimeout(function() {
                link.classList.remove('ff-animating');
                link.classList.add('ff-done');
                link.style.opacity = '1';
                link.style.transform = 'none';
            }, (i * cardStagger + 0.25) * 1000);
        });

        setTimeout(function() {
            window._ffInitialDashAnimDone = true;
        }, (unAnimatedLinks.length * cardStagger + 0.3) * 1000);
    }


    function animateFileRows() {
        var subNav = document.querySelector('div[class*="SubNavigation"]') || document.querySelector('nav[class*="SubNavigation"]');
        document.querySelectorAll('section a[href*="/files"], section div[class*="FileObject"]').forEach((row, i) => {
            if (subNav && subNav.contains(row)) return;
            if (row.dataset.ffAnDone) return;

            var href = (row.getAttribute('href') || '').toLowerCase();
            if (href.includes('/files/new') || href.includes('/files/create') || row.closest('button, [class*="Button"], [class*="ActionsContainer"]') || (row.tagName === 'A' && !row.querySelector('div[class*="FileObject"]') && row.textContent.trim() === '/')) {
                row.dataset.ffAnDone = '1';
                return;
            }

            row.classList.add('ff-file-row');
            row.dataset.ffAnDone = '1';
            requestAnimationFrame(() => {
                row.classList.add('ff-fade-in');
                row.style.animationDelay = (i * {{ $animStagger }}) + 's';
            });
        });
    }

    function setupFileFeatures() {
        if (!isFilesPage()) return;
        animateFileRows();
        if (!window._ffPreviewAdded) {
            window._ffPreviewAdded = true;
            document.addEventListener('click', e => {
                if (!isFilesPage()) return;
                var target = e.target;
                if (target.closest('button, input, textarea, [role="menu"], [class*="Checkbox"], input[type="checkbox"], [class*="EditorContainer"], [class*="Editor"], .ff-media-page, .ff-cmd-overlay')) return;

                var row = target.closest('a, tr, div[css], div[class]');
                if (!row) return;

                var spans = row.querySelectorAll('span, p, div, td, a');
                var fileName = '';
                for (var i = 0; i < spans.length; i++) {
                    var txt = spans[i].textContent.trim();
                    if (txt && isPreviewable(getFileExt(txt))) {
                        fileName = txt;
                        break;
                    }
                }
                if (!fileName) return;

                e.preventDefault(); e.stopPropagation();
                var dir = getCurrentDir();
                var filePath = (dir === '/' ? '/' + fileName : dir + '/' + fileName);
                filePath = '/' + filePath.replace(/\/+/g, '/').replace(/^\//, '');
                showMediaPage(fileName, getServerUuid(), filePath);
            }, true);
        }
    }
function setupQuickCommands() {
        var inp = document.querySelector('input[placeholder*="Type a command"]') || document.querySelector('input[class*="Console__Input"]');
        if (!inp || document.querySelector('.ff-cmd-btn')) return;
        var btn = document.createElement('button'); btn.className = 'ff-cmd-btn';
        btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg> Quick Commands';
        var target = inp.closest('div[class*="Navigation"]') || inp.parentElement;
        if (target) { var wrap = document.createElement('div'); wrap.style.width = '100%'; wrap.appendChild(btn); target.after(wrap); }
        else inp.after(btn);
        btn.onclick = () => showQuickCommandsModal(getServerUuid());
    }

function showQuickCommandsModal(uuid) {
        if (document.querySelector('.ff-cmd-overlay')) return;
        var overlay = document.createElement('div'); overlay.className = 'ff-cmd-overlay';
        overlay.innerHTML = '<div class="ff-cmd-modal"><div class="ff-cmd-col" style="background:rgba(255,255,255,0.01);"><div class="ff-cmd-col-title">Create Command</div><div class="ff-form-group"><label class="ff-form-label">Name</label><input type="text" class="ff-form-input ff-new-label" placeholder="Restart Server"></div><div class="ff-form-group"><label class="ff-form-label">Command</label><input type="text" class="ff-form-input ff-new-cmd" placeholder="say {1}"></div><div style="margin-top:auto; padding-top:20px; border-top:1px solid rgba(255,255,255,0.05);"><div class="ff-form-label">Variables</div><div id="ff-vars-setup"></div><button class="ff-cmd-btn ff-add-btn" style="width:100%;">Save Command</button></div></div><div class="ff-cmd-col"><div class="ff-cmd-col-title">Saved Commands <button class="ff-close" style="margin-left:auto; background:none; border:none; color:rgba(255,255,255,0.3); font-size:24px; cursor:pointer;">&times;</button></div><div class="ff-cmd-list">Loading...</div></div><div class="ff-cmd-col" id="ff-run-col" style="display:none; background:rgba(59,130,246,0.02);"><div class="ff-cmd-col-title">Execute</div><div id="ff-run-content"></div><button class="ff-cmd-btn run-now" style="width:100%; margin-top:auto; background:#10b981;">Run Now</button></div></div>';
        document.body.appendChild(overlay); document.body.style.overflow = 'hidden';
        var list = overlay.querySelector('.ff-cmd-list'), varsSetup = overlay.querySelector('#ff-vars-setup');
        for(var i=1; i<=3; i++) { var vDiv = document.createElement('div'); vDiv.className = 'ff-form-group'; vDiv.innerHTML = '<div style="display:flex;gap:8px;"><input type="text" class="v-name ff-form-input" placeholder="Var '+i+'" style="flex:1"><input type="text" class="v-def ff-form-input" placeholder="Default" style="flex:1"></div>'; varsSetup.appendChild(vDiv); }
        var load = () => { fetch('/api/client/extensions/fileflow/servers/'+uuid+'/commands', { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() } }).then(r=>r.json()).then(d => {
            list.innerHTML = ''; (d.commands || []).forEach(c => {
                var item = document.createElement('div'); item.className = 'ff-cmd-item';
                item.innerHTML = '<div style="flex:1;"><span class="ff-cmd-name">'+c.label+'</span><span class="ff-cmd-string">'+c.command+'</span></div><button class="ff-cmd-act-btn del">&times;</button>';
                item.onclick = e => { if(e.target.closest('.del')) return; overlay.querySelectorAll('.ff-cmd-item').forEach(el=>el.classList.remove('active')); item.classList.add('active'); prepareRun(c, overlay); };
                item.querySelector('.del').onclick = () => fetch('/api/client/extensions/fileflow/servers/'+uuid+'/commands/'+c.id, { method:'DELETE', headers:{'X-CSRF-TOKEN':getCsrfToken()} }).then(load);
                list.appendChild(item);
            });
        });}; load();
        overlay.querySelector('.ff-add-btn').onclick = () => {
            var vIn = varsSetup.querySelectorAll('.ff-form-group'), payload = { label:overlay.querySelector('.ff-new-label').value, command:overlay.querySelector('.ff-new-cmd').value, v1_name:vIn[0].querySelector('.v-name').value, v1_default:vIn[0].querySelector('.v-def').value, v2_name:vIn[1].querySelector('.v-name').value, v2_default:vIn[1].querySelector('.v-def').value, v3_name:vIn[2].querySelector('.v-name').value, v3_default:vIn[2].querySelector('.v-def').value };
            if(!payload.label || !payload.command) return;
            fetch('/api/client/extensions/fileflow/servers/'+uuid+'/commands', { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':getCsrfToken()}, body:JSON.stringify(payload) }).then(load);
        };
        overlay.querySelector('.ff-close').onclick = () => { overlay.remove(); document.body.style.overflow = ''; };
    }

function prepareRun(c, overlay) {
        var col = overlay.querySelector('#ff-run-col'), cont = overlay.querySelector('#ff-run-content');
        col.style.display = 'flex'; cont.innerHTML = '<div style="margin-bottom:15px; padding:10px; background:rgba(0,0,0,0.2); border-radius:6px; font-family:monospace; font-size:12px; color:#3b82f6;">'+c.command+'</div>';
        [1,2,3].forEach(i => { var n = c['v'+i+'_name']; if(n) { var g = document.createElement('div'); g.className = 'ff-form-group'; g.innerHTML = '<label class="ff-form-label">'+n+'</label><input type="text" class="ff-form-input run-v'+i+'" value="'+(c['v'+i+'_default']||'')+'">'; cont.appendChild(g); } });
        overlay.querySelector('.run-now').onclick = () => {
            var final = c.command; [1,2,3].forEach(i => { var v = cont.querySelector('.run-v'+i)?.value || ''; final = final.replace(new RegExp('\\{'+i+'\\}', 'g'), v); });
            runCommand(final); overlay.remove(); document.body.style.overflow = '';
        };
    }

function runCommand(cmd) {
        var inp = document.querySelector('input[placeholder*="Type a command"]') || document.querySelector('input[class*="Console__Input"]');
        if (inp) {
            var setter = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, "value").set;
            setter.call(inp, cmd); inp.dispatchEvent(new Event('input', { bubbles: true }));
            ['keydown', 'keypress', 'keyup'].forEach(t => inp.dispatchEvent(new KeyboardEvent(t, { bubbles:true, cancelable:true, keyCode:13, which:13, key:'Enter', code:'Enter' })));
        }
    }


    var skipKey = '{{ $skipShortcut }}'.toLowerCase();
    document.addEventListener('keydown', e => {
        if (e.key.toLowerCase() === skipKey) {
            if (['INPUT', 'TEXTAREA'].includes(e.target.tagName) || e.target.isContentEditable) return;
            var s = document.createElement('style'); s.innerHTML = '* { animation-delay: 0s !important; transition-delay: 0s !important; }'; document.head.appendChild(s);
            document.querySelectorAll('.ff-fade-in').forEach(el => el.style.animationDelay = '0s');
        }
    });

    var _ffObsTimer = null;
    var obs = new MutationObserver(muts => {
        if (_ffInSearchUpdate) return;
        var sig = false;
        for (var m of muts) {
            var t = m.target;
            if (t.closest?.('[class*="Terminal"], .xterm-viewport, [class*="Stat"], [class*="Indicator"], [class*="Chart"], canvas, .ff-preview-overlay, .ff-cmd-overlay')) continue;
            sig = true; break;
        }
        if (location.href !== lastUrl) {
            window._ffInitAnimDone = false;
            window._ffInitialDashAnimDone = false;
        }
        var isNav = (location.href !== lastUrl);
        if (isNav) {
            lastUrl = location.href;
            triggerUpdate();
            [50, 150, 300, 600, 1000].forEach(delay => setTimeout(triggerUpdate, delay));
        } else if (sig) {
            if (_ffObsTimer) clearTimeout(_ffObsTimer);
            _ffObsTimer = setTimeout(() => {
                triggerUpdate();
            }, 100);
        }
    });
    if (document.body) obs.observe(document.body, { childList: true, subtree: true });
    triggerUpdate();
    [50, 150, 300, 600, 1000, 1500].forEach(delay => setTimeout(triggerUpdate, delay));
})();
</script>
