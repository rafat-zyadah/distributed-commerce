# المتطلب الأول: تصميم الواجهات البرمجية والأمان (API Design & Security)

هذا المجلد يحتوي **الكود الخاص بمشروعك فقط** (الوحدات، الـ migrations، الاختبارات، ملفات Docker، والتوثيق). هيكل Laravel الأساسي تنشئه على جهازك بأمر Composer، ثم تنسخ هذه الملفات فوقه.

## الوضع الحالي (اقرأ هذا أولاً)

- ما قدرت أنشئ مشروع Laravel أو أشغّل الاختبارات في بيئتي، لأن مستودع Packagist محجوب عن الشبكة هناك. لذلك:
  - تأكدت من صياغة كل ملف PHP (`php -l`)، لكن **الكود ما اتشغّل فعلياً**.
  - أول ما تشغّل `php artisan test` عندك، إذا طلع خطأ، ابعته لي وأصلحه.
- Docker غير مثبت عندك، فالتشغيل المحلي يكون بـ `php artisan serve` (الخطوة 4). ملفات Docker جاهزة للمرحلة التي تثبت فيها Docker.

## الخطوات

### 1) إنشاء مشروع Laravel (PHP 8.2+ و Composer)

```bash
composer create-project laravel/laravel ecommerce-api
cd ecommerce-api
composer require php-open-source-saver/jwt-auth
php artisan vendor:publish --provider="PHPOpenSourceSaver\JWTAuth\Providers\LaravelServiceProvider"
php artisan jwt:secret
```

### 2) نسخ ملفات هذا المجلد فوق المشروع

- انسخ كل الملفات والمجلدات من هنا إلى مجلد المشروع، واستبدل الملفات الموجودة.
- احذف `app/Models/User.php`، لأن الـ User صار داخل وحدة Users.

### 3) تعديل `config/auth.php`

أضف guard الـ `api` وحدّث مزود المستخدمين:

```php
'guards' => [
    'web' => ['driver' => 'session', 'provider' => 'users'],
    'api' => ['driver' => 'jwt', 'provider' => 'users'],
],

'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => App\Modules\Users\Models\User::class,
    ],
],
```

### 4) قاعدة البيانات والتشغيل المحلي (بدون Docker)

في `.env` اضبط:

```
DB_CONNECTION=sqlite
```

ثم أنشئ ملف القاعدة وشغّل الـ migrations:

```bash
touch database/database.sqlite          # على Windows: type nul > database\database.sqlite
php artisan migrate
php artisan storage:link
php artisan test                        # الاختبارات
php artisan serve                       # http://127.0.0.1:8000
```

التوثيق يفتح على: `http://127.0.0.1:8000/docs/index.html`

## الهيكل

```
app/Modules/
  Users/          تسجيل، دخول JWT، خروج، الأدوار (customer / admin)
  Products/       عرض، إضافة مع صورة، تعديل، تغيير الحالة، تصفية وتقسيم صفحات
  Orders/         إنشاء الطلب (مع حجز المخزون)، عرض، تغيير الحالة، Policy
  Notifications/  إشعارات تُنشأ عند الطلب وعند تغيير حالته
```

كل وحدة فيها `routes.php` خاص بها، وتُحمَّل كلها من `routes/api.php`.

## نقاط النهاية (Endpoints)

| الطريقة | المسار | من يستطيع |
|---|---|---|
| POST | `/api/v1/auth/register` | الجميع (يُنشأ دائماً كـ customer) |
| POST | `/api/v1/auth/login` | الجميع (5 محاولات في الدقيقة) |
| GET | `/api/v1/auth/me` | مسجل دخول |
| POST | `/api/v1/auth/logout` | مسجل دخول |
| GET | `/api/v1/products?category=&per_page=` | الجميع (غير المسؤول يرى النشط فقط) |
| GET | `/api/v1/products/{id}` | الجميع (غير النشط = 404 لغير المسؤول) |
| POST | `/api/v1/products` (multipart مع `image`) | admin |
| PUT | `/api/v1/products/{id}` | admin |
| PATCH | `/api/v1/products/{id}/status` | admin |
| GET | `/api/v1/orders` | customer: طلباته فقط، admin: الكل |
| POST | `/api/v1/orders` | customer |
| GET | `/api/v1/orders/{id}` | صاحب الطلب أو admin |
| PATCH | `/api/v1/orders/{id}/status` | admin |
| GET | `/api/v1/notifications` | مسجل دخول (إشعاراته هو) |
| PATCH | `/api/v1/notifications/{id}/read` | صاحب الإشعار |

## إثبات المتطلب: "أي محاولة وصول غير مسموحة تُرفض"

كل حالة لها اختبار في مجلد `tests/Feature/`:

| الحالة | النتيجة | الاختبار |
|---|---|---|
| زبون يحاول عرض طلب زبون آخر | 403 | `OrderSecurityTest::test_customer_cannot_view_another_customers_order` |
| زبون يحاول تغيير حالة طلب | 403 | `OrderSecurityTest::test_customer_cannot_change_an_order_status` |
| زبون يحاول إضافة منتج | 403 | `ProductTest::test_customer_cannot_create_a_product` |
| زبون يحاول قراءة إشعار غيره | 403 | `OrderSecurityTest::test_notifications_are_private_to_their_owner` |
| طلب بدون توكن | 401 | `AuthTest::test_protected_route_rejects_requests_without_a_token` |
| توكن مزوّر | 401 | `AuthTest::test_protected_route_rejects_a_garbage_token` |
| كمية أكبر من المخزون | 422 ولا يُنشأ طلب | `OrderSecurityTest::test_order_reduces_stock_and_insufficient_stock_is_rejected` |
| 6 محاولات دخول خاطئة | 429 | `AuthTest::test_login_is_throttled_after_five_attempts` |
| التسجيل كـ admin عبر الطلب | يُتجاهل، يبقى customer | `AuthTest::test_client_cannot_register_itself_as_admin` |
| ترويسات الأمان | موجودة | `AuthTest::test_responses_carry_security_headers` |

## ما تم تطبيقه من المتطلب

- **الوحدات الأربع** منفصلة في `app/Modules/`.
- **REST** مع فلترة حسب التصنيف وتقسيم صفحات.
- **JWT** للمصادقة، و**Policies** وmiddleware للصلاحيات.
- **تحديد محاولات الدخول** (`RateLimiter` باسم `login`).
- **ترويسات أمان** في `SecurityHeaders` (nosniff، X-Frame-Options، CSP، Cache-Control...).
- **التوثيق** في `public/docs/openapi.yaml` (يُعرض عبر Swagger UI).
- **Docker**: `Dockerfile` و`docker-compose.yml` جاهزان، لكن لم يُجرَّبا بعد.

## أشياء تفيدك في المقابلة

المشروع يُقيَّم في المقابلات، فافهم هذه النقاط قبل ما تروح:

1. **لماذا `role` غير قابل للتعبئة (not fillable)؟** عشان ما يقدر أحد يرسل `"role": "admin"` ويصير مسؤول.
2. **لماذا `lockForUpdate()` في الطلب؟** لو زبونين اشتروا آخر قطعة بنفس اللحظة، القفل يخلي أحدهم ينتظر، فالمخزون ما ينزل تحت الصفر.
3. **لماذا يُعاد المخزون عند الإلغاء؟** عشان الكمية المحجوزة ترجع للمخزون.
4. **لماذا رسالة خطأ واحدة عند فشل الدخول؟** عشان ما يعرف المهاجم أي إيميل مسجل عندنا.
5. **لماذا يُقرأ الدور من قاعدة البيانات وليس من التوكن؟** لو تم تخفيض صلاحية مستخدم، يفقدها فوراً.

## Docker (عندما تثبته)

1. ثبّت **Docker Desktop** على Windows (يحتاج WSL2).
2. انسخ `.env.example` إلى `.env` (أو أنشئه).
3. شغّل: `docker compose up --build`
4. افتح `http://localhost:8000/api/v1/products`

الملف `docker-compose.yml` يشغّل MySQL 8.4 ويطبّق الـ migrations تلقائياً عند البدء.
