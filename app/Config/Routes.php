<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Home route
$routes->get('/', 'Home::index');

/*
 * Additional Routing
 * --------------------------------------------------------------------
 *
 * There will often be times that you need additional routing and you
 * need it to be able to override any defaults in this file. Environment
 * based routes is one such time. require() additional route files here
 * to make that happen.
 *  
 * You will have access to the $routes object within that file without
 * needing to reload it.
 */
/* if (is_file(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
 */

// Product Dashboard
$routes->group('dashboard', static function ($routes) {
    $routes->group('/', ['filter' => 'authFilter'], static function ($routes) {
        $routes->get('get-statics', 'ProductDashboardController::getProductStatics');
        $routes->get('products-statics', 'ProductDashboardController::productsStats');
        $routes->get('getQuotationsdata', 'DashboardController::getDashboardData');
    });
});

// Privileges
$routes->group('privileges', static function ($routes) {
    $routes->group('/', ['filter' => 'authFilter'], static function ($routes) {
        $routes->post('create-section', 'PrivilegesController::createSection');
        $routes->post('create-role', 'PrivilegesController::createRole');
        $routes->post('update-role', 'PrivilegesController::updateRole');
        $routes->get('get-role/(:num)', 'PrivilegesController::getRole/$1');
        $routes->delete('delete-role', 'PrivilegesController::deleteRole');
        $routes->get('get-all-roles', 'PrivilegesController::getAllRoles');
        $routes->get('get-all-sections', 'PrivilegesController::getAllSections');
        $routes->get('get-admin-privileges', 'PrivilegesController::getAdminPrivileges');
    });
});



//Arrange free customers//
$routes->group('customer', static function ($routes) {
    // Public routes (no auth required)
    $routes->post('send-otp', 'CustomerController::sendOTP');
    $routes->post('new-send-otp', 'CustomerController::sendSeebOTP');
    $routes->post('login', 'CustomerController::login');
    $routes->post('register', 'CustomerController::createCustomer');
    $routes->post('contact-us/query', 'CustomerController::contactUs');
    $routes->put('updateCustomer/(:num)', 'CustomerController::updateCustomer/$1');

    // Protected routes (auth required)
    $routes->group('/', ['filter' => 'authFilter'], static function ($routes) {
        $routes->post('getCustomer', 'CustomerController::getCustomer');
        $routes->get('getCustomerById/(:num)', 'CustomerController::getCustomerById/$1');
        $routes->delete('delete/(:num)', 'CustomerController::deleteCustomer/$1');
        $routes->get('deleteCustomer/(:num)', 'CustomerController::deleteCustomer/$1');
        $routes->post('getAllContactUs', 'CustomerController::getAllContactUs');
        $routes->put('updateRemark/(:num)', 'CustomerController::updateRemark/$1');
        $routes->get('user-access/requests', 'AdminUserAccessController::userAccessRequests');
        $routes->put('user-access/request/(:num)/(:segment)', 'AdminUserAccessController::userRespondRequest/$1/$2');
    });
});


//mailing
$routes->get('/post_order_mail/(:num)', 'MailingController::post_order_mail/$1');


//Admin//

$routes->group('admin', static function ($routes) {
    // Public routes (no auth required)
    $routes->post('adminSendOTP', 'AdminController::adminSendOTP');
    $routes->post('adminLogin', 'AdminController::adminLogin');
    $routes->post('register', 'AdminController::createAdmin');
    $routes->post('user-access/login-with-grant', 'AdminUserAccessController::loginWithGrant');

    // Protected routes (auth required)
    $routes->group('/', ['filter' => 'authFilter'], static function ($routes) {
        $routes->get('getAdmin', 'AdminController::getAdmin');
        $routes->get('getAdminByID/(:num)', 'AdminController::getAdminByID/$1');
        $routes->put('updateAdmin/(:num)', 'AdminController::updateAdmin/$1');
        $routes->delete('deleteAdmin/(:num)', 'AdminController::deleteAdmin/$1');

        // Customer
        $routes->get('getCustomer/(:num)', 'CustomerController::getCustomer_fromAdmin/$1');

        $routes->get('partner-referrals', 'PartnerReferralController::adminReferralSummary');
        $routes->get('partner-referrals/(:num)', 'PartnerReferralController::partnerReferrals/$1');
        $routes->get('partners/summary', 'PartnerController::getPartnerTaskSummary');
        $routes->get('partner/unregistered', 'PartnerController::getUnregisteredPartners');

        $routes->post('payouts/create', 'PartnerPayoutController::create');
        $routes->get('payouts/requests', 'PartnerPayoutController::adminListRequests');
        $routes->get('payouts/partner/(:num)', 'PartnerPayoutController::listByPartner/$1');
        $routes->get('wallet/withdraw-requests', 'PartnerController::walletWithdrawRequestsAll');
        $routes->get('wallet/withdraw-requests/(:num)', 'PartnerController::walletWithdrawRequests/$1');

        $routes->post('service-offers/create', 'ServiceOfferController::create');
        $routes->post('service-offers/update/(:num)', 'ServiceOfferController::update/$1');
        $routes->get('service-offers/list', 'ServiceOfferController::list');
        $routes->delete('service-offers/delete/(:num)', 'ServiceOfferController::delete/$1');
        $routes->post('service-offers/status/(:num)', 'ServiceOfferController::changeStatus/$1');

        $routes->post('user-access/request', 'AdminUserAccessController::createRequest');
        $routes->get('user-access/requests', 'AdminUserAccessController::listRequests');
        $routes->put('user-access/request/(:num)/status', 'AdminUserAccessController::updateRequestStatus/$1');
        $routes->post('user-access/create-login-grant', 'AdminUserAccessController::createLoginGrant');
        $routes->post('user-access/login', 'AdminUserAccessController::impersonationLogin');
        $routes->post('user-access/logout', 'AdminUserAccessController::impersonationLogout');
        $routes->get('user-access/session/validate', 'AdminUserAccessController::validateSession');
        $routes->get('user-access/logs', 'AdminUserAccessController::logs');
        $routes->get('user-access/request/(:num)/logs', 'AdminUserAccessController::requestLogs/$1');
    });
});




// Blog
$routes->group('blog', static function ($routes) {
    // Public routes
    $routes->get('get-all-blogs', 'BlogsController::getPublicBlogs');
    $routes->get('single-blog/(:num)', 'BlogsController::singleBlog/$1');
    $routes->get('get-all', 'BlogsController::getAllBlogs');

    // Protected routes
    $routes->group('/', ['filter' => 'authFilter'], static function ($routes) {
        $routes->post('createBlog', 'BlogsController::createBlog');
        $routes->post('createBlogImage', 'BlogsController::createBlogImage');
        $routes->post('deleteBlogImage', 'BlogsController::deleteBlogImage');
        $routes->post('deleteSectionImage', 'BlogsController::deleteSectionImage');
        $routes->delete('deleteBlog/(:num)', 'BlogsController::deleteBlog/$1');
        $routes->post('updateStatus/(:num)', 'BlogsController::updateBlogStatus/$1');
        $routes->put('updateBlog/(:num)', 'BlogsController::update/$1');
        $routes->post('delete-image', 'BlogsController::deleteImage');
        $routes->get('blog-section/(:num)', 'BlogsController::getBlogSections/$1');
        $routes->post('blog-section', 'BlogsController::createBlogSection');
        $routes->put('blog-section/(:num)', 'BlogsController::updateBlogSection/$1');
        $routes->delete('delete-blog-section/(:num)', 'BlogsController::deleteBlogSection/$1');
    });
});




//Coupon Api

$routes->group('coupon', static function ($routes) {
    $routes->get('active', 'CouponController::getActiveCoupons');
    $routes->get('getAllCoupon', 'CouponController::getAllCoupon');
    $routes->group('/', ['filter' => 'authFilter'], static function ($routes) {
        $routes->post('couponcreate', 'CouponController::create');
        $routes->get('getById/(:num)', 'CouponController::getById/$1');
        $routes->put('couponupdate/(:num)', 'CouponController::update/$1');
        $routes->delete('coupondelete/(:num)', 'CouponController::delete/$1');
        $routes->post('apply-coupon', 'CouponController::applyCoupon');
        $routes->post('use-coupon', 'CouponController::applyCouponSeeb');
    });
});


$routes->group('banner', static function ($routes) {
    $routes->group('/', ['filter' => 'authFilter'], static function ($routes) {
        $routes->post('createBannerImage', 'BannerController::createBannerImage');
        $routes->delete('deleteBanner/(:num)', 'BannerController::deleteBanner/$1');
        $routes->post('createMainBanner', 'BannerController::createMainBanner');
        $routes->get('by-service/(:num)', 'BannerController::getBannersByServiceId/$1');
    });
    $routes->get('getMainBanner', 'BannerController::getMainBanner');
});


//Otp
$routes->group('otp', static function ($routes) {
    $routes->group('/', ['filter' => 'authFilter'], static function ($routes) {
        $routes->post('notify_vendor_reg', 'OtpController::notify_vendor_reg');
        $routes->post('notify_order_confirmation', 'OtpController::notify_order_confirmation');
        $routes->post('notify_otp_verification', 'OtpController::notify_otp_verification');
    });
});


$routes->group('freepik-api', static function ($routes) {
    $routes->group('/', ['filter' => 'authFilter'], static function ($routes) {
        $routes->post('store', 'FreepikApiHistoryController::store');
        $routes->post('all', 'FreepikApiHistoryController::getAll');
        $routes->get('user/(:num)', 'FreepikApiHistoryController::getByUser/$1');
        $routes->get('check-user-limit/(:num)', 'FreepikApiHistoryController::checkUserLimit/$1');
        $routes->post('image-generate', 'FreepikApiHistoryController::imageGenerate');
        $routes->get('user-all/(:num)', 'FreepikApiHistoryController::getByUserAll/$1');
    });
});

$routes->group('services-type', function ($routes) {
    $routes->get('/', 'ServiceTypeController::index'); // Get all services
    $routes->get('(:num)/rooms', 'ServiceTypeController::getRoomsByServiceType/$1');
    $routes->group('/', ['filter' => 'authFilter'], static function ($routes) {
        $routes->get('(:num)', 'ServiceTypeController::show/$1'); // Get service by ID
        $routes->post('upload-image', 'ServiceTypeController::uploadImage');
        $routes->post('create', 'ServiceTypeController::create'); // Create service
        $routes->put('update/(:num)', 'ServiceTypeController::update/$1'); // Update service
        $routes->delete('delete/(:num)', 'ServiceTypeController::delete/$1'); // Delete service
        $routes->put('change-status/(:num)', 'ServiceTypeController::changeStatus/$1');
    });
});
$routes->group('rooms', function ($routes) {
    $routes->get('/', 'RoomsController::index'); // Get all services
    $routes->group('/', ['filter' => 'authFilter'], static function ($routes) {
        $routes->get('(:num)', 'RoomsController::show/$1'); // Get service by ID
        $routes->post('create', 'RoomsController::create'); // Create service
        $routes->put('update/(:num)', 'RoomsController::update/$1'); // Update service
        $routes->delete('delete/(:num)', 'RoomsController::delete/$1'); // Delete service
    });
});

// $routes->post('update-all-slugs', 'ServiceController::updateAllSlugs'); // Update all service slugs
$routes->group('services', function ($routes) {
    // Search endpoint
    $routes->get('search', 'ServiceController::search');

    // More specific routes MUST come before catch-all routes
    // Old endpoint for iOS and Android apps (using numeric IDs)
    $routes->get('service-type/(:num)/room/(:num)', 'ServiceController::findByServiceTypeAndRoom/$1/$2');
    // New endpoint for slug-based lookups
    $routes->get('by-slug/service-type/(:any)/room/(:any)', 'ServiceController::findByServiceTypeAndRoomSlug/$1/$2');

    // Less specific/catch-all routes
    $routes->get('/', 'ServiceController::index');
    $routes->get('(:num)', 'ServiceController::show/$1');
    $routes->get('(:any)', 'ServiceController::showBySlug/$1');
    $routes->group('/', ['filter' => 'authFilter'], static function ($routes) {
        $routes->post('upload-image', 'ServiceController::uploadImages'); // Upload image separately
        $routes->post('create', 'ServiceController::create'); // Create work type
        $routes->put('update/(:num)', 'ServiceController::update/$1'); // Update work type
        // $routes->delete('delete/(:num)', 'ServiceController::delete/$1'); // Delete work type
        $routes->post('delete-image', 'ServiceController::deleteImage');
        $routes->put('change-status/(:num)', 'ServiceController::changeStatus/$1');
    });
});
$routes->group('service-gallery', function ($routes) {
    $routes->get('/', 'ServiceGalleryController::list');
    $routes->get('(:num)', 'ServiceGalleryController::getById/$1');
    $routes->get('services/stats', 'ServiceGalleryController::getServicesWithGalleryStats');
    $routes->group('/', ['filter' => 'authFilter'], static function ($routes) {
        $routes->post('create', 'ServiceGalleryController::create');
        $routes->put('update/(:num)', 'ServiceGalleryController::update/$1');
        $routes->delete('delete/(:num)', 'ServiceGalleryController::delete/$1');
        $routes->post('upload-images', 'ServiceGalleryController::uploadImages');
        $routes->post('add-video', 'ServiceGalleryController::addVideo');
        $routes->post('add-tutorial-video', 'ServiceGalleryController::addTutorialVideo');

        // Specific update endpoints for each media type
        $routes->put('update-image/(:num)', 'ServiceGalleryController::updateImage/$1');
        $routes->put('update-video/(:num)', 'ServiceGalleryController::updateVideo/$1');
        $routes->put('update-tutorial-video/(:num)', 'ServiceGalleryController::updateTutorialVideo/$1');

        // Specific delete endpoints for each media type
        $routes->delete('delete-image/(:num)', 'ServiceGalleryController::deleteImage/$1');
        $routes->delete('delete-video/(:num)', 'ServiceGalleryController::deleteVideo/$1');
        $routes->delete('delete-tutorial-video/(:num)', 'ServiceGalleryController::deleteTutorialVideo/$1');
    });
});
$routes->group('selected-design', function ($routes) {
    $routes->group('/', ['filter' => 'authFilter'], static function ($routes) {
        $routes->post('save', 'SelectedDesignController::saveSelectedDesign');
        $routes->get('(:num)', 'SelectedDesignController::getSelectedDesign/$1');
    });
});

$routes->group('seeb-cart', function ($routes) {
    $routes->group('/', ['filter' => 'authFilter'], static function ($routes) {
        $routes->get('getCart/(:num)', 'SeebCartController::index/$1');       // Get all cart items (or filter by user_id)
        $routes->get('(:num)', 'SeebCartController::show/$1'); // Get single cart item by ID
        $routes->post('save', 'SeebCartController::save');    // Create/Update cart item
        $routes->delete('(:num)', 'SeebCartController::delete/$1'); // Delete cart item
        $routes->post('uploadImages', 'SeebCartController::uploadImages');
        $routes->post('/', 'SeebCartController::getCartGroupedByUser');
        $routes->put('update/(:num)', 'SeebCartController::update/$1');
    });
});

$routes->group('customer-address', function ($routes) {
    $routes->group('/', ['filter' => 'authFilter'], static function ($routes) {
        $routes->get('(:num)', 'AddressController::index/$1');              // Get all addresses (filtered by user_id)
        $routes->get('default/(:num)', 'AddressController::getDefault/$1'); // Get default address by user_id
        $routes->get('(:num)', 'AddressController::show/$1');       // Get single address by ID
        $routes->post('/', 'AddressController::create');            // Add new address
        $routes->put('(:num)', 'AddressController::update/$1');     // Update address by ID
        // $routes->delete('(:num)', 'AddressController::delete/$1');  // Delete address by ID
        $routes->put('change-default/(:num)', 'AddressController::changeDefault/$1');
    });
});

$routes->group('razorpay-order', function ($routes) {
    $routes->group('/', ['filter' => 'authFilter'], static function ($routes) {
        $routes->post('create', 'RazorpayOrdersController::createOrder');
        $routes->get('(:num)', 'RazorpayOrdersController::getOrder/$1');
        $routes->get('user/(:num)', 'RazorpayOrdersController::getUserOrders/$1');
        $routes->post('update-status', 'RazorpayOrdersController::updateOrderStatus');
        $routes->delete('delete/(:num)', 'RazorpayOrdersController::deleteOrder/$1');
    });
});

$routes->group('booking', function ($routes) {
    $routes->group('/', ['filter' => 'authFilter'], static function ($routes) {
        $routes->post('store', 'BookingController::createBooking');
        $routes->post('', 'BookingController::getAllBookings'); // Get all bookings
        $routes->get('user/(:num)', 'BookingController::getBookingsByUser/$1');
        $routes->get('(:num)', 'BookingController::getBookingById/$1');
        $routes->get('admin/(:num)', 'BookingController::getBookingDetails/$1');
        $routes->post('verify-payment', 'BookingController::verifyPayment');
        $routes->post('payment/manual', 'BookingController::addManualPayment');
        $routes->put('change-status/(:num)', 'BookingController::changeStatus/$1');
        $routes->delete('delete/(:num)', 'BookingController::deleteBooking/$1');
        $routes->post('add-additional-services', 'BookingController::addAdditionalServices');
        $routes->post('additional-services/approval', 'BookingController::approveAdditionalServices');
        $routes->post('cancel-service', 'BookingController::cancelService');
        $routes->post('adjustments/(:num)', 'BookingController::createAdjustment/$1');
        $routes->get('adjustments/(:num)', 'BookingController::getAdjustments/$1');
        $routes->get('cancellation-details/(:num)', 'BookingController::getCancellationDetails/$1');
        $routes->post('create-by-admin', 'BookingController::createBookingByAdmin');
        $routes->post('initiatePayment', 'BookingController::initiatePayment');
        $routes->post('verify-admin-payment-request', 'BookingController::verifyAdminPaymentRequest');
        $routes->post('verify-post-booking-payment', 'BookingController::verifyPostBookingPayment');
    });
});

$routes->post('razorpay-webhook', 'BookingController::webhookRazorpay');

$routes->get('payments/receipt/(:num)', 'BookingController::downloadReceipt/$1');
$routes->get('invoice/(:num)', 'BookingController::downloadInvoice/$1');


$routes->group('faqs', function ($routes) {
    $routes->group('/', ['filter' => 'authFilter'], static function ($routes) {
        $routes->get('/', 'FaqController::index');  // Get all FAQs
        $routes->get('(:num)', 'FaqController::show/$1');  // Get single FAQ
        $routes->post('/', 'FaqController::create');  // Create FAQ
        $routes->put('(:num)', 'FaqController::update/$1');  // Update FAQ
        $routes->delete('(:num)', 'FaqController::delete/$1');  // Delete FAQ
        $routes->get('category/(:num)', 'FaqController::getFaqsByCategory/$1'); // Get FAQs by category
        $routes->get('services-with-count', 'FaqController::listServicesWithFaqCount');
    });
    $routes->get('service/(:num)', 'FaqController::listForService/$1');
    $routes->get('service/(:any)', 'FaqController::listForService/$1');
});

// FAQ Categories
$routes->group('faqs-category', function ($routes) {
    $routes->group('/', ['filter' => 'authFilter'], static function ($routes) {
        $routes->get('with-count', 'FaqCategoryController::listWithFaqCount');
        $routes->get('/', 'FaqCategoryController::index');  // Get all categories
        $routes->post('/', 'FaqCategoryController::create');  // Create category
        $routes->get('(:num)', 'FaqCategoryController::show/$1'); // Get single category
        $routes->put('(:num)', 'FaqCategoryController::update/$1'); // Update category
        $routes->delete('(:num)', 'FaqCategoryController::delete/$1');
    });
});

$routes->group('ai-api-history', function ($routes) {
    $routes->group('/', ['filter' => 'authFilter'], static function ($routes) {
        $routes->post('all', 'AIAPIHistoryController::getAll'); // Get all AI API history records
        $routes->get('by-user/(:num)', 'AIAPIHistoryController::getHistoryByUser/$1');
        $routes->post('analyze-image', 'AIAPIHistoryController::analyzeImage');
    });
});
$routes->group('ai-api', function ($routes) {
    $routes->group('/', ['filter' => 'authFilter'], static function ($routes) {
        $routes->post('prompt-generate', 'AIAPIHistoryController::gpt4oMini');
        $routes->post('dalle-image', 'AIAPIHistoryController::dalleImageGenerate');
        $routes->post('gemini-generate', 'AIAPIHistoryController::generateWithGemini');
    });
});
$routes->group('payment', function ($routes) {
    $routes->group('/', ['filter' => 'authFilter'], static function ($routes) {
        $routes->post('request', 'PaymentRequestController::create');
        $routes->get('requests', 'PaymentRequestController::index');
        $routes->post('request/update/(:num)', 'PaymentRequestController::update/$1');
        $routes->delete('request/delete/(:num)', 'PaymentRequestController::delete/$1');
    });
});

$routes->group('expenses', function ($routes) {
    $routes->group('/', ['filter' => 'authFilter'], static function ($routes) {
        $routes->post('add', 'BookingExpenseController::addExpense'); // Add a new expense
        $routes->get('list/(:num)', 'BookingExpenseController::getExpenses/$1'); // Fetch all expenses for a booking
        $routes->delete('delete/(:num)', 'BookingExpenseController::deleteExpense/$1'); // Delete an expense
    });
});

$routes->group('tickets', function ($routes) {
    $routes->group('/', ['filter' => 'authFilter'], static function ($routes) {
        $routes->get('ticket/(:num)', 'TicketController::getTicketById/$1');
        $routes->post('create', 'TicketController::createTicket');          // Create a ticket
        $routes->post('all', 'TicketController::getAllTickets');             // Get all tickets
        $routes->post('update-status/(:num)', 'TicketController::updateStatus/$1');  // Update ticket status
        $routes->post('add-message', 'TicketController::addMessage');       // Add a message to a ticket
        $routes->get('messages/(:num)', 'TicketController::getMessages/$1'); // Get all messages for a ticket
        $routes->post('upload-image', 'TicketController::uploadFile');
        $routes->get('user/(:num)', 'TicketController::getTicketsByUserId/$1');
        $routes->post('mark-as-read', 'TicketController::markTicketAsRead');
    });
});

$routes->group('guide-videos', function ($routes) {
    $routes->group('/', ['filter' => 'authFilter'], static function ($routes) {
        $routes->get('/', 'GuideVideosController::index');          // Fetch all guide videos
        $routes->get('(:num)', 'GuideVideosController::show/$1');   // Fetch a single video by ID
        $routes->post('create', 'GuideVideosController::create');   // Add a new guide video
        $routes->put('update/(:num)', 'GuideVideosController::update/$1'); // Update a video
        $routes->delete('delete/(:num)', 'GuideVideosController::delete/$1'); // Delete a video
    });
});

$routes->group('guide-images', function ($routes) {
    $routes->group('/', ['filter' => 'authFilter'], static function ($routes) {
        $routes->get('/', 'GuideImagesController::index');           // Get all guide images
        $routes->get('(:num)', 'GuideImagesController::show/$1');    // Get a single image
        $routes->post('create', 'GuideImagesController::create');    // Add new guide image
        $routes->post('upload-image', 'GuideImagesController::uploadImage');    // Add new guide image
        $routes->put('update/(:num)', 'GuideImagesController::update/$1'); // Update guide image
        $routes->delete('delete/(:num)', 'GuideImagesController::delete/$1'); // Delete guide image
    });
});

$routes->group('assets', function ($routes) {
    $routes->group('/', ['filter' => 'authFilter'], static function ($routes) {
        $routes->get('/', 'AssetController::index');
        $routes->get('(:num)', 'AssetController::show/$1');
        $routes->post('/', 'AssetController::create');
        $routes->put('(:num)', 'AssetController::update/$1');
        $routes->delete('(:num)', 'AssetController::delete/$1');
        $routes->post('upload', 'AssetController::uploadFile');
        $routes->get('room/(:num)', 'AssetController::getByRoom/$1');
    });
});


$routes->get('serve-file/(:any)', 'FileController::serveFile/$1');


$routes->group('styles', function ($routes) {
    $routes->group('/', ['filter' => 'authFilter'], static function ($routes) {
        $routes->get('/', 'StyleController::index');
        $routes->get('(:num)', 'StyleController::show/$1');
        $routes->post('/', 'StyleController::create');
        $routes->post('update/(:num)', 'StyleController::update/$1');
        $routes->delete('(:num)', 'StyleController::delete/$1');

        $routes->get('by-category', 'StylesCategoryController::getAllCategoriesWithStyles');

        $routes->get('category', 'StylesCategoryController::index');
        $routes->post('category/create', 'StylesCategoryController::create');
        $routes->get('category/show/(:num)', 'StylesCategoryController::show/$1');
        $routes->post('category/update/(:num)', 'StylesCategoryController::update/$1');
        $routes->delete('category/delete/(:num)', 'StylesCategoryController::delete/$1');
    });
});


$routes->group('room-elements', function ($routes) {
    $routes->group('/', ['filter' => 'authFilter'], static function ($routes) {
        $routes->get('/', 'RoomElementController::index');         // Get all room elements
        $routes->post('/', 'RoomElementController::create');       // Create a new room element
        $routes->get('(:num)', 'RoomElementController::show/$1');  // Get a single room element
        $routes->put('(:num)', 'RoomElementController::update/$1'); // Update a room element
        $routes->delete('(:num)', 'RoomElementController::delete/$1'); // Delete a room element
    });
});

$routes->group('floor-plans', function ($routes) {
    $routes->group('/', ['filter' => 'authFilter'], static function ($routes) {
        $routes->post('all', 'FloorPlanController::getAll');
        $routes->get('', 'FloorPlanController::index');          // List all plans (optional: ?user_id=1)
        $routes->get('user-id/(:num)', 'FloorPlanController::index/$1');          // List all plans (optional: ?user_id=1)
        $routes->get('(:num)', 'FloorPlanController::show/$1');   // Get single plan
        $routes->post('/', 'FloorPlanController::create');        // Create new plan
        $routes->put('(:num)', 'FloorPlanController::update/$1'); // Update plan
        $routes->delete('(:num)', 'FloorPlanController::delete/$1'); // Delete plan
        $routes->post('upload-image', 'FloorPlanController::upload'); // Upload image for a plan
    });
});

$routes->group('dashboard', static function ($routes) {
    $routes->group('/', ['filter' => 'authFilter'], static function ($routes) {
        $routes->get('overview', 'DashboardController::overview'); // Get user dashboard data
        $routes->get('monthly-sales', 'DashboardController::monthlySales');
        $routes->get('yearly-sales', 'DashboardController::yearlySales');
    });
});

$routes->group('partner', function ($routes) {
    // Public routes (no auth required)
    $routes->post('login', 'PartnerController::login');
    $routes->post('send-otp', 'PartnerController::sendOtp');
    $routes->post('verify-otp', 'PartnerController::verifyOtp');
    $routes->post('register', 'PartnerController::registerOrUpdate');
    $routes->get('referral/mobile/(:segment)', 'ReferralController::getReferrerByMobile/$1');
    $routes->get('photo/(:num)', 'PartnerController::servePhoto/$1');
    $routes->get('onboarding-data/(:num)', 'PartnerController::onboardingData/$1');

    // Protected routes (auth required)
    $routes->group('/', ['filter' => 'authFilter'], function ($routes) {
        $routes->get('profile/(:num)', 'PartnerController::profile/$1');
        $routes->get('onboarding-status', 'PartnerController::onboardingStatus');
        $routes->post('list', 'PartnerController::index');
        $routes->post('verify-bank', 'PartnerController::verifyBank');
        $routes->post('verify-documents/(:num)', 'PartnerController::verifyDocument/$1');
        $routes->put('update-personal-info/(:num)', 'PartnerController::updatePersonalInfo/$1');
        $routes->put('update-bank-details/(:num)', 'PartnerController::updateBankDetails/$1');
        $routes->put('update-address/(:num)', 'PartnerController::updateAddress/$1');
        $routes->post('update-documents', 'PartnerController::updateDocuments');
        $routes->post('store-firebase-uid', 'PartnerController::storeFirebaseUid');

        $routes->get('wallet/balance/(:num)', 'PartnerController::walletBalance/$1');
        $routes->get('wallet/withdraw-requests/(:num)', 'PartnerController::walletWithdrawRequests/$1');
        $routes->post('wallet/withdraw-requests/(:num)', 'PartnerController::createWalletWithdrawRequest/$1');
        $routes->get('wallet/transactions/(:num)', 'PartnerController::walletTransactions/$1');
        $routes->get('bank-details/(:num)', 'PartnerController::getBankDetails/$1');

        $routes->post('tickets/create', 'TicketController::createTicket');
        $routes->get('tickets/partner/(:num)', 'TicketController::getTicketsByPartnerId/$1');
        $routes->get('ticket/(:num)', 'TicketController::getTicketById/$1');
        $routes->post('ticket/add-message', 'TicketController::addMessage');
        $routes->post('ticket/mark-as-read', 'TicketController::markTicketAsRead');
        $routes->post('tickets/upload-image', 'TicketController::uploadFile');

        $routes->post('notifications/mark-all-read', 'NotificationController::markAllAsRead');
        $routes->delete('notifications/delete/(:num)', 'NotificationController::delete/$1');
        $routes->post('notifications/user', 'NotificationController::index');
        $routes->post('notifications/clear-all', 'NotificationController::clearAll');

        // $routes->post('booking-assignment/accept', 'BookingAssignmentController::acceptAssignment');
        // $routes->get('accepted-bookings/(:num)', 'BookingAssignmentController::getAcceptedBookings/$1');
        // $routes->get('assignment/details/(:num)', 'BookingAssignmentController::getAssignmentDetails/$1');

        $routes->post('jobs/accept/(:num)', 'PartnerJobController::acceptJob/$1');

        $routes->get('jobs/active/(:num)', 'PartnerJobController::listActiveByPartner/$1');
        $routes->get('jobs/details/(:num)', 'PartnerJobController::details/$1');
        $routes->get('jobs/preview/(:num)', 'PartnerJobController::preview/$1');
        $routes->get('jobs/all/(:num)', 'PartnerJobController::listAllByPartner/$1');
        $routes->get('jobs/on-site-status', 'PartnerJobController::getOnSiteStatus');
        $routes->post('jobs/update-on-site-status', 'PartnerJobController::updateOnSiteStatus');

        $routes->post('jobs/items/(:num)/media', 'PartnerJobController::uploadItemMedia/$1');
        $routes->get('jobs/items/(:num)/media', 'PartnerJobController::listItemMedia/$1');
        $routes->delete('jobs/items/media/(:num)', 'PartnerJobController::deleteItemMedia/$1');

        $routes->get('payouts/(:num)', 'PartnerPayoutController::listByPartner/$1');

        $routes->get('referral/summary/(:num)', 'PartnerReferralController::summary/$1');
        $routes->post('referral/invite', 'ReferralController::invite');

        $routes->get('referral/validate', 'PartnerReferralController::validateCode');
    });
});

$routes->group('partner-jobs', static function ($routes) {
    $routes->group('/', ['filter' => 'authFilter'], static function ($routes) {
        $routes->get('/', 'PartnerJobController::index');
        $routes->get('(:num)', 'PartnerJobController::show/$1');
        $routes->post('create', 'PartnerJobController::create');
        $routes->put('(:num)/status', 'PartnerJobController::updateStatus/$1');
        $routes->get('(:num)/items', 'PartnerJobController::listItems/$1');
        $routes->post('(:num)/items', 'PartnerJobController::addItems/$1');
        $routes->post('items/(:num)/media', 'PartnerJobController::uploadItemMedia/$1');
        $routes->get('items/(:num)/media', 'PartnerJobController::listItemMedia/$1');
        $routes->delete('items/media/(:num)', 'PartnerJobController::deleteItemMedia/$1');
        $routes->get('partner/(:num)', 'PartnerJobController::listByPartner/$1');
        $routes->get('partner/(:num)/all', 'PartnerJobController::listAllByPartner/$1');
        $routes->get('booking/(:num)', 'PartnerJobController::listByBooking/$1');
        $routes->post('assign', 'PartnerJobController::assign');
        $routes->post('(:num)/request', 'PartnerJobController::requestPartner/$1');
        $routes->put('requests/(:num)', 'PartnerJobController::respondRequest/$1');
    });
});

$routes->group('cron', function ($routes) {
    $routes->get('daily/first-step-email', 'EmailController::sendFirstStepEmail');         // Get all room elements
});


$routes->get('test-email', 'EmailController::sendComparisonEmail');
$routes->get('test-step-email', 'EmailController::sendRoomStepEmailToMultiple');

$routes->group('/', ['filter' => 'authFilter'], static function ($routes) {
    $routes->get('send-notification', 'NotificationController::send');
});


$routes->group('prompts', function ($routes) {
    $routes->group('/', ['filter' => 'authFilter'], static function ($routes) {
        $routes->get('style/(:num)', 'PromptController::getByStyle/$1');
        $routes->get('(:num)', 'PromptController::show/$1');
        $routes->post('/', 'PromptController::create');
        $routes->post('update/(:num)', 'PromptController::update/$1'); // or use PUT with route filter
        $routes->delete('(:num)', 'PromptController::delete/$1');
    });
});

$routes->group('notifications', function ($routes) {
    $routes->group('/', ['filter' => 'authFilter'], static function ($routes) {
        $routes->post('/user', 'NotificationController::index');
        $routes->post('create', 'NotificationController::create');
        $routes->post('mark-as-read', 'NotificationController::markAsRead');
        $routes->post('mark-all-read', 'NotificationController::markAllAsRead');
        $routes->delete('delete', 'NotificationController::delete');
    });
});

$routes->group('assignment', function ($routes) {
    $routes->group('/', ['filter' => 'authFilter'], static function ($routes) {
        $routes->post('create-requests', 'BookingAssignmentController::createMultipleAssignmentRequests');
        $routes->get('booking-requests/(:num)', 'BookingAssignmentController::getRequestsByBookingServiceId/$1');
    });
});
$routes->group('booking-updates', function ($routes) {
    $routes->group('/', ['filter' => 'authFilter'], static function ($routes) {
        $routes->post('/', 'BookingUpdateController::create');

        // GET: Get all updates + media for a booking_service_id
        $routes->get('(:num)', 'BookingUpdateController::list/$1');
    });
    // POST: Create booking update with optional media
});

$routes->group('checklists', function ($routes) {
    $routes->group('/', ['filter' => 'authFilter'], static function ($routes) {
        $routes->get('service/(:num)', 'ChecklistController::getServiceChecklist/$1'); // Get master checklist for service
        $routes->get('status/(:num)', 'ChecklistController::getChecklistStatus/$1');   // Get partner's status for booking
        $routes->post('update', 'ChecklistController::updateChecklistItem');           // Submit checklist update
        $routes->post('create', 'ServiceChecklistController::insertServiceChecklists');
    });
});
$routes->group('payouts', function ($routes) {
    $routes->group('/', ['filter' => 'authFilter'], static function ($routes) {
        $routes->get('partner/(:num)', 'PartnerPayoutController::listByPartner/$1');
        $routes->post('create', 'PartnerPayoutController::create');
        $routes->post('release', 'PartnerPayoutController::release');
    });
});
$routes->group('reviews', function ($routes) {
    $routes->group('/', ['filter' => 'authFilter'], static function ($routes) {
        $routes->post('submit', 'PartnerReviewController::submit');
        $routes->get('partner/(:num)', 'PartnerReviewController::getByPartner/$1');
    });
});

$routes->post('checklist/feedback', 'ChecklistFeedbackController::submit');
$routes->post('checklist/feedback/bulk', 'ChecklistFeedbackController::submitBulk');
$routes->get('checklist/feedback/assignment/(:num)', 'ChecklistFeedbackController::getByAssignment/$1');

$routes->group('/', ['filter' => 'authFilter'], static function ($routes) {
    $routes->post('location/reverse-geocode', 'LocationController::reverseGeocode');
    $routes->post('location/search-places', 'LocationController::searchPlaces');
});



/*
 * --------------------------------------------------------------------
 * Additional Routing
 * --------------------------------------------------------------------
 *
 * There will often be times that you need additional routing and you
 * need it to be able to override any defaults in this file. Environment
 * based routes is one such time. require() additional route files here
 * to make that happen.
 *  
 * You will have access to the $routes object within that file without
 * needing to reload it.
 */
/* if (is_file(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
 */
