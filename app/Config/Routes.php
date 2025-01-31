<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

$routes->get('test', 'Home::generateThumbnailImages');
$routes->get('test2', 'FeaturesController::test2');

// Product Dashboard
$routes->group('dashboard', static function ($routes) {
    // $routes->group('/', ['filter' => 'authFilter'], static function ($routes) {
    $routes->get('get-statics', 'ProductDashboardController::getProductStatics');
    $routes->get('products-statics', 'ProductDashboardController::productsStats');
    $routes->get('getQuotationsdata', 'DashboardController::getDashboardData');
});
// });

// Privileges
$routes->group('privileges', static function ($routes) {
    // $routes->group('/', ['filter' => 'authFilter'], static function ($routes) {
    $routes->post('create-section', 'PrivilegesController::createSection');
    $routes->post('create-role', 'PrivilegesController::createRole');
    $routes->post('update-role', 'PrivilegesController::updateRole');
    $routes->get('get-role/(:num)', 'PrivilegesController::getRole/$1');
    $routes->delete('delete-role', 'PrivilegesController::deleteRole');
    $routes->get('get-all-roles', 'PrivilegesController::getAllRoles');
    $routes->get('get-all-sections', 'PrivilegesController::getAllSections');
    $routes->get('get-admin-privileges', 'PrivilegesController::getAdminPrivileges');
    // });
});

// Invoice Routes
// $routes->get('generate-invoice', 'InvoiceController::index');
$routes->get('generate-invoice', 'InvoiceController::makeInvoice');

//Arrange free customers//
$routes->group('customer', static function ($routes) {
    $routes->post('send-otp', 'CustomerController::sendOTP');
    $routes->post('login', 'CustomerController::login');
    $routes->post('register', 'CustomerController::createCustomer');
    $routes->get('getCustomer', 'CustomerController::getCustomer');
    $routes->post('contact-us/query', 'CustomerController::contactUs');
    $routes->group('/', ['filter' => 'authFilter'], static function ($routes) {
        $routes->put('updateCustomer/(:num)', 'CustomerController::updateCustomer/$1');
        $routes->get('getCustomerById/(:num)', 'CustomerController::getCustomerById/$1');
        $routes->get('getRecentView', 'RecentlyViewedController::getRecentView');
        $routes->get('getRecentViewBySlug/(:any)', 'RecentlyViewedController::getRecentViewBySlug/$1');
        $routes->get('deleteCustomer/(:num)', 'CustomerController::DeleteCustomer/$1');
        $routes->post('cancel-order', 'OrderController::cancelOrder');
    });
    $routes->get('getAllContactUs', 'CustomerController::getAllContactUs');
});

$routes->group('staff', static function ($routes) {
    // $routes->group('/', ['filter' => 'authFilter'], static function ($routes) {
    $routes->post('create', 'StaffController::Create');
    $routes->get('getAllStaffs', 'StaffController::getAllStaffs');
    $routes->get('getByID/(:num)', 'StaffController::getAllStaffByID/$1');
    $routes->put('updateStaff/(:num)', 'StaffController::UpdateStaff/$1');
    $routes->put('update-status/(:num)', 'StaffController::UpdateStaffstatus/$1');
    $routes->delete('Delete/(:num)', 'StaffController::Delete/$1');
    $routes->post('FileUpload', 'StaffController::FileUpload');
    $routes->post('filedelete', 'StaffController::deletefile');
    // });
});


$routes->group('quotation', static function ($routes) {
    // $routes->group('/', ['filter' => 'authFilter'], static function ($routes) {
    $routes->post('create', 'QuotationController::store');
    $routes->post('getAll', 'QuotationController::getAll');
    $routes->put('update/(:num)', 'QuotationController::update/$1');
    $routes->get('getById/(:num)', 'QuotationController::getById/$1');
    $routes->get('quotationById/(:num)', 'QuotationController::quotationById/$1');
    $routes->get('customerMobileNumber/(:num)', 'QuotationController::quotationByCustomerMobileNumber/$1');
    $routes->post('changeStatus/(:num)', 'QuotationController::changeStatus/$1');
    // $routes->delete('Delete/(:num)', 'QuotationController::QuotationDelete/$1');
    // });
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
});

// Razorpay Payments
$routes->group('payment', static function ($routes) {
    // $routes->post('make', 'RazorpayController::createPayment');
    $routes->post('verify', 'RazorpayController::verifyWebPayment');
    $routes->post('verify-app', 'RazorpayController::verfiyAppPayment');
    $routes->get('success', 'RazorpayController::paymentSuccess');

    $routes->post('razorpay-initiate', 'RazorpayController::razorPayinitiate');

    // PhonePe
    $routes->post('make', 'PhonePeController::makeCOD');
    $routes->post('initiate', 'PhonePeController::initiatePayment');
    $routes->post('verify-payment', 'PhonePeController::verifyPayment');
});


// Blog
$routes->group('blog', static function ($routes) {
    $routes->post('createBlog', 'BlogsController::createBlog');
    $routes->post('createBlogImage', 'BlogsController::createBlogImage');
    $routes->get('get-all-blogs', 'BlogsController::getPublicBlogs');
    $routes->get('get-all', 'BlogsController::getAllBlogs');
    $routes->post('deleteBlogImage', 'BlogsController::deleteBlogImage');
    $routes->post('deleteSectionImage', 'BlogsController::deleteSectionImage');
    $routes->delete('deleteBlog/(:num)', 'BlogsController::deleteBlog/$1');
    $routes->get('single-blog/(:num)', 'BlogsController::singleBlog/$1');
    $routes->post('updateStatus/(:num)', 'BlogsController::updateBlogStatus/$1');
    $routes->put('updateBlog/(:num)', 'BlogsController::update/$1');


    $routes->get('blog-section/(:num)', 'BlogsController::getBlogSections/$1');
    $routes->post('blog-section', 'BlogsController::createBlogSection');
    $routes->put('blog-section/(:num)', 'BlogsController::updateBlogSection/$1');
    $routes->delete('delete-blog-section/(:num)', 'BlogsController::deleteBlogSection/$1');
});


$routes->group('master', function ($routes) {
    $routes->get('categories', 'MasterCategoryController::index');
    $routes->get('categories/(:num)', 'MasterCategoryController::show/$1');
    $routes->post('categories', 'MasterCategoryController::create');
    $routes->put('categories/(:num)', 'MasterCategoryController::update/$1');
    $routes->delete('categories/(:num)', 'MasterCategoryController::delete/$1');
    $routes->get('categories/subcategories', 'MasterCategoryController::getAllCategoriesWithSubCategories');

    $routes->get('categories/(:num)/subcategories', 'MasterCategoryController::getSubCategories/$1');
    $routes->post('subcategories', 'MasterCategoryController::createSubCategory');
    $routes->put('subcategories/(:num)', 'MasterCategoryController::updateSubCategory/$1');
    $routes->delete('subcategories/(:num)', 'MasterCategoryController::deleteSubCategory/$1');
});

// PhonePe
/* $routes->group('order', static function ($routes) {
}); */

$routes->group('Campaign', static function ($routes) {
    $routes->post('createIndependenceCampaign', 'DrfIndependenceCampaignController::createIndependenceCampaign');
    $routes->get('getIndependenceCampaign', 'DrfIndependenceCampaignController::getIndependenceCampaign');
});



$routes->group('AppText', static function ($routes) {
    $routes->post('AddHeaders', 'AppTextController::AddHeaders');
    $routes->get('GetHeaders', 'AppTextController::GetHeaders');
    $routes->post('AddHeadersValue', 'AppTextController::AddHeadersValue');
    $routes->put('UpdateHeadersValue/(:num)', 'AppTextController::UpdateHeadersValue/$1');
});

//Product//

$routes->group('product', static function ($routes) {
    $routes->post('createProduct', 'ProductController::createProduct');
    $routes->get('rawProductsList', 'ProductController::rawProductsList');
    $routes->get('getProducts', 'ProductController::getAllProducts');
    $routes->post('updateProductVenderName', 'ProductController::UpdateProductVenderName');
    $routes->put('updateProduct/(:num)', 'ProductController::updateProduct/$1');
    $routes->delete('deleteProduct/(:num)', 'ProductController::deleteProduct/$1');
    $routes->post('getProductByHomeAppliancesId/(:any)', 'ProductController::getProductByHomeAppliancesId/$1');
    $routes->get('getProductById/(:any)', 'ProductController::getProductById/$1');
    $routes->get('get-product/(:num)', 'ProductController::getProduct/$1');
    $routes->put('updateProductStatus/(:num)', 'ProductController::updateProductStatus/$1');
    $routes->get('getProductByLatest', 'ProductController::getProductByLatest');
    $routes->get('get10ProductByHomeAppliancesId', 'ProductController::get10ProductByHomeAppliancesId');
    $routes->post('getProductByHomeCategoryId/(:any)', 'ProductController::getProductByHomeCategoryId/$1');
    $routes->get('get5ProductBySubCategory/(:any)', 'ProductController::get5ProductBySubCategory/$1');
    $routes->post('getAllProductsByHomeZoneAppliances/(:any)', 'ProductController::getAllProductsByHomeZoneAppliances/$1');

    $routes->post('getSimillarProducts/(:any)', 'ProductController::getSimillarProducts/$1');
    //Search
    $routes->post('getSearchAll', 'ProductController::getSearchAll');
    $routes->post('searchProduct', 'ProductController::SearchProduct');

    //Product Variation //
    $routes->post('createProductVariation', 'ProductVariationController::createProductVariation');
    $routes->get('getProductVariation', 'ProductVariationController::getProductVariation');
    $routes->put('updateProductVariation/(:num)', 'ProductVariationController::updateProductVariation/$1');
    $routes->delete('deleteProductVariation/(:num)', 'ProductVariationController::deleteProductVariation/$1');

    //Product Image
    $routes->post('createProductImage', 'ProductImageController::createProductImage');
    $routes->delete('deleteProductImage', 'ProductImageController::deleteProductImage');
    $routes->post('deleteProductImageById', 'ProductImageController::deleteProductImageById');

    //APPLY DISCOUNT
    $routes->put('applyDiscount', 'MetaDataController::applyDiscount');
    $routes->put('increase-price', 'MetaDataController::setPrice');
    $routes->get('getDiscount', 'MetaDataController::getDiscount');
    $routes->get('get-increment', 'MetaDataController::getIncrement');

    //Checkout//
    $routes->post('createOrder', 'OrderController::createOrder');
    $routes->get('getOrder/(:any)', 'OrderController::getOrder/$1');
    $routes->get('track-order/(:any)', 'OrderController::trackOrder/$1');
    $routes->put('updateOrder/(:num)', 'OrderController::updateOrder/$1');
    $routes->delete('deleteOrder/(:num)', 'OrderController::deleteOrder/$1');
    $routes->get('orders/getbycustomer/(:num)', 'OrderController::listOrders/$1');
    $routes->get('orders/getOrderHistorybycustomer/(:num)', 'OrderController::getOrderHistorybycustomer/$1');
    $routes->get('getAllOrder', 'OrderController::getAllOrder');
    $routes->get('getAllOrderByDate', 'OrderController::getAllOrderByDate');


    //Rating Review//
    $routes->post('createRatingReview', 'RatingReviewController::createRatingReview');
    $routes->get('getRatingReviewById/(:num)', 'RatingReviewController::getRatingReviewById/$1');
    $routes->get('getAllRatingReview', 'RatingReviewController::getAllRatingReview');
    $routes->put('updateRatingReview/(:num)', 'RatingReviewController::updateRatingReview/$1');
    $routes->delete('deleteRatingReview/(:num)', 'RatingReviewController::deleteRatingReview/$1');
    $routes->post('update-review-status/(:num)', 'RatingReviewController::updateStatus/$1');
    $routes->post('getReviewRatingByCustomerId/(:num)', 'RatingReviewController::getReviewRatingByCustomerId/$1');

    //Customer Address//
    $routes->post('createCustomerAddress', 'CustomerAddressController::createCustomerAddress');
    $routes->get('customerAddressById/(:num)', 'CustomerAddressController::customerAddressById/$1');
    $routes->put('updateCustomerAddress/(:num)', 'CustomerAddressController::updateCustomerAddress/$1');
    $routes->delete('deleteCustomerAddress/(:num)', 'CustomerAddressController::deleteCustomerAddress/$1');

    //Transaction//
    $routes->get('transactions/(:num)', 'TransactionController::getTransactionsByCustomerId/$1');
});

//General Options
$routes->group('general', static function ($routes) {
    $routes->get('getBestDeal', 'FeaturesController::getBestDeal');
    $routes->get('getCartAndWhishlistCount', 'FeaturesController::getCartAndWhishlistCount');
    $routes->get('getFiltersParams', 'FeaturesController::getFiltersParams');
});
$routes->group('MetaData', static function ($routes) {
    $routes->post('MetaDatacreate', 'MetaDataController::create');
    $routes->get('MetaDataread', 'MetaDataController::read');
    $routes->put('MetaDataupdate/(:num)', 'MetaDataController::update/$1');
    $routes->delete('MetaDatadelete/(:num)', 'MetaDataController::delete/$1');
});

//Task Force Contact Us
$routes->group('task-force', static function ($routes) {
    $routes->post('contactUs', 'ContactTaskForceController::contactUs');
    $routes->get('getAllContactUs', 'ContactTaskForceController::getAllContactUs');
});

//Coupon Api

$routes->group('coupon', static function ($routes) {
    $routes->post('couponcreate', 'CouponController::create');
    $routes->get('getById/(:num)', 'CouponController::getById/$1');
    $routes->put('couponupdate/(:num)', 'CouponController::update/$1');
    $routes->delete('coupondelete/(:num)', 'CouponController::delete/$1');
    $routes->get('getAllCoupon', 'CouponController::getAllCoupon');
    $routes->post('apply-coupon', 'CouponController::applyCoupon');
});

//WishList
$routes->group('wishlist', static function ($routes) {
    $routes->post('wishlistcreate', 'WishlistController::create');
    $routes->get('getwishlistById/(:num)', 'WishlistController::getwishlistById/$1');
    $routes->delete('deletewishlist/(:num)', 'WishlistController::deletewishlist/$1');
});

//Dealer//
$routes->group('dealer', static function ($routes) {
    $routes->post('createDealer', 'DealerController::createDealer');
    $routes->get('getDealers', 'DealerController::getDealers');
    $routes->put('updateDealer/(:num)', 'DealerController::updateDealer/$1');
    $routes->delete('deleteDealer/(:num)', 'DealerController::deleteDealer/$1');
    $routes->get('countAllDealers', 'DealerController::countAllDealers');
});

//Cart
$routes->group('cart', static function ($routes) {
    $routes->post('createCart', 'CartController::createCart');
    $routes->get('getCarts', 'CartController::getCarts');
    $routes->put('updateCart/(:num)', 'CartController::updateCart/$1');
    $routes->delete('deleteCart/(:num)', 'CartController::deleteCart/$1');
    $routes->get('getCartById/(:num)', 'CartController::getCartById/$1');
    $routes->delete('deleteHomeZoneAppliances/(:num)', 'HomeZoneAppliancesController::deleteHomeZoneAppliances/$1');
});
$routes->group('banner', static function ($routes) {
    $routes->post('createBannerImage', 'BannerController::createBannerImage');
    $routes->delete('deleteBanner/(:num)', 'BannerController::deleteBanner/$1');
    $routes->post('createMainBanner', 'BannerController::createMainBanner');
    $routes->get('getMainBanner', 'BannerController::getMainBanner');
});



//Seller//

$routes->group('seller', static function ($routes) {
    $routes->post('register', 'SellerController::createSeller');
    $routes->get('getSeller', 'SellerController::getSeller');
    $routes->put('updateSeller/(:num)', 'SellerController::updateSeller/$1');
});

$routes->post('multiple-products', 'ProductController::multipleProductUpload');


//Brand
$routes->group('brand', static function ($routes) {
    $routes->post('createBrand', 'BrandController::createBrand');
    $routes->get('getAllBrand', 'BrandController::getAllBrand');
    $routes->get('getBrandById/(:num)', 'BrandController::getBrandById/$1');
    $routes->put('updateBrand/(:num)', 'BrandController::updateBrand/$1');
    $routes->delete('deleteBrand/(:num)', 'BrandController::deleteBrand/$1');
    $routes->get('getBrandBySlug/(:any)', 'BrandController::getBrandBySlug/$1');
});

//Offers
$routes->group('Offers', static function ($routes) {
    $routes->post('createOffers', 'OffersController::createOffers');
    $routes->get('getOffersById/(:num)', 'OffersController::getOffersById/$1');
    $routes->put('updateOffers/(:num)', 'OffersController::updateOffers/$1');
    $routes->delete('deleteOffers/(:num)', 'OffersController::deleteOffers/$1');
    $routes->get('getAllOffers', 'OffersController::getAllOffers');
    $routes->post('createOffersImage', 'OffersController::createOffersImage');
    $routes->delete('deleteOfferImage', 'OffersController::deleteOfferImage');
});

// DORFEE MANAGEMENT
//vendor//
$routes->group('vendor', static function ($routes) {
    $routes->post('createvendor', 'VendorsController::createvendor');
    $routes->get('getvendors/(:num)', 'VendorsController::getvendors/$1');
    $routes->put('updatevendor/(:num)', 'VendorsController::updatevendor/$1');
    $routes->delete('deletevendor/(:num)', 'VendorsController::deletevendor/$1');
    $routes->post('createvendorImage', 'VendorsController::createvendorImage');
    $routes->get('getAllvendors', 'VendorsController::getAllvendors');
    $routes->get('getvendorsByUserid/(:num)', 'VendorsController::getvendorsByUserid/$1');
    $routes->post('createvendorPdf', 'VendorsController::createvendorPdf');
    $routes->put('updateVendorAgreement', 'VendorsController::updateVendorAgreement');
});

//Users
$routes->group('user', static function ($routes) {
    $routes->post('login', 'UsersController::login');
    $routes->post('createUser', 'UsersController::createUser');
    $routes->get('getUsers/(:num)', 'UsersController::getUsers/$1');
});

// Subscription
$routes->group('subscription', static function ($routes) {
    $routes->post('createSubscription', 'SubsriptionController::createSubscription');
    $routes->put('updateSubscription/(:num)', 'SubsriptionController::updateSubscription/$1');
    $routes->get('getSubscriptions/(:num)', 'SubsriptionController::getSubscriptions/$1');
    $routes->delete('deleteSubscription/(:num)', 'SubsriptionController::deleteSubscription/$1');
    $routes->get('getSubscriptionsByUserId/(:num)', 'SubsriptionController::getSubscriptionsByUserId/$1');
    $routes->post('createMembership', 'AfSubcribedUserController::createMembership');
    $routes->get('getMembership', 'AfSubcribedUserController::getMembership');
    $routes->get('getMumbershipByCardId/(:num)', 'AfSubcribedUserController::getMumbershipByCardId/$1');
});
//CampaignData

$routes->group('Campaign', static function ($routes) {
    $routes->post('createCampaignData', 'CampaignsDataController::createCampaignData');
    $routes->get('getCampaignData', 'CampaignsDataController::getCampaignData');
    $routes->put('updateCampaignData/(:num)', 'CampaignsDataController::updateCampaignData/$1');
    $routes->delete('deleteCampaignData/(:num)', 'CampaignsDataController::deleteCampaignData/$1');
});
$routes->group('Request', static function ($routes) {
    $routes->post('createservice', 'ServiceRequestController::createservice');
    $routes->get('getservices', 'ServiceRequestController::getservices');
    $routes->put('updateServices/(:num)', 'ServiceRequestController::updateServices/$1');
    $routes->get('getServiceByUserId/(:num)', 'ServiceRequestController::getServiceByUserId/$1');
});


//Otp
$routes->group('otp', static function ($routes) {
    $routes->post('notify_vendor_reg', 'OtpController::notify_vendor_reg');
    $routes->post('notify_order_confirmation', 'OtpController::notify_order_confirmation');
    $routes->post('notify_otp_verification', 'OtpController::notify_otp_verification');
});

$routes->group('role', static function ($routes) {
    $routes->post('createRole', 'DrfRoleController::createRole');
    $routes->post('updateRole', 'DrfRoleController::updateRole');
    $routes->get('getRole/(:num)', 'DrfRoleController::getRole/$1');
    $routes->get('getAllRoles', 'DrfRoleController::getAllRoles');
});

//Product
$routes->group('Product', static function ($routes) {
    //Products 
    $routes->post('createProduct', 'ProductController::createProduct');
    $routes->get('getAllProducts', 'ProductController::getAllProducts');
    $routes->get('getProductById/(:num)', 'ProductController::getProductById/$1');
    $routes->put('updateProduct/(:num)', 'ProductController::updateProduct/$1');
    $routes->delete('deleteProduct/(:num)', 'ProductController::deleteProduct/$1');
    $routes->post('createProductImage', 'ProductController::createProductImage');
    //Category
    $routes->post('createCategory', 'CategoryController::createCategory');
    $routes->post('updateCategory/(:num)', 'CategoryController::updateCategory/$1');
    $routes->get('getAllCategory', 'CategoryController::getAllCategory');
    $routes->get('getCategoryById/(:any)', 'CategoryController::getCategoryById/$1');
    $routes->delete('deleteCategory/(:num)', 'CategoryController::deleteCategory/$1');

    //Sub Category
    $routes->post('createSubCaterory', 'SubCategoryController::createSubCaterory');
    $routes->get('getAllSubCategory', 'SubCategoryController::getAllSubCategory');
    $routes->put('updateSubCaterory/(:num)', 'SubCategoryController::updateSubCaterory/$1');
    $routes->delete('deleteSubCaterory/(:num)', 'SubCategoryController::deleteSubCaterory/$1');
    $routes->get('getSubCateroryByid/(:num)', 'SubCategoryController::getSubCateroryByid/$1');

    // Get 10 percent products of all categories
    $routes->get('get-10-percent-products-of-homeappliances', 'ProductController::get10PercentProductsHomeappliances');
});

$routes->group('Designer', static function ($routes) {
    $routes->post('Create', 'DesignerController::Create');
    $routes->get('GetAll', 'DesignerController::GetAll');
    $routes->get('GetById/(:num)', 'DesignerController::GetById/$1');
    $routes->put('Update', 'DesignerController::Update');
    $routes->delete('Delete/(:num)', 'DesignerController::Delete/$1');
    $routes->get('GetDeletedDesigner', 'DesignerController::GetDeletedDesigner');
    $routes->post('createDesignerImage', 'DesignerController::createDesignerImage');






    $routes->post('AssignProduct', 'DesignerController::AssignProduct');
    $routes->put('UpdateAssignProduct', 'DesignerController::UpdateAssignProduct');
    $routes->delete('UnAssignProduct/(:num)', 'DesignerController::UnAssignProduct/$1');
    $routes->get('GetProductsByDesignerId/(:num)', 'DesignerController::GetProductsByDesignerId/$1');
});



$routes->group('Complaints', static function ($routes) {
    $routes->post('Create', 'ComplaintsController::Create');
    $routes->get('GetAll', 'ComplaintsController::GetAll');
    $routes->get('GetById/(:num)', 'ComplaintsController::GetById/$1');
    $routes->delete('Delete/(:num)', 'ComplaintsController::Delete/$1');
    $routes->put('Update', 'ComplaintsController::Update');
});

$routes->group('transactions', static function ($routes) {
    $routes->get('(:num)', 'InteriorTransactionController::index/$1');
    $routes->post('/', 'InteriorTransactionController::create');
    $routes->post('getAll', 'InteriorTransactionController::getAll');
    $routes->get('(:num)', 'InteriorTransactionController::show/$1');
    $routes->post('office-expense', 'InteriorTransactionController::getOfficeExpense');
    $routes->put('(:num)', 'InteriorTransactionController::update/$1');
    // $routes->delete('(:num)', 'InteriorTransactionController::delete/$1');
});



$routes->group('Transaction', static function ($routes) {
    $routes->get('GetAll', 'TransactionController::GetAll');
    $routes->get('GetById/(:num)', 'TransactionController::GetById/$1');
});

// Subscription Cards Api's
$routes->group('SubscriptionCards', static function ($routes) {
    $routes->post('create-card', 'SubscriptionCardsController::create_cards');
    $routes->get('get-all-cards', 'SubscriptionCardsController::get_all_cards');
    $routes->get('get-cards-byId/(:num)', 'SubscriptionCardsController::get_all_cards_byId/$1');
    $routes->delete('delete-card/(:num)', 'SubscriptionCardsController::delete_card/$1');
    $routes->put('update-card/(:num)', 'SubscriptionCardsController::update_card/$1');
    $routes->post('verify-payment', 'AfSubcribedUserController::verifyPayment');
    $routes->get('get-cards-pricing-residential-byId/(:num)', 'SubscriptionCardsController::get_cards_pricing_residential_byId/$1');
    $routes->get('get-cards-pricing-commercial-byId/(:num)', 'SubscriptionCardsController::get_cards_pricing_commercial_byId/$1');
    $routes->get('get-cards-pricing-byId/(:num)', 'SubscriptionCardsController::get_cards_pricing_byId/$1');
    // $routes->post('push-card-details', 'SubscriptionCardsController::push_card_details');
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