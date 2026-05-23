# laravel-traits

A collection of reusable Laravel Eloquent model traits for common functionalities.

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Tests](https://github.com/osoobe/laravel-traits/actions/workflows/tests.yml/badge.svg)](https://github.com/osoobe/laravel-traits/actions/workflows/tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/osoobe/laravel-traits.svg?style=flat-square)](https://packagist.org/packages/osoobe/laravel-traits)

## Requirements

| Package Version | Laravel | PHP |
|----------------|---------|-----|
| 2.2.x | 8.x – 12.x | 8.1+ |
| 2.x | 6.x – 10.x | 7.3+ |

## Installation

```bash
composer require osoobe/laravel-traits
```

The service provider is auto-discovered via Laravel's package discovery.

---

## Available Traits

| Trait | Namespace | Purpose |
|-------|-----------|---------|
| [Active](#active) | `Osoobe\LaravelTraits\Support` | Active/inactive/hidden status scopes |
| [AdvanceWhereQuery](#advancewherequery) | `Osoobe\LaravelTraits\Support` | Conditional query building |
| [BelongsToUser](#belongstouser) | `Osoobe\LaravelTraits\Support` | User ownership relationship |
| [HasEmail](#hasemail) | `Osoobe\LaravelTraits\Support` | Email verification helpers and scopes |
| [HasFiles](#hasfiles) | `Osoobe\LaravelTraits\Support` | File storage and management |
| [HasFullTextSearch](#hasfulltextsearch) | `Osoobe\LaravelTraits\Support` | MySQL full-text search |
| [HasLang](#haslang) | `Osoobe\LaravelTraits\Support` | Multi-language support |
| [HasLocation](#haslocation) | `Osoobe\LaravelTraits\Support` | Address formatting and validation |
| [HasPhoneNumber](#hasphonenumber) | `Osoobe\LaravelTraits\Support` | Phone number utilities |
| [HasSlug](#hasslug) | `Osoobe\LaravelTraits\Support` | Slug-based model retrieval |
| [IsDefault](#isdefault) | `Osoobe\LaravelTraits\Support` | Default item scopes |
| [IsVerified](#isverified) | `Osoobe\LaravelTraits\Support` | Verification status scopes |
| [SEO](#seo) | `Osoobe\LaravelTraits\Support` | SEO metadata generation |
| [TimeDiff](#timediff) | `Osoobe\LaravelTraits\Support` | Time-based scopes and expiry management |
| [ToString](#tostring) | `Osoobe\LaravelTraits\Support` | Convert model properties to string |
| [Userstamp](#userstamp) | `Osoobe\LaravelTraits\Support` | Track who created/updated a record |
| [TestRouteTrait](#testroutetrait) | `Osoobe\LaravelTraits\Test` | HTTP route testing helpers |

---

## Active

Provides query scopes for active/inactive/hidden status management.

### Required columns

```php
$table->boolean('is_active')->default(false);
$table->boolean('hidden')->default(false);
```

### Model setup

```php
use Osoobe\LaravelTraits\Support\Active;

class Post extends Model
{
    use Active;
}
```

### Usage

```php
// Get all published posts
$posts = Post::active()->get();

// Get all drafts or inactive posts
$drafts = Post::notActive()->get();

// Get posts hidden from listings (but not deleted)
$hidden = Post::hidden()->get();

// Combine scopes
$activeVisible = Post::active()->where('hidden', false)->paginate(20);
```

### Use cases
- Blog posts with draft/published toggle
- Product listings with enabled/disabled state
- Menu items with show/hide control
- Feature flags on user-facing content

---

## AdvanceWhereQuery

Adds a conditional WHERE clause that is completely skipped when the value is empty — avoids manual `if ($value)` checks in controllers.

### Model setup

```php
use Osoobe\LaravelTraits\Support\AdvanceWhereQuery;

class Product extends Model
{
    use AdvanceWhereQuery;
}
```

### Usage

```php
// If $categoryId is empty/null, the WHERE is skipped entirely
$products = Product::query()
    ->whereKeyOrNull('category_id', $request->category_id)
    ->whereKeyOrNull('brand_id', $request->brand_id)
    ->get();
```

### Use cases
- Search/filter forms where any field may be blank
- API endpoints with optional query parameters
- Admin list pages with optional column filters

---

## BelongsToUser

Adds a standard `belongsTo` relationship to `App\Models\User` via a `user_id` foreign key.

### Required columns

```php
$table->foreignId('user_id')->constrained()->cascadeOnDelete();
```

### Model setup

```php
use Osoobe\LaravelTraits\Support\BelongsToUser;

class Post extends Model
{
    use BelongsToUser;
}
```

### Usage

```php
// Access the owner
$post->user;                         // User model
$post->user->name;                   // "Jane Smith"

// Query by owner
Post::where('user_id', auth()->id())->get();

// Eager load
Post::with('user')->latest()->get();
```

### Use cases
- Blog posts owned by a user
- Orders placed by a customer
- Files uploaded by a user
- Comments written by a user

---

## HasEmail

Email verification helpers and query scopes for models that store an `email` field.

### Required columns

```php
$table->string('email')->unique();
$table->timestamp('email_verified_at')->nullable();
```

### Model setup

```php
use Osoobe\LaravelTraits\Support\HasEmail;

class Subscriber extends Model
{
    use HasEmail;
}
```

### Methods

| Method | Description |
|--------|-------------|
| `isEmailVerified()` | Returns `true` if `email` and `email_verified_at` are both set |
| `scopeEmail($query, $email)` | Filter by exact email address |
| `scopeEmailVerified($query)` | Only records with a verified email |
| `scopeEmailNotVerified($query)` | Only records awaiting verification |
| `scopeEmailVerifiedSince($query, $time, $carbon_fn)` | Verified within a time window |

### Usage

```php
// Instance check
if ($subscriber->isEmailVerified()) {
    // send marketing email
}

// Query scopes
$verified   = Subscriber::emailVerified()->get();
$unverified = Subscriber::emailNotVerified()->get();
$recent     = Subscriber::emailVerifiedSince(7, 'subDays')->get();  // last 7 days
$byEmail    = Subscriber::email('jane@example.com')->first();

// Combined
$activeVerified = Subscriber::emailVerified()
    ->where('subscribed', true)
    ->get();
```

### Use cases
- Newsletter subscription management
- User account verification flows
- Filtering unverified leads in a CRM
- Sending verification reminders to users who signed up recently

---

## HasFiles

File storage helpers that namespace all files under a model-specific directory path using the class name and model ID.

**Path format**: `{plural-kebab-class}/{kebab-class}-{id}/filename`

Example: A `UserDocument` model with `id = 42` stores files under `user-documents/user-document-42/`.

### Required columns

```php
$table->id(); // used in path generation
```

### Model setup

```php
use Osoobe\LaravelTraits\Support\HasFiles;

class UserDocument extends Model
{
    use HasFiles;
}
```

### Methods

| Method | Description |
|--------|-------------|
| `kekabPath()` | Returns the model's storage directory path |
| `storeFile($path, $contents, $disk, $options)` | Store a file on any disk |
| `storePublicFile($path, $contents, $options)` | Store on the `public` disk |
| `storePrivateFile($path, $contents, $options)` | Store on the `private` disk |
| `getPublicFile($filename)` | Absolute path to a public file |
| `getPrivateFile($filename)` | Absolute path to a private file |
| `publicDisk()` | Returns a Storage disk instance for public files |
| `privateDisk()` | Returns a Storage disk instance for private files |
| `downloadFile($path, $disk)` | Trigger a file download response |

### Usage

```php
$doc = UserDocument::find(1);

// Generate path: "user-documents/user-document-1"
$path = $doc->kekabPath();

// Store an uploaded file
$doc->storePublicFile('avatar.jpg', $request->file('avatar'));
$doc->storePrivateFile('contract.pdf', file_get_contents($pdfPath));

// Get absolute path
$absolutePath = $doc->getPublicFile('avatar.jpg');

// Download
return $doc->downloadFile('contract.pdf', 'private');
```

### Use cases
- User profile photo management
- Document management systems
- Per-model media libraries
- Invoice and receipt storage

---

## HasFullTextSearch

MySQL MATCH/AGAINST full-text search integration. **Requires a MySQL FULLTEXT index** on the target columns. Not compatible with SQLite.

### Required database setup

```php
// Migration
$table->fullText(['title', 'body']);
```

### Model setup

```php
use Osoobe\LaravelTraits\Support\HasFullTextSearch;

class Article extends Model
{
    use HasFullTextSearch;
}
```

### Methods

| Method | Description |
|--------|-------------|
| `scopeFullTextSearch($query, $columns, $text, $boolMode)` | Adds WHERE MATCH/AGAINST clause |
| `buildFullTextSearch($columns, $text, $boolMode)` | Static: builds raw SQL string |
| `selectFTSScore($columns, $text, $boolMode)` | Static: builds a score SELECT expression |

### Usage

```php
// Basic full-text search
$results = Article::fullTextSearch(['title', 'body'], 'laravel eloquent')->get();

// Boolean mode (supports +required -excluded operators)
$results = Article::fullTextSearch(['title', 'body'], '+laravel -php', true)->get();

// With relevance score (order by best match)
$scoreExpr = Article::selectFTSScore(['title', 'body'], 'laravel');
$results = Article::select('*')
    ->selectRaw($scoreExpr)
    ->fullTextSearch(['title', 'body'], 'laravel')
    ->orderByDesc('fts_score')
    ->get();

// Raw SQL string
$sql = Article::buildFullTextSearch(['title'], 'keyword', true);
// → "MATCH (title) AGAINST ('keyword' IN BOOLEAN MODE)"
```

### Use cases
- Site-wide search functionality
- Article/blog search with relevance ranking
- Product catalog search
- Knowledge base search

---

## HasLang

Multi-language support via a `lang` column. Filters content by the current application locale.

### Required columns

```php
$table->string('lang', 10)->default('en');
```

### Model setup

```php
use Osoobe\LaravelTraits\Support\HasLang;

class Article extends Model
{
    use HasLang;
}
```

### Methods

| Method | Description |
|--------|-------------|
| `scopeLang($query)` | Filter by current `app()->getLocale()` |
| `scopeLangEN($query)` | Filter to English (`lang = 'en'`) |
| `language()` | `hasOne` to `App\Language` via `iso_code` → `lang` |
| `isLangRTL()` | Returns `true` if the language is right-to-left |

### Usage

```php
// Get articles in the active locale (e.g., 'fr' if app is set to French)
$articles = Article::lang()->get();

// Always get English content (e.g., for fallback)
$fallback = Article::langEN()->get();

// RTL detection
if ($article->isLangRTL()) {
    // set text direction to RTL in template
}
```

### Use cases
- Multilingual CMS content
- Localized product descriptions
- i18n-aware API responses
- Right-to-left language support (Arabic, Hebrew)

---

## HasLocation

Address formatting and validation helpers. All methods are pure string operations — no database queries are made.

### Required columns

```php
$table->string('street_address')->nullable();
$table->string('city')->nullable();
$table->string('state')->nullable();
$table->string('zip_code')->nullable();
$table->string('country')->nullable();
```

### Model setup

```php
use Osoobe\LaravelTraits\Support\HasLocation;

class Store extends Model
{
    use HasLocation;
}
```

### Methods

| Method | Description |
|--------|-------------|
| `isValidLocation()` | Returns `true` if all four main address fields are set |
| `getFullAddress($delimiter, $exclude_zip)` | Formatted full address string |
| `getFullAddressWithZipCode()` | Full address including zip code |
| `getStateAddress()` | `"State Country"` string |
| `getCityAddress()` | `"City State Country"` string |
| `getGoogleMapLinkAttribute()` | Google Maps URL for the address |
| `getAddressArray()` | Address as a keyed array |

### Usage

```php
$store = Store::find(1);

// Validation
if ($store->isValidLocation()) {
    // show map or calculate shipping
}

// Formatting
echo $store->getFullAddress();
// → "123 Main St, Springfield, IL US"

echo $store->getFullAddressWithZipCode();
// → "123 Main St, Springfield, IL 62701 US"

echo $store->getCityAddress();
// → "Springfield IL US"

// Google Maps link (accessible via Eloquent accessor)
echo $store->google_map_link;
// → "http://maps.google.com/?q=123 Main St, Springfield, IL US"

// Array form
$address = $store->getAddressArray();
// → ['street' => '123 Main St', 'city' => 'Springfield', ...]
```

### Use cases
- Store locator pages
- Shipping address display
- Google Maps embed links
- Address label generation
- Delivery area validation

---

## HasPhoneNumber

Phone number utilities including E.164 normalization and query scoping.

### Required columns

```php
$table->string('phone_number')->nullable();
```

### Model setup

```php
use Osoobe\LaravelTraits\Support\HasPhoneNumber;

class Contact extends Model
{
    use HasPhoneNumber;
}
```

### Methods

| Method | Description |
|--------|-------------|
| `getPhoneNumberBaseAttribute()` | Strips the `+1` country code prefix |
| `scopePhoneNumber($query, $phone_number)` | Searches both formatted and raw phone number values |

### Usage

```php
$contact = Contact::find(1); // phone_number = '+15551234567'

// Strip country code for display
echo $contact->phone_number_base;  // "5551234567"

// Search for a contact by phone (handles different formats)
$found = Contact::phoneNumber('5551234567')->first();
$found = Contact::phoneNumber('+15551234567')->first();
```

### Use cases
- CRM contact search by phone number
- SMS/calling apps that display numbers without country code
- Deduplication of contacts stored in different formats
- Phone number lookup endpoints

---

## HasSlug

Slug-based model retrieval with fallback to numeric ID.

### Required columns

```php
$table->string('slug')->unique();
```

### Model setup

```php
use Osoobe\LaravelTraits\Support\HasSlug;

class Post extends Model
{
    use HasSlug;
}
```

### Methods

| Method | Description |
|--------|-------------|
| `hasSlug($query, $slug)` | Query scope: WHERE `slug` = value |
| `findBySlugOrFail($slug)` | Static: find by slug OR id; throws `ModelNotFoundException` |

### Usage

```php
// Route model binding alternative
$post = Post::findBySlugOrFail('my-first-post');

// Works with numeric IDs too
$post = Post::findBySlugOrFail(42);

// Query scope
$post = Post::hasSlug('my-first-post')->first();

// In a controller
public function show(string $slug)
{
    $post = Post::findBySlugOrFail($slug);
    return view('posts.show', compact('post'));
}
```

### Use cases
- SEO-friendly URLs for blog posts
- Product page routes
- Category pages
- URL shorteners with slug fallback

---

## IsDefault

Query scopes for default/non-default status.

### Required columns

```php
$table->boolean('is_default')->default(false);
```

### Model setup

```php
use Osoobe\LaravelTraits\Support\IsDefault;

class PaymentMethod extends Model
{
    use IsDefault;
}
```

### Usage

```php
// Get the default payment method
$default = PaymentMethod::isDefault()->where('user_id', $userId)->first();

// Get non-default methods
$others = PaymentMethod::notDefault()->where('user_id', $userId)->get();
```

### Use cases
- Default shipping addresses
- Default payment methods
- Preferred notification channels
- Default currency or language settings

---

## IsVerified

Verification status scopes and boolean accessor.

### Required columns

```php
$table->tinyInteger('verified')->default(0);
```

### Model setup

```php
use Osoobe\LaravelTraits\Support\IsVerified;

class Vendor extends Model
{
    use IsVerified;
}
```

### Methods

| Method | Description |
|--------|-------------|
| `scopeVerified($query)` | WHERE `verified` = 1 |
| `scopeNotVerified($query)` | WHERE `verified` != 1 |
| `getIsVerifiedAttribute()` | Returns `(bool) $this->verified` |

### Usage

```php
// Query scopes
$verifiedVendors = Vendor::verified()->get();
$pendingVendors  = Vendor::notVerified()->get();

// Boolean accessor
if ($vendor->is_verified) {
    // show verified badge
}

// Combined
$activeVerified = Vendor::verified()
    ->where('active', true)
    ->orderBy('name')
    ->get();
```

### Use cases
- Marketplace vendor verification
- Identity verification for users
- Document verification workflows
- Business listing approval

---

## SEO

Provides SEO metadata generation for models. Two abstract methods must be implemented.

### Model setup

```php
use Osoobe\LaravelTraits\Support\SEO;

class Product extends Model
{
    use SEO;

    public string $seo_model_name = 'Product';

    public function getRouteURL(): string
    {
        return route('products.show', $this->slug);
    }

    public function getSEOTitleAttribute(): string
    {
        return $this->name . ' | ' . config('app.name');
    }
}
```

### Methods

| Method | Description |
|--------|-------------|
| `getRouteURL()` | **Abstract** — return the canonical URL for this model |
| `getSEOTitleAttribute()` | **Abstract** — return the SEO `<title>` string |
| `getSEODescriptionAttribute()` | Auto-generates a meta description |
| `getURLAttribute()` | Returns `getRouteURL()` — accessible as `$model->url` |

### Usage

```php
$product = Product::find(1);

// In a Blade view
<title>{{ $product->seo_title }}</title>
<meta name="description" content="{{ $product->seo_description }}">
<link rel="canonical" href="{{ $product->url }}">

// Auto-generated description example:
// "Check out Cool Widget and other Products on MyShop"
```

### Use cases
- Blog post SEO metadata
- Product page open graph tags
- Category page canonical URLs
- Dynamic sitemap generation

---

## TimeDiff

The most comprehensive trait in the package. Provides human-readable time accessors, expiry management, and 20+ Eloquent query scopes.

### Required columns

```php
$table->timestamps();                               // created_at, updated_at
$table->timestamp('expiry_date')->nullable();       // for expiry methods
```

Model must cast `expiry_date` for expiry methods to work:
```php
protected $casts = ['expiry_date' => 'datetime'];
```

### Model setup

```php
use Osoobe\LaravelTraits\Support\TimeDiff;

class Coupon extends Model
{
    use TimeDiff;

    protected $casts = ['expiry_date' => 'datetime'];
}
```

### Accessors

```php
$model->created_time_diff;       // "2 hours ago"
$model->updated_time_diff;       // "just now"
$model->posted_time_diff;        // alias for created_time_diff
$model->expiry_date_time_diff;   // "in 3 days"
```

### Expiry management

```php
$coupon = Coupon::find(1);

// Set expiry
$coupon->expireInDays(30);   // expires in 30 days
$coupon->expireInHours(24);  // expires in 24 hours
$coupon->save();

// Check expiry
if ($coupon->isExpired()) {
    // deactivate coupon
}

// Time remaining
$mins    = $coupon->expiresInMinutes();
$seconds = $coupon->expiresInSeconds();
```

### Query scopes

```php
// Expiry scopes
Coupon::notExpired()->active()->get();
Coupon::expired()->delete();

// Creation time scopes
Post::createdToday()->get();
Post::createdSinceWeek()->get();
Post::recentlyCreated(3, 'subDays')->get();    // last 3 days
Post::createdHoursAgo(6)->get();               // last 6 hours
Post::createdMinutesAgo(30)->get();            // last 30 minutes
Post::createdDate(Carbon::parse('2024-01-15'))->get();
Post::createdWeek(Carbon::now())->get();       // this week
Post::createdMonth(Carbon::now())->get();      // this month

// Update time scopes
Post::recentlyUpdated(7, 'subDays')->get();
Post::updatedSinceWeek()->get();

// Range scopes
Post::olderThan(30)->get();                    // not updated in 30 days
Post::betweenDates('created_at', $start, $end)->get();

// Frequency scopes (exact time windows)
Order::hourly(2)->get();      // created during the hour 2 hours ago
Order::daily(1)->get();       // created yesterday
Order::weekly(1)->get();      // created last week
Order::monthly(1)->get();     // created last month
Order::yearly(1)->get();      // created last year
```

### Instance checks

```php
$post->recentlyCreated(1, 'subDays');  // created in the last day?
$post->recentlyUpdated(2, 'subHours'); // updated in the last 2 hours?
$post->justCreated();                   // created in the last 5 minutes?
$post->justCreated(10);                 // created in the last 10 minutes?
```

### Use cases
- Coupon/offer expiry management
- Activity feeds with "posted 2 hours ago"
- Admin dashboards with "new today" counts
- Scheduled cleanup of stale records
- Analytics aggregation by time period

---

## ToString

Converts selected model property values to a formatted, delimited string.

### Model setup

```php
use Osoobe\LaravelTraits\Support\ToString;

class Address extends Model
{
    use ToString;
}
```

### Usage

```php
$address = Address::find(1);

// Default delimiter is newline
echo $address->toString("\n", null, 'street_address', 'city', 'state');

// Custom delimiter and format
echo $address->propertyToString(', ', null, 'city', 'state', 'country');
// → "Springfield, Illinois, US"
```

### Use cases
- Generating formatted mailing labels
- Building display strings from multiple model fields
- Export formatters for reports

---

## Userstamp

Automatically records who created and last edited a model record using polymorphic relationships. Fires on Eloquent `creating` and `updating` events.

### Required columns

```php
$table->nullableMorphs('creator');   // creator_id, creator_type
$table->nullableMorphs('editor');    // editor_id, editor_type
```

### Model setup

```php
use Osoobe\LaravelTraits\Support\Userstamp;

class Invoice extends Model
{
    use Userstamp;
}
```

### Usage

```php
// User must be authenticated — the trait reads Auth::user() automatically
auth()->login($user);

$invoice = Invoice::create(['amount' => 500]);
$invoice->creator;  // User model who created it
$invoice->editor;   // User model who last edited it

// After an update
$invoice->update(['amount' => 600]);
$invoice->editor;  // now the user who just updated it

// In a view
Created by {{ $invoice->creator->name }} on {{ $invoice->created_at->format('M d, Y') }}
Last edited by {{ $invoice->editor->name }}
```

### Use cases
- Audit trails for financial records
- CMS content tracking
- Compliance logging (who approved what)
- Collaborative document editing

---

## TestRouteTrait

Testing helpers for HTTP route assertions in downstream applications and packages. Use this in your own test suite, not in this package's tests.

```php
use Osoobe\LaravelTraits\Test\TestRouteTrait;

class RouteTest extends TestCase
{
    use TestRouteTrait;

    public function test_public_pages_are_accessible(): void
    {
        $this->checkRoutes(
            ['/'],
            ['/about', 200],
            ['/contact', 200, fn($r) => $r->assertSee('Contact Us')]
        );
    }

    public function test_all_web_routes_respond(): void
    {
        $this->checkDefinedRoutes('web', ['admin', 'api']);
    }
}
```

### Methods

| Method | Description |
|--------|-------------|
| `checkRoute($path, $statusCode, $message)` | Assert a single route returns an expected status |
| `checkRoutes(...$pages)` | Assert multiple routes; each entry: `[path, code?, callback?]` |
| `setUpUserLogin($user, $redirect)` | Authenticate a user and assert redirect after login |
| `checkDefinedRoutes($middleware, $excludePatterns, $isMatchFn, $statusCodes)` | Test all registered GET routes for a given middleware group |

---

## Combining Multiple Traits

Traits are designed to be mixed and matched freely:

```php
use Osoobe\LaravelTraits\Support\Active;
use Osoobe\LaravelTraits\Support\BelongsToUser;
use Osoobe\LaravelTraits\Support\HasEmail;
use Osoobe\LaravelTraits\Support\HasSlug;
use Osoobe\LaravelTraits\Support\TimeDiff;
use Osoobe\LaravelTraits\Support\Userstamp;

class BlogPost extends Model
{
    use Active, BelongsToUser, HasSlug, TimeDiff, Userstamp;

    protected $casts = ['expiry_date' => 'datetime'];
}
```

```php
// Chain multiple trait scopes together
$posts = BlogPost::active()
    ->notExpired()
    ->recentlyCreated(7, 'subDays')
    ->with('user')
    ->latest()
    ->paginate(15);

// Find by slug with full context
$post = BlogPost::findBySlugOrFail('hello-world');
echo $post->created_time_diff;  // "3 days ago"
echo $post->user->name;         // post owner
echo $post->creator->name;      // who created it (via Userstamp)
```

---

## Testing

```bash
composer install
composer test
```

---

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for information on recent changes.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

- [Oshane Bailey](https://github.com/osoobe)
- [All Contributors](https://github.com/osoobe/laravel-traits/contributors)

## Security

If you discover any security-related issues, please email b4.oshany@gmail.com instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
