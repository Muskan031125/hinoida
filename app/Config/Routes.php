<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');
$routes->get('sitemap.xml', 'Sitemap::index');
$routes->get('llms.txt', 'Llms::index');   // plain-text site summary for answer engines
$routes->get('cron/email-send', 'Cron::emailSend');
$routes->get('cron/expire-featured', 'Cron::expireFeatured');
$routes->get('cron/rotate-featured', 'Cron::rotateFeatured');

// ---------------------------------------------------------------------------
// Public browsing — these are the live hinoida.com URL shapes, kept exactly so
// every indexed link, backlink and printed card still lands on the right page.
// The short paths this rebuild used at first (/l/28, /c/restaurants, /area/…)
// now 301 here; see the "legacy" block at the bottom.
// ---------------------------------------------------------------------------
$routes->get('listings', 'Search::index');
// the property front door — businesses are at /listings, flats and PGs here
$routes->get('property', 'Property::index');
$routes->post('property/alert', 'Property::alert');
$routes->post('search/save', 'Property::saveSearch');
$routes->post('search/delete/(:num)', 'Property::deleteSearch/$1');
// the original's search form posted here
$routes->match(['get', 'post'], 'search/search', 'Search::index');
$routes->get('category', 'Categories::index');
$routes->get('category/(:segment)', 'Category::show/$1');
$routes->get('city', 'Areas::index');

// News
$routes->get('news', 'Blog::index');
$routes->get('news/category/(:segment)', 'Blog::index/$1');
$routes->get('news/post/(:num)/(:segment)', 'Blog::show/$1');
$routes->get('news/post/(:num)', 'Blog::show/$1');
$routes->post('news/post/(:num)/comment', 'Blog::comment/$1');
$routes->get('news/(:num)', 'Blog::show/$1');

// Events
$routes->get('event', 'Events::index');
$routes->get('event/search', 'Events::index');
$routes->get('event/category/(:segment)', 'Events::index/$1');
$routes->get('event/post/(:num)/(:segment)', 'Events::show/$1');
$routes->get('event/post/(:num)', 'Events::show/$1');
$routes->get('event/(:num)', 'Events::show/$1');

// Offers
$routes->get('offer', 'Offers::index');
$routes->get('offer/search', 'Offers::index');
$routes->get('offer/category/(:segment)', 'Offers::index/$1');
$routes->get('offer/post/(:num)/(:segment)', 'Offers::show/$1');
$routes->get('offer/post/(:num)', 'Offers::show/$1');
$routes->get('offer/(:num)', 'Offers::show/$1');

// Listing actions. The pretty listing URL is /{type}/{slug}/{id} (registered at
// the very bottom); the form and beacon endpoints stay on the short path, which
// was never public-facing.
$routes->post('l/(:num)/enquiry', 'Listing::enquiry/$1');
$routes->post('l/(:num)/book', 'Listing::book/$1');
$routes->post('l/(:num)/review', 'Listing::review/$1');
$routes->post('l/(:num)/ask', 'Listing::ask/$1');
$routes->post('l/(:num)/report', 'Listing::report/$1');
$routes->post('l/(:num)/track', 'Listing::track/$1');
$routes->get('l/(:num)/save', 'Listing::save/$1');
$routes->post('wishlist/toggle', 'Listing::wishlist');
$routes->post('shortlist/set', 'Listing::shortlistSet');
$routes->post('shortlist/sync', 'Listing::shortlistSync');
$routes->get('claim/(:num)', 'Claim::form/$1');
$routes->post('claim/(:num)', 'Claim::send/$1');

// Pages the original kept under /home/…
$routes->get('home/about', 'Pages::about');
$routes->get('home/faq', 'Pages::faq');
$routes->get('home/privacy_policy', 'Pages::privacy');
$routes->get('home/terms_and_conditions', 'Pages::terms');
$routes->get('home/pricing', 'Packages::pricing');
$routes->get('contact', 'Pages::contact');
$routes->post('contact', 'Pages::contactSend');
$routes->post('send_contact_message', 'Pages::contactSend');
$routes->get('contact_us/(:segment)', 'Pages::contact');

// Pages with no equivalent on the original — they keep their own paths.
$routes->get('g/(:segment)', 'Groups::show/$1');
$routes->get('u/(:num)', 'Profile::show/$1');
$routes->get('compare', 'Compare::index');
$routes->get('a/(:segment)', 'Areas::place/$1');
$routes->get('card/(:num)', 'VCard::show/$1');
$routes->get('card/(:num)/download', 'VCard::download/$1');
$routes->get('advertise', 'Pages::advertise');
$routes->get('credits', 'Pages::credits');
$routes->get('cookie', 'Pages::cookie');

// Auth
$routes->get('home/login', 'Auth::login');
$routes->post('home/login', 'Auth::attempt');
$routes->get('login', 'Auth::login');
$routes->post('login', 'Auth::attempt');
$routes->post('login/validate_login', 'Auth::attempt');
$routes->get('home/sign_up', 'Auth::register');
$routes->post('home/sign_up', 'Auth::create');
$routes->get('directories/sign_up', 'Auth::registerBusiness');
$routes->post('directories/sign_up', 'Auth::create');
$routes->get('home/forgot_password', 'Auth::forgotForm');
$routes->post('home/forgot_password', 'Auth::forgotSend');
$routes->get('reset', 'Auth::resetForm');
$routes->post('reset', 'Auth::resetConfirm');
$routes->post('register', 'Auth::create');
$routes->get('signup/verify', 'Auth::otpForm');
$routes->post('signup/verify', 'Auth::otpConfirm');
$routes->post('signup/resend', 'Auth::otpResend');
$routes->get('signup/done', 'Auth::done');
$routes->get('verify-email', 'Auth::emailVerifyForm');
$routes->post('verify-email', 'Auth::emailVerifyConfirm');
$routes->post('verify-email/resend', 'Auth::emailVerifyResend');
$routes->get('verify', 'Auth::verify');
$routes->get('logout', 'Auth::logout');

// Owner
$routes->get('dashboard', 'Dashboard::index');
$routes->post('dashboard/profile/save', 'Dashboard::saveProfile');
$routes->get('dashboard/lead/(:segment)/(:num)/(:segment)', 'Dashboard::leadStatus/$1/$2/$3');
$routes->post('dashboard/review/(:num)/reply', 'Dashboard::replyReview/$1');
$routes->post('dashboard/question/(:num)/answer', 'Dashboard::answerQuestion/$1');
$routes->get('dashboard/photos/(:num)', 'Dashboard::photos/$1');
$routes->post('dashboard/photos/(:num)/save', 'Dashboard::photosSave/$1');
$routes->get('dashboard/catalog/(:num)', 'Dashboard::catalog/$1');
$routes->post('dashboard/catalog/(:num)/save', 'Dashboard::catalogSave/$1');
$routes->get('dashboard/catalog/(:num)/delete/(:num)', 'Dashboard::catalogDelete/$1/$2');
$routes->post('dashboard/listing/(:num)/delete', 'Dashboard::deleteListing/$1');
$routes->get('dashboard/booking/(:num)/(:segment)', 'Dashboard::bookingDecision/$1/$2');
$routes->post('dashboard/review/(:num)/edit', 'Dashboard::editReview/$1');
$routes->post('dashboard/review/(:num)/delete', 'Dashboard::deleteReview/$1');
$routes->get('dashboard/invoice/(:num)', 'Dashboard::invoice/$1');
$routes->post('dashboard/request/send', 'Dashboard::requestSend');
$routes->post('dashboard/listings/import', 'Dashboard::importListings');
$routes->post('dashboard/offer/save', 'Dashboard::saveOffer');
$routes->get('dashboard/(:segment)', 'Dashboard::section/$1');
$routes->get('inbox', 'Dashboard::inbox');
// Multi-step listing wizard
$routes->get('list', 'Wizard::step/basics/0');
$routes->get('list/(:segment)/(:num)', 'Wizard::step/$1/$2');
$routes->post('list/(:segment)/(:num)', 'Wizard::save/$1/$2');
$routes->post('list', 'Wizard::save/basics/0');
// Packages / pricing
$routes->get('pricing', 'Packages::pricing');
$routes->post('pricing/buy', 'Packages::buy');
$routes->get('pricing/checkout/(:num)', 'Packages::checkout/$1');
$routes->post('pricing/return', 'Packages::paymentReturn');

// Admin
$routes->get('admin', 'Admin::index');
// bulk per-listing SEO (the original's "SEO report") — before the wildcard,
// which would otherwise swallow it as a settings section
$routes->get('admin/seo-bulk', 'Admin::seoBulk');
$routes->get('admin/seo-bulk/export', 'Admin::exportSeo');
$routes->post('admin/seo-bulk/import', 'Admin::importSeo');
$routes->get('admin/(:segment)', 'Admin::section/$1');
$routes->post('admin/moderate', 'Admin::moderate');
$routes->get('admin/approve-user/(:num)', 'Admin::approveUser/$1');
$routes->get('admin/user/(:num)', 'Admin::userDetail/$1');
$routes->get('admin/user/(:segment)/(:num)', 'Admin::userAction/$1/$2');
$routes->get('admin/content/(:segment)', 'AdminContent::index/$1');
$routes->post('admin/content/(:segment)/save', 'AdminContent::save/$1');
$routes->post('admin/content/(:segment)/delete/(:num)', 'AdminContent::delete/$1/$2');
$routes->post('admin/content/qa/answer/(:num)', 'AdminContent::answer/$1');
$routes->get('admin/listing/new', 'Admin::editListing/0');
$routes->get('admin/listing/(:num)', 'Admin::editListing/$1');
$routes->post('admin/listing/save', 'Admin::saveListing');
$routes->post('admin/listing/delete/(:num)', 'Admin::deleteListing/$1');
$routes->post('admin/crud/(:segment)', 'Admin::crud/$1');
$routes->get('admin/export/listings', 'Admin::exportListings');
$routes->get('admin/export/categories', 'Admin::exportCategories');
$routes->get('admin/export/users', 'Admin::exportUsers');
$routes->get('admin/export/leads', 'Admin::exportLeads');
$routes->get('admin/export/reviews', 'Admin::exportReviews');
$routes->get('admin/claim/(:num)/(:segment)', 'Claim::decide/$1/$2');
$routes->post('admin/settings/save', 'Admin::saveSettings');
$routes->post('admin/seo/save', 'Admin::saveSeo');
$routes->post('admin/map/save', 'Admin::saveMap');
$routes->post('admin/frontend/save', 'Admin::saveFrontend');
$routes->post('admin/sitetext/save', 'Admin::saveSiteText');
$routes->get('admin/change/(:num)/(:segment)', 'Admin::changeAction/$1/$2');
$routes->post('admin/package/save', 'Admin::savePackage');
$routes->post('admin/payment/offline', 'Admin::offlinePayment');
$routes->get('admin/export/revenue', 'Admin::exportRevenue');
$routes->post('admin/import/listings', 'Admin::importListings');
$routes->get('admin/import/template', 'Admin::importTemplate');
$routes->get('admin/booking/(:num)/(:segment)', 'Admin::bookingAction/$1/$2');
$routes->get('admin/review/(:num)/(:segment)', 'Admin::reviewAction/$1/$2');
$routes->get('admin/comment/(:num)/(:segment)', 'Admin::commentAction/$1/$2');
$routes->get('admin/addon/(:segment)', 'Admin::toggleAddon/$1');
$routes->post('admin/profile/save', 'Admin::saveOwnProfile');
$routes->get('admin/report/(:num)/(:segment)', 'Admin::reportAction/$1/$2');
$routes->post('admin/language/(:segment)', 'Admin::language/$1');
$routes->get('admin/export/category-tree', 'Admin::exportCategoryTree');
$routes->get('admin/export/user-stats', 'Admin::exportUserStats');
$routes->get('admin/export/user-listings/(:num)', 'Admin::exportUserListings/$1');
$routes->get('admin/request/(:num)/(:segment)', 'Admin::requestAction/$1/$2');
$routes->post('admin/request/(:num)/reply', 'Admin::requestReply/$1');
$routes->post('admin/quality/save', 'Admin::saveQuality');
$routes->post('admin/place/(:segment)/save', 'Admin::savePlace/$1');

// XHR endpoints (Home/User/Admin::get_*_by_*, set_location, footer_more_category)
$routes->match(['get','post'], 'ajax/cities', 'Ajax::cities');
$routes->match(['get','post'], 'ajax/localities', 'Ajax::localities');
$routes->match(['get','post'], 'ajax/areas', 'Ajax::areas');
$routes->match(['get','post'], 'ajax/categories-by-locality', 'Ajax::categoriesByLocality');
$routes->post('ajax/set-location', 'Ajax::setLocation');
// header locality filter — remembered for the session
$routes->get('locality/set/(:num)', 'Ajax::setLocality/$1');
$routes->get('locality/clear', 'Ajax::setLocality/0');
$routes->get('ajax/footer-categories', 'Ajax::footerCategories');
$routes->get('ajax/search-index', 'Ajax::searchIndex');

// ---------------------------------------------------------------------------
// Legacy short paths from the first cut of this rebuild — 301 to the live
// shapes above so nothing that was linked in the meantime dead-ends.
// ---------------------------------------------------------------------------
$routes->get('l/(:num)', 'Legacy::listing/$1');
$routes->get('c/(:segment)', 'Legacy::category/$1');
$routes->get('categories', 'Legacy::page/categories');
$routes->get('area/(:segment)', 'Legacy::locality/$1');
$routes->get('areas', 'Legacy::page/areas');
$routes->get('search', 'Legacy::page/search');
$routes->get('events', 'Legacy::page/events');
$routes->get('events/(:segment)', 'Legacy::post/event/$1');
$routes->get('offers', 'Legacy::page/offers');
$routes->get('offers/(:segment)', 'Legacy::post/offer/$1');
$routes->get('news/(:segment)', 'Legacy::post/news/$1');
$routes->post('news/(:segment)/comment', 'Blog::comment/$1');
$routes->get('about', 'Legacy::page/about');
$routes->get('faq', 'Legacy::page/faq');
$routes->get('privacy', 'Legacy::page/privacy');
$routes->get('terms', 'Legacy::page/terms');
$routes->get('register', 'Legacy::page/register');
$routes->get('register/business', 'Legacy::page/register/business');
$routes->get('forgot', 'Legacy::page/forgot');
$routes->post('forgot', 'Auth::forgotSend');

// ---------------------------------------------------------------------------
// Catch-alls, last on purpose.
//   /{listing_type}/{slug}/{id}   a business — the id identifies it, the slug
//                                 is decoration, exactly as on the original
//   /{locality-slug}              a locality, bare at the root (/sector-63)
// Anything that matches neither falls through to a 404 inside the controller.
// ---------------------------------------------------------------------------
$routes->get('(:segment)/(:segment)/(:num)', 'Listing::show/$3');
$routes->get('(:segment)', 'Areas::show/$1');
