@extends('layouts.admin')
@include('blueprint.admin.template')

@section('title')
  PteroStore
@endsection

@section('content-header')
  <h1>PteroStore<small>Billing, shop, and resource splitter management.</small></h1>
  <ol class="breadcrumb">
    <li><a href="{{ route('admin.index') }}">Admin</a></li>
    <li><a href="{{ route('admin.extensions') }}">Extensions</a></li>
    <li class="active">PteroStore</li>
  </ol>
@endsection

@section('content')
  @yield('blueprint.import')

  @if(session('success'))
    <div class="alert alert-success alert-dismissible">
      <button type="button" class="close" data-dismiss="alert">&times;</button>
      {{ session('success') }}
    </div>
  @endif

  {{-- General Settings --}}
  <form action="{{ route('admin.extensions.{identifier}.index') }}" method="POST">
    @csrf
    @method('PATCH')
    <input type="hidden" name="_action" value="settings">
    <div class="row">
      <div class="col-md-4">
        <div class="box box-primary">
          <div class="box-header with-border">
            <h3 class="box-title">General Settings</h3>
          </div>
          <div class="box-body">
            <div class="form-group">
              <label>
                <input type="checkbox" name="store_enabled" value="1" {{ $store_enabled == '1' ? 'checked' : '' }}>
                Enable Store
              </label>
              <p class="text-muted small">When disabled, the Store navigation link, store page, and all purchase features are hidden from users.</p>
            </div>
            <div class="form-group">
              <label>
                <input type="checkbox" name="splitter_enabled" value="1" {{ $splitter_enabled == '1' ? 'checked' : '' }}>
                Enable Splitter
              </label>
              <p class="text-muted small">When disabled, the Splitter button, split server creation, and splitter tab are hidden from users.</p>
            </div>
            <div class="form-group">
              <label>
                <input type="checkbox" name="billing_change_enabled" value="1" {{ ($billing_change_enabled ?? '1') == '1' ? 'checked' : '' }}>
                Allow Billing Cycle Changes
              </label>
              <p class="text-muted small">When disabled, users cannot change their billing cycle (monthly/weekly/hourly) on existing servers.</p>
            </div>
            <div class="form-group">
              <label>Max Expiration (days)</label>
              <input type="number" name="max_expiration_days" class="form-control" value="{{ $max_expiration_days ?? '0' }}" min="0">
              <p class="text-muted small">Maximum number of days a server can have before expiring. 0 = unlimited. Renewals/extensions that would exceed this limit are blocked.</p>
            </div>
            <hr>
            <div class="form-group">
              <label>Currency Name</label>
              <input type="text" name="currency_name" class="form-control" value="{{ $currency_name }}">
              <p class="text-muted small">Name shown to users (e.g. Coins, Credits, Points).</p>
            </div>
            <div class="form-group">
              <label>Grace Period (minutes)</label>
              <input type="number" name="grace_period" class="form-control" value="{{ $grace_period }}" min="0">
              <p class="text-muted small">After expiry, server is suspended. After grace period, server is deleted. 0 = never delete.</p>
            </div>
            <div class="form-group">
              <label>Allowed Eggs for Splitter (comma-separated IDs)</label>
              <input type="text" name="allowed_eggs" class="form-control" value="{{ $allowed_eggs }}" placeholder="1,2,5">
              <p class="text-muted small">
                Available eggs:
                @foreach($eggs as $egg)
                  <span class="label label-default">{{ $egg->id }}: {{ $egg->name }}</span>
                @endforeach
              </p>
            </div>
            <div class="form-group">
              <label>Splitter Badge Text</label>
              <input type="text" name="splitter_badge_text" class="form-control" value="{{ $splitter_badge_text ?? 'SPLITTER' }}" placeholder="SPLITTER">
              <p class="text-muted small">Text shown as a badge next to split server names in the server list.</p>
            </div>
            <div class="form-group">
              <label>Splitter Badge Color</label>
              <input type="color" name="splitter_badge_color" class="form-control" value="{{ $splitter_badge_color ?? '#3182ce' }}" style="width:80px;padding:2px;height:36px">
              <p class="text-muted small">Background color of the badge.</p>
            </div>
            <div class="form-group">
              <label>Node Configuration for Splitter</label>
              <textarea name="splitter_nodes" class="form-control" rows="6" placeholder='[{"node_id":1,"name":"Node 1","ip":"192.168.1.1","ports":"25565-25600"}]'>{{ $splitter_nodes ?? '' }}</textarea>
              <p class="text-muted small">
                JSON array of node configs. Each entry: <code>{"node_id": 1, "name": "US Node", "ip": "1.2.3.4", "ports": "25565-25600", "max_servers": 10}</code><br>
                Users will be able to choose which node to deploy on. The <code>ports</code> field restricts which allocations to use. <code>max_servers</code> limits how many split servers can be on this node (0 or omit = unlimited). Leave empty to use any node/allocation.
              </p>
              <p class="text-muted small">
                Available nodes:
                @foreach($nodes as $node)
                  <span class="label label-primary">{{ $node->name }} (ID: {{ $node->id }})</span>
                @endforeach
                &mdash;
                Free allocations:
                {{ $allocations->whereNull('server_id')->count() }} / {{ $allocations->count() }} total
              </p>
            </div>
            <div class="form-group">
              <label>Node Configuration for Store</label>
              <textarea name="store_nodes" class="form-control" rows="6" placeholder='[{"node_id":1,"name":"Node 1","ip":"192.168.1.1","ports":"25565-25600"}]'>{{ $store_nodes ?? '' }}</textarea>
              <p class="text-muted small">
                JSON array of node configs for store purchases. Same format as splitter: <code>{"node_id": 1, "name": "US Node", "ip": "1.2.3.4", "ports": "25565-25600", "max_servers": 10}</code><br>
                When set, store purchases will use these node/allocation configurations instead of individual package node settings. <code>max_servers</code> limits total store servers on this node (0 or omit = unlimited). Leave empty to use per-package node settings.
              </p>
              <p class="text-muted small">
                Available nodes:
                @foreach($nodes as $node)
                  <span class="label label-primary">{{ $node->name }} (ID: {{ $node->id }})</span>
                @endforeach
                &mdash;
                Free allocations:
                {{ $allocations->whereNull('server_id')->count() }} / {{ $allocations->count() }} total
              </p>
            </div>
          </div>
        </div>
      </div>

      {{-- Free Resources --}}
      <div class="col-md-4">
        <div class="box box-success">
          <div class="box-header with-border">
            <h3 class="box-title">Free Resources (Splitter)</h3>
          </div>
          <div class="box-body">
            <div class="form-group">
              <label>
                <input type="checkbox" name="free_resources_enabled" value="1" {{ $free_resources_enabled == '1' ? 'checked' : '' }}>
                Enable Free Resources
              </label>
              <p class="text-muted small">Users can claim these resources once. Changes apply globally (existing claims stay, amounts update for new claims).</p>
            </div>
            <div class="form-group">
              <label>Free CPU (%)</label>
              <input type="number" name="free_cpu" class="form-control" value="{{ $free_cpu }}" min="0">
            </div>
            <div class="form-group">
              <label>Free RAM (MB)</label>
              <input type="number" name="free_ram" class="form-control" value="{{ $free_ram }}" min="0">
            </div>
            <div class="form-group">
              <label>Free Disk (MB)</label>
              <input type="number" name="free_disk" class="form-control" value="{{ $free_disk }}" min="0">
            </div>
            <div class="form-group">
              <label>Free Ports</label>
              <input type="number" name="free_ports" class="form-control" value="{{ $free_ports }}" min="0">
            </div>
            <div class="form-group">
              <label>Free Databases</label>
              <input type="number" name="free_databases" class="form-control" value="{{ $free_databases }}" min="0">
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-12">
        <button type="submit" class="btn btn-success btn-sm">Save Settings</button>
      </div>
    </div>
  </form>

  {{-- Categories --}}
  <div class="row">
    <div class="col-md-6">
      <div class="box box-info">
        <div class="box-header with-border">
          <h3 class="box-title">Shop Categories</h3>
        </div>
        <div class="box-body">
          <table class="table table-condensed">
            <thead><tr><th>ID</th><th>Name</th><th>Description</th><th>Sort</th><th></th></tr></thead>
            <tbody>
              @foreach($categories as $cat)
              <tr>
                <td>{{ $cat->id }}</td>
                <td>{{ $cat->name }}</td>
                <td>{{ $cat->description }}</td>
                <td>{{ $cat->sort_order }}</td>
                <td>
                  <form action="{{ route('admin.extensions.{identifier}.index') }}" method="POST" style="display:inline">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="_action" value="delete_category">
                    <input type="hidden" name="cat_id" value="{{ $cat->id }}">
                    <button type="submit" class="btn btn-danger btn-xs" onclick="return confirm('Delete?')">Delete</button>
                  </form>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        <div class="box-footer">
          <form action="{{ route('admin.extensions.{identifier}.index') }}" method="POST" class="form-inline">
            @csrf
            @method('PATCH')
            <input type="hidden" name="_action" value="add_category">
            <input type="text" name="cat_name" class="form-control input-sm" placeholder="Name" required>
            <input type="text" name="cat_description" class="form-control input-sm" placeholder="Description">
            <input type="number" name="cat_sort" class="form-control input-sm" placeholder="Sort" value="0" style="width:70px">
            <button type="submit" class="btn btn-success btn-sm">Add Category</button>
          </form>
        </div>
      </div>
    </div>

    {{-- Packages --}}
    <div class="col-md-6">
      <div class="box box-warning">
        <div class="box-header with-border">
          <h3 class="box-title">Packages</h3>
          <div class="box-tools pull-right">
            <button type="button" class="btn btn-success btn-sm" data-toggle="collapse" data-target="#pkg-add-form">
              <i class="fa fa-plus"></i> New Package
            </button>
          </div>
        </div>
        <div class="box-body" style="max-height:600px;overflow:auto;padding:0">
          @forelse($packages as $pkg)
          @php $pkgNodeIds = array_filter(explode(',', $pkg->node_ids ?? '')); @endphp
          <div class="panel panel-default" style="margin:8px;border-radius:4px">
            <div class="panel-heading" style="padding:8px 12px;cursor:pointer" data-toggle="collapse" data-target="#pkg-edit-{{ $pkg->id }}">
              <div style="display:flex;align-items:center;justify-content:space-between">
                <div>
                  <strong>{{ $pkg->name }}</strong>
                  <span class="label label-info" style="margin-left:6px">{{ optional($categories->firstWhere('id', $pkg->category_id))->name ?? '?' }}</span>
                  @if(!empty($pkgNodeIds))
                    <span class="label label-primary" style="margin-left:4px">{{ count($pkgNodeIds) }} node(s)</span>
                  @endif
                </div>
                <i class="fa fa-chevron-down text-muted"></i>
              </div>
              <small class="text-muted">
                {{ $pkg->cpu }}% &bull; {{ $pkg->ram }}MB &bull; {{ $pkg->disk }}MB &bull;
                {{ $pkg->ports ?? 1 }}P &bull; {{ $pkg->databases ?? 0 }}DB &bull;
                {{ $pkg->price_monthly }}/mo
              </small>
            </div>
            <div class="collapse" id="pkg-edit-{{ $pkg->id }}">
              <div class="panel-body" style="padding:12px">
                <form action="{{ route('admin.extensions.{identifier}.index') }}" method="POST">
                  @csrf @method('PATCH')
                  <input type="hidden" name="_action" value="update_package">
                  <input type="hidden" name="pkg_id" value="{{ $pkg->id }}">

                  {{-- Row 1: Basic Info --}}
                  <fieldset style="border:1px solid #ddd;border-radius:4px;padding:10px 12px;margin-bottom:10px">
                    <legend style="font-size:13px;font-weight:600;width:auto;margin-bottom:5px;border:0;padding:0 4px">Basic Info</legend>
                    <div class="row">
                      <div class="col-sm-4"><label class="small">Name</label><input type="text" name="pkg_name" class="form-control input-sm" value="{{ $pkg->name }}" required></div>
                      <div class="col-sm-4"><label class="small">Category</label>
                        <select name="pkg_category_id" class="form-control input-sm">
                          @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ $pkg->category_id == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                          @endforeach
                        </select>
                      </div>
                      <div class="col-sm-4"><label class="small">Egg</label>
                        <select name="pkg_egg_id" class="form-control input-sm">
                          @foreach($eggs as $egg)
                            <option value="{{ $egg->id }}" {{ ($pkg->egg_id ?? 0) == $egg->id ? 'selected' : '' }}>{{ $egg->name }}</option>
                          @endforeach
                        </select>
                      </div>
                    </div>
                    <div class="row" style="margin-top:5px">
                      <div class="col-sm-6"><label class="small">Description</label><input type="text" name="pkg_description" class="form-control input-sm" value="{{ $pkg->description }}" placeholder="Short description"></div>
                      <div class="col-sm-6"><label class="small">Image URL</label><input type="text" name="pkg_image" class="form-control input-sm" value="{{ $pkg->image }}" placeholder="https://..."></div>
                    </div>
                  </fieldset>

                  {{-- Row 2: Resources --}}
                  <fieldset style="border:1px solid #ddd;border-radius:4px;padding:10px 12px;margin-bottom:10px">
                    <legend style="font-size:13px;font-weight:600;width:auto;margin-bottom:5px;border:0;padding:0 4px">Resources</legend>
                    <div class="row">
                      <div class="col-sm-2"><label class="small">CPU %</label><input type="number" name="pkg_cpu" class="form-control input-sm" value="{{ $pkg->cpu }}" min="1"></div>
                      <div class="col-sm-2"><label class="small">RAM (MB)</label><input type="number" name="pkg_ram" class="form-control input-sm" value="{{ $pkg->ram }}" min="1"></div>
                      <div class="col-sm-3"><label class="small">Disk (MB)</label><input type="number" name="pkg_disk" class="form-control input-sm" value="{{ $pkg->disk }}" min="1"></div>
                      <div class="col-sm-2"><label class="small">Ports</label><input type="number" name="pkg_ports" class="form-control input-sm" value="{{ $pkg->ports ?? 1 }}" min="0"></div>
                      <div class="col-sm-2"><label class="small">Databases</label><input type="number" name="pkg_databases" class="form-control input-sm" value="{{ $pkg->databases ?? 0 }}" min="0"></div>
                    </div>
                  </fieldset>

                  {{-- Row 3: Pricing --}}
                  <fieldset style="border:1px solid #ddd;border-radius:4px;padding:10px 12px;margin-bottom:10px">
                    <legend style="font-size:13px;font-weight:600;width:auto;margin-bottom:5px;border:0;padding:0 4px">Pricing ({{ $currency_name }})</legend>
                    <div class="row">
                      <div class="col-sm-4"><label class="small">Monthly</label><input type="text" name="pkg_price_monthly" class="form-control input-sm" value="{{ $pkg->price_monthly }}"></div>
                      <div class="col-sm-4"><label class="small">Weekly</label><input type="text" name="pkg_price_weekly" class="form-control input-sm" value="{{ $pkg->price_weekly }}"></div>
                      <div class="col-sm-4"><label class="small">Hourly</label><input type="text" name="pkg_price_hourly" class="form-control input-sm" value="{{ $pkg->price_hourly }}"></div>
                    </div>
                  </fieldset>

                  {{-- Row 4: Stock --}}
                  <fieldset style="border:1px solid #ddd;border-radius:4px;padding:10px 12px;margin-bottom:10px">
                    <legend style="font-size:13px;font-weight:600;width:auto;margin-bottom:5px;border:0;padding:0 4px">Stock</legend>
                    <div class="row">
                      <div class="col-sm-4">
                        <label class="small">Stock Limit</label>
                        <input type="number" name="pkg_stock" class="form-control input-sm" value="{{ $pkg->stock ?? 0 }}" min="0">
                        <p class="text-muted small" style="margin-top:2px">Max servers from this package. 0 = unlimited.</p>
                      </div>
                      <div class="col-sm-8">
                        @php
                            $soldCount = \Illuminate\Support\Facades\DB::table('pterostore_server_expiry')->where('package_id', $pkg->id)->count();
                        @endphp
                        <label class="small">Current Usage</label>
                        <p class="text-muted" style="margin-top:6px">
                          {{ $soldCount }} server(s) using this package
                          @if(($pkg->stock ?? 0) > 0)
                            &mdash; <strong>{{ max(0, $pkg->stock - $soldCount) }}</strong> remaining
                          @endif
                        </p>
                      </div>
                    </div>
                  </fieldset>

                  {{-- Row 5: Deploy Nodes --}}
                  <fieldset style="border:1px solid #ddd;border-radius:4px;padding:10px 12px;margin-bottom:10px">
                    <legend style="font-size:13px;font-weight:600;width:auto;margin-bottom:5px;border:0;padding:0 4px">Deploy Nodes</legend>
                    <p class="text-muted small" style="margin:0 0 6px">Select which nodes this package can deploy to. Leave all unchecked to allow any node.</p>
                    <div style="display:flex;flex-wrap:wrap;gap:6px 16px">
                      @foreach($nodes as $node)
                        <label style="font-weight:normal;margin:0;cursor:pointer;display:flex;align-items:center;gap:4px">
                          <input type="checkbox" name="pkg_node_ids[]" value="{{ $node->id }}" {{ in_array((string)$node->id, $pkgNodeIds) ? 'checked' : '' }}>
                          <span class="small">{{ $node->name }} <span class="text-muted">(ID: {{ $node->id }})</span></span>
                        </label>
                      @endforeach
                      @if($nodes->isEmpty())
                        <span class="text-muted small">No nodes found in Pterodactyl.</span>
                      @endif
                    </div>
                  </fieldset>

                  <div style="display:flex;justify-content:space-between;align-items:center">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-save"></i> Save Changes</button>
                </form>
                <form action="{{ route('admin.extensions.{identifier}.index') }}" method="POST" style="display:inline" onsubmit="return confirm('Delete this package permanently?');">
                  @csrf @method('PATCH')
                  <input type="hidden" name="_action" value="delete_package">
                  <input type="hidden" name="pkg_id" value="{{ $pkg->id }}">
                  <button type="submit" class="btn btn-danger btn-sm"><i class="fa fa-trash"></i> Delete</button>
                </form>
                  </div>
              </div>
            </div>
          </div>
          @empty
          <p class="text-center text-muted" style="padding:20px">No packages created yet. Click "New Package" to get started.</p>
          @endforelse
        </div>

        {{-- Add new package --}}
        <div class="collapse" id="pkg-add-form">
          <div class="box-footer">
            <h4 style="margin-top:0"><i class="fa fa-plus-circle"></i> Create New Package</h4>
            <form action="{{ route('admin.extensions.{identifier}.index') }}" method="POST">
              @csrf @method('PATCH')
              <input type="hidden" name="_action" value="add_package">

              <fieldset style="border:1px solid #ddd;border-radius:4px;padding:10px 12px;margin-bottom:10px">
                <legend style="font-size:13px;font-weight:600;width:auto;margin-bottom:5px;border:0;padding:0 4px">Basic Info</legend>
                <div class="row">
                  <div class="col-sm-4"><label class="small">Package Name</label><input type="text" name="pkg_name" class="form-control input-sm" placeholder="e.g. Starter Plan" required></div>
                  <div class="col-sm-4"><label class="small">Category</label>
                    <select name="pkg_category_id" class="form-control input-sm" required>
                      @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                      @endforeach
                      @if($categories->isEmpty())
                        <option value="" disabled>Create a category first</option>
                      @endif
                    </select>
                  </div>
                  <div class="col-sm-4"><label class="small">Egg</label>
                    <select name="pkg_egg_id" class="form-control input-sm">
                      <option value="">-- Select Egg --</option>
                      @foreach($eggs as $egg)
                        <option value="{{ $egg->id }}">{{ $egg->name }}</option>
                      @endforeach
                    </select>
                  </div>
                </div>
                <div class="row" style="margin-top:5px">
                  <div class="col-sm-6"><label class="small">Description</label><input type="text" name="pkg_description" class="form-control input-sm" placeholder="Short description (optional)"></div>
                  <div class="col-sm-6"><label class="small">Image URL</label><input type="text" name="pkg_image" class="form-control input-sm" placeholder="https://... (optional)"></div>
                </div>
              </fieldset>

              <fieldset style="border:1px solid #ddd;border-radius:4px;padding:10px 12px;margin-bottom:10px">
                <legend style="font-size:13px;font-weight:600;width:auto;margin-bottom:5px;border:0;padding:0 4px">Resources</legend>
                <div class="row">
                  <div class="col-sm-2"><label class="small">CPU %</label><input type="number" name="pkg_cpu" class="form-control input-sm" value="100" min="1"></div>
                  <div class="col-sm-2"><label class="small">RAM (MB)</label><input type="number" name="pkg_ram" class="form-control input-sm" value="1024" min="1"></div>
                  <div class="col-sm-3"><label class="small">Disk (MB)</label><input type="number" name="pkg_disk" class="form-control input-sm" value="5120" min="1"></div>
                  <div class="col-sm-2"><label class="small">Ports</label><input type="number" name="pkg_ports" class="form-control input-sm" value="1" min="0"></div>
                  <div class="col-sm-2"><label class="small">Databases</label><input type="number" name="pkg_databases" class="form-control input-sm" value="0" min="0"></div>
                </div>
              </fieldset>

              <fieldset style="border:1px solid #ddd;border-radius:4px;padding:10px 12px;margin-bottom:10px">
                <legend style="font-size:13px;font-weight:600;width:auto;margin-bottom:5px;border:0;padding:0 4px">Pricing ({{ $currency_name }})</legend>
                <div class="row">
                  <div class="col-sm-4"><label class="small">Monthly</label><input type="text" name="pkg_price_monthly" class="form-control input-sm" value="100"></div>
                  <div class="col-sm-4"><label class="small">Weekly</label><input type="text" name="pkg_price_weekly" class="form-control input-sm" value="30"></div>
                  <div class="col-sm-4"><label class="small">Hourly</label><input type="text" name="pkg_price_hourly" class="form-control input-sm" value="5"></div>
                </div>
              </fieldset>

              <fieldset style="border:1px solid #ddd;border-radius:4px;padding:10px 12px;margin-bottom:10px">
                <legend style="font-size:13px;font-weight:600;width:auto;margin-bottom:5px;border:0;padding:0 4px">Stock</legend>
                <div class="row">
                  <div class="col-sm-4">
                    <label class="small">Stock Limit</label>
                    <input type="number" name="pkg_stock" class="form-control input-sm" value="0" min="0">
                    <p class="text-muted small" style="margin-top:2px">Max servers from this package. 0 = unlimited.</p>
                  </div>
                </div>
              </fieldset>

              <fieldset style="border:1px solid #ddd;border-radius:4px;padding:10px 12px;margin-bottom:10px">
                <legend style="font-size:13px;font-weight:600;width:auto;margin-bottom:5px;border:0;padding:0 4px">Deploy Nodes</legend>
                <p class="text-muted small" style="margin:0 0 6px">Select which nodes this package can deploy to. Leave all unchecked to allow any node.</p>
                <div style="display:flex;flex-wrap:wrap;gap:6px 16px">
                  @foreach($nodes as $node)
                    <label style="font-weight:normal;margin:0;cursor:pointer;display:flex;align-items:center;gap:4px">
                      <input type="checkbox" name="pkg_node_ids[]" value="{{ $node->id }}">
                      <span class="small">{{ $node->name }} <span class="text-muted">(ID: {{ $node->id }})</span></span>
                    </label>
                  @endforeach
                  @if($nodes->isEmpty())
                    <span class="text-muted small">No nodes found in Pterodactyl.</span>
                  @endif
                </div>
              </fieldset>

              <button type="submit" class="btn btn-success btn-sm"><i class="fa fa-check"></i> Create Package</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- User Balances --}}
  <div class="row">
    <div class="col-md-6">
      <div class="box box-success">
        <div class="box-header with-border">
          <h3 class="box-title">User Balances</h3>
        </div>
        <div class="box-body" style="max-height:300px;overflow:auto">
          <table class="table table-condensed">
            <thead><tr><th>User</th><th>Balance</th></tr></thead>
            <tbody>
              @foreach($users as $u)
                @php $bal = $balances->get($u->id); @endphp
                <tr>
                  <td>{{ $u->username }} ({{ $u->email }})</td>
                  <td>{{ $bal ? number_format($bal->balance, 2) : '0.00' }} {{ $currency_name }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        <div class="box-footer">
          <form action="{{ route('admin.extensions.{identifier}.index') }}" method="POST" class="form-inline">
            @csrf
            @method('PATCH')
            <input type="hidden" name="_action" value="update_balance">
            <select name="user_id" class="form-control input-sm" required>
              @foreach($users as $u)
                <option value="{{ $u->id }}">{{ $u->username }}</option>
              @endforeach
            </select>
            <select name="balance_action" class="form-control input-sm">
              <option value="add">Add</option>
              <option value="remove">Remove</option>
              <option value="set">Set to</option>
            </select>
            <input type="number" name="balance_amount" class="form-control input-sm" placeholder="Amount" step="0.01" required style="width:100px">
            <button type="submit" class="btn btn-success btn-sm">Update</button>
          </form>
        </div>
      </div>
    </div>

    {{-- Resource Splitter --}}
    <div class="col-md-6">
      <div class="box box-default">
        <div class="box-header with-border">
          <h3 class="box-title">Resource Splitter - User Allocations & Node Access</h3>
        </div>
        <div class="box-body" style="max-height:300px;overflow:auto">
          <table class="table table-condensed table-striped">
            <thead><tr><th>User</th><th>CPU</th><th>RAM</th><th>Disk</th><th>Ports</th><th>DBs</th><th>Limit</th><th>Node Restriction</th></tr></thead>
            <tbody>
              @foreach($users as $u)
                @php $res = $resourceSplits->get($u->id); @endphp
                @if($res)
                <tr>
                  <td><strong>{{ $u->username }}</strong></td>
                  <td>{{ $res->cpu }}%</td>
                  <td>{{ $res->ram }} MB</td>
                  <td>{{ $res->disk }} MB</td>
                  <td>{{ $res->ports }}</td>
                  <td>{{ $res->databases }}</td>
                  <td>{{ $res->server_limit }}</td>
                  <td>
                    @if(!empty($res->node_ids))
                      <span class="label {{ ($res->node_mode ?? 'whitelist') === 'whitelist' ? 'label-success' : 'label-danger' }}">
                        {{ strtoupper($res->node_mode ?? 'whitelist') }}: {{ $res->node_ids }}
                      </span>
                    @else
                      <span class="label label-default">All Nodes</span>
                    @endif
                  </td>
                </tr>
                @endif
              @endforeach
            </tbody>
          </table>
        </div>
        <div class="box-footer">
          <form action="{{ route('admin.extensions.{identifier}.index') }}" method="POST">
            @csrf
            @method('PATCH')
            <input type="hidden" name="_action" value="update_resources">
            <div class="row" style="margin-bottom:10px;">
              <div class="col-sm-3">
                <label class="small text-muted">User</label>
                <select name="res_user_id" class="form-control input-sm" required>
                  @foreach($users as $u)
                    <option value="{{ $u->id }}">{{ $u->username }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-sm-2"><label class="small text-muted">CPU%</label><input type="number" name="res_cpu" class="form-control input-sm" placeholder="CPU%" value="100"></div>
              <div class="col-sm-2"><label class="small text-muted">RAM (MB)</label><input type="number" name="res_ram" class="form-control input-sm" placeholder="RAM" value="1024"></div>
              <div class="col-sm-2"><label class="small text-muted">Disk (MB)</label><input type="number" name="res_disk" class="form-control input-sm" placeholder="Disk" value="5120"></div>
              <div class="col-sm-1"><label class="small text-muted">Ports</label><input type="number" name="res_ports" class="form-control input-sm" placeholder="Ports" value="1"></div>
              <div class="col-sm-1"><label class="small text-muted">DBs</label><input type="number" name="res_databases" class="form-control input-sm" placeholder="DBs" value="0"></div>
              <div class="col-sm-1"><label class="small text-muted">Limit</label><input type="number" name="res_server_limit" class="form-control input-sm" placeholder="Limit" value="1"></div>
            </div>
            <div class="row">
              <div class="col-sm-3">
                <label class="small text-muted">Node Mode</label>
                <select name="res_node_mode" class="form-control input-sm">
                  <option value="whitelist">Whitelist (Allowed Nodes)</option>
                  <option value="blacklist">Blacklist (Blocked Nodes)</option>
                </select>
              </div>
              <div class="col-sm-7">
                <label class="small text-muted">Node IDs (comma-separated, e.g., 1,2,5)</label>
                <input type="text" name="res_node_ids" class="form-control input-sm" placeholder="e.g. 1,2 (leave blank for all nodes)">
              </div>
              <div class="col-sm-2" style="margin-top:21px;">
                <button type="submit" class="btn btn-primary btn-sm btn-block">Set Allocation</button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  {{-- Coupons --}}
  <div class="row">
    <div class="col-xs-12">
      <div class="box box-info">
        <div class="box-header with-border">
          <h3 class="box-title">Coupons</h3>
          <span class="label label-default pull-right">{{ $coupons->count() }} coupon(s)</span>
        </div>
        <div class="box-body" style="max-height:400px;overflow:auto">
          <table class="table table-condensed table-striped">
            <thead><tr><th>Code</th><th>Type</th><th>Value</th><th>Usage</th><th>Used</th><th>Max</th><th>Packages</th><th>Status</th><th></th></tr></thead>
            <tbody>
              @foreach($coupons as $c)
              <tr>
                <td><code>{{ $c->code }}</code></td>
                <td>{{ $c->type === 'percent' ? 'Percent' : 'Fixed' }}</td>
                <td>{{ $c->value }}{{ $c->type === 'percent' ? '%' : ' ' . $currency_name }}</td>
                <td>{{ ucfirst($c->usage_type) }}</td>
                <td>{{ $c->times_used }}</td>
                <td>{{ $c->usage_type === 'unlimited' ? '∞' : $c->max_uses }}</td>
                <td>{{ $c->package_ids ?: 'All' }}</td>
                <td><span class="label {{ $c->enabled ? 'label-success' : 'label-default' }}">{{ $c->enabled ? 'Active' : 'Disabled' }}</span></td>
                <td>
                  <form action="{{ route('admin.extensions.{identifier}.index') }}" method="POST" style="display:inline" onsubmit="return confirm('Delete this coupon?')">
                    @csrf @method('PATCH')
                    <input type="hidden" name="_action" value="delete_coupon">
                    <input type="hidden" name="coupon_id" value="{{ $c->id }}">
                    <button type="submit" class="btn btn-danger btn-xs"><i class="fa fa-trash"></i></button>
                  </form>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        <div class="box-footer">
          <form action="{{ route('admin.extensions.{identifier}.index') }}" method="POST" class="form-inline">
            @csrf @method('PATCH')
            <input type="hidden" name="_action" value="add_coupon">
            <input type="text" name="coupon_code" class="form-control input-sm" placeholder="CODE" required style="width:100px;text-transform:uppercase">
            <select name="coupon_type" class="form-control input-sm">
              <option value="percent">% Off</option>
              <option value="fixed">Fixed Amount Off</option>
            </select>
            <input type="number" name="coupon_value" class="form-control input-sm" placeholder="Value" step="0.01" min="0" required style="width:80px">
            <select name="coupon_usage_type" class="form-control input-sm">
              <option value="single">Single Use</option>
              <option value="multi">Multi Use</option>
              <option value="unlimited">Unlimited</option>
            </select>
            <input type="number" name="coupon_max_uses" class="form-control input-sm" placeholder="Max uses" min="1" value="1" style="width:80px">
            <input type="text" name="coupon_package_ids" class="form-control input-sm" placeholder="Package IDs (e.g. 1,3) or empty=all" style="width:180px">
            <button type="submit" class="btn btn-success btn-sm"><i class="fa fa-plus"></i> Add Coupon</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  {{-- Server Expirations --}}
  <div class="row">
    <div class="col-xs-12">
      <div class="box box-danger">
        <div class="box-header with-border">
          <h3 class="box-title">Purchased Servers</h3>
          <span class="label label-default pull-right">{{ $expirations->count() }} server(s)</span>
        </div>
        <div class="box-body" style="max-height:500px;overflow:auto">
          <table class="table table-condensed table-striped">
            <thead><tr><th>Server</th><th>User</th><th>Package</th><th>Cycle</th><th>Cost</th><th>Expires</th><th>Auto-Renew</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
              @foreach($expirations as $exp)
              @php
                $pkg = $packages->firstWhere('id', $exp->package_id);
                $isExpired = \Carbon\Carbon::parse($exp->expires_at)->isPast();
              @endphp
              <tr class="{{ $exp->suspended ? 'danger' : ($isExpired ? 'warning' : '') }}">
                <td>
                  {{ $exp->server_name ?? 'Deleted' }} (#{{ $exp->server_id }})
                  @if(!$exp->server_name)
                    <span class="label label-default">orphan</span>
                  @endif
                </td>
                <td>{{ optional($users->firstWhere('id', $exp->user_id))->username ?? $exp->user_id }}</td>
                <td>{{ $pkg->name ?? 'Unknown' }}</td>
                <td>{{ ucfirst($exp->billing_cycle) }}</td>
                <td>{{ $exp->cost }} {{ $currency_name }}</td>
                <td>
                  {{ \Carbon\Carbon::parse($exp->expires_at)->format('M d, Y H:i') }}
                  @if($isExpired)
                    <span class="label label-danger">expired</span>
                  @endif
                </td>
                <td>
                  <form action="{{ route('admin.extensions.{identifier}.index') }}" method="POST" style="display:inline">
                    @csrf @method('PATCH')
                    <input type="hidden" name="_action" value="toggle_auto_renew">
                    <input type="hidden" name="expiry_id" value="{{ $exp->id }}">
                    <button type="submit" class="btn btn-xs {{ ($exp->auto_renew ?? false) ? 'btn-success' : 'btn-default' }}">
                      {{ ($exp->auto_renew ?? false) ? 'ON' : 'OFF' }}
                    </button>
                  </form>
                </td>
                <td>
                  @if($exp->suspended)
                    <span class="label label-danger">Suspended</span>
                  @else
                    <span class="label label-success">Active</span>
                  @endif
                </td>
                <td style="white-space:nowrap">
                  <form action="{{ route('admin.extensions.{identifier}.index') }}" method="POST" class="form-inline" style="display:inline">
                    @csrf @method('PATCH')
                    <input type="hidden" name="_action" value="update_expiry">
                    <input type="hidden" name="expiry_id" value="{{ $exp->id }}">
                    <select name="expiry_action" class="form-control input-sm" style="width:auto">
                      <option value="add_time">+Time</option>
                      <option value="remove_time">-Time</option>
                      <option value="change_cost">Cost</option>
                    </select>
                    <input type="number" name="expiry_minutes" class="form-control input-sm" placeholder="Min" style="width:70px">
                    <input type="text" name="expiry_cost" class="form-control input-sm" placeholder="Cost" style="width:70px">
                    <button type="submit" class="btn btn-warning btn-xs">Apply</button>
                  </form>
                  <form action="{{ route('admin.extensions.{identifier}.index') }}" method="POST" style="display:inline;margin-left:4px" onsubmit="return confirm('Delete this server and remove the billing record? The Pterodactyl server will also be deleted.')">
                    @csrf @method('PATCH')
                    <input type="hidden" name="_action" value="delete_purchased_server">
                    <input type="hidden" name="expiry_id" value="{{ $exp->id }}">
                    <input type="hidden" name="server_id" value="{{ $exp->server_id }}">
                    <button type="submit" class="btn btn-danger btn-xs"><i class="fa fa-trash"></i></button>
                  </form>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
@endsection
