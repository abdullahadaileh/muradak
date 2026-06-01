<?php
// ============================================================
//  Muradak Admin — Dashboard
// ============================================================
session_start();
if (empty($_SESSION['admin_id'])) {
    header('Location: index.html'); exit;
}
require_once __DIR__ . '/../config/database.php';
$db = getDB();

// ─── Quick stats ─────────────────────────────────────────────
$stats = [
  'orders_today'   => $db->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at)=CURDATE()")->fetchColumn(),
  'orders_pending' => $db->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn(),
  'orders_total'   => $db->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
  'revenue_today'  => $db->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE DATE(created_at)=CURDATE() AND status!='cancelled'")->fetchColumn(),
  'revenue_total'  => $db->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status!='cancelled'")->fetchColumn(),
  'products_count' => $db->query("SELECT COUNT(*) FROM products WHERE is_active=1")->fetchColumn(),
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Muradak Admin — Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
:root {
  --brown:    #8B4513; --brown-mid:#A0522D; --brown-lt:#CD853F;
  --cream:    #FDF6EF; --cream-dk:#F5E8D8;
  --text:     #2C1A0E; --muted:#7A6050;
  --green:    #2E7D32; --red:#C62828; --blue:#1565C0; --orange:#E65100;
  --sidebar:  #2C1A0E;
}
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Tajawal',sans-serif;background:var(--cream);color:var(--text);display:flex;min-height:100vh;}

/* Sidebar */
.sidebar{
  width:240px;background:var(--sidebar);color:#fff;display:flex;flex-direction:column;
  position:fixed;top:0;bottom:0;right:0;z-index:50;padding:0;
}
.sidebar-logo{
  padding:24px 20px;border-bottom:1px solid rgba(255,255,255,.1);
  text-align:center;
}
.sidebar-logo .emoji{font-size:36px;display:block;margin-bottom:6px;}
.sidebar-logo h1{font-size:18px;font-weight:800;color:var(--brown-lt);}
.sidebar-logo p{font-size:11px;opacity:.6;margin-top:2px;}
nav{flex:1;padding:12px 0;}
.nav-item{
  display:flex;align-items:center;gap:12px;padding:13px 20px;
  color:rgba(255,255,255,.7);cursor:pointer;border:none;background:none;
  font-family:inherit;font-size:15px;font-weight:600;width:100%;text-align:right;
  transition:all .2s;text-decoration:none;
}
.nav-item:hover,.nav-item.active{background:rgba(255,255,255,.1);color:#fff;}
.nav-item.active{border-right:3px solid var(--brown-lt);}
.nav-icon{font-size:18px;width:24px;text-align:center;}
.sidebar-foot{padding:16px 20px;border-top:1px solid rgba(255,255,255,.1);}
.logout-btn{
  display:block;width:100%;padding:10px;text-align:center;
  background:rgba(255,255,255,.08);color:rgba(255,255,255,.7);
  border:1px solid rgba(255,255,255,.15);border-radius:8px;
  font-family:inherit;font-size:14px;cursor:pointer;text-decoration:none;
  transition:all .2s;
}
.logout-btn:hover{background:rgba(255,255,255,.18);color:#fff;}

/* Main */
.main{margin-right:240px;flex:1;display:flex;flex-direction:column;min-height:100vh;}
.topbar{
  background:#fff;padding:16px 28px;border-bottom:2px solid var(--cream-dk);
  display:flex;align-items:center;justify-content:space-between;
}
.topbar h2{font-size:20px;font-weight:800;color:var(--brown);}
.topbar .time{color:var(--muted);font-size:14px;}
.content{padding:28px;flex:1;}

/* Stats */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px;margin-bottom:32px;}
.stat-card{
  background:#fff;border-radius:14px;padding:20px;
  box-shadow:0 2px 12px rgba(139,69,19,.08);
  border-top:4px solid var(--brown-lt);
}
.stat-label{font-size:13px;color:var(--muted);margin-bottom:6px;}
.stat-value{font-size:28px;font-weight:800;color:var(--brown);}
.stat-icon{font-size:28px;float:left;}

/* Table */
.card{background:#fff;border-radius:14px;padding:24px;box-shadow:0 2px 12px rgba(139,69,19,.08);margin-bottom:24px;}
.card-title{font-size:18px;font-weight:800;color:var(--brown);margin-bottom:20px;display:flex;align-items:center;gap:8px;}
table{width:100%;border-collapse:collapse;}
th{background:var(--cream-dk);padding:12px 14px;text-align:right;font-size:13px;color:var(--muted);font-weight:700;}
td{padding:12px 14px;border-bottom:1px solid var(--cream-dk);font-size:14px;vertical-align:middle;}
tr:last-child td{border-bottom:none;}
tr:hover td{background:#fdf9f5;}

/* Status badges */
.badge{display:inline-flex;align-items:center;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700;gap:4px;}
.badge-pending    {background:#FFF8E1;color:#F57F17;}
.badge-confirmed  {background:#E8F5E9;color:#2E7D32;}
.badge-processing {background:#E3F2FD;color:#1565C0;}
.badge-out_for_delivery{background:#F3E5F5;color:#6A1B9A;}
.badge-delivered  {background:#E8F5E9;color:#1B5E20;}
.badge-cancelled  {background:#FFEBEE;color:#C62828;}

/* Buttons */
.btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;font-family:inherit;font-size:13px;font-weight:700;cursor:pointer;border:none;transition:all .2s;}
.btn-primary{background:var(--brown);color:#fff;} .btn-primary:hover{background:var(--brown-mid);}
.btn-sm{padding:5px 12px;font-size:12px;}
.btn-green{background:var(--green);color:#fff;}
.btn-red{background:var(--red);color:#fff;}
.btn-orange{background:var(--orange);color:#fff;}

/* Filter bar */
.filter-bar{display:flex;gap:10px;margin-bottom:18px;flex-wrap:wrap;align-items:center;}
.filter-bar select,.filter-bar input{
  padding:9px 14px;border:2px solid var(--cream-dk);border-radius:10px;
  font-family:inherit;font-size:14px;outline:none;background:var(--cream);
}
.filter-bar select:focus,.filter-bar input:focus{border-color:var(--brown-lt);}

/* Order detail modal */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:200;display:flex;align-items:center;justify-content:center;padding:20px;opacity:0;pointer-events:none;transition:opacity .2s;}
.modal-overlay.open{opacity:1;pointer-events:all;}
.modal{background:#fff;border-radius:16px;width:100%;max-width:600px;max-height:90vh;overflow-y:auto;padding:28px;transform:scale(.95);transition:transform .2s;}
.modal-overlay.open .modal{transform:scale(1);}
.modal h3{font-size:20px;font-weight:800;color:var(--brown);margin-bottom:20px;}
.detail-row{display:flex;gap:8px;margin-bottom:8px;font-size:14px;}
.detail-label{font-weight:700;color:var(--muted);min-width:140px;}

/* Sections */
.section-hidden{display:none;}

/* Responsive */
@media(max-width:768px){.sidebar{width:200px;} .main{margin-right:200px;}}
</style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
  <div class="sidebar-logo">
    <span class="emoji">🛒</span>
    <h1>Muradak Admin</h1>
    <p>Control Panel</p>
  </div>
  <nav>
    <button class="nav-item active" onclick="showSection('orders')" id="nav-orders">
      <span class="nav-icon">📋</span> الطلبات
    </button>
    <button class="nav-item" onclick="showSection('products')" id="nav-products">
      <span class="nav-icon">📦</span> المنتجات
    </button>
    <button class="nav-item" onclick="showSection('stats')" id="nav-stats">
      <span class="nav-icon">📊</span> الإحصائيات
    </button>
    <button class="nav-item" onclick="showSection('settings')" id="nav-settings">
      <span class="nav-icon">⚙️</span> الإعدادات
    </button>
  </nav>
  <div class="sidebar-foot">
    <a href="logout.php" class="logout-btn">🚪 تسجيل الخروج</a>
  </div>
</aside>

<!-- MAIN -->
<div class="main">
  <div class="topbar">
    <h2 id="pageTitle">📋 إدارة الطلبات</h2>
    <span class="time" id="clock"></span>
  </div>
  <div class="content">

    <!-- STATS CARDS (always visible) -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon">📅</div>
        <div class="stat-label">طلبات اليوم</div>
        <div class="stat-value"><?= $stats['orders_today'] ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">⏳</div>
        <div class="stat-label">طلبات معلقة</div>
        <div class="stat-value" id="pendingCount"><?= $stats['orders_pending'] ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">🛒</div>
        <div class="stat-label">إجمالي الطلبات</div>
        <div class="stat-value"><?= $stats['orders_total'] ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">💰</div>
        <div class="stat-label">إيراد اليوم (KD)</div>
        <div class="stat-value">KD <?= number_format($stats['revenue_today'],3) ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">📈</div>
        <div class="stat-label">إجمالي الإيراد</div>
        <div class="stat-value">KD <?= number_format($stats['revenue_total'],3) ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">🏪</div>
        <div class="stat-label">المنتجات النشطة</div>
        <div class="stat-value"><?= $stats['products_count'] ?></div>
      </div>
    </div>

    <!-- ORDERS SECTION -->
    <div id="section-orders">
      <div class="card">
        <div class="card-title">📋 جميع الطلبات</div>
        <div class="filter-bar">
          <select id="statusFilter" onchange="loadOrders()">
            <option value="">كل الحالات</option>
            <option value="pending">معلق</option>
            <option value="confirmed">مؤكد</option>
            <option value="processing">قيد التجهيز</option>
            <option value="out_for_delivery">في الطريق</option>
            <option value="delivered">تم التوصيل</option>
            <option value="cancelled">ملغي</option>
          </select>
          <input type="text" id="orderSearch" placeholder="بحث باسم أو رقم طلب..." oninput="loadOrders()">
          <button class="btn btn-primary" onclick="loadOrders()">🔄 تحديث</button>
        </div>
        <div id="ordersTable"><p style="color:#888;text-align:center;padding:30px;">جاري التحميل...</p></div>
      </div>
    </div>

    <!-- PRODUCTS SECTION -->
    <div id="section-products" class="section-hidden">
      <div class="card">
        <div class="card-title">📦 إدارة المنتجات
          <button class="btn btn-primary btn-sm" style="margin-right:auto;" onclick="openProductForm()">+ إضافة منتج</button>
        </div>
        <div id="productsTable"><p style="color:#888;text-align:center;padding:30px;">جاري التحميل...</p></div>
      </div>
    </div>

    <!-- STATS SECTION -->
    <div id="section-stats" class="section-hidden">
      <div class="card">
        <div class="card-title">📊 أحدث الطلبات</div>
        <div id="recentOrders"></div>
      </div>
    </div>

    <!-- SETTINGS SECTION -->
    <div id="section-settings" class="section-hidden">
      <div class="card">
        <div class="card-title">⚙️ إعدادات المتجر</div>
        <div id="settingsForm"><p style="color:#888;text-align:center;padding:30px;">جاري التحميل...</p></div>
      </div>
    </div>

  </div>
</div>

<!-- ORDER DETAIL MODAL -->
<div class="modal-overlay" id="orderModal">
  <div class="modal" id="orderModalContent"></div>
</div>

<!-- PRODUCT FORM MODAL -->
<div class="modal-overlay" id="productModal">
  <div class="modal" id="productModalContent"></div>
</div>

<script>
// Clock
setInterval(() => {
  document.getElementById('clock').textContent = new Date().toLocaleString('ar-KW', {
    weekday:'long', hour:'2-digit', minute:'2-digit'
  });
}, 1000);

// Section switching
const sections = ['orders','products','stats','settings'];
function showSection(name) {
  sections.forEach(s => {
    document.getElementById('section-'+s).classList.toggle('section-hidden', s !== name);
    document.getElementById('nav-'+s).classList.toggle('active', s === name);
  });
  const titles = { orders:'📋 إدارة الطلبات', products:'📦 المنتجات', stats:'📊 الإحصائيات', settings:'⚙️ الإعدادات' };
  document.getElementById('pageTitle').textContent = titles[name];
  if (name === 'products') loadProducts();
  if (name === 'stats')    loadRecent();
  if (name === 'settings') loadSettings();
}

// ─── ORDERS ─────────────────────────────────────────────────
const STATUS_LABELS = {
  pending:'⏳ معلق', confirmed:'✅ مؤكد', processing:'⚙️ قيد التجهيز',
  out_for_delivery:'🚗 في الطريق', delivered:'📦 تم التوصيل', cancelled:'❌ ملغي'
};
const STATUS_FLOW = ['pending','confirmed','processing','out_for_delivery','delivered'];

async function loadOrders() {
  const status = document.getElementById('statusFilter').value;
  const search = document.getElementById('orderSearch').value;
  const qs = new URLSearchParams({ action:'admin_orders', status, search }).toString();
  const data = await (await fetch(`admin_api.php?${qs}`)).json();
  const el = document.getElementById('ordersTable');
  if (!data.length) { el.innerHTML = '<p style="color:#888;text-align:center;padding:30px;">لا توجد طلبات</p>'; return; }
  el.innerHTML = `<table>
    <thead><tr>
      <th>رقم الطلب</th><th>العميل</th><th>الهاتف</th><th>الإجمالي</th><th>الحالة</th><th>التاريخ</th><th>إجراء</th>
    </tr></thead>
    <tbody>${data.map(o => `
      <tr>
        <td><strong>${o.order_number}</strong></td>
        <td>${o.customer_name}</td>
        <td dir="ltr">${o.customer_phone}</td>
        <td>KD ${Number(o.total).toFixed(3)}</td>
        <td><span class="badge badge-${o.status}">${STATUS_LABELS[o.status]||o.status}</span></td>
        <td style="font-size:12px;color:#888;">${new Date(o.created_at).toLocaleDateString('ar-KW')}</td>
        <td>
          <button class="btn btn-primary btn-sm" onclick="viewOrder(${o.id})">عرض</button>
          ${STATUS_FLOW.indexOf(o.status) < STATUS_FLOW.length-1 ? `<button class="btn btn-green btn-sm" onclick="advanceStatus(${o.id},'${nextStatus(o.status)}')">التالي ▶</button>` : ''}
          ${o.status !== 'cancelled' && o.status !== 'delivered' ? `<button class="btn btn-red btn-sm" onclick="cancelOrder(${o.id})">إلغاء</button>` : ''}
        </td>
      </tr>`).join('')}
    </tbody>
  </table>`;
}

function nextStatus(s) {
  const i = STATUS_FLOW.indexOf(s);
  return STATUS_FLOW[Math.min(i+1, STATUS_FLOW.length-1)];
}

async function advanceStatus(id, status) {
  if (!confirm(`تغيير الحالة إلى: ${STATUS_LABELS[status]}?`)) return;
  const fd = new FormData(); fd.append('id', id); fd.append('status', status);
  await fetch('admin_api.php?action=update_status', { method:'POST', body:fd });
  loadOrders();
}

async function cancelOrder(id) {
  if (!confirm('هل تريد إلغاء هذا الطلب؟')) return;
  const fd = new FormData(); fd.append('id', id); fd.append('status', 'cancelled');
  await fetch('admin_api.php?action=update_status', { method:'POST', body:fd });
  loadOrders();
}

async function viewOrder(id) {
  const data = await (await fetch(`admin_api.php?action=order_detail&id=${id}`)).json();
  const o = data.order; const items = data.items;
  document.getElementById('orderModalContent').innerHTML = `
    <h3>📋 تفاصيل الطلب — ${o.order_number}</h3>
    <div class="detail-row"><span class="detail-label">العميل:</span> ${o.customer_name}</div>
    <div class="detail-row"><span class="detail-label">الهاتف:</span> <span dir="ltr">${o.customer_phone}</span></div>
    ${o.customer_email ? `<div class="detail-row"><span class="detail-label">البريد:</span> ${o.customer_email}</div>` : ''}
    <div class="detail-row"><span class="detail-label">العنوان:</span> ${o.delivery_address}</div>
    ${o.notes ? `<div class="detail-row"><span class="detail-label">ملاحظات:</span> ${o.notes}</div>` : ''}
    <div class="detail-row"><span class="detail-label">الحالة:</span> <span class="badge badge-${o.status}">${STATUS_LABELS[o.status]||o.status}</span></div>
    <div class="detail-row"><span class="detail-label">التاريخ:</span> ${o.created_at}</div>
    <hr style="margin:16px 0;border-color:#f5e8d8;">
    <table><thead><tr><th>المنتج</th><th>الكمية</th><th>السعر</th><th>الإجمالي</th></tr></thead>
    <tbody>${items.map(i=>`<tr><td>${i.name_ar}<br><small style="color:#888;">${i.name_en}</small></td>
      <td>${i.qty}</td><td>KD ${Number(i.price).toFixed(3)}</td><td>KD ${Number(i.subtotal).toFixed(3)}</td></tr>`).join('')}
    </tbody></table>
    <div style="text-align:left;margin-top:12px;font-size:14px;">
      <div>Subtotal: <strong>KD ${Number(o.subtotal).toFixed(3)}</strong></div>
      <div>Delivery: <strong>KD ${Number(o.delivery_fee).toFixed(3)}</strong></div>
      <div style="font-size:18px;font-weight:800;color:#8B4513;margin-top:6px;">Total: KD ${Number(o.total).toFixed(3)}</div>
    </div>
    <button class="btn" style="margin-top:20px;background:#ddd;color:#555;" onclick="closeModal('orderModal')">إغلاق</button>
  `;
  document.getElementById('orderModal').classList.add('open');
}

// ─── PRODUCTS ────────────────────────────────────────────────
async function loadProducts() {
  const data = await (await fetch('admin_api.php?action=products')).json();
  const el = document.getElementById('productsTable');
  if (!data.length) { el.innerHTML = '<p style="color:#888;text-align:center;padding:30px;">لا توجد منتجات</p>'; return; }
  el.innerHTML = `<table>
    <thead><tr><th>المنتج</th><th>التصنيف</th><th>السعر</th><th>المخزون</th><th>الحالة</th><th>إجراء</th></tr></thead>
    <tbody>${data.map(p=>`<tr>
      <td><strong>${p.name_ar}</strong><br><small style="color:#888;">${p.name_en}</small></td>
      <td>${p.cat_ar}</td>
      <td>KD ${Number(p.price).toFixed(3)}</td>
      <td>${p.stock}</td>
      <td><span class="badge ${p.is_active==1?'badge-confirmed':'badge-cancelled'}">${p.is_active==1?'✅ نشط':'❌ مخفي'}</span></td>
      <td>
        <button class="btn btn-primary btn-sm" onclick="editProduct(${p.id})">تعديل</button>
        <button class="btn btn-sm" style="background:#f5e8d8;color:#8B4513;" onclick="toggleProduct(${p.id},${p.is_active})">${p.is_active==1?'إخفاء':'إظهار'}</button>
      </td>
    </tr>`).join('')}
    </tbody></table>`;
}

async function toggleProduct(id, current) {
  const fd = new FormData(); fd.append('id', id); fd.append('is_active', current==1?0:1);
  await fetch('admin_api.php?action=toggle_product', { method:'POST', body:fd });
  loadProducts();
}

async function openProductForm(id = null) {
  let product = null;
  if (id) {
    const data = await (await fetch(`admin_api.php?action=product_detail&id=${id}`)).json();
    product = data;
  }
  const cats = await (await fetch('../api/index.php?action=categories')).json();
  const catOptions = cats.map(c=>`<option value="${c.id}" ${product&&product.category_id==c.id?'selected':''}>${c.name_ar}</option>`).join('');
  document.getElementById('productModalContent').innerHTML = `
    <h3>${id ? '✏️ تعديل منتج' : '➕ إضافة منتج جديد'}</h3>
    <div class="form-group" style="margin-bottom:14px;">
      <label style="font-size:13px;font-weight:700;color:#7A6050;display:block;margin-bottom:6px;">اسم المنتج (عربي) *</label>
      <input id="p_name_ar" style="width:100%;padding:10px 12px;border:2px solid #f5e8d8;border-radius:10px;font-family:inherit;font-size:14px;" value="${product?.name_ar||''}">
    </div>
    <div class="form-group" style="margin-bottom:14px;">
      <label style="font-size:13px;font-weight:700;color:#7A6050;display:block;margin-bottom:6px;">Product Name (English) *</label>
      <input id="p_name_en" dir="ltr" style="width:100%;padding:10px 12px;border:2px solid #f5e8d8;border-radius:10px;font-family:inherit;font-size:14px;" value="${product?.name_en||''}">
    </div>
    <div class="form-group" style="margin-bottom:14px;">
      <label style="font-size:13px;font-weight:700;color:#7A6050;display:block;margin-bottom:6px;">التصنيف *</label>
      <select id="p_cat" style="width:100%;padding:10px 12px;border:2px solid #f5e8d8;border-radius:10px;font-family:inherit;font-size:14px;">${catOptions}</select>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
      <div>
        <label style="font-size:13px;font-weight:700;color:#7A6050;display:block;margin-bottom:6px;">السعر (KD) *</label>
        <input id="p_price" type="number" step="0.001" style="width:100%;padding:10px 12px;border:2px solid #f5e8d8;border-radius:10px;font-family:inherit;font-size:14px;" value="${product?.price||''}">
      </div>
      <div>
        <label style="font-size:13px;font-weight:700;color:#7A6050;display:block;margin-bottom:6px;">السعر القديم (اختياري)</label>
        <input id="p_old_price" type="number" step="0.001" style="width:100%;padding:10px 12px;border:2px solid #f5e8d8;border-radius:10px;font-family:inherit;font-size:14px;" value="${product?.old_price||''}">
      </div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px;">
      <div>
        <label style="font-size:13px;font-weight:700;color:#7A6050;display:block;margin-bottom:6px;">المخزون</label>
        <input id="p_stock" type="number" style="width:100%;padding:10px 12px;border:2px solid #f5e8d8;border-radius:10px;font-family:inherit;font-size:14px;" value="${product?.stock||100}">
      </div>
      <div>
        <label style="font-size:13px;font-weight:700;color:#7A6050;display:block;margin-bottom:6px;">منتج مميز؟</label>
        <select id="p_featured" style="width:100%;padding:10px 12px;border:2px solid #f5e8d8;border-radius:10px;font-family:inherit;font-size:14px;">
          <option value="0" ${product?.is_featured==0?'selected':''}>لا</option>
          <option value="1" ${product?.is_featured==1?'selected':''}>نعم</option>
        </select>
      </div>
    </div>
    <button class="btn btn-primary" onclick="saveProduct(${id||'null'})" style="width:100%;padding:13px;font-size:16px;">💾 حفظ المنتج</button>
    <button class="btn" style="width:100%;margin-top:10px;background:#f5e8d8;color:#8B4513;padding:11px;" onclick="closeModal('productModal')">إلغاء</button>
  `;
  document.getElementById('productModal').classList.add('open');
}

async function editProduct(id) { openProductForm(id); }

async function saveProduct(id) {
  const fd = new FormData();
  if (id) fd.append('id', id);
  fd.append('name_ar',    document.getElementById('p_name_ar').value);
  fd.append('name_en',    document.getElementById('p_name_en').value);
  fd.append('category_id',document.getElementById('p_cat').value);
  fd.append('price',      document.getElementById('p_price').value);
  fd.append('old_price',  document.getElementById('p_old_price').value);
  fd.append('stock',      document.getElementById('p_stock').value);
  fd.append('is_featured',document.getElementById('p_featured').value);
  const action = id ? 'update_product' : 'add_product';
  const res = await (await fetch(`admin_api.php?action=${action}`, { method:'POST', body:fd })).json();
  if (res.success) { closeModal('productModal'); loadProducts(); }
  else alert(res.error || 'Error');
}

// ─── SETTINGS ────────────────────────────────────────────────
async function loadSettings() {
  const data = await (await fetch('admin_api.php?action=settings')).json();
  document.getElementById('settingsForm').innerHTML = `
    <div style="max-width:480px;">
      <div style="margin-bottom:16px;">
        <label style="font-size:14px;font-weight:700;color:#7A6050;display:block;margin-bottom:6px;">رسوم التوصيل (KD)</label>
        <input id="s_delivery_fee" type="number" step="0.001" style="width:100%;padding:11px 14px;border:2px solid #f5e8d8;border-radius:10px;font-family:inherit;font-size:14px;" value="${data.delivery_fee||'0.500'}">
      </div>
      <div style="margin-bottom:16px;">
        <label style="font-size:14px;font-weight:700;color:#7A6050;display:block;margin-bottom:6px;">الحد الأدنى للطلب (KD)</label>
        <input id="s_min_order" type="number" step="0.001" style="width:100%;padding:11px 14px;border:2px solid #f5e8d8;border-radius:10px;font-family:inherit;font-size:14px;" value="${data.min_order||'2.000'}">
      </div>
      <div style="margin-bottom:16px;">
        <label style="font-size:14px;font-weight:700;color:#7A6050;display:block;margin-bottom:6px;">رقم الواتساب</label>
        <input id="s_whatsapp" dir="ltr" style="width:100%;padding:11px 14px;border:2px solid #f5e8d8;border-radius:10px;font-family:inherit;font-size:14px;" value="${data.whatsapp||''}">
      </div>
      <div style="margin-bottom:24px;">
        <label style="font-size:14px;font-weight:700;color:#7A6050;display:block;margin-bottom:6px;">المتجر مفتوح؟</label>
        <select id="s_store_open" style="width:100%;padding:11px 14px;border:2px solid #f5e8d8;border-radius:10px;font-family:inherit;font-size:14px;">
          <option value="1" ${data.store_open=='1'?'selected':''}>✅ مفتوح</option>
          <option value="0" ${data.store_open=='0'?'selected':''}>❌ مغلق</option>
        </select>
      </div>
      <button class="btn btn-primary" onclick="saveSettings()" style="padding:13px 28px;font-size:16px;">💾 حفظ الإعدادات</button>
    </div>
  `;
}

async function saveSettings() {
  const fd = new FormData();
  fd.append('delivery_fee', document.getElementById('s_delivery_fee').value);
  fd.append('min_order',    document.getElementById('s_min_order').value);
  fd.append('whatsapp',     document.getElementById('s_whatsapp').value);
  fd.append('store_open',   document.getElementById('s_store_open').value);
  const res = await (await fetch('admin_api.php?action=save_settings', { method:'POST', body:fd })).json();
  if (res.success) alert('✅ تم حفظ الإعدادات');
}

// ─── RECENT ──────────────────────────────────────────────────
async function loadRecent() {
  const data = await (await fetch('admin_api.php?action=admin_orders&limit=5')).json();
  document.getElementById('recentOrders').innerHTML = `<table>
    <thead><tr><th>رقم الطلب</th><th>العميل</th><th>الإجمالي</th><th>الحالة</th></tr></thead>
    <tbody>${data.map(o=>`<tr>
      <td><strong>${o.order_number}</strong></td>
      <td>${o.customer_name}</td>
      <td>KD ${Number(o.total).toFixed(3)}</td>
      <td><span class="badge badge-${o.status}">${STATUS_LABELS[o.status]||o.status}</span></td>
    </tr>`).join('')}</tbody></table>`;
}

function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.getElementById('orderModal').addEventListener('click', function(e){ if(e.target===this)closeModal('orderModal'); });
document.getElementById('productModal').addEventListener('click', function(e){ if(e.target===this)closeModal('productModal'); });

// Init
loadOrders();
</script>
</body>
</html>
