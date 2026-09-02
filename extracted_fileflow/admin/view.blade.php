@extends('layouts.admin')

@section('title')
    FileFlow
@endsection

@section('content')
    @if(session('success'))
    <div class="alert alert-success alert-dismissible">
      <button type="button" class="close" data-dismiss="alert">&times;</button>
      {{ session('success') }}
    </div>
  @endif

  <form action="{{ route('admin.extensions.fileflow.index') }}" method="POST">
    @csrf
    @method('PATCH')

    <div class="row">
      <div class="col-md-6">
        <div class="box box-default">
          <div class="box-header with-border">
            <h3 class="box-title">Animation Settings</h3>
          </div>
          <div class="box-body">
            <div class="form-group">
              <label>Animation Speed (seconds)</label>
              <input type="text" name="anim_speed" class="form-control" value="{{ $anim_speed }}" placeholder="0.35">
              <p class="text-muted small">How fast each file row fades in (default: 0.35s).</p>
            </div>
            <div class="form-group">
              <label>Stagger Delay (seconds)</label>
              <input type="text" name="anim_stagger" class="form-control" value="{{ $anim_stagger }}" placeholder="0.04">
              <p class="text-muted small">Delay between each row's appearance (default: 0.04s). Higher = more visible cascade effect.</p>
            </div>
            <div class="form-group">
              <label>Row Gap (pixels)</label>
              <input type="text" name="row_gap" class="form-control" value="{{ $row_gap }}" placeholder="6">
              <p class="text-muted small">Space between file rows in pixels (default: 6).</p>
            </div>
            <div class="form-group">
              <label>Search Depth (1-5)</label>
              <input type="number" name="max_depth" class="form-control" value="{{ $max_depth }}" min="1" max="5">
              <p class="text-muted small">Recursive search depth for files (default: 2). Higher = slower searches.</p>
            </div>
            <div class="form-group">
              <label>Animation Skip Shortcut</label>
              <input type="text" name="skip_shortcut" class="form-control" value="{{ $skip_shortcut }}" placeholder="x">
              <p class="text-muted small">The key to press to skip animations (default: x).</p>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-6">
        <div class="box box-primary">
          <div class="box-header with-border">
            <h3 class="box-title">Registration Settings</h3>
          </div>
          <div class="box-body">
            <div class="form-group">
              <label>Registration Enabled</label>
              <select name="registration_enabled" class="form-control">
                <option value="1" {{ $registration_enabled === '1' ? 'selected' : '' }}>Enabled</option>
                <option value="0" {{ $registration_enabled === '0' ? 'selected' : '' }}>Disabled</option>
              </select>
            </div>
            <div class="form-group">
              <label>Minimum Password Length</label>
              <input type="number" name="password_min_length" class="form-control" value="{{ $password_min_length }}" min="6" max="64">
            </div>
            <div class="form-group">
              <label>Require Email Verification</label>
              <select name="require_email_verification" class="form-control">
                <option value="0" {{ $require_email_verification === '0' ? 'selected' : '' }}>No</option>
                <option value="1" {{ $require_email_verification === '1' ? 'selected' : '' }}>Yes</option>
              </select>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-6">
        <div class="box box-warning">
          <div class="box-header with-border">
            <h3 class="box-title">Rate Limiting</h3>
          </div>
          <div class="box-body">
            <div class="form-group">
              <label>Max Registrations per IP</label>
              <input type="number" name="rate_limit_max" class="form-control" value="{{ $rate_limit_max }}" min="1" max="100">
            </div>
            <div class="form-group">
              <label>Rate Limit Window (seconds)</label>
              <input type="number" name="rate_limit_window" class="form-control" value="{{ $rate_limit_window }}" min="60" max="86400">
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-6">
        <div class="box box-info">
          <div class="box-header with-border">
            <h3 class="box-title">reCAPTCHA (v2)</h3>
          </div>
          <div class="box-body">
            <div class="form-group">
              <label>Enable reCAPTCHA</label>
              <select name="recaptcha_enabled" class="form-control">
                <option value="0" {{ $recaptcha_enabled === '0' ? 'selected' : '' }}>Disabled</option>
                <option value="1" {{ $recaptcha_enabled === '1' ? 'selected' : '' }}>Enabled</option>
              </select>
            </div>
            <div class="form-group">
              <label>Site Key</label>
              <input type="text" name="recaptcha_site_key" class="form-control" value="{{ $recaptcha_site_key }}">
            </div>
            <div class="form-group">
              <label>Secret Key</label>
              <input type="password" name="recaptcha_secret_key" class="form-control" value="{{ $recaptcha_secret_key }}">
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-12">
        <div class="box box-success">
          <div class="box-header with-border">
            <h3 class="box-title">Sidebar Icons Manager</h3>
            <div class="box-tools">
              <button type="button" class="btn btn-primary btn-xs" onclick="addIconRow()">Add Custom Tab</button>
            </div>
          </div>
          <div class="box-body no-padding">
            <table class="table table-hover" id="icons-table">
              <thead>
                <tr>
                  <th>Tab Keyword (URL part)</th>
                  <th>Icon Type</th>
                  <th>Icon Content (SVG or File)</th>
                  <th style="width: 40px"></th>
                </tr>
              </thead>
              <tbody id="icons-container">
                <!-- Rows will be added by JS -->
              </tbody>
            </table>
            <input type="hidden" name="custom_icons" id="custom_icons_input">
          </div>
          <div class="box-footer">
            <p class="text-muted small">Tab Keyword is matched against the link URL (e.g. 'files', 'databases'). Content can be raw &lt;svg&gt; or an uploaded image.</p>
          </div>
        </div>
      </div>

      <div class="col-md-6">
        <div class="box box-danger">
          <div class="box-header with-border">
            <h3 class="box-title">Email Domain Restrictions</h3>
          </div>
          <div class="box-body">
            <div class="form-group">
              <label>Allowed Domains (whitelist)</label>
              <input type="text" name="email_domain_whitelist" class="form-control" value="{{ $email_domain_whitelist }}" placeholder="gmail.com, outlook.com">
              <p class="text-muted small">Comma-separated. Leave empty to allow all.</p>
            </div>
            <div class="form-group">
              <label>Blocked Domains (blacklist)</label>
              <input type="text" name="email_domain_blacklist" class="form-control" value="{{ $email_domain_blacklist }}" placeholder="tempmail.com, throwaway.email">
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-xs-12">
        <button type="submit" class="btn btn-success btn-sm" onclick="prepareIcons()">Save Settings</button>
      </div>
    </div>
  </form>

  <textarea id="ff_icons_data" style="display:none">{{ $custom_icons ?: '{}' }}</textarea>
  <script>
    var icons = {};
    try { icons = JSON.parse(document.getElementById('ff_icons_data').value); } catch(e) { console.error(e); }
    var container = document.getElementById('icons-container');

    function addIconRow(key = '', val = '') {
      var row = document.createElement('tr');
      var type = val.startsWith('data:image') ? 'image' : 'svg';

      var td1 = document.createElement('td');
      var inputKey = document.createElement('input');
      inputKey.className = 'form-control icon-key';
      inputKey.value = key;
      inputKey.placeholder = 'e.g. settings';
      td1.appendChild(inputKey);

      var td2 = document.createElement('td');
      var selectType = document.createElement('select');
      selectType.className = 'form-control icon-type';
      selectType.innerHTML = '<option value="svg">SVG Code</option><option value="image">Upload Image</option>';
      selectType.value = type;
      selectType.onchange = function() { toggleInput(this); };
      td2.appendChild(selectType);

      var td3 = document.createElement('td');
      td3.className = 'icon-input-cell';

      var textSvg = document.createElement('textarea');
      textSvg.className = 'form-control icon-val-svg';
      textSvg.rows = 1;
      textSvg.style.display = type === 'svg' ? 'block' : 'none';
      textSvg.style.fontFamily = 'monospace';
      textSvg.style.fontSize = '11px';
      textSvg.value = type === 'svg' ? val : '';
      td3.appendChild(textSvg);

      var wrapImg = document.createElement('div');
      wrapImg.className = 'icon-val-img-wrap';
      wrapImg.style.display = type === 'image' ? 'block' : 'none';

      var inputFile = document.createElement('input');
      inputFile.type = 'file';
      inputFile.className = 'icon-val-file';
      inputFile.accept = 'image/*';
      inputFile.onchange = function() { handleFile(this); };
      wrapImg.appendChild(inputFile);

      var hiddenImg = document.createElement('input');
      hiddenImg.type = 'hidden';
      hiddenImg.className = 'icon-val-img';
      hiddenImg.value = type === 'image' ? val : '';
      wrapImg.appendChild(hiddenImg);

      var previewDiv = document.createElement('div');
      previewDiv.className = 'img-preview-container';
      previewDiv.style.marginTop = '5px';
      var img = document.createElement('img');
      img.src = type === 'image' ? val : '';
      img.style.maxHeight = '30px';
      img.style.display = (val && type === 'image') ? 'block' : 'none';
      previewDiv.appendChild(img);
      wrapImg.appendChild(previewDiv);
      td3.appendChild(wrapImg);

      var td4 = document.createElement('td');
      var btnDel = document.createElement('button');
      btnDel.type = 'button';
      btnDel.className = 'btn btn-danger btn-xs';
      btnDel.innerHTML = '&times;';
      btnDel.onclick = function() { this.closest('tr').remove(); };
      td4.appendChild(btnDel);

      row.appendChild(td1);
      row.appendChild(td2);
      row.appendChild(td3);
      row.appendChild(td4);
      container.appendChild(row);
    }

    function toggleInput(select) {
      var row = select.closest('tr');
      row.querySelector('.icon-val-svg').style.display = select.value === 'svg' ? 'block' : 'none';
      row.querySelector('.icon-val-img-wrap').style.display = select.value === 'image' ? 'block' : 'none';
    }

    function handleFile(input) {
      var reader = new FileReader();
      reader.onload = function(e) {
        var wrap = input.closest('.icon-val-img-wrap');
        wrap.querySelector('.icon-val-img').value = e.target.result;
        var img = wrap.querySelector('img');
        img.src = e.target.result;
        img.style.display = 'block';
      };
      reader.readAsDataURL(input.files[0]);
    }

    function prepareIcons() {
      var result = {};
      var seenKeys = {};
      document.querySelectorAll('#icons-container tr').forEach(row => {
        var key = row.querySelector('.icon-key').value.trim();
        if (!key || seenKeys[key]) return;
        seenKeys[key] = true;

        var type = row.querySelector('.icon-type').value;
        var val = type === 'svg' ? row.querySelector('.icon-val-svg').value.trim() : row.querySelector('.icon-val-img').value;

        if (val) {
          result[key] = val;
        }
      });
      document.getElementById('custom_icons_input').value = JSON.stringify(result);
    }

    // Initial load
    Object.keys(icons).forEach(k => addIconRow(k, icons[k]));

    // Add defaults if empty
    if (Object.keys(icons).length === 0) {
      ['console', 'files', 'databases', 'schedules', 'users', 'backups', 'network', 'startup', 'settings', 'activity'].forEach(k => addIconRow(k, ''));
    }
  </script>
@endsection
