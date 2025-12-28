<?php

/**
 * All application routes are defined here
 * This file is included twice: once directly (for app) and once within /web/ group (for web)
 */

$routes->get('test-gcm', 'TestController::testGcm');

$routes->get('test', 'Home::generateThumbnailImages');
$routes->get('test2', 'FeaturesController::test2');

// Product Dashboard
$routes->group('dashboard', static function ($routes) {
    $routes->get('get-statics', 'ProductDashboardController::getProductStatics');
    $routes->get('products-statics', 'ProductDashboardController::productsStats');
    $routes->get('getQuotationsdata', 'DashboardController::getDashboardData');
});

// Privileges
$routes->group('privileges', static function ($routes) {
    $routes->post('create-section', 'PrivilegesController::createSection');
    $routes->post('create-role', 'PrivilegesController::createRole');
    $routes->post('update-role', 'PrivilegesController::updateRole');
    $routes->get('get-role/(:num)', 'PrivilegesController::getRole/$1');
    $routes->delete('delete-role', 'PrivilegesController::deleteRole');
    $routes->get('get-all-roles', 'PrivilegesController::getAllRoles');
    $routes->get('get-all-sections', 'PrivilegesController::getAllSections');
    $routes->get('get-admin-privileges', 'PrivilegesController::getAdminPrivileges');
});

// Invoice Routes
$routes->get('generate-invoice', 'InvoiceController::makeInvoice');

//Arrange free customers//
$routes->group('customer', static function ($routes) {
    $routes->post('send-otp', 'CustomerController::sendOTP');
    $routes->post('new-send-otp', 'CustomerController::sendSeebOTP');
    $routes->post('login', 'CustomerController::login');
    $routes->post('register', 'CustomerController::createCustomer');
    $routes->post('getCustomer', 'CustomerController::getCustomer');
    $routes->post('contact-us/query', 'CustomerController::contactUs');
    $routes->put('updateCustomer/(:num)', 'CustomerController::updateCustomer/$1');
    $routes->delete('delete/(:num)', 'CustomerController::deleteCustomer/$1');
    $routes->group('/', ['filter' => 'authFilter'], static function ($routes) {
        $routes->get('getCustomerById/(:num)', 'CustomerController::getCustomerById/$1');
        $routes->get('getRecentView', 'RecentlyViewedController::getRecentView');
        $routes->get('getRecentViewBySlug/(:any)', 'RecentlyViewedController::getRecentViewBySlug/$1');
        $routes->get('deleteCustomer/(:num)', 'CustomerController::deleteCustomer/$1');
        $routes->post('cancel-order', 'OrderController::cancelOrder');
    });
    $routes->post('getAllContactUs', 'CustomerController::getAllContactUs');
    $routes->put('updateRemark/(:num)', 'CustomerController::updateRemark/$1');
});

$routes->group('staff', static function ($routes) {
    $routes->post('create', 'StaffController::Create');
    $routes->get('getAllStaffs', 'StaffController::getAllStaffs');
    $routes->get('getByID/(:num)', 'StaffController::getAllStaffByID/$1');
    $routes->put('updateStaff/(:num)', 'StaffController::UpdateStaff/$1');
    $routes->put('update-status/(:num)', 'StaffController::UpdateStaffstatus/$1');
    $routes->delete('Delete/(:num)', 'StaffController::Delete/$1');
    $routes->post('FileUpload', 'StaffController::FileUpload');
    $routes->post('filedelete', 'StaffController::deletefile');
});


$routes->group('quotation', static function ($routes) {
    $routes->post('create', 'QuotationController::store');
    $routes->post('getAll', 'QuotationController::getAll');
    $routes->put('update/(:num)', 'QuotationController::update/$1');
    $routes->get('getById/(:num)', 'QuotationController::getById/$1');
    $routes->get('quotationById/(:num)', 'QuotationController::quotationById/$1');
    $routes->get('customerMobileNumber/(:num)', 'QuotationController::quotationByCustomerMobileNumber/$1');
    $routes->post('changeStatus/(:num)', 'QuotationController::changeStatus/$1');
});

$routes->group('sites', static function ($routes) {
    $routes->post('getAllSites', 'QuotationController::getAllSites');
});

//mailing
$routes->get('/post_order_mail/(:num)', 'MailingController::post_order_mail/$1');

//Interior Contacty Us
$routes->group('interior', static function ($routes) {
    $routes->post('contactUs', 'InteriorContactUsController::contactUs');
    $routes->post('getAllContactUs', 'InteriorContactUsController::getAllContactUs');
    $routes->put('updateRemark/(:num)', 'InteriorContactUsController::updateRemark/$1');
});


//Admin//

$routes->group('admin', static function ($routes) {
    $routes->post('adminSendOTP', 'AdminController::adminSendOTP');
    $routes->post('adminLogin', 'AdminController::adminLogin');
    $routes->post('register', 'AdminController::createAdmin');
    $routes->get('getAdmin', 'AdminController::getAdmin');
    $routes->get('getAdminByID/(:num)', 'AdminController::getAdminByID/$1');
    $routes->put('updateAdmin/(:num)', 'AdminController::updateAdmin/$1');
    $routes->delete('deleteAdmin/(:num)', 'AdminController::deleteAdmin/$1');

    // Customer
    $routes->get('getCustomer/(:num)', 'CustomerController::getCustomer_fromAdmin/$1');

    // Orders
    $routes->post('update-order-status', 'OrderController::updateOrderStatus');


    // Home Zone
    $routes->post('AddHomeZone', 'HomeZoneController::createHomeZone');
    $routes->get('getHomeZone', 'HomeZoneController::getHomeZone');
    $routes->put('updateHomeZone/(:num)', 'HomeZoneController::updateHomeZone/$1');
    $routes->delete('deleteHomeZone/(:num)', 'HomeZoneController::deleteHomeZone/$1');

    //Home zone Category
    $routes->post('AddHomeZoneCaterory', 'HomeZoneCategoryController::createHomeZoneCaterory');
    $routes->get('getHomeZoneCaterory', 'HomeZoneCategoryController::getHomeZoneCaterory');
    $routes->post('updateHomeZoneSubCategoryImage', 'HomeZoneCategoryController::updateHomeZoneSubCategoryImage');
    $routes->post('updateHomeZoneCaterory/(:num)', 'HomeZoneCategoryController::updateHomeZoneCaterory/$1');
    $routes->delete('deleteHomeZoneCaterory/(:num)', 'HomeZoneCategoryController::deleteHomeZoneCaterory/$1');
    $routes->delete('deleteHomeZoneCaterory/(:num)', 'HomeZoneCategoryController::deleteHomeZoneCaterory/$1');
    $routes->get('getHomeZoneCateroryByid/(:num)', 'HomeZoneCategoryController::getHomeZoneCateroryByid/$1');

    //Home zone Appliances
    $routes->post('AddHomeZoneAppliances', 'HomeZoneAppliancesController::createHomeZoneAppliances');
    $routes->get('getHomeZoneAppliances', 'HomeZoneAppliancesController::getHomeZoneAppliances');
    $routes->get('getHomeZoneAppliancesWithOrderCount', 'HomeZoneAppliancesController::getHomeZoneAppliancesWithOrderCount');
    $routes->get('getHomeZoneAppliancesById/(:any)', 'HomeZoneAppliancesController::getHomeZoneAppliancesById/$1');
    $routes->get('getBestHomeZoneAppliancesDeals/(:num)', 'HomeZoneAppliancesController::getBestHomeZoneAppliancesDeals/$1');
    $routes->get('getHomeZoneAppliancesWithCategory', 'HomeZoneAppliancesController::getHomeZoneAppliancesWithCategory');
    $routes->post('updateHomeZoneAppliances/(:num)', 'HomeZoneAppliancesController::updateHomeZoneAppliances/$1');
    $routes->delete('deleteHomeZoneAppliances/(:num)', 'HomeZoneAppliancesController::deleteHomeZoneAppliances/$1');
    $routes->post('updateHomeZoneCategoryImage', 'HomeZoneAppliancesController::updateHomeZoneCategoryImage');
    $routes->delete('deleteHomeZoneImage', 'HomeZoneAppliancesController::deleteHomeZoneImage');

    $routes->get('partner-referrals', 'PartnerReferralController::adminReferralSummary');
});

// Payment routes
$routes->group('payment', static function ($routes) {
    $routes->post('init-razorpay', 'PaymentController::initRazorpay');
    $routes->post('verify-payment', 'PaymentController::verifyPayment');
    $routes->post('capture-payment', 'PaymentController::capturePayment');
    $routes->get('order-details/(:num)', 'PaymentController::getOrderDetails/$1');
});

// Blog routes
$routes->group('blog', static function ($routes) {
    $routes->get('list', 'BlogsController::index');
    $routes->get('detail/(:any)', 'BlogsController::detail/$1');
    $routes->post('create', 'BlogsController::create');
    $routes->put('update/(:num)', 'BlogsController::update/$1');
    $routes->delete('delete/(:num)', 'BlogsController::delete/$1');
});

// Master data routes
$routes->group('master', function ($routes) {
    $routes->get('home-zone', 'HomeMasterController::getHomeZones');
    $routes->get('categories', 'MasterController::getCategories');
    $routes->get('services', 'MasterController::getServices');
});

// Campaigns routes
$routes->group('Campaign', static function ($routes) {
    $routes->post('create', 'CampaignsDataController::createCampaign');
    $routes->get('list', 'CampaignsDataController::getCampaignsList');
    $routes->get('detail/(:num)', 'CampaignsDataController::getCampaignDetail/$1');
});

// AppText routes
$routes->group('AppText', static function ($routes) {
    $routes->get('section/(:any)', 'AppTextController::getAppTextBySection/$1');
    $routes->post('create', 'AppTextController::create');
    $routes->put('update/(:num)', 'AppTextController::update/$1');
});

// Product routes
$routes->group('product', static function ($routes) {
    $routes->get('list', 'ProductController::getProducts');
    $routes->get('detail/(:any)', 'ProductController::getProductDetail/$1');
    $routes->get('search', 'ProductController::searchProducts');
    $routes->get('trending', 'ProductController::getTrendingProducts');
    $routes->get('by-category/(:num)', 'ProductController::getProductsByCategory/$1');
    $routes->post('create', 'ProductController::create');
    $routes->put('update/(:num)', 'ProductController::update/$1');
    $routes->delete('delete/(:num)', 'ProductController::delete/$1');
});

// General routes
$routes->group('general', static function ($routes) {
    $routes->get('settings', 'GeneralController::getSettings');
    $routes->get('currencies', 'GeneralController::getCurrencies');
    $routes->post('contact-support', 'GeneralController::contactSupport');
});

// Booking routes
$routes->group('booking', static function ($routes) {
    $routes->post('create', 'BookingController::createBooking');
    $routes->get('list', 'BookingController::getAllBookings');
    $routes->get('user/(:num)', 'BookingController::getBookingsByUser/$1');
    $routes->get('detail/(:num)', 'BookingController::getBookingById/$1');
    $routes->post('verify-payment', 'BookingController::verifyPayment');
    $routes->post('add-manual-payment', 'BookingController::addManualPayment');
    $routes->post('change-status/(:num)', 'BookingController::changeStatus/$1');
    $routes->delete('delete/(:num)', 'BookingController::deleteBooking/$1');
    $routes->post('webhook-razorpay', 'BookingController::webhookRazorpay');
});

// Order routes
$routes->group('order', static function ($routes) {
    $routes->post('create', 'OrderController::createOrder');
    $routes->get('list', 'OrderController::getAllOrders');
    $routes->get('user/(:num)', 'OrderController::getOrdersByUser/$1');
    $routes->get('detail/(:num)', 'OrderController::getOrderById/$1');
    $routes->post('update-status', 'OrderController::updateOrderStatus');
    $routes->delete('cancel/(:num)', 'OrderController::cancelOrder/$1');
});

// Cart routes
$routes->group('cart', static function ($routes) {
    $routes->get('list/(:num)', 'CartController::getCart/$1');
    $routes->post('add', 'CartController::addToCart');
    $routes->put('update/(:num)', 'CartController::updateCart/$1');
    $routes->delete('remove/(:num)', 'CartController::removeFromCart/$1');
    $routes->post('clear/(:num)', 'CartController::clearCart/$1');
    $routes->post('apply-coupon', 'CartController::applyCoupon');
});

// Coupon routes
$routes->group('coupon', static function ($routes) {
    $routes->get('validate/(:any)', 'CouponController::validateCoupon/$1');
    $routes->get('list', 'CouponController::getCoupons');
    $routes->post('create', 'CouponController::create');
    $routes->put('update/(:num)', 'CouponController::update/$1');
    $routes->delete('delete/(:num)', 'CouponController::delete/$1');
});

// Partner routes
$routes->group('partner', static function ($routes) {
    $routes->post('register', 'PartnerController::registerPartner');
    $routes->post('login', 'PartnerController::loginPartner');
    $routes->get('detail/(:num)', 'PartnerController::getPartnerDetail/$1');
    $routes->put('update/(:num)', 'PartnerController::updatePartner/$1');
    $routes->get('stats/(:num)', 'PartnerController::getPartnerStats/$1');
});

// Reviews and ratings routes
$routes->group('rating', static function ($routes) {
    $routes->post('submit', 'RatingController::submitRating');
    $routes->get('product/(:num)', 'RatingController::getProductRatings/$1');
    $routes->get('average/(:num)', 'RatingController::getAverageRating/$1');
});

// Wishlist routes
$routes->group('wishlist', static function ($routes) {
    $routes->get('user/(:num)', 'WishlistController::getUserWishlist/$1');
    $routes->post('add', 'WishlistController::addToWishlist');
    $routes->delete('remove/(:num)', 'WishlistController::removeFromWishlist/$1');
});

// Address routes
$routes->group('address', static function ($routes) {
    $routes->get('user/(:num)', 'AddressController::getUserAddresses/$1');
    $routes->post('create', 'AddressController::createAddress');
    $routes->put('update/(:num)', 'AddressController::updateAddress/$1');
    $routes->delete('delete/(:num)', 'AddressController::deleteAddress/$1');
});

// Category routes
$routes->group('category', static function ($routes) {
    $routes->get('list', 'CategoryController::getCategories');
    $routes->get('detail/(:num)', 'CategoryController::getCategoryDetail/$1');
    $routes->post('create', 'CategoryController::create');
    $routes->put('update/(:num)', 'CategoryController::update/$1');
    $routes->delete('delete/(:num)', 'CategoryController::delete/$1');
});

// Service routes
$routes->group('service', static function ($routes) {
    $routes->get('list', 'ServiceController::getServices');
    $routes->get('detail/(:num)', 'ServiceController::getServiceDetail/$1');
    $routes->get('category/(:num)', 'ServiceController::getServicesByCategory/$1');
    $routes->post('create', 'ServiceController::create');
    $routes->put('update/(:num)', 'ServiceController::update/$1');
    $routes->delete('delete/(:num)', 'ServiceController::delete/$1');
});

// Notification routes
$routes->group('notification', static function ($routes) {
    $routes->get('list/(:num)', 'NotificationController::getUserNotifications/$1');
    $routes->post('mark-read/(:num)', 'NotificationController::markAsRead/$1');
    $routes->delete('delete/(:num)', 'NotificationController::deleteNotification/$1');
});

// Report/Complaint routes
$routes->group('complaint', static function ($routes) {
    $routes->post('submit', 'ComplaintsController::submitComplaint');
    $routes->get('list', 'ComplaintsController::getComplaints');
    $routes->get('detail/(:num)', 'ComplaintsController::getComplaintDetail/$1');
    $routes->put('update-status/(:num)', 'ComplaintsController::updateStatus/$1');
});

// Banner routes
$routes->group('banner', static function ($routes) {
    $routes->get('list', 'BannerController::getBanners');
    $routes->post('create', 'BannerController::create');
    $routes->put('update/(:num)', 'BannerController::update/$1');
    $routes->delete('delete/(:num)', 'BannerController::delete/$1');
});

// Brand routes
$routes->group('brand', static function ($routes) {
    $routes->get('list', 'BrandController::getBrands');
    $routes->get('detail/(:num)', 'BrandController::getBrandDetail/$1');
    $routes->post('create', 'BrandController::create');
    $routes->put('update/(:num)', 'BrandController::update/$1');
    $routes->delete('delete/(:num)', 'BrandController::delete/$1');
});

// Additional routes
$routes->group('payouts', function ($routes) {
    $routes->get('partner/(:num)', 'PartnerPayoutController::listByPartner/$1');
    $routes->post('create', 'PartnerPayoutController::create');
    $routes->post('release', 'PartnerPayoutController::release');
});

$routes->group('reviews', function ($routes) {
    $routes->post('submit', 'PartnerReviewController::submit');
    $routes->get('partner/(:num)', 'PartnerReviewController::getByPartner/$1');
});

$routes->post('checklist/feedback', 'ChecklistFeedbackController::submit');
$routes->post('checklist/feedback/bulk', 'ChecklistFeedbackController::submitBulk');
$routes->get('checklist/feedback/assignment/(:num)', 'ChecklistFeedbackController::getByAssignment/$1');

$routes->group('/', ['filter' => 'authFilter'], static function ($routes) {
    $routes->post('location/reverse-geocode', 'LocationController::reverseGeocode');
    $routes->post('location/search-places', 'LocationController::searchPlaces');
});
