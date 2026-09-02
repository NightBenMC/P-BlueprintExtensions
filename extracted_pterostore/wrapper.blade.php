<style>
/* ── Dashboard View Buttons ── */
#ps-view-btns {
    display: inline-flex;
    gap: 8px;
    align-items: center;
}
#ps-view-btns button {
    padding: 8px 22px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    border: 1px solid transparent;
    transition: all 0.15s ease;
    outline: none;
}
#ps-view-btns button.ps-on {
    background: #3182ce;
    color: #fff;
    border-color: #3182ce;
}
#ps-view-btns button:not(.ps-on) {
    background: rgba(255,255,255,0.04);
    color: #a0aec0;
    border-color: rgba(255,255,255,0.08);
}
#ps-view-btns button:not(.ps-on):hover {
    background: rgba(255,255,255,0.08);
    color: #e2e8f0;
}

/* ── Splitter Section ── */
#ps-splitter {
    display: none;
    padding-bottom: 24px;
    width: 100%;
    box-sizing: border-box;
}
#ps-splitter.ps-show { display: block; }

/* View header row (top of site under topbar) */
#ps-view-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100% !important;
    flex: 0 0 100% !important;
    padding: 0 0 16px 0;
    margin-bottom: 4px;
    box-sizing: border-box;
}
.ps-second-desc {
    display: block;
    font-size: 11px;
    font-weight: 600;
    margin-top: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.ps-desc-blue {
    color: #63b3ed !important;
}
#ps-splitter {
    flex: 0 0 100% !important;
}

/* Resource cards grid */
.ps-rg {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
    margin-bottom: 10px;
    width: 100%;
}
@media (max-width: 640px) {
    .ps-rg { grid-template-columns: 1fr; }
}

.ps-rc {
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.06);
    border-radius: 10px;
    padding: 14px 16px;
    display: flex;
    align-items: center;
    gap: 14px;
    position: relative;
    overflow: hidden;
}
.ps-rc .ps-ri {
    width: 44px; height: 44px;
    background: rgba(255,255,255,0.04);
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    color: #a0aec0;
}
.ps-rc .ps-ri svg { width: 22px; height: 22px; }
.ps-rc .ps-rt { flex: 1; min-width: 0; }
.ps-rc .ps-rl { color: #e2e8f0; font-size: 14px; font-weight: 600; margin-bottom: 2px; }
.ps-rc .ps-rv { color: #a0aec0; font-size: 13px; font-weight: 400; }

/* Split server list */
.ps-ss-title {
    color: #e2e8f0; font-size: 16px; font-weight: 700;
    margin: 24px 0 14px 0; display: flex; align-items: center; gap: 8px;
}
.ps-ss-title span { color: #718096; font-size: 12px; font-weight: 400; }

.ps-sc {
    background: linear-gradient(135deg, rgba(26,32,44,0.9), rgba(45,55,72,0.5));
    border: 1px solid rgba(255,255,255,0.06);
    border-radius: 12px;
    padding: 16px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    transition: border-color 0.15s;
    flex-wrap: wrap;
    gap: 10px;
    cursor: pointer;
}
.ps-sc:hover { border-color: rgba(255,255,255,0.12); }
.ps-sc-name { color: #e2e8f0; font-size: 15px; font-weight: 600; margin-bottom: 4px; }
.ps-sc-tags { display: flex; gap: 6px; flex-wrap: wrap; }
.ps-sc-tag {
    font-size: 11px; color: #a0aec0; background: rgba(255,255,255,0.04);
    padding: 3px 8px; border-radius: 6px; display: inline-flex; align-items: center; gap: 3px;
}
.ps-sc-right { display: flex; gap: 6px; align-items: center; }
.ps-sc-right button {
    padding: 7px 14px; border-radius: 8px; font-size: 12px; cursor: pointer;
    border: none; font-weight: 600; transition: all 0.15s;
}
.ps-btn-edit { background: rgba(99,179,237,0.1); color: #63b3ed; }
.ps-btn-edit:hover { background: rgba(99,179,237,0.2); }
.ps-sc-status {
    padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600;
}
.ps-no-srv {
    text-align: center; color: #718096; padding: 40px 20px;
    background: rgba(26,32,44,0.5); border-radius: 12px;
    border: 1px dashed rgba(255,255,255,0.06);
}

/* Splitted servers separator on regular server list */
.ps-split-sep {
    display: flex; align-items: center; gap: 12px;
    margin: 20px 0 12px 0; color: #718096; font-size: 12px;
    text-transform: uppercase; letter-spacing: 1px; font-weight: 600;
    width: 100%; flex: 0 0 100%;
}
.ps-split-sep::before, .ps-split-sep::after {
    content: ''; flex: 1; height: 1px; background: rgba(255,255,255,0.08);
}

/* Edit modal */
.ps-mo {
    position: fixed; top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.75); z-index: 10000;
    display: flex; justify-content: center; align-items: center;
}
.ps-md {
    background: #1a202c; border: 1px solid rgba(255,255,255,0.1);
    border-radius: 14px; padding: 28px; max-width: 460px; width: 90%;
    box-shadow: 0 20px 60px rgba(0,0,0,0.5);
}
.ps-md h3 { color: #e2e8f0; font-size: 18px; font-weight: 700; margin: 0 0 20px 0; }
.ps-md label {
    display: block; color: #a0aec0; font-size: 11px; text-transform: uppercase;
    letter-spacing: 0.5px; margin-bottom: 4px; font-weight: 600;
}
.ps-md input[type="number"] {
    width: 100%; padding: 10px 14px; border-radius: 8px;
    border: 1px solid rgba(255,255,255,0.08); background: rgba(255,255,255,0.03);
    color: #e2e8f0; font-size: 14px; outline: none; margin-bottom: 14px;
    box-sizing: border-box; transition: border-color 0.15s;
}
.ps-md input:focus { border-color: #3182ce; }
.ps-md .ps-warn { color: #f6ad55; font-size: 12px; margin-top: -10px; margin-bottom: 14px; }
.ps-md .ps-mb { display: flex; gap: 8px; justify-content: flex-end; margin-top: 4px; }
.ps-md .ps-mb button {
    padding: 10px 20px; border-radius: 8px; font-size: 14px; cursor: pointer;
    border: none; font-weight: 600; transition: all 0.15s;
}
.ps-md .ps-mb .ps-save { background: #3182ce; color: #fff; }
.ps-md .ps-mb .ps-save:hover { background: #2b6cb0; }
.ps-md .ps-mb .ps-cancel { background: transparent; border: 1px solid rgba(255,255,255,0.1); color: #a0aec0; }
.ps-md .ps-mb .ps-cancel:hover { background: rgba(255,255,255,0.04); }
.ps-md .ps-mb .ps-danger { background: #e53e3e; color: #fff; }
.ps-md .ps-mb .ps-danger:hover { background: #c53030; }

/* Toast */
.ps-toast {
    position: fixed; top: 20px; right: 20px; z-index: 10002;
    padding: 12px 20px; border-radius: 10px; color: #fff; font-size: 14px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.4); transition: opacity 0.3s; font-weight: 500;
}
.ps-t-ok { background: #276749; }
.ps-t-err { background: #9b2c2c; }

/* Create split server form */
.ps-create-form {
    background: rgba(26,32,44,0.8);
    border: 1px solid rgba(255,255,255,0.06);
    border-radius: 14px;
    padding: 24px;
    margin-top: 16px;
}
.ps-create-form h3 { color: #e2e8f0; font-size: 16px; font-weight: 700; margin: 0 0 16px 0; }
.ps-create-form label {
    display: block; color: #a0aec0; font-size: 11px; text-transform: uppercase;
    letter-spacing: 0.5px; margin-bottom: 4px; font-weight: 600;
}
.ps-create-form input, .ps-create-form select {
    width: 100%; padding: 10px 14px; border-radius: 8px;
    border: 1px solid rgba(255,255,255,0.08); background: rgba(255,255,255,0.03);
    color: #e2e8f0; font-size: 14px; outline: none; margin-bottom: 14px;
    box-sizing: border-box;
}
.ps-create-form .ps-form-grid {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;
}
@media (max-width: 640px) {
    .ps-create-form .ps-form-grid { grid-template-columns: repeat(2, 1fr); }
}
.ps-create-form .ps-form-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 4px; }
.ps-create-form .ps-form-actions button {
    padding: 10px 24px; border-radius: 8px; font-size: 14px; cursor: pointer;
    border: none; font-weight: 600;
}
.ps-create-btn { background: #3182ce; color: #fff; }
.ps-create-btn:hover { background: #2b6cb0; }
.ps-toggle-create {
    padding: 10px 20px; border-radius: 8px; background: #3182ce; color: #fff;
    border: none; font-size: 14px; font-weight: 600; cursor: pointer; margin-top: 16px;
}
.ps-toggle-create:hover { background: #2b6cb0; }
</style>

<script>
(function() {
    var API = '/api/client/extensions/pterostore';
    var currentView = 'servers';
    var resources = null;
    var splitServers = [];
    var eggs = [];
    var splitterNodes = [];
    var initialized = false;
    var editModal = null;
    var showCreateForm = false;
    var psSettings = { store_enabled: true, splitter_enabled: true };
    var settingsLoaded = false;

    function fmt(key, val) {
        val = Number(val) || 0;
        if (key === 'cpu') return val + ' %';
        if (key === 'ram' || key === 'disk' || key === 'memory') return val >= 1024 ? (val / 1024).toFixed(0) + ' GiB' : val + ' MiB';
        return String(val);
    }

    function barCol(pct) { return pct > 80 ? '#fc8181' : pct > 50 ? '#f6ad55' : '#68d391'; }
    function cardCol(pct) { return pct > 80 ? 'ps-rc-red' : pct > 50 ? 'ps-rc-yellow' : 'ps-rc-green'; }

    function toast(msg, ok) {
        var el = document.createElement('div');
        el.className = 'ps-toast ' + (ok ? 'ps-t-ok' : 'ps-t-err');
        el.textContent = msg;
        document.body.appendChild(el);
        setTimeout(function() { el.style.opacity = '0'; setTimeout(function() { el.remove(); }, 300); }, 4000);
    }

    function getCookie(n) {
        var m = document.cookie.match('(^|;)\\s*' + n + '\\s*=\\s*([^;]+)');
        return m ? decodeURIComponent(m[2]) : '';
    }

    function apiPost(url, data) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': getCookie('XSRF-TOKEN'),
            },
            credentials: 'same-origin',
            body: JSON.stringify(data),
        }).then(function(r) {
            return r.text().then(function(txt) {
                console.log('[PteroStore] POST ' + url + ' status=' + r.status + ' body=' + txt.substring(0, 500));
                try {
                    var d = JSON.parse(txt);
                    return { ok: r.ok, data: d };
                } catch(e) {
                    return { ok: false, data: { error: 'Server error (status ' + r.status + '): ' + txt.substring(0, 200) } };
                }
            });
        }).catch(function(err) {
            console.error('[PteroStore] POST error', url, err);
            return { ok: false, data: { error: 'Request failed.' } };
        });
    }

    function apiGet(url) {
        return fetch(url, {
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': getCookie('XSRF-TOKEN'),
            },
            credentials: 'same-origin',
        }).then(function(r) {
            if (!r.ok) {
                console.error('[PteroStore] GET failed:', r.status, url);
                return r.text().then(function(t) {
                    console.error('[PteroStore] Response body:', t.substring(0, 500));
                    return null;
                });
            }
            return r.json();
        }).catch(function(err) {
            console.error('[PteroStore] GET error', url, err);
            return null;
        });
    }

    // ── Load settings + trigger auto-renewals ──
    apiGet(API + '/store/settings').then(function(s) {
        if (s) psSettings = s;
        settingsLoaded = true;
        if (!psSettings.store_enabled) {
            var storeLinks = document.querySelectorAll('a[href*="/store"], a[href*="/account/store"]');
            storeLinks.forEach(function(el) { el.style.setProperty('display', 'none', 'important'); });
        }
    });
    apiPost(API + '/store/process-auto-renewals', {}).then(function(res) {
        if (res && res.ok && res.data && res.data.renewed && res.data.renewed.length > 0) {
            toast('Auto-renewed ' + res.data.renewed.length + ' server(s)', true);
        }
    });

    // ── SVG icons for resource cards ──
    var rcIcons = {
        cpu: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="16" rx="2" ry="2"/><rect x="9" y="9" width="6" height="6"/><line x1="9" y1="1" x2="9" y2="4"/><line x1="15" y1="1" x2="15" y2="4"/><line x1="9" y1="20" x2="9" y2="23"/><line x1="15" y1="20" x2="15" y2="23"/><line x1="20" y1="9" x2="23" y2="9"/><line x1="20" y1="14" x2="23" y2="14"/><line x1="1" y1="9" x2="4" y2="9"/><line x1="1" y1="14" x2="4" y2="14"/></svg>',
        ram: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"/><line x1="6" y1="10" x2="6" y2="14"/><line x1="10" y1="10" x2="10" y2="14"/><line x1="14" y1="10" x2="14" y2="14"/><line x1="18" y1="10" x2="18" y2="14"/></svg>',
        disk: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>',
        ports: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>',
        servers: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="8" rx="2" ry="2"/><rect x="2" y="14" width="20" height="8" rx="2" ry="2"/><line x1="6" y1="6" x2="6.01" y2="6"/><line x1="6" y1="18" x2="6.01" y2="18"/></svg>',
        databases: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>',
    };

    // ── Resource Card Builder ──
    function resCard(iconKey, label, used, total) {
        var svgIcon = rcIcons[iconKey] || rcIcons.cpu;
        return '<div class="ps-rc">' +
            '<div class="ps-ri">' + svgIcon + '</div>' +
            '<div class="ps-rt">' +
                '<div class="ps-rl">Assigned ' + label + '</div>' +
                '<div class="ps-rv">' + fmt(iconKey, used) + ' / ' + fmt(iconKey, total) + '</div>' +
            '</div>' +
        '</div>';
    }

    // ── Edit Modal ──
    function openEdit(srv) {
        if (editModal) editModal.remove();
        var ov = document.createElement('div');
        ov.className = 'ps-mo';
        var r = srv.resources || {};
        var u = (resources && resources.used) ? resources.used : {};
        var freeCpu = Math.max(0, (resources ? resources.cpu : 0) - (u.cpu || 0));
        var freeRam = Math.max(0, (resources ? resources.ram : 0) - (u.ram || 0));
        var freeDisk = Math.max(0, (resources ? resources.disk : 0) - (u.disk || 0));
        var freePorts = Math.max(0, (resources ? resources.ports : 0) - (u.ports || 0));
        var freeDbs = Math.max(0, (resources ? resources.databases : 0) - (u.databases || 0));
        ov.innerHTML = '<div class="ps-md">' +
            '<h3>Edit: ' + (srv.name || 'Server') + '</h3>' +
            '<div class="ps-free-res" style="background:rgba(104,211,145,0.1);border:1px solid rgba(104,211,145,0.3);border-radius:6px;padding:10px 14px;margin-bottom:14px;font-size:13px;">' +
                '<strong style="font-size:12px;color:#68d391;">Free Resources Available</strong><br>' +
                '<span>CPU: ' + freeCpu + '% &bull; RAM: ' + fmt('ram', freeRam) + ' &bull; Disk: ' + fmt('disk', freeDisk) + ' &bull; Ports: ' + freePorts + ' &bull; DBs: ' + freeDbs + '</span>' +
            '</div>' +
            '<label>CPU (%)</label><input type="number" id="ps-e-cpu" value="' + (r.cpu || 100) + '" min="1">' +
            '<label>RAM (MB)</label><input type="number" id="ps-e-ram" value="' + (r.ram || 1024) + '" min="64">' +
            '<label>Disk (MB)</label><input type="number" id="ps-e-disk" value="' + (r.disk || 5120) + '" min="256">' +
            '<label>Allocations (Ports)</label><input type="number" id="ps-e-ports" value="' + (r.ports || 1) + '" min="' + (r.ports || 1) + '">' +
            '<div class="ps-warn">Cannot be lower than current value (' + (r.ports || 1) + ').</div>' +
            '<label>Databases</label><input type="number" id="ps-e-dbs" value="' + (r.databases || 0) + '" min="' + (r.databases || 0) + '">' +
            '<div class="ps-warn">Cannot be lower than current value (' + (r.databases || 0) + ').</div>' +
            '<div class="ps-mb">' +
                '<button class="ps-danger" id="ps-e-del">Delete Server</button>' +
                '<button class="ps-cancel" id="ps-e-no">Cancel</button>' +
                '<button class="ps-save" id="ps-e-yes">Save Changes</button>' +
            '</div>' +
        '</div>';
        document.body.appendChild(ov);
        editModal = ov;

        ov.addEventListener('click', function(e) { if (e.target === ov) { ov.remove(); editModal = null; } });
        document.getElementById('ps-e-no').onclick = function() { ov.remove(); editModal = null; };

        document.getElementById('ps-e-yes').onclick = function() {
            var btn = this;
            var newPorts = parseInt(document.getElementById('ps-e-ports').value) || 1;
            var newDbs = parseInt(document.getElementById('ps-e-dbs').value) || 0;
            if (newPorts < (r.ports || 1)) { toast('Ports cannot be lower than ' + (r.ports || 1), false); return; }
            if (newDbs < (r.databases || 0)) { toast('Databases cannot be lower than ' + (r.databases || 0), false); return; }
            btn.disabled = true; btn.textContent = 'Saving...';
            apiPost(API + '/splitter/update-server', {
                server_id: srv.id,
                cpu: parseInt(document.getElementById('ps-e-cpu').value) || 100,
                ram: parseInt(document.getElementById('ps-e-ram').value) || 1024,
                disk: parseInt(document.getElementById('ps-e-disk').value) || 5120,
                ports: newPorts,
                databases: newDbs,
            }).then(function(res) {
                if (res.ok) { toast(res.data.message || 'Updated!', true); ov.remove(); editModal = null; reloadSplitter(); }
                else { toast(res.data.error || 'Failed.', false); btn.disabled = false; btn.textContent = 'Save Changes'; }
            });
        };

        document.getElementById('ps-e-del').onclick = function() {
            if (!confirm('Permanently delete this split server? Resources will be freed.')) return;
            var btn = this;
            btn.disabled = true; btn.textContent = 'Deleting...';
            apiPost(API + '/splitter/delete-server', { server_id: srv.id })
            .then(function(res) {
                if (res.ok) { toast(res.data.message || 'Deleted!', true); ov.remove(); editModal = null; reloadSplitter(); }
                else { toast(res.data.error || 'Failed.', false); btn.disabled = false; btn.textContent = 'Delete Server'; }
            });
        };
    }

    // ── Render Splitter View ──
    function renderSplitter(container) {
        console.log('[PteroStore] renderSplitter called. resources=', resources, 'splitServers=', splitServers.length, 'eggs=', eggs.length);
        var html = '';

        if (resources && (Number(resources.cpu) > 0 || Number(resources.ram) > 0 || Number(resources.disk) > 0 || Number(resources.server_limit) > 0)) {
            var u = resources.used || {};

            html += '<div class="ps-rg">';
            html += resCard('cpu', 'CPU', u.cpu || 0, resources.cpu);
            html += resCard('ram', 'Memory', u.ram || 0, resources.ram);
            html += resCard('disk', 'Disk', u.disk || 0, resources.disk);
            html += '</div>';

            html += '<div class="ps-rg">';
            html += resCard('ports', 'Ports', u.ports || 0, resources.ports);
            html += resCard('servers', 'Servers', u.servers || 0, resources.server_limit);
            html += resCard('databases', 'Databases', u.databases || 0, resources.databases);
            html += '</div>';

            if (splitServers.length > 0) {
                html += '<div class="ps-ss-title">Your Split Servers <span>(' + splitServers.length + ')</span></div>';
                for (var i = 0; i < splitServers.length; i++) {
                    var srv = splitServers[i];
                    var sc = srv.status === 'suspended' ? '#fc5050' : (srv.status === 'installing' ? '#f6ad55' : '#68d391');
                    var sb = srv.status === 'suspended' ? 'rgba(252,80,80,0.1)' : (srv.status === 'installing' ? 'rgba(246,173,85,0.1)' : 'rgba(104,211,145,0.1)');
                    var r = srv.resources || {};
                    html += '<div class="ps-sc" data-si="' + i + '" data-uuid="' + (srv.uuid || '') + '">' +
                        '<div class="ps-ri" style="width:36px;height:36px;border-radius:6px;">' + rcIcons.servers + '</div>' +
                        '<div style="flex:1;min-width:0;">' +
                            '<div class="ps-sc-name">' + (srv.name || 'Server') + '</div>' +
                            '<div class="ps-sc-tags">' +
                                (srv.ip ? '<span class="ps-sc-tag"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg> ' + srv.ip + '</span>' : '') +
                                '<span class="ps-sc-tag"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="4" width="16" height="16" rx="2"/></svg> ' + (r.cpu || 0) + ' %</span>' +
                                '<span class="ps-sc-tag"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="12" rx="2"/></svg> ' + fmt('ram', r.ram || 0) + '</span>' +
                                '<span class="ps-sc-tag"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg> ' + fmt('disk', r.disk || 0) + '</span>' +
                            '</div>' +
                        '</div>' +
                        '<div class="ps-sc-right">' +
                            '<button class="ps-btn-edit" data-ei="' + i + '">Edit</button>' +
                            '<span class="ps-sc-status" style="background:' + sb + ';color:' + sc + '">' + (srv.status || 'Running') + '</span>' +
                        '</div>' +
                    '</div>';
                }
            } else {
                html += '<div class="ps-no-srv">No split servers yet. Use the button below to create one.</div>';
            }

            html += '<button class="ps-toggle-create" id="ps-show-create">' + (showCreateForm ? 'Cancel' : '+ Create Split Server') + '</button>';

            if (showCreateForm) {
                html += '<div class="ps-create-form">' +
                    '<h3>Create Split Server</h3>' +
                    '<label>Server Name</label><input type="text" id="ps-c-name" placeholder="My Server" maxlength="100">' +
                    '<label>Description</label><input type="text" id="ps-c-desc" placeholder="Short description (optional)" maxlength="255">' +
                    '<label>Egg</label><select id="ps-c-egg">';
                for (var j = 0; j < eggs.length; j++) {
                    html += '<option value="' + eggs[j].id + '">' + eggs[j].name + '</option>';
                }
                if (eggs.length === 0) html += '<option value="">No eggs available</option>';
                html += '</select>';
                html += '<label>Node</label><select id="ps-c-node">';
                if (splitterNodes.length > 0) {
                    for (var ni = 0; ni < splitterNodes.length; ni++) {
                        var sn = splitterNodes[ni];
                        var nodeLabel = sn.name + (sn.ip ? ' (' + sn.ip + ')' : '');
                        if (sn.max_servers) {
                            nodeLabel += ' [' + sn.slots_remaining + '/' + sn.max_servers + ' slots]';
                        } else if (sn.servers_count !== undefined) {
                            nodeLabel += ' [' + sn.servers_count + ' servers]';
                        }
                        var nodeDisabled = sn.max_servers && sn.slots_remaining <= 0;
                        html += '<option value="' + sn.node_id + '"' + (nodeDisabled ? ' disabled' : '') + '>' + nodeLabel + (nodeDisabled ? ' (FULL)' : '') + '</option>';
                    }
                } else {
                    html += '<option value="">Loading nodes...</option>';
                }
                html += '</select>';
                html += '<div class="ps-form-grid">' +
                    '<div><label>CPU (%)</label><input type="number" id="ps-c-cpu" value="100" min="1"></div>' +
                    '<div><label>RAM (MB)</label><input type="number" id="ps-c-ram" value="1024" min="64"></div>' +
                    '<div><label>Disk (MB)</label><input type="number" id="ps-c-disk" value="5120" min="256"></div>' +
                    '<div><label>Ports</label><input type="number" id="ps-c-ports" value="1" min="1" max="10"></div>' +
                    '<div><label>Databases</label><input type="number" id="ps-c-dbs" value="0" min="0" max="10"></div>' +
                '</div>';
                html += '<div class="ps-form-actions">' +
                    '<button class="ps-create-btn" id="ps-c-submit">Create Server</button>' +
                '</div></div>';
            }
        } else {
            html += '<div class="ps-no-srv">No resources allocated to your account.<br><span style="font-size:13px;margin-top:6px;display:block;">An administrator must assign resources before you can create split servers.</span></div>';
        }

        container.innerHTML = html;

        // Attach event handlers
        container.querySelectorAll('.ps-sc').forEach(function(card) {
            card.addEventListener('click', function(e) {
                if (e.target.closest('.ps-btn-edit')) {
                    e.preventDefault();
                    e.stopPropagation();
                    var editBtn = e.target.closest('.ps-btn-edit');
                    var idx = parseInt(editBtn.dataset.ei);
                    if (splitServers[idx]) openEdit(splitServers[idx]);
                    return;
                }
                var uuid = card.dataset.uuid;
                if (uuid) {
                    window.location.href = '/server/' + uuid;
                }
            });
        });

        var toggleBtn = document.getElementById('ps-show-create');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                showCreateForm = !showCreateForm;
                renderSplitter(container);
            });
        }

        var submitBtn = document.getElementById('ps-c-submit');
        if (submitBtn) {
            submitBtn.addEventListener('click', function() {
                var name = (document.getElementById('ps-c-name').value || '').trim();
                if (!name) { toast('Server name is required.', false); return; }
                var eggId = parseInt(document.getElementById('ps-c-egg').value);
                if (!eggId) { toast('Select an egg.', false); return; }
                submitBtn.disabled = true; submitBtn.textContent = 'Creating...';
                var descEl = document.getElementById('ps-c-desc');
                var createData = {
                    name: name,
                    description: descEl ? descEl.value.trim() : '',
                    egg_id: eggId,
                    cpu: parseInt(document.getElementById('ps-c-cpu').value) || 100,
                    ram: parseInt(document.getElementById('ps-c-ram').value) || 1024,
                    disk: parseInt(document.getElementById('ps-c-disk').value) || 5120,
                    ports: parseInt(document.getElementById('ps-c-ports').value) || 1,
                    databases: parseInt(document.getElementById('ps-c-dbs').value) || 0,
                };
                var nodeSelect = document.getElementById('ps-c-node');
                if (nodeSelect) createData.node_id = parseInt(nodeSelect.value) || null;
                apiPost(API + '/splitter/create', createData).then(function(res) {
                    if (res.ok) {
                        toast(res.data.message || 'Server created!', true);
                        showCreateForm = false;
                        reloadSplitter();
                    } else {
                        toast(res.data.error || 'Failed.', false);
                        submitBtn.disabled = false; submitBtn.textContent = 'Create Server';
                    }
                });
            });
        }
    }

    function reloadSplitter() {
        console.log('[PteroStore] Loading splitter data...');

        // Show cached data immediately for faster perceived loading
        try {
            var cachedEggs = localStorage.getItem('ps_eggs');
            var cachedRes = localStorage.getItem('ps_resources');
            if (cachedEggs) eggs = JSON.parse(cachedEggs);
            if (cachedRes) resources = JSON.parse(cachedRes);
            if (eggs.length > 0 && resources) {
                var container = document.getElementById('ps-splitter');
                if (container && currentView === 'splitter') renderSplitter(container);
            }
        } catch(e) {}

        Promise.all([
            apiGet(API + '/splitter/resources').then(function(d) {
                if (d) { resources = d; try { localStorage.setItem('ps_resources', JSON.stringify(d)); } catch(e) {} }
                else resources = null;
            }),
            apiGet(API + '/splitter/servers').then(function(d) {
                splitServers = d || [];
            }),
            apiGet(API + '/splitter/eggs').then(function(d) {
                eggs = d || [];
                try { localStorage.setItem('ps_eggs', JSON.stringify(eggs)); } catch(e) {}
            }),
            apiGet(API + '/splitter/nodes').then(function(d) {
                splitterNodes = d || [];
            }),
        ]).then(function() {
            console.log('[PteroStore] Data loaded. resources=', resources);
            var container = document.getElementById('ps-splitter');
            if (container && currentView === 'splitter') {
                renderSplitter(container);
            }
        }).catch(function(err) {
            console.error('[PteroStore] Reload error', err);
        });
    }

    // ── View Switch ──
    function switchView(view) {
        console.log('[PteroStore] switchView:', view);
        currentView = view;

        document.querySelectorAll('#ps-view-btns button').forEach(function(b) {
            b.classList.toggle('ps-on', b.dataset.v === view);
        });

        var splitter = document.getElementById('ps-splitter');
        var viewRow = document.getElementById('ps-view-row');
        if (!splitter || !viewRow) return;

        var container = viewRow.parentElement;
        if (!container) return;

        if (view === 'splitter') {
            // Show splitter section (resource cards + custom split server list)
            splitter.classList.add('ps-show');
            splitter.innerHTML = '<div style="text-align:center;color:#718096;padding:30px;">Loading resources...</div>';
            reloadSplitter();

            // Hide normal server list completely
            toggleNormalServerList(false);
        } else {
            // Hide splitter section
            splitter.classList.remove('ps-show');

            // Show normal server list
            toggleNormalServerList(true);
        }
    }

    function toggleNormalServerList(show) {
        var viewRow = document.getElementById('ps-view-row');
        var container = viewRow ? viewRow.parentElement : null;
        if (!container) return;

        for (var i = 0; i < container.children.length; i++) {
            var child = container.children[i];
            if (child.id === 'ps-view-row' || child.id === 'ps-splitter') continue;
            if (child.tagName === 'STYLE' || child.tagName === 'SCRIPT') continue;
            if (show) {
                child.style.display = '';
            } else {
                child.style.display = 'none';
            }
        }
    }

    // ── Find dashboard content area (works with or without servers) ──
    function findDashboardContainer() {
        // First try: find server links and walk up
        var serverLinks = document.querySelectorAll('a[href*="/server/"]');
        if (serverLinks.length > 0) {
            var link = serverLinks[0];
            var target = link;
            for (var depth = 0; depth < 20; depth++) {
                if (!target.parentElement || target.parentElement === document.body) break;
                target = target.parentElement;
                if (target.querySelectorAll('a[href*="/server/"]').length >= serverLinks.length) break;
            }
            return { target: target, hasServers: true };
        }

        // Fallback for users with NO servers: find the main content area
        // Pterodactyl renders a "no servers" message or empty state in a container
        // Look for the main content wrapper (usually a div with substantial content)
        var mainContent = document.getElementById('main-content') || document.querySelector('[class*="ContentContainer"]');
        if (!mainContent) {
            // Try finding the largest content div on the page
            var divs = document.querySelectorAll('div');
            for (var i = 0; i < divs.length; i++) {
                var d = divs[i];
                // Look for divs that contain text about servers or empty states
                var txt = (d.textContent || '').toLowerCase();
                if ((txt.indexOf('no servers') >= 0 || txt.indexOf('server') >= 0) && d.children.length < 10 && d.offsetHeight > 50) {
                    // Heuristic: find the meaningful container, not a tiny label
                    if (d.offsetWidth > 300) {
                        mainContent = d;
                        break;
                    }
                }
            }
        }
        if (mainContent) {
            return { target: mainContent, hasServers: false };
        }
        return null;
    }

    // ── Inject on Dashboard ──
    function inject() {
        if (initialized) return;
        var path = window.location.pathname.replace(/\/+$/, '') || '/';
        if (path !== '' && path !== '/') return;

        var tries = 0;
        var iv = setInterval(function() {
            tries++;
            if (tries > 80) {
                clearInterval(iv);
                console.warn('[PteroStore] Gave up waiting for dashboard elements after 40s');
                return;
            }
            if (document.getElementById('ps-view-row')) { clearInterval(iv); return; }

            var found = findDashboardContainer();

            if (!found) {
                if (tries < 10) return;
                var reactRoot = document.getElementById('app') || document.getElementById('root') || document.querySelector('[data-reactroot]');
                if (!reactRoot) return;
                var contentDivs = reactRoot.querySelectorAll('div');
                for (var ci = 0; ci < contentDivs.length; ci++) {
                    if (contentDivs[ci].offsetWidth > 400 && contentDivs[ci].offsetHeight > 100 && contentDivs[ci].children.length > 0) {
                        found = { target: contentDivs[ci], hasServers: false };
                        break;
                    }
                }
                if (!found) return;
            }

            clearInterval(iv);
            initialized = true;
            var target = found.target;

            console.log('[PteroStore] Injecting. hasServers=' + found.hasServers + ' target:', target.tagName, target.className);

            target.style.display = 'flex';
            target.style.flexDirection = 'column';
            target.style.flexWrap = 'nowrap';

            // Create top buttons row (directly under topbar)
            var rowDiv = document.createElement('div');
            rowDiv.id = 'ps-view-row';

            var btnDiv = document.createElement('div');
            btnDiv.id = 'ps-view-btns';

            var b1 = document.createElement('button');
            b1.textContent = 'Servers';
            b1.dataset.v = 'servers';
            b1.className = 'ps-on';
            b1.onclick = function() { switchView('servers'); };
            btnDiv.appendChild(b1);

            if (psSettings.splitter_enabled) {
                var b2 = document.createElement('button');
                b2.textContent = 'Splitter';
                b2.dataset.v = 'splitter';
                b2.onclick = function() { switchView('splitter'); };
                btnDiv.appendChild(b2);
            }

            rowDiv.appendChild(btnDiv);

            // Create splitter container
            var splDiv = document.createElement('div');
            splDiv.id = 'ps-splitter';

            target.insertBefore(rowDiv, target.firstChild);
            if (rowDiv.nextSibling) {
                target.insertBefore(splDiv, rowDiv.nextSibling);
            } else {
                target.appendChild(splDiv);
            }

            console.log('[PteroStore] Buttons and splitter injected at top of content');

            reloadSplitter();

            // Handle 2nd Description Field injection (SPLITTED in blue or Store Package Info / Expiry)
            Promise.all([
                apiGet(API + '/store/expirations'),
                apiGet(API + '/splitter/badge')
            ]).then(function(results) {
                var exps = results[0] || {};
                var badge = results[1] || {};
                var splitUuids = badge.server_uuids || [];
                var splitMap = {};
                if (badge.split_servers) {
                    badge.split_servers.forEach(function(s) { splitMap[s.uuid] = s; });
                }

                fetch('/api/client?page=1', { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                .then(function(r) { return r.json(); })
                .then(function(srvResp) {
                    var srvList = (srvResp.data || []);
                    var idToUuid = {};
                    var shortUuidToId = {};
                    srvList.forEach(function(s) {
                        var attr = s.attributes || s;
                        if (attr.internal_id) idToUuid[attr.internal_id] = attr.uuid || attr.identifier;
                        if (attr.uuid) shortUuidToId[attr.uuid.substring(0, 8)] = attr.internal_id;
                        if (attr.identifier) shortUuidToId[attr.identifier] = attr.internal_id;
                    });

                    function applyDescriptions() {
                        var curPath = window.location.pathname.replace(/\/+$/, '') || '/';
                        if (curPath !== '' && curPath !== '/') return;

                        var serverLinks = document.querySelectorAll('a[href*="/server/"]');

                        serverLinks.forEach(function(link) {
                            if (link.closest('nav, [class*="Sidebar"], [class*="sidebar"], [class*="Navigation"], [class*="navigation"]')) return;

                            var href = link.getAttribute('href') || '';
                            var match = href.match(/\/server\/([a-f0-9\-]+)/);
                            if (!match) return;
                            var linkUuid = match[1];

                            var matchedSplitUuid = null;
                            var isSplit = splitUuids.some(function(su) {
                                if (su === linkUuid || su.indexOf(linkUuid) === 0 || linkUuid.indexOf(su) === 0) {
                                    matchedSplitUuid = su;
                                    return true;
                                }
                                return false;
                            });

                            if (!isSplit && splitMap) {
                                for (var su in splitMap) {
                                    if (su === linkUuid || su.indexOf(linkUuid) === 0 || linkUuid.indexOf(su) === 0) {
                                        matchedSplitUuid = su;
                                        isSplit = true;
                                        break;
                                    }
                                }
                            }

                            var serverId = shortUuidToId[linkUuid];
                            if (!serverId) {
                                for (var sid in idToUuid) {
                                    var uuid = idToUuid[sid];
                                    if (uuid && (uuid === linkUuid || uuid.indexOf(linkUuid) === 0 || linkUuid.indexOf(uuid.substring(0, 8)) === 0)) {
                                        serverId = parseInt(sid);
                                        break;
                                    }
                                }
                            }

                            var exp = serverId ? exps[serverId] : null;

                            // Existing 2nd description element check
                            var desc2 = link.querySelector('.ps-second-desc');

                            if (isSplit) {
                                var badgeTextStr = badge.text || 'SPLITTER';
                                var badgeColorStr = badge.color || '#3182ce';

                                if (!desc2) {
                                    desc2 = document.createElement('span');
                                    desc2.className = 'ps-second-desc ps-desc-blue';
                                    desc2.style.cssText = 'display:inline-flex;align-items:center;gap:8px;margin-top:2px;';

                                    var splitText = document.createElement('span');
                                    splitText.textContent = badgeTextStr;
                                    splitText.style.cssText = 'padding:2px 6px;border-radius:4px;background:' + badgeColorStr + ';color:#fff;font-weight:600;font-size:10px;text-transform:uppercase;line-height:1.2;';
                                    desc2.appendChild(splitText);

                                    var targetContainer = link.querySelector('div > div:nth-child(2)') || link.querySelector('div > div') || link;
                                    targetContainer.appendChild(desc2);
                                } else {
                                    desc2.className = 'ps-second-desc ps-desc-blue';
                                }
                            } else if (exp) {
                                var expiresAt = new Date(exp.expires_at);
                                var now = new Date();
                                var diffMs = expiresAt.getTime() - now.getTime();
                                var diffDays = Math.ceil(diffMs / (1000 * 60 * 60 * 24));
                                var diffHours = Math.ceil(diffMs / (1000 * 60 * 60));
                                var text, color;

                                if (exp.suspended) {
                                    text = 'Expired - Suspended'; color = '#fc5050';
                                } else if (diffDays < 0) {
                                    text = 'Expired'; color = '#fc5050';
                                } else if (diffDays === 0) {
                                    text = diffHours <= 0 ? 'Expires soon' : 'Expires in ' + diffHours + 'h';
                                    color = '#f6ad55';
                                } else if (diffDays <= 3) {
                                    text = 'Expires in ' + diffDays + ' day' + (diffDays === 1 ? '' : 's');
                                    color = '#f6ad55';
                                } else {
                                    text = 'Expires in ' + diffDays + ' days';
                                    color = '#68d391';
                                }

                                var dateStr = expiresAt.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
                                var pkgName = exp.package_name || 'Store Server';
                                var fullDesc = pkgName + ' \u00B7 ' + text + ' \u00B7 ' + dateStr;

                                if (!desc2) {
                                    desc2 = document.createElement('span');
                                    desc2.className = 'ps-second-desc';
                                    desc2.style.color = color;
                                    desc2.textContent = fullDesc;
                                    var targetContainer = link.querySelector('div > div:nth-child(2)') || link.querySelector('div > div') || link;
                                    targetContainer.appendChild(desc2);
                                } else {
                                    desc2.className = 'ps-second-desc';
                                    desc2.style.color = color;
                                    desc2.textContent = fullDesc;
                                }
                            } else if (desc2) {
                                desc2.remove();
                            }
                        });
                    }

                    applyDescriptions();
                    setInterval(function() {
                        applyDescriptions();
                    }, 1500);
                });
            });
        }, 500);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', inject);
    } else {
        inject();
    }

    // ── Hide/show sidebar tabs based on server type ──
    var sidebarTabData = {};
    function updateSidebarTabs() {
        var curPath = window.location.pathname;
        var serverMatch = curPath.match(/\/server\/([a-f0-9\-]+)/);
        if (!serverMatch) return;
        var uuid = serverMatch[1];

        apiGet(API + '/store/server-package/' + uuid).then(function(d) {
            sidebarTabData.hasPackage = d && d.has_package;
            applySidebarVisibility(uuid);
        });

        apiGet(API + '/splitter/server-info/' + uuid).then(function(d) {
            sidebarTabData.isSplit = d && d.is_split;
            applySidebarVisibility(uuid);
        });
    }

    function applySidebarVisibility(uuid) {
        var pkgLinks = document.querySelectorAll('a[href*="/server/' + uuid + '/package"]');
        pkgLinks.forEach(function(el) {
            if (!psSettings.store_enabled || sidebarTabData.hasPackage === false) {
                el.style.setProperty('display', 'none', 'important');
            } else if (sidebarTabData.hasPackage === true) {
                el.style.removeProperty('display');
            }
        });

        var splLinks = document.querySelectorAll('a[href*="/server/' + uuid + '/splitter"]');
        splLinks.forEach(function(el) {
            if (!psSettings.splitter_enabled || sidebarTabData.isSplit === false) {
                el.style.setProperty('display', 'none', 'important');
            } else if (sidebarTabData.isSplit === true) {
                el.style.removeProperty('display');
            }
        });
    }

    // SPA navigation watch — clean up when leaving dashboard
    var lp = window.location.pathname;
    setInterval(function() {
        var curPath = window.location.pathname.replace(/\/+$/, '') || '/';
        if (curPath !== lp) {
            lp = curPath;

            // If navigating AWAY from dashboard, remove injected elements
            if (curPath !== '' && curPath !== '/') {
                var viewRow = document.getElementById('ps-view-row');
                var spl = document.getElementById('ps-splitter');
                if (viewRow) viewRow.remove();
                if (spl) spl.remove();
                // Remove all injected edit buttons, second descriptions, and badges
                document.querySelectorAll('.ps-badge, .ps-edit-inline, .ps-second-desc, .ps-expiry-tag').forEach(function(el) { el.remove(); });
                initialized = false;
                currentView = 'servers';
            } else {
                // Navigated back to dashboard
                initialized = false;
                currentView = 'servers';
                inject();
            }

            // Update sidebar tab visibility on server pages
            if (curPath.match(/\/server\/[a-f0-9\-]+/)) {
                setTimeout(updateSidebarTabs, 500);
            }
        }
    }, 500);

    // Initial sidebar tab check + periodic re-apply
    if (window.location.pathname.match(/\/server\/[a-f0-9\-]+/)) {
        setTimeout(updateSidebarTabs, 500);
        setTimeout(updateSidebarTabs, 1500);
    }
    // Re-apply sidebar visibility periodically to handle React re-renders
    setInterval(function() {
        var curPath = window.location.pathname;
        var m = curPath.match(/\/server\/([a-f0-9\-]+)/);
        if (m && (sidebarTabData.hasPackage !== undefined || sidebarTabData.isSplit !== undefined)) {
            applySidebarVisibility(m[1]);
        }
    }, 2000);

})();
</script>
