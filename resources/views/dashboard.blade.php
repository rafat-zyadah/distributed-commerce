
<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Distributed Commerce | Store & Admin</title>
@vite('resources/js/app.js')
<style>
:root{--bg:#f5f7fb;--card:#fff;--ink:#172033;--muted:#697386;--line:#e5e9f2;--blue:#3157d5;--blue2:#2443ad;--green:#16845b;--red:#c43d4b;--amber:#a76a12;--shadow:0 8px 24px #1c2b5010}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--ink);font-family:Tahoma,Arial,sans-serif}button,input,select,textarea{font:inherit}button{border:0;border-radius:10px;padding:10px 14px;background:var(--blue);color:white;font-weight:bold;cursor:pointer}button:hover{background:var(--blue2)}button:disabled{opacity:.5;cursor:not-allowed}.secondary{background:#edf1ff;color:#2946ad}.danger{background:#fff0f1;color:var(--red)}.ghost{background:white;color:var(--muted);border:1px solid var(--line)}.top{background:#111b35;color:white;padding:15px 24px;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}.brand{font-size:19px;font-weight:bold}.brand small{display:block;color:#b9c6ed;font-size:11px;font-weight:normal;margin-top:4px}.topright{display:flex;gap:12px;align-items:center;color:#e0e7ff;font-size:13px;flex-wrap:wrap}.wrap{max-width:1420px;margin:auto;padding:22px}.panel,.stat,.product{background:var(--card);border:1px solid var(--line);border-radius:15px;box-shadow:var(--shadow)}.panel{padding:20px}.auth{max-width:460px;margin:45px auto}.muted{color:var(--muted)}.small{font-size:12px}.field{display:flex;flex-direction:column;gap:7px;margin:12px 0}.field label{font-size:13px;font-weight:bold}.field input,.field select,.field textarea,.toolbar input,.toolbar select{padding:11px;border:1px solid #dce2ee;border-radius:9px;background:white;width:100%;min-width:0}.row{display:flex;gap:10px;align-items:center;flex-wrap:wrap}.row>*{flex:1}.hidden{display:none!important}.layout{display:grid;grid-template-columns:225px minmax(0,1fr);gap:18px;margin-top:20px}.side{background:#fff;border:1px solid var(--line);border-radius:15px;padding:10px;height:max-content;position:sticky;top:12px}.nav{width:100%;text-align:right;background:transparent;color:#526078;margin:3px 0}.nav:hover,.nav.active{background:#edf1ff;color:var(--blue)}.nav span{margin-left:8px}.content{min-width:0}.section{display:none}.section.active{display:block}.head{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin:3px 0 16px}.head h2{margin:0 0 5px}.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}.stat{padding:15px}.stat .value{font-size:25px;font-weight:bold;margin-top:8px}.toolbar{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px}.toolbar input{max-width:340px}.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:13px}.product{overflow:hidden}.product .image{height:145px;background:#eef1f7;display:flex;align-items:center;justify-content:center;color:#9099aa;font-size:38px}.product .image img{width:100%;height:100%;object-fit:cover}.product .body{padding:14px}.product h3{margin:0 0 7px;font-size:16px}.price{font-weight:bold;color:var(--blue);font-size:18px}.pill{display:inline-block;border-radius:20px;padding:4px 9px;font-size:11px;background:#eef1f7;color:#566176}.green{background:#e8f7f0;color:var(--green)}.red{background:#fff0f1;color:var(--red)}.amber{background:#fff5e5;color:var(--amber)}.tablewrap{overflow:auto;background:#fff;border:1px solid var(--line);border-radius:13px}table{border-collapse:collapse;width:100%;min-width:640px}th,td{padding:12px;border-bottom:1px solid var(--line);text-align:right;font-size:13px}th{background:#f8f9fc;color:#667087}tr:last-child td{border-bottom:0}.empty{text-align:center;padding:35px;color:var(--muted)}.cartline{display:grid;grid-template-columns:1fr 90px 110px 75px;gap:9px;align-items:center;padding:12px 0;border-bottom:1px solid var(--line)}.cartline input{width:85px;padding:8px;border:1px solid var(--line);border-radius:8px}.notice{padding:11px 13px;border-radius:9px;margin:12px 0;background:#edf3ff;color:#29418e;font-size:13px}.notice.error{background:#fff0f1;color:var(--red)}.notice.success{background:#eaf8f1;color:var(--green)}.actions{display:flex;gap:6px;flex-wrap:wrap}.actions button{font-size:12px;padding:7px 9px}.modalback{position:fixed;inset:0;background:#111a2d99;display:none;align-items:center;justify-content:center;padding:16px;z-index:20}.modalback.open{display:flex}.modal{background:white;border-radius:15px;padding:20px;width:min(560px,100%);max-height:92vh;overflow:auto}.modalhead{display:flex;justify-content:space-between;align-items:center}.toast{position:fixed;bottom:18px;left:18px;max-width:380px;background:#17233f;color:#fff;padding:13px 16px;border-radius:11px;display:none;z-index:40;box-shadow:var(--shadow)}.hint{font-size:12px;line-height:1.9;color:var(--muted);margin-top:14px}pre{direction:ltr;text-align:left;overflow:auto;background:#111b35;color:#e7edff;padding:12px;border-radius:9px}
@media(max-width:850px){.layout{grid-template-columns:1fr}.side{position:static;display:grid;grid-template-columns:repeat(3,1fr)}.stats{grid-template-columns:repeat(2,1fr)}}@media(max-width:540px){.wrap{padding:12px}.side{grid-template-columns:repeat(2,1fr)}.cartline{grid-template-columns:1fr 80px}.cartline>*:nth-child(3),.cartline>*:nth-child(4){grid-row:2}.top{padding:13px}.stats{gap:8px}.stat{padding:11px}}
</style>
</head>
<body>
<header class="top">
 <div class="brand">Distributed Commerce<small>Storefront & Administration • Laravel REST API</small></div>
 <div class="topright">
  <span id="who">غير مسجل الدخول</span>
  <span id="realtimeStatus" class="pill amber">WebSocket: غير متصل</span>
  <button id="logoutBtn" class="secondary hidden" onclick="logout()">تسجيل الخروج</button>
 </div>
</header>

<main id="authScreen" class="wrap auth">
 <div class="panel">
  <h2 id="authHeading">تسجيل الدخول</h2><p class="muted small">سجّل الدخول بحساب زبون أو مسؤول موجود.</p><div id="authError"></div>
  <form id="loginForm">
   <div class="field"><label>البريد الإلكتروني</label><input name="email" type="email" required autocomplete="username"></div>
   <div class="field"><label>كلمة المرور</label><input name="password" type="password" required autocomplete="current-password"></div>
   <button style="width:100%">دخول</button>
  </form>
  <form id="registerForm" class="hidden">
   <div class="field"><label>الاسم</label><input name="name" maxlength="100" required></div>
   <div class="field"><label>البريد الإلكتروني</label><input name="email" type="email" required></div>
   <div class="field"><label>كلمة المرور</label><input name="password" type="password" minlength="8" required></div>
   <div class="field"><label>تأكيد كلمة المرور</label><input name="password_confirmation" type="password" minlength="8" required></div>
   <button style="width:100%">إنشاء حساب زبون</button>
  </form>
  <button id="toggleAuth" class="ghost" style="width:100%;margin-top:12px" type="button" onclick="toggleAuth()">إنشاء حساب زبون جديد</button>
  <p class="hint">التسجيل العام ينشئ صلاحية customer فقط. لا يمكن إنشاء مسؤول من الواجهة؛ استخدم حساب admin موجوداً مسبقاً.</p>
 </div>
</main>

<main id="appScreen" class="wrap hidden">
 <div class="stats">
  <div class="stat"><div class="muted small">المنتجات</div><div id="sProducts" class="value">—</div></div>
  <div class="stat"><div class="muted small">الطلبات</div><div id="sOrders" class="value">—</div></div>
  <div class="stat"><div class="muted small">غير مقروء</div><div id="sUnread" class="value">—</div></div>
  <div class="stat"><div class="muted small">نوع الحساب</div><div id="sRole" class="value" style="font-size:18px">—</div></div>
 </div>
 <div class="layout">
  <aside class="side">
   <button class="nav active" data-page="store" onclick="page('store')"><span>🛍️</span>المتجر</button>
   <button class="nav" data-page="cart" onclick="page('cart')"><span>🛒</span>السلة <b id="cartBadge"></b></button>
   <button class="nav" data-page="orders" onclick="page('orders')"><span>📦</span>طلباتي</button>
   <button class="nav" data-page="notifications" onclick="page('notifications')"><span>🔔</span>الإشعارات</button>
   <button class="nav admin hidden" data-page="manageProducts" onclick="page('manageProducts')"><span>🧰</span>إدارة المنتجات</button>
   <button class="nav admin hidden" data-page="manageOrders" onclick="page('manageOrders')"><span>📋</span>إدارة الطلبات</button>
   <button class="nav" data-page="account" onclick="page('account')"><span>👤</span>حسابي</button>
   <button class="nav" data-page="queue" onclick="page('queue')"><span>⚙️</span>اختبار Queue / DLQ</button>
  </aside>
  <div class="content">
   <section id="page-store" class="section active">
    <div class="head"><div><h2>المتجر</h2><div class="muted small">المنتجات النشطة المتاحة للشراء.</div></div><button class="secondary" onclick="loadProducts()">تحديث</button></div>
    <div class="toolbar"><input id="searchProducts" placeholder="ابحث بالاسم أو الوصف..." oninput="renderStore()"><select id="filterCategory" onchange="renderStore()"><option value="">كل التصنيفات</option></select></div>
    <div id="storeGrid" class="grid"></div>
   </section>
   <section id="page-cart" class="section">
    <div class="head"><div><h2>سلة المشتريات</h2><div class="muted small">تأكيد الطلب يرسل POST /api/v1/orders.</div></div></div>
    <div class="panel"><div id="cartContent"></div><div class="row" style="margin-top:15px"><strong>الإجمالي التقديري: <span id="cartTotal">0.00</span></strong><button id="checkout" onclick="checkout()">تأكيد الطلب</button></div><p class="hint">السعر والمخزون النهائيان يتحقق منهما الخادم عند إنشاء الطلب، ويجري حجز المخزون عبر Inventory gRPC.</p></div>
   </section>
   <section id="page-orders" class="section">
    <div class="head"><div><h2>الطلبات</h2><div class="muted small">الزبون يرى طلباته فقط، والمسؤول يرى جميع الطلبات وفق صلاحيات الخادم.</div></div><button class="secondary" onclick="loadOrders()">تحديث</button></div>
    <div id="ordersTable" class="tablewrap"></div>
   </section>
   <section id="page-notifications" class="section">
    <div class="head"><div><h2>الإشعارات</h2><div class="muted small">إشعارات المستخدم الحالي.</div></div><button class="secondary" onclick="loadNotifications()">تحديث</button></div>
    <div id="notificationsList"></div>
   </section>
   <section id="page-manageProducts" class="section">
    <div class="head"><div><h2>إدارة المنتجات</h2><div class="muted small">إضافة منتج، تعديل بياناته، وتغيير حالته.</div></div><button onclick="openProduct()">＋ إضافة منتج</button></div>
    <div id="productsTable" class="tablewrap"></div>
   </section>
   <section id="page-manageOrders" class="section">
    <div class="head"><div><h2>إدارة الطلبات</h2><div class="muted small">عرض كل الطلبات وتحديث حالتها.</div></div><button class="secondary" onclick="loadOrders()">تحديث</button></div>
    <div class="toolbar"><select id="filterOrderStatus" onchange="loadOrders()"><option value="">كل الحالات</option><option value="pending">pending</option><option value="paid">paid</option><option value="shipped">shipped</option><option value="delivered">delivered</option><option value="cancelled">cancelled</option></select></div>
    <div id="adminOrdersTable" class="tablewrap"></div>
   </section>
   <section id="page-account" class="section">
    <div class="head"><div><h2>حسابي</h2><div class="muted small">بيانات الحساب من GET /auth/me.</div></div><button class="secondary" onclick="loadMe()">تحديث</button></div>
    <div id="accountCard" class="panel"></div>
   </section>
   <section id="page-queue" class="section">
    <div class="head"><div><h2>اختبار Queue و DLQ</h2><div class="muted small">إرشادات الاختبار من خلال واجهة المتجر.</div></div><a href="http://localhost:15672" target="_blank" rel="noopener">RabbitMQ UI ↗</a></div>
    <div class="panel">
     <h3>اختبار A — إرسال البريد</h3><ol><li>شغّل Worker.</li><li>أنشئ طلباً من المتجر.</li><li>تحقق من وصول رسالة التأكيد عبر Mailpit/SMTP المهيأ في المشروع.</li></ol>
     <pre>php artisan queue:work rabbitmq --queue=emails</pre>
     <hr><h3>اختبار B — Worker متوقف</h3><ol><li>أوقف Worker بـ Ctrl+C مع إبقاء RabbitMQ يعمل.</li><li>أنشئ طلباً جديداً.</li><li>تحقق من نجاح الطلب وبقاء رسالة في Queue emails بحالة Ready.</li><li>أعد تشغيل Worker وتأكد من معالجة الرسالة.</li></ol>
     <hr><h3>اختبار C — Retry و DLQ</h3><ol><li>أضف استثناءً مؤقتاً في Listener الخاص بالبريد.</li><li>أنشئ طلباً جديداً ثم شغّل Worker.</li><li>راقب المحاولات وتحقق من وصول الرسالة إلى emails.failed بعد استنفادها.</li><li>احذف الاستثناء بعد الاختبار.</li></ol>
     <p class="hint">هذه الصفحة لا تتصل بإدارة RabbitMQ مباشرة. راقب Ready / Unacked / DLQ من RabbitMQ Management UI.</p>
    </div>
   </section>
  </div>
 </div>
 <p class="hint">حالة WebSocket تظهر في الشريط العلوي. عند تعذر الاتصال يبقى التحديث الدوري كل 15 ثانية فعالاً كخطة احتياطية.</p>
</main>

<div id="productModal" class="modalback"><div class="modal">
 <div class="modalhead"><h3 id="productModalHeading">إضافة منتج</h3><button class="ghost" onclick="closeProduct()">إغلاق</button></div>
 <form id="productForm">
  <input type="hidden" name="id">
  <div class="field"><label>اسم المنتج</label><input name="name" maxlength="150" required></div>
  <div class="field"><label>الوصف</label><textarea name="description" rows="3" maxlength="5000"></textarea></div>
  <div class="row"><div class="field"><label>التصنيف</label><input name="category" maxlength="50" required></div><div class="field"><label>السعر</label><input name="price" type="number" min="0" max="999999.99" step=".01" required></div></div>
  <div class="row"><div class="field"><label>المخزون</label><input name="stock" type="number" min="0" required></div><div class="field"><label>الحالة</label><select name="status"><option value="active">active</option><option value="inactive">inactive</option></select></div></div>
  <div class="field"><label>صورة المنتج (عند الإضافة فقط)</label><input name="image" type="file" accept=".jpg,.jpeg,.png,.webp"></div>
  <button>حفظ</button>
 </form>
</div></div>
<div id="toast" class="toast"></div>

<script>
const API='/api/v1';
let token=localStorage.getItem('dc_jwt')||'', me=null, products=[], orders=[], notices=[], cart=JSON.parse(localStorage.getItem('dc_cart')||'{}'), refresh=null, authMode='login';
let realtimeChannel=null;
const $=id=>document.getElementById(id);
const isAdmin=()=>me?.role==='admin';

function esc(v){return String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]))}
function money(v){return Number(v||0).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2})}
function toast(s){$('toast').textContent=s;$('toast').style.display='block';setTimeout(()=>$('toast').style.display='none',3500)}
function message(id,s,error=false){$(id).innerHTML=s?`<div class="notice ${error?'error':'success'}">${esc(s)}</div>`:''}
function pill(s){let c=['active','delivered','paid'].includes(s)?'green':['inactive','cancelled'].includes(s)?'red':'amber';return `<span class="pill ${c}">${esc(s||'—')}</span>`}

/* Reverb / WebSocket */
function setRealtimeStatus(status,text){
 const el=$('realtimeStatus');
 if(!el)return;
 el.textContent=text;
 el.className='pill '+(status==='connected'?'green':status==='connecting'?'amber':'red');
}

function disconnectRealtime(){
 if(window.Echo&&realtimeChannel&&me){
  try{
   window.Echo.leave('App.Models.User.'+me.id);
  }catch(error){
   console.warn('Error leaving private channel:',error);
  }
 }
 realtimeChannel=null;
 setRealtimeStatus('disconnected','WebSocket: غير متصل');
}

function connectRealtime(){
 if(!window.Echo){
  setRealtimeStatus('disconnected','WebSocket: Echo غير محمّل');
  console.error('Laravel Echo is not loaded. Check resources/js/app.js');
  return;
 }

 if(!me||!me.id||!token){
  setRealtimeStatus('disconnected','WebSocket: لا يوجد مستخدم');
  return;
 }

 disconnectRealtime();
 setRealtimeStatus('connecting','WebSocket: جاري الاتصال...');

 try{
  const connection=window.Echo.connector?.pusher?.connection;

  if(connection){
   connection.bind('connected',()=>{
    setRealtimeStatus('connected','WebSocket: متصل');
   });

   connection.bind('disconnected',()=>{
    setRealtimeStatus('disconnected','WebSocket: انقطع الاتصال');
   });

   connection.bind('connecting',()=>{
    setRealtimeStatus('connecting','WebSocket: جاري الاتصال...');
   });

   connection.bind('error',(error)=>{
    console.error('Reverb connection error:',error);
    setRealtimeStatus('disconnected','WebSocket: خطأ بالاتصال');
   });
  }

  const channelName='App.Models.User.'+me.id;

  realtimeChannel=window.Echo.private(channelName);

  realtimeChannel
   .listen('.notification.created',(event)=>{
    console.log('Realtime notification received:',event);
    toast('🔔 وصل إشعار جديد');
    loadNotifications(true);
   })
   .listen('.order.updated',(event)=>{
    console.log('Realtime order update received:',event);
    toast('📦 تم تحديث أحد الطلبات');
    loadOrders(true);
    loadNotifications(true);
   });

  realtimeChannel.subscription.bind('pusher:subscription_succeeded',()=>{
   console.log('Private channel subscription succeeded:',channelName);
   setRealtimeStatus('connected','WebSocket: متصل');
  });

  realtimeChannel.subscription.bind('pusher:subscription_error',(status)=>{
   console.error('Private channel subscription failed:',status);
   setRealtimeStatus('disconnected','WebSocket: فشل الاشتراك');
  });

 }catch(error){
  console.error('Reverb setup error:',error);
  setRealtimeStatus('disconnected','WebSocket: فشل الاتصال');
 }
}

async function request(path,options={}){
 const headers={Accept:'application/json',...(options.headers||{})};
 if(token)headers.Authorization='Bearer '+token;
 let body=options.body;
 if(body&&!(body instanceof FormData)){
  headers['Content-Type']='application/json';
  body=JSON.stringify(body);
 }
 const response=await fetch(API+path,{...options,headers,body});
 let data={};
 const raw=await response.text();
 try{data=raw?JSON.parse(raw):{}}catch{data={message:raw}}
 if(!response.ok){
  if(response.status===401&&token)logout(false);
  let msg=data.message||`HTTP ${response.status}`;
  if(data.errors)msg=Object.values(data.errors).flat().join(' | ');
  throw new Error(msg);
 }
 return data;
}

function toggleAuth(){
 authMode=authMode==='login'?'register':'login';
 $('loginForm').classList.toggle('hidden',authMode!=='login');
 $('registerForm').classList.toggle('hidden',authMode!=='register');
 $('authHeading').textContent=authMode==='login'?'تسجيل الدخول':'إنشاء حساب زبون';
 $('toggleAuth').textContent=authMode==='login'?'إنشاء حساب زبون جديد':'العودة لتسجيل الدخول';
 message('authError','');
}

$('loginForm').addEventListener('submit',async e=>{
 e.preventDefault();
 const f=new FormData(e.target);
 try{
  await accept(await request('/auth/login',{method:'POST',body:{email:f.get('email'),password:f.get('password')}}));
 }catch(err){
  message('authError',err.message,true);
 }
});

$('registerForm').addEventListener('submit',async e=>{
 e.preventDefault();
 const f=new FormData(e.target);
 try{
  await accept(await request('/auth/register',{method:'POST',body:{name:f.get('name'),email:f.get('email'),password:f.get('password'),password_confirmation:f.get('password_confirmation')}}));
 }catch(err){
  message('authError',err.message,true);
 }
});

async function accept(data){
 if(!data.access_token||!data.user)throw new Error('استجابة تسجيل الدخول لا تحتوي access_token/user');
 token=data.access_token;
 localStorage.setItem('dc_jwt',token);
 me=data.user;
 await startApp();
}

async function startApp(){
 $('authScreen').classList.add('hidden');
 $('appScreen').classList.remove('hidden');
 $('logoutBtn').classList.remove('hidden');

 $('who').textContent=`${me.name} — ${isAdmin()?'مسؤول':'زبون'}`;
 $('sRole').textContent=isAdmin()?'مسؤول':'زبون';

 document.querySelectorAll('.admin').forEach(x=>{
  x.classList.toggle('hidden',!isAdmin());
 });

 await Promise.allSettled([
  loadMe(),
  loadProducts(),
  loadOrders(),
  loadNotifications()
 ]);

 renderCart();
 connectRealtime();

 if(refresh)clearInterval(refresh);
 refresh=setInterval(()=>{
  if(token){
   loadOrders(true);
   loadNotifications(true);
  }
 },15000);
}

async function logout(call=true){
 if(call&&token){
  try{
   await request('/auth/logout',{method:'POST'});
  }catch{}
 }

 disconnectRealtime();

 token='';
 me=null;
 localStorage.removeItem('dc_jwt');

 if(refresh){
  clearInterval(refresh);
  refresh=null;
 }

 $('appScreen').classList.add('hidden');
 $('authScreen').classList.remove('hidden');
 $('logoutBtn').classList.add('hidden');
 $('who').textContent='غير مسجل الدخول';
}

function page(name){
 document.querySelectorAll('.section').forEach(x=>x.classList.remove('active'));
 $('page-'+name).classList.add('active');

 document.querySelectorAll('.nav').forEach(x=>{
  x.classList.toggle('active',x.dataset.page===name);
 });

 if(name==='cart')renderCart();
 if(name==='orders'||name==='manageOrders')loadOrders();
 if(name==='notifications')loadNotifications();
 if(name==='manageProducts')loadProducts();
}

async function loadMe(){
 try{
  const d=await request('/auth/me');
  me=d.data;
  $('who').textContent=`${me.name} — ${isAdmin()?'مسؤول':'زبون'}`;
  $('accountCard').innerHTML=`<h3>${esc(me.name)}</h3><p>${esc(me.email)}</p><p>رقم المستخدم: ${esc(me.id)}</p><p>الصلاحية: ${pill(me.role)}</p>`;
 }catch(e){
  toast('تعذر تحميل الحساب: '+e.message);
 }
}

async function loadProducts(){
 try{
  const d=await request('/products?per_page=50');
  products=d.data||[];
  $('sProducts').textContent=products.length;
  renderStore();
  renderProductsTable();

  const cats=[...new Set(products.map(p=>p.category).filter(Boolean))];
  const sel=$('filterCategory'),old=sel.value;
  sel.innerHTML='<option value="">كل التصنيفات</option>'+cats.map(c=>`<option value="${esc(c)}">${esc(c)}</option>`).join('');
  sel.value=old;
  renderStore();
 }catch(e){
  toast('تعذر تحميل المنتجات: '+e.message);
 }
}

function renderStore(){
 const q=$('searchProducts').value.toLowerCase();
 const cat=$('filterCategory').value;
 const list=products.filter(p=>p.status==='active'&&(!cat||p.category===cat)&&(`${p.name} ${p.category||''} ${p.description||''}`).toLowerCase().includes(q));

 $('storeGrid').innerHTML=list.length?list.map(p=>`
  <article class="product">
   <div class="image">${p.image_url?`<img src="${esc(p.image_url)}" alt="${esc(p.name)}">`:'🛍️'}</div>
   <div class="body">
    <h3>${esc(p.name)}</h3>
    <div class="muted small">${esc(p.category)}</div>
    <p class="small">${esc(p.description||'')}</p>
    <div class="row"><span class="price">${money(p.price)}</span><span class="pill">المخزون: ${p.stock}</span></div>
    <button style="width:100%;margin-top:12px" onclick="addCart(${p.id})">أضف إلى السلة</button>
   </div>
  </article>
 `).join(''):'<div class="empty">لا توجد منتجات نشطة مطابقة.</div>';
}

function saveCart(){
 localStorage.setItem('dc_cart',JSON.stringify(cart));
 renderCart();
}

function addCart(id){
 cart[id]=(Number(cart[id])||0)+1;
 saveCart();
 toast('تمت إضافة المنتج إلى السلة');
}

function renderCart(){
 const ids=Object.keys(cart);
 $('cartBadge').textContent=ids.length?`(${ids.length})`:'';
 let total=0;

 const html=ids.map(id=>{
  const p=products.find(x=>String(x.id)===String(id));
  if(!p)return'';
  const qty=Number(cart[id])||1;
  total+=Number(p.price)*qty;

  return `
   <div class="cartline">
    <div><b>${esc(p.name)}</b><div class="muted small">${money(p.price)} للقطعة</div></div>
    <input type="number" min="1" max="100" value="${qty}" onchange="setCartQty(${p.id},this.value)">
    <strong>${money(Number(p.price)*qty)}</strong>
    <button class="danger" onclick="removeCart(${p.id})">حذف</button>
   </div>
  `;
 }).join('');

 $('cartContent').innerHTML=html||'<div class="empty">السلة فارغة.</div>';
 $('cartTotal').textContent=money(total);
 $('checkout').disabled=!ids.length;
}

function setCartQty(id,v){
 cart[id]=Math.max(1,Math.min(100,parseInt(v)||1));
 saveCart();
}

function removeCart(id){
 delete cart[id];
 saveCart();
}

async function checkout(){
 const items=Object.entries(cart).map(([product_id,quantity])=>({
  product_id:Number(product_id),
  quantity:Number(quantity)
 }));

 if(!items.length)return;

 const b=$('checkout');
 b.disabled=true;

 try{
  const d=await request('/orders',{method:'POST',body:{items}});
  cart={};
  saveCart();
  toast(`تم إنشاء الطلب #${d.data?.id??''} بنجاح`);
  await loadOrders();
  page('orders');
 }catch(e){
  toast('فشل إنشاء الطلب: '+e.message);
 }finally{
  b.disabled=false;
 }
}

async function loadOrders(silent=false){
 try{
  let url='/orders?per_page=50';
  if(isAdmin()&&$('filterOrderStatus')?.value){
   url+='&status='+encodeURIComponent($('filterOrderStatus').value);
  }

  const d=await request(url);
  orders=d.data||[];
  $('sOrders').textContent=orders.length;
  renderOrders();
 }catch(e){
  if(!silent)toast('تعذر تحميل الطلبات: '+e.message);
 }
}

function orderTable(list,admin=false){
 if(!list.length)return '<div class="empty">لا توجد طلبات.</div>';

 return `
  <table>
   <thead>
    <tr>
     <th>رقم الطلب</th>
     ${admin?'<th>رقم الزبون</th>':''}
     <th>التاريخ</th>
     <th>الحالة</th>
     <th>الإجمالي</th>
     <th>التفاصيل</th>
     ${admin?'<th>تحديث الحالة</th>':''}
    </tr>
   </thead>
   <tbody>
    ${list.map(o=>`
     <tr>
      <td>#${o.id}</td>
      ${admin?`<td>${o.user_id}</td>`:''}
      <td>${o.created_at?new Date(o.created_at).toLocaleString('ar'):'—'}</td>
      <td>${pill(o.status)}</td>
      <td>${money(o.total)}</td>
      <td><button class="secondary" onclick="orderDetails(${o.id})">عرض</button></td>
      ${admin?`
       <td>
        <div class="actions">
         <select id="status-${o.id}">
          ${['pending','paid','shipped','delivered','cancelled'].map(s=>`<option value="${s}" ${s===o.status?'selected':''}>${s}</option>`).join('')}
         </select>
         <button onclick="changeOrderStatus(${o.id})">حفظ</button>
        </div>
       </td>
      `:''}
     </tr>
    `).join('')}
   </tbody>
  </table>
 `;
}

function renderOrders(){
 $('ordersTable').innerHTML=orderTable(orders,false);
 if(isAdmin())$('adminOrdersTable').innerHTML=orderTable(orders,true);
}

async function orderDetails(id){
 try{
  const d=await request('/orders/'+id);
  const o=d.data;
  const lines=(o.items||[]).map(i=>`المنتج #${i.product_id} — ${i.quantity} × ${money(i.unit_price)}`).join('\n');
  alert(`الطلب #${o.id}\nالحالة: ${o.status}\nالإجمالي: ${money(o.total)}\n\n${lines||'لا توجد عناصر'}`);
 }catch(e){
  toast(e.message);
 }
}

async function changeOrderStatus(id){
 try{
  const status=$('status-'+id).value;
  await request('/orders/'+id+'/status',{method:'PATCH',body:{status}});
  toast('تم تحديث حالة الطلب');
  await loadOrders();
 }catch(e){
  toast('تعذر تحديث الحالة: '+e.message);
 }
}

async function loadNotifications(silent=false){
 try{
  const d=await request('/notifications');
  notices=d.data||[];
  $('sUnread').textContent=notices.filter(n=>!n.read_at).length;

  $('notificationsList').innerHTML=notices.length?notices.map(n=>`
   <div class="panel" style="margin-bottom:10px">
    <div class="row">
     <b>${esc(n.title)}</b>
     <span>${n.read_at?pill('مقروء'):pill('جديد')}</span>
    </div>
    <p>${esc(n.message)}</p>
    <div class="row">
     <span class="muted small">${esc(n.created_at||'')}</span>
     ${!n.read_at?`<button class="secondary" onclick="markRead(${n.id})">تحديد كمقروء</button>`:''}
    </div>
   </div>
  `).join(''):'<div class="empty">لا توجد إشعارات.</div>';
 }catch(e){
  if(!silent)toast('تعذر تحميل الإشعارات: '+e.message);
 }
}

async function markRead(id){
 try{
  await request('/notifications/'+id+'/read',{method:'PATCH'});
  await loadNotifications();
 }catch(e){
  toast(e.message);
 }
}

function renderProductsTable(){
 if(!isAdmin())return;

 const rows=products.map(p=>`
  <tr>
   <td>${p.id}</td>
   <td>${esc(p.name)}</td>
   <td>${esc(p.category)}</td>
   <td>${money(p.price)}</td>
   <td>${p.stock}</td>
   <td>${pill(p.status)}</td>
   <td class="actions">
    <button class="secondary" onclick="editProduct(${p.id})">تعديل</button>
    <button class="ghost" onclick="toggleProduct(${p.id})">${p.status==='active'?'إيقاف':'تفعيل'}</button>
   </td>
  </tr>
 `).join('');

 $('productsTable').innerHTML=`
  <table>
   <thead>
    <tr><th>#</th><th>المنتج</th><th>التصنيف</th><th>السعر</th><th>المخزون</th><th>الحالة</th><th>الإجراءات</th></tr>
   </thead>
   <tbody>${rows}</tbody>
  </table>
 `;
}

function openProduct(){
 const f=$('productForm');
 f.reset();
 f.elements.id.value='';
 f.elements.status.value='active';
 $('productModalHeading').textContent='إضافة منتج';
 $('productModal').classList.add('open');
}

function closeProduct(){
 $('productModal').classList.remove('open');
}

function editProduct(id){
 const p=products.find(x=>x.id===id);
 const f=$('productForm');
 if(!p)return;

 ['id','name','description','category','price','stock','status'].forEach(k=>{
  f.elements[k].value=p[k]??'';
 });

 f.elements.image.value='';
 $('productModalHeading').textContent='تعديل المنتج #'+id;
 $('productModal').classList.add('open');
}

$('productForm').addEventListener('submit',async e=>{
 e.preventDefault();
 const f=e.target,id=f.elements.id.value;

 try{
  if(id){
   const body={};
   ['name','description','category','price','stock'].forEach(k=>{
    body[k]=f.elements[k].value;
   });

   await request('/products/'+id,{method:'PUT',body});

   const old=products.find(p=>String(p.id)===String(id));
   if(old&&old.status!==f.elements.status.value){
    await request('/products/'+id+'/status',{
     method:'PATCH',
     body:{status:f.elements.status.value}
    });
   }
  }else{
   const body=new FormData();
   ['name','description','category','price','stock','status'].forEach(k=>{
    body.append(k,f.elements[k].value);
   });

   if(f.elements.image.files[0]){
    body.append('image',f.elements.image.files[0]);
   }

   await request('/products',{method:'POST',body});
  }

  closeProduct();
  toast('تم حفظ المنتج');
  await loadProducts();
 }catch(err){
  toast('فشل حفظ المنتج: '+err.message);
 }
});

async function toggleProduct(id){
 const p=products.find(x=>x.id===id);

 try{
  await request('/products/'+id+'/status',{
   method:'PATCH',
   body:{status:p.status==='active'?'inactive':'active'}
  });

  toast('تم تغيير الحالة');
  await loadProducts();
 }catch(e){
  toast(e.message);
 }
}

/* إعادة الاتصال عند عودة الإنترنت */
window.addEventListener('offline',()=>{
 setRealtimeStatus('disconnected','WebSocket: لا يوجد اتصال بالإنترنت');
});

window.addEventListener('online',()=>{
 if(token&&me){
  connectRealtime();
 }
});

/* استعادة جلسة الدخول عند تحديث الصفحة */
(async()=>{
 if(token){
  try{
   const d=await request('/auth/me');
   me=d.data;
   await startApp();
  }catch(error){
   console.error('Session restore failed:',error);
   disconnectRealtime();
  }
 }
})();
</script>
</body>
</html>
