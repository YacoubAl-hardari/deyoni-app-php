# ديوني | Deyoni

تطبيق أندرويد شخصي عربي لتتبع الديون والسداد، مبني على Laravel 13 وNativePHP Mobile 4.6 وEDGE. تُحفظ البيانات في SQLite داخل مساحة التطبيق. جميع الشاشات NativeComponent، دون واجهة WebView أو خادم خارجي أو حسابات مستخدمين.

## الوظائف

- إضافة الجهات وتعديلها والبحث فيها وأرشفتها واسترجاعها.
- تسجيل الديون والسداد الجزئي والكامل والزائد، بعملة YER أو SAR.
- رصيد مستقل لكل جهة وعملة، محسوب من الحركات الفعالة؛ الرصيد الزائد يُستهلك بصافي الحساب.
- إدخال الريال اليمني دون كسور، والسعودي بالهللات. الحساب والتخزين بأعداد صحيحة.
- تصحيح الحركة ببديل مرتبط مع إلغاء أثر الأصل، وإلغاء آمن يحفظ سجل التدقيق.
- منع تكرار العمليات المالية بمعرّف طلب فريد على مستوى قاعدة البيانات.
- تأكيد السداد الزائد والتصحيح والإلغاء والاستعادة، وتنبيه قبل مغادرة نموذج غير محفوظ.
- مظهر فاتح وداكن أو حسب النظام، وعملة افتراضية قابلة للتغيير.
- نسخة محلية مشفرة بكلمة مرور، مع منتقي ملفات Android الأصلي وحفظ في المكان الذي تختاره.
- لا تُضاف بيانات تجريبية. إجماليات الديون تشمل الأرشيف ولا تخصم أرصدة الجهات الأخرى.

## المتطلبات على Windows

PHP 8.4 أو أحدث متوافق، Composer، Android Studio، Android SDK 36، NDK وCMake، وJDK متوافق مع Gradle 8.14.5. تحتاج PHP إلى pdo_sqlite وopenssl وmbstring وzip وgd. يستهدف التطبيق Android 8.0 / API 26 فأحدث، ومعمارية arm64-v8a وفق قالب NativePHP المثبّت.

التنزيل والبناء يحتاجان الإنترنت على الكمبيوتر؛ التشغيل على الهاتف محلي بالكامل. لا تحتاج الشاشات الأصلية إلى Vite أو Node.

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate
php artisan native:install android --no-interaction
php artisan native:plugin:list
```

لا تنسخ .env.example فوق إعدادات مشروعك الموجودة. عند نقل المشروع، حدّد في .env مساري NATIVEPHP_ANDROID_SDK_LOCATION وNATIVEPHP_GRADLE_PATH المناسبين لجهازك، واجعل NATIVEPHP_APP_ID=com.deyoni.app. الحزمة المحلية packages/deyoni/device مطلوبة وتُثبّت من Composer path repository.

## تشغيل نسخة Debug

فعّل USB debugging ووصل هاتفًا، أو أنشئ AVD مناسبًا عبر Android Studio أولًا:

```powershell
.\tools\build-android.ps1 -DebugBuild
```

أو أضف platform-tools وPHP وJDK إلى PATH وشغّل:

```powershell
php artisan native:run android --no-interaction --no-tty
```

## بناء Release موقّع

```powershell
.\tools\build-android.ps1
```

ينشئ السكربت أول مرة مفتاح RSA محليًا في .signing/deyoni-release.p12 وكلمة مروره في .signing/password.txt. كلاهما خارج Git وخارج حزمة التطبيق. احتفظ بنسخة آمنة منهما، واحمِ الوصول إلى حساب Windows؛ لا يمكن تحديث تثبيت سابق بمفتاح مختلف. لا يطبع السكربت كلمة المرور.

يستخدم السكربت native:package android --build-type=release --output=dist. تحفظ NativePHP بيانات التوقيع محليًا أيضًا في nativephp/android/gradle.properties؛ هذا المجلد مستبعد من Git ومن التوزيع. لا تشارك .signing أو nativephp أو سجلات بناء غير منقّحة.

توجد ملفات التوزيع في dist عند نجاح البناء، والملف الأصلي في nativephp/android/app/build/outputs/apk/release/. يزداد version_code آليًا عند إعادة الحزم؛ غيّر NATIVEPHP_APP_VERSION عند إصدار نسخة جديدة. احتفظ بمعرّف التطبيق ومفتاح التوقيع نفسه، وثبّت التحديث فوق النسخة الحالية للحفاظ على SQLite. لا تستخدم حذف التطبيق أو migrate:fresh للترقية.

## النسخ الاحتياطي والاستعادة

من الإعدادات، أدخل كلمة مرور من 8 أحرف على الأقل ثم اختر إنشاء نسخة. الملف بصيغة .deyoni، يحتوي JSON مشفرًا بـ AES-256-GCM مع PBKDF2-SHA256 بعدد 600000 دورة، وملح وIV عشوائيين، والتحقق من سلامة المحتوى. الحد الأقصى 20 ميغابايت. لا يمكن استعادة كلمة المرور المنسية.

للاستعادة، أدخل كلمة المرور واختر الملف. يُفحص الإصدار والعلاقات والعملات والمبالغ أولًا، ثم يظهر عدد الجهات والحركات لتأكيد استبدال البيانات. التنفيذ داخل معاملة SQLite، مع نقطة رجوع مشفرة في storage/app/private/backups/before-restore.deyoni. عند الفشل تتراجع المعاملة وتظل البيانات الحالية سليمة. نقطة الرجوع تستخدم كلمة مرور عملية الاستعادة نفسها.

لا يوجد رفع تلقائي. يزيل hook الأمر deyoni:prepare-android صلاحيات الشبكة والصلاحيات غير المستخدمة ويعطّل Android Auto Backup ونسخ النقل التلقائي بعد كل تجميع للمكونات الأصلية. يفرض المكوّن المحلي اتجاه RTL حول شجرة Compose.

## الاختبارات

```powershell
php vendor/pestphp/pest/bin/pest
```

يعمل الاختبار على SQLite في الذاكرة دون تعديل قاعدة المستخدم. اختبارات Native::test تتحقق من تركيب EDGE وتدفقات حفظ النماذج داخل PHP؛ لا تُثبت وحدها جودة العرض أو عمل جسر Kotlin على جهاز.

يلزم التحقق على هاتف فعلي من اختيار الملفات وحفظها، وRTL والمظهر، ووضع الطيران، وإعادة فتح التطبيق بعد إيقافه، وتثبيت تحديث فوق إصدار يحتوي على بيانات. راجع نتيجة التنفيذ الفعلية في المحادثة؛ لا تعتبر وجود المصدر أو نجاح الاختبارات دليلًا على نجاح APK أو اختباره على الهاتف.

## بنية الملفات

```text
app/
  Models/             Creditor, LedgerEntry, AuditEvent, AppSetting
  Services/           CreditorService, LedgerService, BalanceService, BackupService, AppearanceService
  Support/Enums/      Currency, EntryKind
  Support/Money/      Money
  NativeComponents/   Home, CreditorForm, CreditorDetails, EntryForm, Settings, BalanceLine, FormScreen
  Console/Commands/   PrepareAndroidPrivacy
database/migrations/  مخطط SQLite وقيوده وفهارسه
resources/views/native/  شاشات EDGE والأجزاء المشتركة
routes/mobile.php     مسارات التطبيق الأصلية
lang/ar/validation.php رسائل التحقق العربية
packages/deyoni/device/ جسر Android للملفات وRTL
tools/                سكربت البناء وتوليد الأيقونة
public/               الأيقونة وشاشتا البداية
tests/Feature/        اختبارات الحسابات والاستعادة والشاشات
```

الأرصدة لا تُخزّن كحقل قابل للتعديل؛ مصدر الحقيقة الحركات غير الملغاة. لا تُجمع عملتان معًا، ولا تُولّد قيود وهمية لاستهلاك الرصيد. تحظر مفاتيح SQLite الأجنبية حذف جهة ذات حركات. تستخدم الترقية migrations المعتادة التي يشغلها NativePHP تلقائيًا على الجهاز.

