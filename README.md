# Strategy Pattern

Strategy Pattern "behavioral design pattern" guruhiga kiruvchi pattern bo'lib, biror algoritmlar oilasini bir nechta klasga bo'lib beradi va ular orasida obyektlar almashinuvini ta'minlaydi. Bu ta'rifni tushunish boshlanishiga biroz qiyinday ko'rinishi mumkin, lekin bu siz o'ylaganchalik qo'rqinchli narsa emas.

Strategy Patterni kodimizni qismlarga ajratib, ularning kodini yozishni davom ettirish(kengaytirish)ga juda oson qiladi.

Aslida, bu patterndan Laravel proyektlarda deyarli har kuni foydalanasiz (M: `Storage` va `Cache` fasadlaridan foydalanganingizda)

Faraz qilaylik, siz quyidagiga o'xshash kodni ishlatgansiz:

```
Cache::put('name', 'Salom', 600);
```

Yuqoridagi kodda, fasaddan foydalanib klas service container-dan olib ishlatilgan. Bu yerda fasad haqida to'liq ma'lumot berilmaydi, lekin bu yerda bilishimiz kerak bo'lgan narsa - Cache fasadi interfeysni klasga bog'lab, uni ishlatadi.

Laravel documentation yoki laravel kodlaridan ko'rgan bo'lsangiz, Laravel keshlashda turli xildagi drayverlardan foydalanadi (M: Redis, DynamoDB, Memcached va Database). Misol uchun, agar biz `.env` faylida cache drayverga `CACHE_DRIVER=redis` deb bersak, keshlangan ma'lumotlar Redis keshda saqlanishni boshlaydi. Yoki, agar `CACHE_DRIVER=database` qilinsa ma'lumotlar bazasida saqlanadi.

Har bitta kesh drayverida o'zining alohida klasi mavjud. .env faylida kesh drayver turini o'zgartirganimizda, Laravel kerak kesh klasni ishga tushiradi. Aynan shu joyda strategy pattern ishlatiladi.

Masalan, Laravelad Cache fasadini ishlatgan paytimiz, laravel `Illuminate\Contracts\Cache\Factory` interfeysini service container-dan oladi. U, avval, config-dan qaysi turdagi kesh drayver o'rnatilganini aniqlaydi. So'ngra shunga qarab kerakli klasni chaqiradi. Misol uchun, kesh drayverimiz `CACHE_DRIVER=redis` deb berilgan bo'lsa, `Factory` interfeysi chaqiriladi, va u o'z navbatida Redis keshni ishga tushiradi.

Ko'rib turganingizdek, strategy pattern kodni kengaytirishni osonlashtiradi. Masalan, agar biz o'zimizning kesh drayverimizni qo'shmoqchi bo'lsak, shunchaki uni yozib, keyin laravel-ga berib qo'ysak yetarli bo'ladi.

# Ishlatilishi

Endi, strategy patternni Laravel-da ishlatishni ko'ramiz.

Faraz qilaylik, biz laravel-da foydalanuvchilar valyuta kursini ko'radigan dastur qilyapmiz. Buning uchun, dasturimiz valyuta kurslari haqidagi ma'lumotlarni oladigan tashqi API-dan foydalansin.

API-dan ma'lumot oluvchi klasni ochamiz (Bundan oldin App papkasida Strategy papka ochiladi. Unda esa API papkasini ochib API klasni shu papkada yozamiz):

```bash
class ExchangeRatesApiIO
{
    public function getRate(string $from, string $to): float
    {
        $rate = 10.0;
        return $rate;
    }
}
```

Keyin esa, bu klasni kontroller metodida ishlatish uchun kontroller ochib olamiz:

`php artisan make:controller CurrencyController`

Kontrollerda valyuta ayirboshlash kursini olamiz. API klasni containerdan olishda dependency injection-dan foylanamiz:

```bash
class CurrencyController extends Controller
{
    public function __invoke(Request $request, ExchangeRatesApiIO $exchangeRatesApiIO): JsonResponse
    {
        $rate = $exchangeRatesApiIO->getRate(
            $request->from,
            $request->to,
        );

        return response()->json(['rate' => $rate]);
    }
}
```

Bu kod aytilganidek ishlaydi, lekin bitta muammo - `ExchangeRatesApiIO` klasi kontrollerga kuchli bog'lanib qolgan. Ya'ni, bu degani, agar boshqa API-dan foydalanmoqchi bo'lsak, `ExchangeRatesApiIO` klasini hamma joyda yangi klas bilan almashtirib chiqish kerak bo'ladi. Tasavvur qiling, agar proyekt hajmi katta bo'lsa, bu qanday muammoga olib keladi? Shu sababli ham, bu muammodan qochish uchun kontrollerda klas obyektini emas, interfeysni ishlatishimiz kerak. Interfeysni bog'lab ishlatish uchun esa strategy pattern-dan foydalanamiz.

Avval, `ExchangeRatesService` interfeysini yaratib olamiz (Strategy papkasida Base papkasini ochib shu yerda interfeyni yozamiz):

```bash
interface ExchangeRatesService
{
    public function getRate(string $from, string $to): float;
}
```

Keyin, ExchangeRatesApiIO klasimizni o'zgartiramiz:

```bash
class ExchangeRatesApiIO implements ExchangeRatesService
{
    public function getRate(string $from, string $to): float
    {
        $rate = 10.0;
        return $rate;
    }
}
```

Endi, kontroller metodida klasni emas interfeysni injection qilamiz:

```bash
class CurrencyController extends Controller
{
    public function __invoke(Request $request, ExchangeRatesService $exchangeRatesService): JsonResponse
    {
        $rate = $exchangeRatesService->getRate(
            $request->from,
            $request->to,
        );

        return response()->json(['rate' => $rate]);
    }
}
```

Albatta, interfeysni dependency injection qilib ExchangeRatesApiIO klasdan obyekt ololmaymiz. Shuning uchun, Laravel-ga container-dan interfeysni chaqirganda nima qilishi kerakligini aytishimiz kerak. Buni service provider-da amalga oshiramiz. Ko'pchilik buni `AppServiceProvider`-da qiladi. Biz esa, buning uchun alohida service provider ochamiz:

`php artisan make:provider ExchangeRatesServiceProvider`

Yangi service provider-imizni `config/app.php` faylda ro'yxatdan o'tkazib qo'yamiz:

```bash
'providers' => [
        // ...
        /*
         * Package Service Providers...
         */
        App\Providers\ExchangeRatesServiceProvider::class,
        // ...
    ],
```

Keyin esa, interfeys va klasimizni service provider ichida bog'lab qo'yamiz:

```bash
class ExchangeRatesServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(ExchangeRatesService::class, ExchangeRatesApiIO::class);
    }
}
```

##### *Yangi API qo'shilganda*

Faraz qilaylik, ma'lum vaqt o'tgandan so'ng yangi tashqi API qo'shib, ulardan keraklisini tanlab ishlatish kerak bo'lsin.

Avval, yangi API klasni yaratib olamiz:

```bash
class FixerIO implements ExchangeRatesService
{
    public function getRate(string $from, string $to): float
    {
        $rate = 15.0;
        return $rate;
    }
}
```

Keyin, `config/services.php` faylida yangi maydon ochamiz (kerakli klasni tanlash uchun sozlama):

```bash
return [
    'exchange-rates-driver' => env('EXCHANGE_RATES_DRIVER'),
];
```

Sozlamalarga qarab qaysi klasni ishlatish kerakligini aniqlash uchun service provider-imizga o'zgartirish kiritamiz:

```bash
class ExchangeRatesServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(ExchangeRatesService::class, function ($app) {
            if (config('services.exchange-rates-driver') === 'exchangeratesapiio') {
                return new ExchangeRatesApiIO();
            }

            if (config('services.exchange-rates-driver') === 'fixerio') {
                return new FixerIO();
            }

            throw new \Exception("Exchange rates uchun noto'g'ri driver turi berilgan!");
        });
    }
}
```

Agar, `EXCHANGE_RATES_DRIVER=exchangeratesapiio` deb bersak `ExchangeRatesService` interfeysidan `ExchangeRatesApiIO` klasini olamiz. `EXCHANGE_RATES_DRIVER=fixerio` qilsak `FixerIO` klasini qabul qilamiz.
