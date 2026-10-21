# مكوّن Android المحلي لديوني

يوفّر DeyoniDevice.Documents عمليتي import وexport باستخدام Android Storage Access Framework. يفتح Fragment منتقي الملفات ويصدر الحدث Deyoni\\Device\\Events\\DocumentResult بنتيجة العملية ومسار النسخة الخاصة عند الاستيراد. لا يطلب صلاحيات تخزين ولا شبكة. الحد الأقصى للملف 20 ميغابايت.

يُسجّل registerDeyoni غلاف Compose باتجاه RTL. يطبق hook post_compile الأمر deyoni:prepare-android لمنع النسخ السحابي والصلاحيات غير المستخدمة. هذا المكوّن خاص بـ Android، ولا يتضمن واجهات iOS.

يُثبّت محليًا من Composer path repository ويُسجّل في app/Providers/NativeServiceProvider.php. شغّل php artisan native:plugin:validate packages/deyoni/device لفحص التصريح. تغييرات Kotlin تتطلب إعادة بناء APK. الاختبار الفعلي لمنتقي الملفات يتطلب جهازًا؛ اختبارات PHP لا تحل محله.

